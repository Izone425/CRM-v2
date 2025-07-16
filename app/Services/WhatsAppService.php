<?php
namespace App\Services;

use App\Models\ChatMessage;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhatsAppService
{
    protected $twilio;

    public function __construct()
    {
        $this->twilio = new Client(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        );
    }

    /**
     * Check if we can send a free-form message to this number
     * based on the 24-hour window from the last customer message
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function canSendFreeformMessage($phoneNumber)
    {
        try {
            // Clean phone number - make sure we're working with just the number
            $phoneNumber = preg_replace('/^\+|^whatsapp:/', '', $phoneNumber);

            // Get the Twilio WhatsApp number (similarly cleaned)
            $twilioNumber = preg_replace('/^\+|^whatsapp:/', '', env('TWILIO_WHATSAPP_FROM'));

            // Find the latest message from this customer to our system
            // FIXED QUERY: Remove the plus sign from both sides of the comparison
            $lastIncomingMessage = ChatMessage::where('sender', 'like', '%' . $phoneNumber)
                ->where('receiver', 'like', '%' . $twilioNumber)
                ->where('is_from_customer', true)
                ->latest()
                ->first();

            if (!$lastIncomingMessage) {
                // Also try with a different format - with country code
                $lastIncomingMessage = ChatMessage::where(function ($query) use ($phoneNumber) {
                    $query->where('sender', 'like', '%' . $phoneNumber)
                        ->orWhere('sender', 'like', '%+' . $phoneNumber);
                })
                ->where(function ($query) use ($twilioNumber) {
                    $query->where('receiver', 'like', '%' . $twilioNumber)
                        ->orWhere('receiver', 'like', '%+' . $twilioNumber);
                })
                ->where('is_from_customer', true)
                ->latest()
                ->first();
            }

            // Log whether we found a message and when it was
            if (!$lastIncomingMessage) {
                // Double-check the database for any messages from this number in ANY format
                $anyMessages = ChatMessage::where('sender', 'like', '%' . substr($phoneNumber, -9))
                    ->where('is_from_customer', true)
                    ->latest()
                    ->first();

                if ($anyMessages) {
                    // Log::info("Found message using partial phone number match: " . $anyMessages->sender);
                    $lastIncomingMessage = $anyMessages;
                } else {
                    return false;
                }
            }

            // Check if the last message from customer was within 24 hours
            $window = Carbon::now()->subHours(24);
            $messageTime = $lastIncomingMessage->created_at;
            $isInWindow = $messageTime->gt($window);

            return $isInWindow;
        } catch (\Exception $e) {
            // If anything goes wrong, log it
            Log::error("Error in canSendFreeformMessage: " . $e->getMessage());
            return false;
        }
    }

    public function sendMessage($to, $message)
    {
        try {
            // First check if we can send a free-form message
            if (!$this->canSendFreeformMessage($to)) {
                // Log the attempt for debugging
                Log::warning("Attempted to send message outside 24-hour window to: {$to}");
                return [
                    'success' => false,
                    'error' => '24_hour_window_closed',
                    'message' => 'Cannot send free-form message outside 24-hour customer service window.'
                ];
            }

            // Format phone number properly
            $formattedTo = preg_replace('/^\+|^whatsapp:/', '', $to);
            if (!str_starts_with($formattedTo, 'whatsapp:')) {
                $formattedTo = "whatsapp:{$formattedTo}";
            }

            $response = $this->twilio->messages->create(
                $formattedTo,
                [
                    "from" => env('TWILIO_WHATSAPP_FROM'),
                    "body" => $message
                ]
            );

            Log::info("✅ WhatsApp message sent to {$to} with Twilio ID: {$response->sid}");

            return [
                'success' => true,
                'sid' => $response->sid
            ];
        } catch (\Exception $e) {
            Log::error("❌ Error sending WhatsApp message: " . $e->getMessage());

            // Check for Twilio error codes related to messaging window
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'free form messages') !== false ||
                strpos($errorMessage, '24-hour window') !== false ||
                strpos($errorMessage, '63049') !== false) {

                return [
                    'success' => false,
                    'error' => '24_hour_window_closed',
                    'message' => 'Cannot send message: 24-hour customer service window has expired.'
                ];
            }

            return [
                'success' => false,
                'error' => 'general_error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function sendFile($to, $fileUrl, $fileMimeType)
    {
        // Check messaging window just like sendMessage
        if (!$this->canSendFreeformMessage($to)) {
            Log::warning("Attempted to send file outside 24-hour window to: {$to}");
            return [
                'success' => false,
                'error' => '24_hour_window_closed',
                'message' => 'Cannot send files outside 24-hour customer service window.'
            ];
        }

        // ✅ Convert private URL to public
        $fileUrl = str_replace('192.168.1.31:8082', 'crm.timeteccloud.com:8083', $fileUrl);

        try {
            // Format phone number properly
            $formattedTo = preg_replace('/^\+|^whatsapp:/', '', $to);
            if (!str_starts_with($formattedTo, 'whatsapp:')) {
                $formattedTo = "whatsapp:{$formattedTo}";
            }

            $response = $this->twilio->messages->create(
                $formattedTo,
                [
                    "from" => env('TWILIO_WHATSAPP_FROM'),
                    "mediaUrl" => [$fileUrl] // ✅ Twilio now receives a public URL
                ]
            );

            Log::info("✅ File sent to {$to} with Twilio ID: {$response->sid}");
            return [
                'success' => true,
                'sid' => $response->sid
            ];
        } catch (\Exception $e) {
            Log::error("❌ Error sending file via WhatsApp: " . $e->getMessage());

            // Check for Twilio error codes
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'free form messages') !== false ||
                strpos($errorMessage, '24-hour window') !== false ||
                strpos($errorMessage, '63049') !== false) {

                return [
                    'success' => false,
                    'error' => '24_hour_window_closed',
                    'message' => 'Cannot send file: 24-hour customer service window has expired.'
                ];
            }

            return [
                'success' => false,
                'error' => 'general_error',
                'message' => $e->getMessage()
            ];
        }
    }
}
