<?php
namespace App\Console\Commands;

use App\Models\ImplementerAppointment;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Microsoft\Graph\Graph;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Carbon\Carbon;

class FetchTeamsRecordings extends Command
{
    protected $signature = 'teams:fetch-recordings {--debug : Show detailed debug information}';
    protected $description = 'Fetch and download Teams meeting recordings to S3';

    public function handle()
    {
        $this->info('Starting to fetch Teams meeting recordings...');

        // Verify S3 configuration
        if (!env('AWS_ACCESS_KEY_ID') || !env('AWS_BUCKET')) {
            $this->error('❌ AWS S3 credentials not configured!');
            $this->error('Please set AWS_ACCESS_KEY_ID and AWS_BUCKET in .env');
            return 1;
        }

        // Test S3 connection first
        if (!$this->testS3Connection()) {
            $this->error('❌ S3 connection test failed! Check your credentials and bucket.');
            return 1;
        }

        try {
            $appointments = ImplementerAppointment::whereNotNull('online_meeting_id')
                ->where(function ($query) {
                    $query->whereNull('session_recording_link')
                        ->orWhere('session_recording_link', '');
                })
                ->whereIn('status', ['New', 'Done'])
                ->where(function ($query) {
                    $now = now();
                    $today = $now->toDateString();
                    $currentTime = $now->format('H:i:s');

                    $query->where(function ($subQuery) use ($today) {
                        $subQuery->whereDate('date', '<', $today);
                    })->orWhere(function ($subQuery) use ($today, $currentTime) {
                        $subQuery->whereDate('date', $today)
                                ->whereTime('end_time', '<=', $currentTime);
                    });
                })
                ->orderBy('date', 'desc')
                ->orderBy('end_time', 'desc')
                ->get();

            $this->info("Found {$appointments->count()} appointments to check");
            $this->info("S3 Bucket: " . env('AWS_BUCKET'));
            $this->info("S3 Region: " . env('AWS_DEFAULT_REGION'));

            $successCount = 0;
            $failCount = 0;

            foreach ($appointments as $appointment) {
                try {
                    $recordingInfo = $this->fetchAndDownloadRecording($appointment);

                    if ($recordingInfo) {
                        $appointment->update([
                            'session_recording_link' => $recordingInfo['public_url'],
                            'recording_file_path' => $recordingInfo['file_path'],
                            'recording_fetched_at' => now(),
                        ]);

                        $successCount++;
                        $this->info("✅ Recording uploaded to S3 for appointment #{$appointment->id}");

                        Log::info('Teams recording uploaded to S3 successfully', [
                            'appointment_id' => $appointment->id,
                            'file_path' => $recordingInfo['file_path'],
                            'public_url' => $recordingInfo['public_url'],
                            's3_bucket' => env('AWS_BUCKET'),
                        ]);
                    } else {
                        $this->warn("⚠️ No recording available yet for appointment #{$appointment->id}");
                    }

                } catch (\Exception $e) {
                    $failCount++;
                    $this->error("❌ Failed for appointment #{$appointment->id}: {$e->getMessage()}");

                    if ($this->option('debug')) {
                        $this->error("Stack trace: " . $e->getTraceAsString());
                    }

                    Log::error('Failed to fetch Teams recording', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                usleep(500000); // 0.5 second delay
            }

            $this->info("\n📊 Summary:");
            $this->info("✅ Successfully fetched: {$successCount}");
            $this->warn("⚠️ Failed: {$failCount}");
            $this->info("Total checked: {$appointments->count()}");

            Log::info('Teams recordings fetch completed', [
                'total_checked' => $appointments->count(),
                'success_count' => $successCount,
                'fail_count' => $failCount,
                's3_bucket' => env('AWS_BUCKET'),
            ]);

        } catch (\Exception $e) {
            $this->error("❌ Command failed: {$e->getMessage()}");
            Log::error('Teams recordings fetch command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Test S3 connection before processing
     */
    private function testS3Connection(): bool
    {
        try {
            $this->info("Testing S3 connection...");

            // Try to write a test file
            $testContent = 'S3 connection test - ' . now()->toDateTimeString();
            $testPath = 'test/connection-test-' . time() . '.txt';

            $result = Storage::disk('s3')->put($testPath, $testContent);

            if ($result) {
                $this->info("✅ S3 write test successful");

                // Test if file exists
                if (Storage::disk('s3')->exists($testPath)) {
                    $this->info("✅ S3 read test successful");

                    // Clean up test file
                    Storage::disk('s3')->delete($testPath);
                    $this->info("✅ S3 delete test successful");

                    return true;
                }
            }

            $this->error("❌ S3 connection test failed");
            return false;

        } catch (\Exception $e) {
            $this->error("❌ S3 connection error: " . $e->getMessage());
            Log::error('S3 connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    private function fetchAndDownloadRecording(ImplementerAppointment $appointment): ?array
    {
        $implementer = User::where('name', $appointment->implementer)->first();

        if (!$implementer) {
            throw new \Exception("Implementer not found: {$appointment->implementer}");
        }

        $userIdentifier = $implementer->azure_user_id ?? $implementer->email;

        if (!$userIdentifier) {
            throw new \Exception("No user identifier found for implementer");
        }

        $accessToken = MicrosoftGraphService::getAccessToken();
        $graph = new Graph();
        $graph->setAccessToken($accessToken);

        $endpoint = "/users/{$userIdentifier}/onlineMeetings/{$appointment->online_meeting_id}/recordings";

        try {
            $response = $graph->createRequest("GET", $endpoint)->execute();
            $responseBody = $response->getBody();

            Log::info('Teams recording API response', [
                'appointment_id' => $appointment->id,
                'total_recordings' => count($responseBody['value'] ?? []),
                'response' => $responseBody,
            ]);

            if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                $recordings = $responseBody['value'];
                $uploadedRecordings = [];
                $publicUrls = [];

                // ✅ Process ALL recordings (Part 1, Part 2, etc.)
                foreach ($recordings as $index => $recording) {
                    $recordingId = $recording['id'];
                    $partNumber = $index + 1;
                    $totalParts = count($recordings);

                    // ✅ Use the recordingContentUrl directly from API response
                    $contentUrl = $recording['recordingContentUrl'];

                    Log::info("Downloading recording Part {$partNumber}/{$totalParts}", [
                        'appointment_id' => $appointment->id,
                        'recording_id' => $recordingId,
                        'part_number' => $partNumber,
                        'content_url' => $contentUrl,
                        'created_at' => $recording['createdDateTime'] ?? 'N/A',
                        'end_time' => $recording['endDateTime'] ?? 'N/A',
                    ]);

                    try {
                        // ✅ Download the video file using the direct URL
                        $videoResponse = Http::timeout(600)->withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Accept' => 'application/octet-stream',
                        ])->get($contentUrl);

                        if (!$videoResponse->successful()) {
                            $this->warn("⚠️ Failed to download Part {$partNumber} for appointment #{$appointment->id}: " . $videoResponse->body());
                            continue; // Skip this part but continue with others
                        }

                        $recordingContent = $videoResponse->body();
                        $fileSize = strlen($recordingContent);
                        $fileSizeMB = round($fileSize / 1024 / 1024, 2);

                        Log::info("Recording Part {$partNumber} content downloaded", [
                            'appointment_id' => $appointment->id,
                            'part_number' => $partNumber,
                            'content_length' => $fileSize,
                            'content_length_mb' => $fileSizeMB,
                            'content_type' => $videoResponse->header('Content-Type'),
                        ]);

                        // ✅ Generate filename with part number and recording timestamps
                        $createdAt = Carbon::parse($recording['createdDateTime'])->format('Y-m-d_His');

                        if ($totalParts > 1) {
                            $filename = "teams_recording_{$appointment->id}_{$createdAt}_part{$partNumber}.mp4";
                        } else {
                            $filename = "teams_recording_{$appointment->id}_{$createdAt}.mp4";
                        }

                        $directory = "teams-recordings/" . date('Y/m');
                        $filePath = "{$directory}/{$filename}";

                        Log::info("Uploading Part {$partNumber} to S3", [
                            'appointment_id' => $appointment->id,
                            'part_number' => $partNumber,
                            'file_path' => $filePath,
                            'file_size' => $fileSize,
                            'file_size_mb' => $fileSizeMB,
                        ]);

                        // ✅ Upload to S3 with public access
                        $s3Client = new S3Client([
                            'version' => 'latest',
                            'region' => env('AWS_DEFAULT_REGION'),
                            'credentials' => [
                                'key' => env('AWS_ACCESS_KEY_ID'),
                                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                            ],
                        ]);

                        $result = $s3Client->putObject([
                            'Bucket' => env('AWS_BUCKET'),
                            'Key' => $filePath,
                            'Body' => $recordingContent,
                            'ContentType' => 'video/mp4',
                            'CacheControl' => 'max-age=31536000',
                            // ✅ Add metadata for better organization
                            'Metadata' => [
                                'appointment-id' => (string)$appointment->id,
                                'part-number' => (string)$partNumber,
                                'total-parts' => (string)$totalParts,
                                'recording-date' => $createdAt,
                                'implementer' => $implementer->name ?? 'Unknown',
                            ]
                        ]);

                        // Verify file was uploaded
                        $exists = $s3Client->doesObjectExist(env('AWS_BUCKET'), $filePath);

                        if (!$exists) {
                            throw new \Exception("Part {$partNumber} file not found in S3 after upload");
                        }

                        // ✅ Generate S3 public URL (accessible to anyone with the link)
                        $bucket = env('AWS_BUCKET');
                        $region = env('AWS_DEFAULT_REGION');
                        $publicUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$filePath}";

                        $uploadedRecordings[] = [
                            'part' => $partNumber,
                            'file_path' => $filePath,
                            'public_url' => $publicUrl,
                            'recording_id' => $recordingId,
                            'file_size_mb' => $fileSizeMB,
                            'created_at' => $recording['createdDateTime'] ?? 'N/A',
                            'end_at' => $recording['endDateTime'] ?? 'N/A',
                            'original_url' => $contentUrl, // ✅ Keep reference to original URL
                        ];

                        $publicUrls[] = $publicUrl;

                        $this->info("✅ Part {$partNumber}/{$totalParts} uploaded successfully ({$fileSizeMB}MB)");
                        $this->info("📹 Public URL: {$publicUrl}");

                        Log::info("Recording Part {$partNumber} uploaded to S3 successfully", [
                            'appointment_id' => $appointment->id,
                            'part_number' => $partNumber,
                            'file_path' => $filePath,
                            'public_url' => $publicUrl,
                            'file_size_mb' => $fileSizeMB,
                            'original_teams_url' => $contentUrl,
                        ]);

                    } catch (\Exception $e) {
                        $this->error("❌ Failed to process Part {$partNumber} for appointment #{$appointment->id}: {$e->getMessage()}");
                        Log::error("Failed to process recording part", [
                            'appointment_id' => $appointment->id,
                            'part_number' => $partNumber,
                            'error' => $e->getMessage(),
                            'content_url' => $contentUrl,
                        ]);
                        continue;
                    }

                    // Small delay between parts
                    usleep(250000); // 0.25 second delay
                }

                // ✅ Return combined results if we have any successful uploads
                if (!empty($uploadedRecordings)) {
                    return [
                        'file_path' => $uploadedRecordings[0]['file_path'], // Keep first part as main path for compatibility
                        'public_url' => implode(';', $publicUrls), // ✅ Store all URLs separated by semicolon
                        'recording_id' => $uploadedRecordings[0]['recording_id'],
                        'all_parts' => $uploadedRecordings, // ✅ Store detailed info about all parts
                        'total_parts' => count($uploadedRecordings),
                    ];
                }
            }

            return null;

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                Log::debug('No recordings available yet', [
                    'appointment_id' => $appointment->id,
                ]);
                return null;
            }

            throw $e;
        }
    }
}
