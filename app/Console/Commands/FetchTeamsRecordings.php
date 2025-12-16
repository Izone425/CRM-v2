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
            // ✅ Only check meetings that ended within the last 1 hour 30 minutes
            $now = Carbon::now();
            $oneHourThirtyMinutesAgo = $now->copy()->subMinutes(90);

            $this->info("Current time: " . $now->format('Y-m-d H:i:s'));
            $this->info("Checking meetings that ended between: " . $oneHourThirtyMinutesAgo->format('Y-m-d H:i:s') . " and " . $now->format('Y-m-d H:i:s'));

            // ✅ Updated query: Check for appointments with online_meeting_id but no session_recording_link
            $appointments = ImplementerAppointment::whereNotNull('online_meeting_id')
                ->where('online_meeting_id', '!=', '') // Ensure online_meeting_id is not empty
                ->whereIn('status', ['New', 'Done'])
                ->where(function ($query) {
                    // ✅ Check for null or empty session_recording_link
                    $query->whereNull('session_recording_link')
                        ->orWhere('session_recording_link', '')
                        ->orWhere('session_recording_link', 'LIKE', '% %') // Only spaces
                        ->orWhere('session_recording_link', 'null'); // Sometimes stored as string 'null'
                })
                ->where(function ($query) use ($oneHourThirtyMinutesAgo, $now) {
                    // ✅ Time window: within 90 minutes from end time
                    $query->whereRaw("
                        STR_TO_DATE(CONCAT(date, ' ', end_time), '%Y-%m-%d %H:%i:%s')
                        BETWEEN ? AND ?
                    ", [$oneHourThirtyMinutesAgo->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')]);
                })
                ->orderBy('date', 'desc')
                ->orderBy('end_time', 'desc')
                ->get();

            $this->info("Found {$appointments->count()} appointments within the 90-minute window");
            $this->info("📋 Criteria: online_meeting_id NOT NULL AND session_recording_link IS NULL/EMPTY");

            if ($appointments->count() === 0) {
                $this->info("No meetings found that match criteria within the last 90 minutes. Exiting.");
                return 0;
            }

            $this->info("S3 Bucket: " . env('AWS_BUCKET'));
            $this->info("S3 Region: " . env('AWS_DEFAULT_REGION'));

            $successCount = 0;
            $failCount = 0;
            $noRecordingCount = 0;
            $skippedCount = 0;
            $duplicateCount = 0;

            foreach ($appointments as $appointment) {
                $appointmentEndTime = null;

                try {
                    // ✅ Fix the date parsing
                    $appointmentDate = $appointment->date;
                    $appointmentTime = $appointment->end_time;

                    if (strlen($appointmentDate) > 10) {
                        $appointmentEndTime = Carbon::parse($appointmentDate);
                    } else {
                        $appointmentEndTime = Carbon::parse($appointmentDate . ' ' . $appointmentTime);
                    }

                    $minutesSinceEnd = $appointmentEndTime->diffInMinutes($now);

                    // ✅ Double-check online_meeting_id and session_recording_link
                    $hasOnlineMeetingId = !empty($appointment->online_meeting_id) && trim($appointment->online_meeting_id) !== '';
                    $hasRecordingLink = !empty($appointment->session_recording_link) && trim($appointment->session_recording_link) !== '';

                    if (!$hasOnlineMeetingId) {
                        $skippedCount++;
                        $this->warn("⏭️ Skipping appointment #{$appointment->id} - No online_meeting_id");
                        continue;
                    }

                    if ($hasRecordingLink) {
                        $skippedCount++;
                        $this->info("⏭️ Skipping appointment #{$appointment->id} - Already has recording link");
                        continue;
                    }

                    // ✅ Check if recently processed (prevent too frequent checks)
                    if ($appointment->recording_fetched_at) {
                        $lastChecked = Carbon::parse($appointment->recording_fetched_at);
                        if ($lastChecked->diffInMinutes($now) < 10) {
                            $skippedCount++;
                            $this->info("⏭️ Skipping appointment #{$appointment->id} - Recently checked (less than 10 minutes ago)");
                            continue;
                        }
                    }

                    $this->info("🔍 Checking appointment #{$appointment->id} (ended {$minutesSinceEnd} minutes ago)");
                    $this->info("    📋 Meeting ID: {$appointment->online_meeting_id}");
                    $this->info("    🎥 Recording Link: " . ($hasRecordingLink ? 'EXISTS' : 'NULL/EMPTY'));

                    $recordingInfo = $this->fetchAndDownloadRecording($appointment);

                    if ($recordingInfo) {
                        $appointment->update([
                            'session_recording_link' => $recordingInfo['public_url'],
                            'recording_file_path' => $recordingInfo['file_path'],
                            'recording_fetched_at' => now(),
                        ]);

                        $successCount++;
                        $duplicateCount += $recordingInfo['duplicates_found'] ?? 0;

                        $this->info("✅ Recording processed for appointment #{$appointment->id}");
                        $this->info("🎥 Total recording parts: " . ($recordingInfo['total_parts'] ?? 1));
                        if (isset($recordingInfo['duplicates_found']) && $recordingInfo['duplicates_found'] > 0) {
                            $this->info("♻️ Duplicates skipped: " . $recordingInfo['duplicates_found']);
                        }

                        Log::info('Teams recording fetched successfully', [
                            'appointment_id' => $appointment->id,
                            'file_path' => $recordingInfo['file_path'],
                            'public_url' => $recordingInfo['public_url'],
                            'total_parts' => $recordingInfo['total_parts'] ?? 1,
                            'duplicates_found' => $recordingInfo['duplicates_found'] ?? 0,
                            's3_bucket' => env('AWS_BUCKET'),
                            'minutes_since_end' => $minutesSinceEnd,
                        ]);
                    } else {
                        // ✅ Update last checked time even if no recordings found
                        $appointment->update([
                            'recording_fetched_at' => now(),
                        ]);

                        $noRecordingCount++;
                        $this->warn("⚠️ No recording available yet for appointment #{$appointment->id} (ended {$minutesSinceEnd} minutes ago)");

                        Log::info('No recording available yet', [
                            'appointment_id' => $appointment->id,
                            'online_meeting_id' => $appointment->online_meeting_id,
                            'minutes_since_end' => $minutesSinceEnd,
                            'end_time' => $appointmentEndTime->format('Y-m-d H:i:s'),
                        ]);
                    }

                } catch (\Exception $e) {
                    $failCount++;
                    $this->error("❌ Failed for appointment #{$appointment->id}: {$e->getMessage()}");

                    if ($this->option('debug')) {
                        $this->error("Stack trace: " . $e->getTraceAsString());
                    }

                    $minutesSinceEnd = $appointmentEndTime ? $appointmentEndTime->diffInMinutes($now) : 'unknown';

                    Log::error('Failed to fetch Teams recording', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'minutes_since_end' => $minutesSinceEnd,
                        'online_meeting_id' => $appointment->online_meeting_id ?? 'N/A',
                        'appointment_date' => $appointment->date ?? 'N/A',
                        'appointment_end_time' => $appointment->end_time ?? 'N/A',
                    ]);
                }

                usleep(500000); // 0.5 second delay
            }

            $this->info("\n📊 Summary:");
            $this->info("✅ Successfully processed: {$successCount}");
            $this->info("⏭️ Skipped (invalid criteria): {$skippedCount}");
            $this->info("♻️ Duplicates avoided: {$duplicateCount}");
            $this->warn("⚠️ No recordings available: {$noRecordingCount}");
            $this->error("❌ Failed: {$failCount}");
            $this->info("📋 Total checked: {$appointments->count()}");

            Log::info('Teams recordings fetch completed', [
                'total_checked' => $appointments->count(),
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
                'duplicate_count' => $duplicateCount,
                'no_recording_count' => $noRecordingCount,
                'fail_count' => $failCount,
                's3_bucket' => env('AWS_BUCKET'),
                'time_window_minutes' => 90,
                'check_time' => $now->format('Y-m-d H:i:s'),
                'criteria' => 'online_meeting_id NOT NULL AND session_recording_link IS NULL/EMPTY',
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
                $duplicatesFound = 0;

                // ✅ Initialize S3 client once
                $s3Client = new S3Client([
                    'version' => 'latest',
                    'region' => env('AWS_DEFAULT_REGION'),
                    'credentials' => [
                        'key' => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);

                // ✅ Process ALL recordings (Part 1, Part 2, etc.)
                foreach ($recordings as $index => $recording) {
                    $recordingId = $recording['id'];
                    $partNumber = $index + 1;
                    $totalParts = count($recordings);

                    // ✅ Generate filename first to check for duplicates
                    $createdAt = Carbon::parse($recording['createdDateTime'])->format('Y-m-d_His');

                    if ($totalParts > 1) {
                        $filename = "teams_recording_{$appointment->id}_{$createdAt}_part{$partNumber}.mp4";
                    } else {
                        $filename = "teams_recording_{$appointment->id}_{$createdAt}.mp4";
                    }

                    $directory = "teams-recordings/" . date('Y/m');
                    $filePath = "{$directory}/{$filename}";

                    // ✅ Check if file already exists in S3
                    try {
                        $fileExists = $s3Client->doesObjectExist(env('AWS_BUCKET'), $filePath);

                        if ($fileExists) {
                            $duplicatesFound++;
                            $this->info("♻️ Part {$partNumber} already exists in S3, skipping upload");
                            $this->info("    📂 File: {$filePath}");

                            // ✅ Generate public URL for existing file
                            $bucket = env('AWS_BUCKET');
                            $region = env('AWS_DEFAULT_REGION');
                            $publicUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$filePath}";
                            $publicUrls[] = $publicUrl;

                            // ✅ Add to uploaded recordings for tracking
                            $uploadedRecordings[] = [
                                'part' => $partNumber,
                                'file_path' => $filePath,
                                'public_url' => $publicUrl,
                                'recording_id' => $recordingId,
                                'file_size_mb' => 'Unknown (existing)',
                                'created_at' => $recording['createdDateTime'] ?? 'N/A',
                                'end_at' => $recording['endDateTime'] ?? 'N/A',
                                'status' => 'duplicate_skipped',
                            ];

                            Log::info("Duplicate file found, skipping upload", [
                                'appointment_id' => $appointment->id,
                                'part_number' => $partNumber,
                                'file_path' => $filePath,
                                'public_url' => $publicUrl,
                            ]);

                            continue; // Skip to next recording
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to check S3 file existence, proceeding with upload', [
                            'appointment_id' => $appointment->id,
                            'file_path' => $filePath,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with upload if check fails
                    }

                    // ✅ File doesn't exist, proceed with download and upload
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

                        Log::info("Uploading Part {$partNumber} to S3", [
                            'appointment_id' => $appointment->id,
                            'part_number' => $partNumber,
                            'file_path' => $filePath,
                            'file_size' => $fileSize,
                            'file_size_mb' => $fileSizeMB,
                        ]);

                        // ✅ Upload to S3 with public access
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
                                'upload-timestamp' => now()->toISOString(),
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
                            'original_url' => $contentUrl,
                            'status' => 'newly_uploaded',
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

                // ✅ Return combined results if we have any recordings (new or existing)
                if (!empty($publicUrls)) {
                    $newUploads = count(array_filter($uploadedRecordings, fn($rec) => ($rec['status'] ?? '') === 'newly_uploaded'));

                    return [
                        'file_path' => $uploadedRecordings[0]['file_path'], // Keep first part as main path for compatibility
                        'public_url' => implode(';', $publicUrls), // ✅ Store all URLs separated by semicolon
                        'recording_id' => $uploadedRecordings[0]['recording_id'],
                        'all_parts' => $uploadedRecordings, // ✅ Store detailed info about all parts
                        'total_parts' => count($publicUrls),
                        'duplicates_found' => $duplicatesFound,
                        'new_uploads' => $newUploads,
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
