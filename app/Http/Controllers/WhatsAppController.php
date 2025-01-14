<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class WhatsAppController extends Controller
{
    public function sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables)
    {
        $twilioSid = env('TWILIO_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioWhatsAppNumber = env('TWILIO_WHATSAPP_FROM');

        $twilio = new Client($twilioSid, $twilioToken);

        try {
            // Construct contentVariables dynamically
            $contentVariables = [];
            foreach ($variables as $index => $value) {
                $contentVariables[(string)($index + 1)] = $value ?? ''; // Assign empty string if null
            }

            $message = $twilio->messages->create(
                "whatsapp:$phoneNumber",
                [
                    "from" => $twilioWhatsAppNumber,
                    "contentSid" => $contentTemplateSid,
                    "contentVariables" => json_encode($contentVariables),
                ]
            );

            return response()->json(['message' => 'WhatsApp template message sent successfully', 'sid' => $message->sid]);
        } catch (\Exception $e) {
            Log::error('WhatsApp Template Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
