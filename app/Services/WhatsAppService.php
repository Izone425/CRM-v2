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
        // Clean phone number
        $phoneNumber = preg_replace('/^\+|^whatsapp:/', '', $phoneNumber);

        // Get the Twilio WhatsApp number
        $twilioNumber = preg_replace('/^\+|^whatsapp:/', '', env('TWILIO_WHATSAPP_FROM'));

        // Find the latest message from this customer to our system
        $lastIncomingMessage = ChatMessage::where('sender', $phoneNumber)
            ->where('receiver', $twilioNumber)
            ->where('is_from_customer', true)
            ->latest()
            ->first();

        if (!$lastIncomingMessage) {
            // No prior communication from this customer
            return false;
        }

        // Check if the last message from customer was within 24 hours
        $window = Carbon::now()->subHours(24);
        return $lastIncomingMessage->created_at->gt($window);
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
