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
        $tempPath = null;

        try {
            // Store video in temporary location for Flask processing
            $tempPath = $video->store('temp', 'public');
            if (!$tempPath) {
                throw new Exception("Failed to store temporary video.");
            }
            Log::info("Temporary video stored at: {$tempPath}");

            // Send to Flask
            $flaskPrediction = 'Unknown';
            $flaskConfidence = 0;
            $notification = null;

            if ($flaskUrl) {
                Log::info("Sending video to Flask: {$flaskUrl}/predict");
                $response = Http::timeout(120)->attach(
                    'video',
                    Storage::disk('public')->get($tempPath),
                    basename($tempPath)
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

            // Only save video permanently and create notification if violence is detected
            if ($flaskPrediction === 'Violence') {
                // Move from temp to permanent storage
                $videoPath = 'videos/' . basename($tempPath);
                Storage::disk('public')->move($tempPath, $videoPath);
                Log::info("Violence detected - Video moved to permanent storage: {$videoPath}");

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
            } else {
                // Delete temporary video if no violence detected
                if ($tempPath && Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->delete($tempPath);
                    Log::info("No violence detected - Temporary video deleted: {$tempPath}");
                }
            }

            return response()->json([
                'message' => 'Video processed.',
                'prediction' => $flaskPrediction,
                'confidence' => $flaskConfidence,
                'notification_id' => $notification?->id,
                'video_path' => $videoPath ? Storage::url($videoPath) : null
            ]);

        } catch (Exception $e) {
            Log::error('Error processing video', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            // Clean up temporary file
            if ($tempPath && Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
                Log::info("Deleted temporary video after error: {$tempPath}");
            }
            return response()->json(['error' => 'Error processing video: ' . $e->getMessage()], 500);
        }
    }
}
