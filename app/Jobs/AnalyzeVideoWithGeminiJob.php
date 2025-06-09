<?php

namespace App\Jobs;

use App\Models\ViolenceNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalyzeVideoWithGeminiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationId;
    protected $maxRetries = 3;
    protected $retryDelay = 5; // seconds

    /**
     * Create a new job instance.
     *
     * @param int $notificationId
     * @return void
     */
    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $notification = ViolenceNotifications::find($this->notificationId);

        if (!$notification) {
            Log::error("Notification not found: ID {$this->notificationId}");
            return;
        }

        if (empty($notification->video_path)) {
            Log::error("Video path not found: ID {$this->notificationId}");
            $notification->note = "Gemini Analysis Failed: Video path missing";
            $notification->save();
            return;
        }

        $videoPath = $notification->video_path;

        if (!Storage::disk('public')->exists($videoPath)) {
            Log::error("Video not found: {$videoPath}", ['notification_id' => $this->notificationId]);
            $notification->note = "Gemini Analysis Failed: Video not found";
            $notification->save();
            return;
        }

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            // Log::error("GEMINI_API_KEY not set: ID {$this->notificationId}");
            Log::error("GEMINI_API_KEYyyyyyy {$apiKey}");
            $notification->note = "Gemini Analysis Failed: API key not configured";
            $notification->save();
            return;
        }

        try {
            // Prepare Video
            $fullVideoPath = storage_path('app/public/' . $videoPath);
            $mimeTypeString = Storage::disk('public')->mimeType($videoPath);
            $videoContent = file_get_contents($fullVideoPath);

            if (!$videoContent || !$mimeTypeString || !str_starts_with($mimeTypeString, 'video/')) {
                throw new Exception("Invalid video or MIME type: {$mimeTypeString}");
            }

            Log::info("Preparing Gemini request: ID {$this->notificationId}, Video: {$videoPath}, MIME: {$mimeTypeString}");

            // Gemini Request
            $modelId = 'gemini-2.5-flash-preview-04-17';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:generateContent?key={$apiKey}";

            $requestBody = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'inlineData' => [
                                    'mimeType' => $mimeTypeString,
                                    'data' => base64_encode($videoContent),
                                ],
                            ],
                            [
                                'text' => 'Analyze this security video. Describe what is happening to the system instructions.',
                            ],
                        ],
                    ],
                ],
                'systemInstruction' => [
                    'parts' => [
                        [
                            'text' => "You are a security analysis AI. Analyze the provided video footage captured by a security system. Describe the events clearly for a security officer."
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'candidateCount' => 1,
                    'responseMimeType' => 'text/plain',
                ],
            ];

            $attempts = 0;
            $success = false;
            $lastError = null;

            while ($attempts < $this->maxRetries && !$success) {
                $attempts++;
                
                if ($attempts > 1) {
                    Log::info("Retrying Gemini request (Attempt {$attempts} of {$this->maxRetries})");
                    sleep($this->retryDelay);
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 503) {
                    $lastError = "Gemini failed: HTTP $httpCode - $response";
                    Log::warning("Gemini service unavailable, attempt {$attempts} of {$this->maxRetries}");
                    continue;
                }

                if ($httpCode < 200 || $httpCode >= 300) {
                    throw new Exception("Gemini failed: HTTP $httpCode - $response");
                }

                $responseData = json_decode($response, true);
                $description = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'No description';
                Log::info("Gemini response for ID: {$this->notificationId}", ['description_length' => strlen($description)]);

                // Update Notification
                $notification->note = $description;
                $notification->save();
                $success = true;
            }

            if (!$success) {
                throw new Exception($lastError ?? "Failed after {$this->maxRetries} attempts");
            }

        } catch (Exception $e) {
            Log::error('Gemini analysis error', [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $notification->note = "Gemini Analysis Failed: {$e->getMessage()}";
            $notification->save();
        }
    }
}
