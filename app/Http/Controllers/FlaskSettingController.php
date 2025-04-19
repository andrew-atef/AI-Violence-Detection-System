<?php

namespace App\Http\Controllers;

use App\Models\FlaskSetting;
use App\Models\ViolenceNotifications;
use App\Jobs\AnalyzeVideoWithGeminiJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class FlaskSettingController extends Controller
{
    /**
     * Show the form for setting Flask URL
     *
     * @return \Illuminate\View\View
     */
    public function showForm()
    {
        return view('set_url');
    }

    /**
     * Store Flask URL in database
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeUrl(Request $request)
    {
        $request->validate([
            'public_url' => 'required|url'
        ]);

        $cleanUrl = rtrim($request->public_url, '/');

        FlaskSetting::updateOrCreate(
            ['id' => 1],
            ['public_url' => $cleanUrl]
        );

        return redirect()->back()->with('success', 'URL saved successfully!');
    }

    /**
     * Get the Flask URL
     *
     * @return JsonResponse
     */
    public function getFlaskUrl(): JsonResponse
    {
        $url = FlaskSetting::find(1)?->public_url;
        return response()->json(['public_url' => $url]);
    }

    /**
     * Send video to Flask for violence detection and dispatch Gemini analysis job
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendVideoToFlask(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'video' => 'required|file|mimes:mp4|max:10000',
            'camera_num' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $video = $request->file('video');

        if (!$video->isValid()) {
            return response()->json(['error' => 'Invalid video file.'], 400);
        }

        $flaskUrl = FlaskSetting::find(1)?->public_url;
        $videoPath = null;

        try {
            // Save video
            $videoPath = $video->store('videos', 'public');
            if (!$videoPath) {
                throw new Exception("Failed to store video.");
            }
            Log::info("Video stored at: {$videoPath}");

            // Send to Flask
            $flaskPrediction = 'Unknown';
            $flaskConfidence = 0;

            if ($flaskUrl) {
                Log::info("Sending video to Flask: {$flaskUrl}/predict");
                $response = Http::timeout(120)->attach(
                    'video',
                    Storage::disk('public')->get($videoPath),
                    basename($videoPath)
                )->post("{$flaskUrl}/predict");

                if ($response->successful()) {
                    $responseData = $response->json();
                    $flaskPrediction = $responseData['prediction'] ?? 'No Prediction';
                    $flaskConfidence = $responseData['confidence'] ?? 0;
                    Log::info("Flask Response: ", $responseData);
                } else {
                    Log::error("Flask API error: {$response->status()}", ['body' => $response->body()]);
                    $flaskPrediction = 'Flask Error';
                }
            } else {
                Log::warning("Flask URL not configured.");
                $flaskPrediction = 'Flask Not Configured';
            }

            // Create Notification
            $notification = ViolenceNotifications::create([
                'note' => null,
                'camera_num' => $request->input('camera_num', 1),
                'video_path' => $videoPath,
                'prediction' => $flaskPrediction,
                'confidence' => $flaskConfidence,
                'user_id' => Auth::id() ?? null,
            ]);
            Log::info("Notification created: ID {$notification->id}");

            // Dispatch Gemini Analysis Job
            AnalyzeVideoWithGeminiJob::dispatch($notification->id);
            Log::info("Dispatched Gemini job for Notification ID: {$notification->id}");

            return response()->json([
                'message' => 'Video processed.',
                'flask_prediction' => $flaskPrediction,
                'flask_confidence' => $flaskConfidence,
                'notification_id' => $notification->id,
                'video_path' => Storage::url($videoPath)
            ]);

        } catch (Exception $e) {
            Log::error('Error processing video', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            if ($videoPath && Storage::disk('public')->exists($videoPath)) {
                Log::info("Keeping video for debugging: {$videoPath}");
            }
            return response()->json(['error' => 'Error processing video: ' . $e->getMessage()], 500);
        }
    }
}
