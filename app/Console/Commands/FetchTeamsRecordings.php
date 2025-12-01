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
                'response' => $responseBody,
            ]);

            if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                $recording = $responseBody['value'][0];
                $recordingId = $recording['id'];

                $contentUrl = "https://graph.microsoft.com/v1.0/users/{$userIdentifier}/onlineMeetings/{$appointment->online_meeting_id}/recordings/{$recordingId}/content";

                Log::info('Downloading recording content', [
                    'appointment_id' => $appointment->id,
                    'recording_id' => $recordingId,
                    'content_url' => $contentUrl,
                ]);

                // Download the video file
                $videoResponse = Http::timeout(600)->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/octet-stream',
                ])->get($contentUrl);

                if (!$videoResponse->successful()) {
                    throw new \Exception("Failed to download recording: " . $videoResponse->body());
                }

                $recordingContent = $videoResponse->body();
                $fileSize = strlen($recordingContent);
                $fileSizeMB = round($fileSize / 1024 / 1024, 2);

                Log::info('Recording content downloaded', [
                    'appointment_id' => $appointment->id,
                    'content_length' => $fileSize,
                    'content_length_mb' => $fileSizeMB,
                    'content_type' => $videoResponse->header('Content-Type'),
                ]);

                // Generate filename and path for S3
                $timestamp = now()->format('Y-m-d_His');
                $filename = "teams_recording_{$appointment->id}_{$timestamp}.mp4";
                $directory = "teams-recordings/" . date('Y/m');
                $filePath = "{$directory}/{$filename}";

                Log::info('Uploading to S3', [
                    'appointment_id' => $appointment->id,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'file_size_mb' => $fileSizeMB,
                    's3_bucket' => env('AWS_BUCKET'),
                    's3_region' => env('AWS_DEFAULT_REGION'),
                ]);

                // ✅ Use AWS SDK directly - WITHOUT ACL (bucket uses bucket policy for public access)
                try {
                    $s3Client = new S3Client([
                        'version' => 'latest',
                        'region' => env('AWS_DEFAULT_REGION'),
                        'credentials' => [
                            'key' => env('AWS_ACCESS_KEY_ID'),
                            'secret' => env('AWS_SECRET_ACCESS_KEY'),
                        ],
                    ]);

                    // ✅ Upload without ACL parameter
                    $result = $s3Client->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key' => $filePath,
                        'Body' => $recordingContent,
                        'ContentType' => 'video/mp4',
                        'CacheControl' => 'max-age=31536000',
                    ]);

                    Log::info('S3 putObject result', [
                        'appointment_id' => $appointment->id,
                        'etag' => $result->get('ETag'),
                        'version_id' => $result->get('VersionId'),
                    ]);

                    // Verify file was uploaded
                    $exists = $s3Client->doesObjectExist(env('AWS_BUCKET'), $filePath);

                    if (!$exists) {
                        throw new \Exception("File not found in S3 after upload");
                    }

                    Log::info('S3 upload verified', [
                        'appointment_id' => $appointment->id,
                        'file_path' => $filePath,
                        'exists' => true,
                    ]);

                } catch (AwsException $e) {
                    Log::error('AWS S3 upload failed', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                        'aws_error_code' => $e->getAwsErrorCode(),
                        'aws_error_type' => $e->getAwsErrorType(),
                        'file_path' => $filePath,
                        's3_config' => [
                            'bucket' => env('AWS_BUCKET'),
                            'region' => env('AWS_DEFAULT_REGION'),
                            'has_key' => !empty(env('AWS_ACCESS_KEY_ID')),
                            'has_secret' => !empty(env('AWS_SECRET_ACCESS_KEY')),
                        ],
                    ]);
                    throw new \Exception("Failed to upload recording to S3: " . $e->getAwsErrorMessage());
                } catch (\Exception $e) {
                    Log::error('S3 upload exception', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                        'file_path' => $filePath,
                    ]);
                    throw new \Exception("Failed to upload recording to S3: " . $e->getMessage());
                }

                // Generate S3 public URL manually
                $bucket = env('AWS_BUCKET');
                $region = env('AWS_DEFAULT_REGION');
                $publicUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$filePath}";

                Log::info('Recording uploaded to S3 successfully', [
                    'appointment_id' => $appointment->id,
                    'file_path' => $filePath,
                    'public_url' => $publicUrl,
                    'file_size' => $fileSize,
                    'file_size_mb' => $fileSizeMB,
                    'created_at' => $recording['createdDateTime'] ?? 'N/A',
                    's3_bucket' => $bucket,
                    's3_region' => $region,
                ]);

                return [
                    'file_path' => $filePath,
                    'public_url' => $publicUrl,
                    'recording_id' => $recordingId,
                ];
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
