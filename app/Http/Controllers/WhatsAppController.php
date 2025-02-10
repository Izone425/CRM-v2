<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ChatMessage;

class WhatsAppController extends Controller
{
    public function receiveMessage(Request $request)
    {
        Log::info('Incoming WhatsApp Message:', $request->all());

        $from = $request->input('From'); // Customer's WhatsApp number
        $body = $request->input('Body'); // Message content

        // Save message to database
        ChatMessage::create([
            'customer_whatsapp' => str_replace("whatsapp:", "", $from),
            'message' => $body,
            'type' => 'received',
        ]);

        return response()->json(['status' => 'ok'], 200);
    }
}
