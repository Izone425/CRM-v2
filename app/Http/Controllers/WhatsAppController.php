<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class WhatsAppController extends Controller
{
    public function receiveWhatsAppMessage(Request $request)
    {
        Log::info('Incoming WhatsApp Message: ', $request->all());

        $from = $request->input('From'); // e.g., whatsapp:+1234567890
        $body = $request->input('Body'); // The text message sent by the user

        // Process the message and determine response
        $responseText = $this->processCustomerMessage($body);

        // Send a response
        return $this->sendWhatsAppMessage($from, $responseText);
    }

    private function processCustomerMessage($message)
    {
        // Add custom logic to process the message and generate a response
        if (strtolower($message) == 'hello') {
            return 'Hello! How can I assist you today? 😊';
        } elseif (strtolower($message) == 'help') {
            return 'Sure! Please tell me what you need help with.';
        } else {
            return 'I am not sure how to respond to that. Type "help" for assistance.';
        }
    }

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
