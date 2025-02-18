<?php
namespace App\Services;

use App\Models\ChatMessage;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

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

    public function sendMessage($to, $message)
    {
        try {
            $response = $this->twilio->messages->create(
                "whatsapp:$to",
                [
                    "from" => env('TWILIO_WHATSAPP_FROM'),
                    "body" => $message
                ]
            );

            // Log::info("✅ WhatsApp message sent to $to with Twilio ID: " . $response->sid);

            // ChatMessage::create([
            //     'sender' => preg_replace('/^\+|^whatsapp:/', '', env('TWILIO_WHATSAPP_FROM')),
            //     'receiver' => preg_replace('/^\+|^whatsapp:/', '', $to),
            //     'message' => $message,
            //     'twilio_message_id' => $response->sid,
            //     'is_from_customer' => false, // Since this is sent by the system
            // ]);

            return $response->sid; // ✅ Return Twilio Message ID
        } catch (\Exception $e) {
            Log::error("❌ Error sending WhatsApp message: " . $e->getMessage());
            return false;
        }
    }

    public function sendFile($to, $fileUrl, $fileMimeType)
    {
        // ✅ Convert private URL to public
        // $fileUrl = str_replace('192.168.1.31:8082', 'crm.timeteccloud.com:8083', $fileUrl);

        try {
            $response = $this->twilio->messages->create(
                "whatsapp:$to",
                [
                    "from" => env('TWILIO_WHATSAPP_FROM'),
                    "mediaUrl" => [$fileUrl] // ✅ Twilio now receives a public URL
                ]
            );

            Log::info("✅ File sent to $to with Twilio ID: " . $response->sid);
            return $response->sid;
        } catch (\Exception $e) {
            Log::error("❌ Error sending file via WhatsApp: " . $e->getMessage());
            return false;
        }
    }
}
