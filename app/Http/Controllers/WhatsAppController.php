<?php
namespace App\Http\Controllers;

use App\Models\ChatMessage;
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

            $messageText = $this->generateMessageFromTemplate($contentTemplateSid, $contentVariables);

            ChatMessage::create([
                'sender' => preg_replace('/^\+|^whatsapp:/', '', env('TWILIO_WHATSAPP_FROM')),
                'receiver' => preg_replace('/^\+|^whatsapp:/', '', $phoneNumber),
                'message' => $messageText,
                'twilio_message_id' => $message->sid,
                'is_from_customer' => false,
            ]);

            return response()->json(['message' => 'WhatsApp template message sent successfully', 'sid' => $message->sid]);
        } catch (\Exception $e) {
            Log::error('WhatsApp Template Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generateMessageFromTemplate($contentTemplateSid, $variables)
    {
        $templates = [
            'HXe771df50cc3d315ec8cd86321b4ff70d' =>
                "Hi {{1}}. As per discussed via phone call, our demo session has been scheduled.
                Company : {{2}}
                Phone No : {{3}}
                PIC : {{4}}
                Email : {{5}}

                Demo Type : {{6}}
                Demo Date / Time : {{7}}
                Meeting Link : {{8}}",

            'HX50fdd31004919fd43e647ebfb934d608' =>
                "Hi {{1}}! I'm {{2}} from TimeTec. Thanks for your interest in our HR Cloud Solutions!\n\n" .
                "We offer awesome modules to make HR tasks a breeze:\n" .
                "✅ Time Attendance\n" .
                "✅ Payroll System\n" .
                "✅ Claim Management\n" .
                "✅ Leave Management\n\n" .
                "🎁 Special Promotion:\n" .
                "Secure a FREE Biometric Device when you subscribe to our Time Attendance module!\n" .
                "Why not schedule a quick demo to see how our solutions can benefit your organization? Plus, I’ll show you how to claim your FREE Biometric Device.\n\n" .
                "🚀 Here’s our brochure to get you started: https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf\n" .
                "Can’t wait to chat with you! 😊",

            'HXee59098cc1d267094875b84ceed0dc09' =>
                "Hi {{1}},  {{2}} here again! 😊\n\n" .
                "Just wanted to check in and see if you've had a chance to look over our brochure.\n" .
                "If you're interested in setting up a demo, please let me know the best time to call you so we can arrange it for you.",

            'HXddbbe2f375b1ad34e9cd6f9e35fa62f0' =>
                "Just a quick reminder—the offer for a FREE Biometric Device is still available!\n" .
                "It’s a great way to enhance your HR capabilities at no extra cost. 😊\n\n" .
                "If now isn't the right time or if there’s someone else I should reach out to, please let me know. I’m here to assist!",

            'HX17778b5cec4858f24535bdbc69eebd8a' =>
                "Just popping in one last time to make sure I’m not overloading your WhatsApp. 🙈\n\n" .
                "If now isn’t the right time for a chat, could you let me know when might be better, or if there’s someone else I should reach out to?\n\n" .
                "And hey, if you ever want to revisit this down the line, I’m just a message away and ready to dive back in whenever you are! 😊"
        ];

        // Get the template text
        $templateText = $templates[$contentTemplateSid] ?? "Message content unavailable.";

        // Replace placeholders with actual values
        foreach ($variables as $key => $value) {
            $templateText = str_replace("{{" . $key . "}}", $value, $templateText);
        }

        return $templateText;
    }
}
