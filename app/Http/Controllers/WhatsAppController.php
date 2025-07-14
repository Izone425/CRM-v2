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
            //EN
            'HXe771df50cc3d315ec8cd86321b4ff70d' =>
                "Hi {{1}}. As per discussed via phone call, our demo session has been scheduled.
                Company : {{2}}
                Phone No : {{3}}
                PIC : {{4}}
                Email : {{5}}

                Demo Type : {{6}}
                Demo Date / Time : {{7}}
                Meeting Link : {{8}}",

            'HX5c9b745783710d7915fedc4e7e503da0' =>
                "Hi {{1}}! I'm {{2}} from TimeTec. Thanks for your interest in our HR Cloud Solutions!\n\n" .
                "We offer awesome modules to make HR tasks a breeze:\n" .
                "✅ Time Attendance\n" .
                "✅ Payroll System\n" .
                "✅ Claim Management\n" .
                "✅ Leave Management\n\n" .
                "🎁 Special Promotion:\n" .
                "Secure a FREE Biometric Device when you subscribe to our Time Attendance module (terms and conditions apply)!\n" .
                "Why not schedule a quick demo to see how our solutions can benefit your organization? Plus, I’ll show you how to claim your FREE Biometric Device.\n\n" .
                "🚀 Here’s our brochure to get you started: https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf\n" .
                "Can’t wait to chat with you! 😊",

            'HX6531d9c843b71e0a45accd0ce2cfe5f2' =>
                "Hi {{1}},  {{2}} here again! 😊\n\n" .
                "Just wanted to check in and see if you've had a chance to look over our brochure.\n" .
                "If you're interested in setting up a demo, please let me know the best time to call you so we can arrange it for you.",

            'HXcccb50b8124d29d7d21af628b92522d4' =>
                "Just a quick reminder—the offer for a FREE Biometric Device (terms and conditions apply) is still available!\n" .
                "It’s a great way to enhance your HR capabilities at no extra cost. 😊\n" .
                "If now isn't the right time or if there’s someone else I should reach out to, please let me know. I’m here to assist!",

            'HX517e06b8e7ddabea51aa799bfd1987f8' =>
                "Just popping in one last time to make sure I’m not overloading your WhatsApp. 🙈\n\n" .
                "If now isn’t the right time for a chat, could you let me know when might be better, or if there’s someone else I should reach out to?\n\n" .
                "And hey, if you ever want to revisit this down the line, I’m just a message away and ready to dive back in whenever you are! 😊",

            //BM
            'HXcc05134b6c74ecc02682a25887978630' =>
                "Hai {{1}}! Saya {{2}} daripada TimeTec. Terima kasih atas minat anda terhadap Penyelesaian HR Berasaskan Awan kami!\n\n" .
                "Kami menawarkan modul-modul hebat untuk memudahkan tugasan HR anda:\n" .
                "✅ Pengurusan Kehadiran\n" .
                "✅ Pengurusan Penggajian\n" .
                "✅ Pengurusan Tuntutan\n" .
                "✅ Pengurusan Cuti\n\n" .
                "🎁 Promosi Istimewa:\n" .
                "Dapatkan Peranti Biometrik PERCUMA apabila anda langgan modul Sistem Kehadiran kami (tertakluk kepada terma dan syarat)!\n" .
                "Mari kita jadualkan sesi demo ringkas untuk lihat bagaimana penyelesaian kami boleh memberi manfaat kepada organisasi anda. Saya juga akan tunjukkan cara untuk menebus Peranti Biometrik PERCUMA anda.\n\n" .
                "🚀 Berikut ialah risalah kami untuk bantu anda mula: https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf\n" .
                "Anda boleh WhatsApp saya jika ada sebarang pertanyaan! 😊",

            'HXbb1b933e2fa363c64c996ae0da7c8773' =>
                "Hai {{1}}, {{2}} di sini lagi! 😊\n" .
                "Cuma nak tanya kalau anda sempat tengok risalah yang kami kongsikan sebelum ni?\n" .
                "Jika anda berminat untuk menetapkan sesi demo, anda boleh maklumkan masa yang sesuai untuk kami hubungi dan kami boleh aturkannya untuk anda.",

            'HX8094ffaa4380226a4c803c10ea59655e' =>
                "Sekadar peringatan ringkas—tawaran untuk mendapatkan Peranti Biometrik PERCUMA masih ada (tertakluk kepada terma dan syarat)!\n" .
                "Ini adalah peluang terbaik untuk meningkatkan keupayaan HR anda tanpa sebarang kos tambahan. 😊\n" .
                "Jika ini bukan masa yang sesuai, atau sekiranya ada individu lain yang patut saya hubungi, sila maklumkan kepada saya. Saya sedia membantu!",

            'HX4d2db45f7de1fd07563369d87a0c8c75' =>
                "Maaf kerana mengganggu, saya cuma nak pastikan mesej saya sebelum ini tak tenggelam di WhatsApp anda.\n" .
                "Sekiranya waktu ini kurang sesuai untuk berbual, mohon maklumkan waktu yang lebih sesuai, atau jika ada individu lain yang lebih berkaitan untuk saya hubungi.\n" .
                "Sekiranya anda ingin berbincang semula pada masa akan datang, saya sentiasa bersedia untuk membantu. Cukup sekadar hantarkan mesej, dan saya akan bantu sebaik mungkin. 😊",

            //Request company info template
            'HX50b95050ff8d2fe33edf0873c4d2e2b4' =>
                "Hi {{1}}, as per our phone conversation, please provide your details below so we can provide quotation to you:
                (Minimum headcount is 5 user/staff)

                Department:
                Company Name (As registered in SSM):
                Address:
                Email:
                Mobile number:
                HRDF Register:
                Headcount:
                Module interested:",

            //Send demo slot template
            'HX8ffc6fd8b995859aa28fa59ba9712529' =>
                "Hi {{1}}, below is our available online/onsite demo slot:

                {{2}}
                {{3}}
                {{4}}

                Please let me know if you are available to join our demo.

                *The demo will take 1 hour, including a Q&A session

                *Kindly reply if you have received this message.*",

            //Send demo confirmation template
            'HX38ef28749e5a21f1725b67a424bc0b31' =>
                "Hi {{1}}, your demo session has been confirmed:

                {{2}} ({{3}}) - {{4}}
                {{5}} Demo
                Salesperson - {{6}}, {{7}}

                The demo will take 1 hour, including a Q&A session. If you have any questions or things you'd like to clarify, feel free to jot them down and bring them up during the demo.

                Our salesperson will be contacting you directly. Please feel free to liaise with them for any further assistance.",
        ];

        if (!isset($templates[$contentTemplateSid])) {
            throw new \Exception("Template not found with ID: $contentTemplateSid");
        }

        // Get the template text
        $templateText = $templates[$contentTemplateSid] ?? "Message content unavailable.";

        // Replace placeholders with actual values
        foreach ($variables as $key => $value) {
            $templateText = str_replace("{{" . $key . "}}", $value, $templateText);
        }

        return $templateText;
    }
}
