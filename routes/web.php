<?php

use App\Livewire\AcceptInvitation;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuotePdfController;
use App\Models\LeadSource;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GenerateProformaInvoicePdfController;
use App\Http\Controllers\GenerateQuotationPdfController;
use App\Http\Controllers\MicrosoftAuthController;
use App\Http\Controllers\PrintPdfController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Livewire\DemoRequest;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect(route('filament.admin.home'));
});

Route::middleware('signed')
    ->get('invitation/{invitation}/accept', AcceptInvitation::class)
    ->name('invitation.accept');

Route::middleware('signed')
    ->get('quotes/{quote}/pdf', QuotePdfController::class)
    ->name('quotes.pdf');

Route::get('/demo-request/{lead_code}', function ($lead_code) {
    // Check if the lead_code exists in the database
    $site = LeadSource::where('lead_code', $lead_code)->first();

    if (!$site) {
        // Return a 404 response if the lead_code is not found
        abort(404);
    }

    return view('demoRequest', ['lead_code' => $lead_code]);
});

Route::get('/referral-demo-request/{lead_code}', function ($lead_code) {
    // Check if the lead_code exists in the database
    $site = LeadSource::where('lead_code', $lead_code)->first();

    if (!$site) {
        // Return a 404 response if the lead_code is not found
        abort(404);
    }

    return view('referralDemoRequest', ['lead_code' => $lead_code]);
});

Route::get('/quotation/{quotation?}', PrintPdfController::class)->name('pdf.print-quotation');
Route::get('/quotation-v2/{quotation?}', GenerateQuotationPdfController::class)->name('pdf.print-quotation-v2');
Route::get('/proforma-invoice/{quotation?}', ProformaInvoiceController::class)->name('pdf.print-proforma-invoice');
Route::get('/proforma-invoice-v2/{quotation?}', GenerateProformaInvoicePdfController::class)->name('pdf.print-proforma-invoice-v2');

Route::post('/webhook/whatsapp', function (Request $request) {
    $data = $request->all();

    if (empty($data)) {
        $data = json_decode($request->getContent(), true);
    }
    Log::info('Incoming WhatsApp Message Data:', $data);

    $sender = $data['From'] ?? 'whatsapp:unknown';
    $receiver = $data['To'] ?? 'whatsapp:unknown';
    $twilioMessageId = $data['MessageSid'] ?? '';
    $profileName = $data['ProfileName'] ?? 'Unknown';
    $numMedia = $data['NumMedia'] ?? 0;

    // Check if the message contains media (file, image, sticker, audio)
    if ($numMedia > 0 && isset($data['MediaUrl0']) && isset($data['MediaContentType0'])) {
        $mediaUrl = $data['MediaUrl0'];
        $mediaType = $data['MediaContentType0']; // Example: "application/pdf", "image/png", "audio/ogg"

        // Determine the placeholder text based on media type
        if (str_contains($mediaType, 'image')) {
            $message = "[Image]";
        } elseif (str_contains($mediaType, 'audio')) {
            $message = "[Voice Message]";
        } elseif (str_contains($mediaType, 'application') || str_contains($mediaType, 'text')) {
            $message = "[File]";
        } else {
            $message = "[Media Message]";
        }
    } else {
        $message = $data['Body'] ?? 'No message received';
        $mediaUrl = null;
        $mediaType = null;
    }

    // Store the message
    ChatMessage::create([
        'sender' => preg_replace('/^\+|^whatsapp:/', '', $sender),
        'receiver' => preg_replace('/^\+|^whatsapp:/', '', $receiver),
        'message' => $message, // Text or placeholder for media
        'twilio_message_id' => $twilioMessageId,
        'profile_name' => $profileName,
        'is_from_customer' => true,
        'media_url' => $mediaUrl, // ✅ Save media URL
        'media_type' => $mediaType, // ✅ Save media type
    ]);
});

// Route::get('/demo-request', DemoRequest::class)->name('demo-request');

// Route::get('/auth/microsoft', function () {
//     return Socialite::driver('microsoft')->redirect();
// });

// Route::get('/auth/microsoft/callback', function () {
//     $user = Socialite::driver('microsoft')->user();
//     // Store $user->token in the database for API requests
// });

// Route::get('auth/microsoft', [MicrosoftAuthController::class, 'redirectToMicrosoft'])->name('microsoft.auth');
// Route::get('auth/microsoft/callback', [MicrosoftAuthController::class, 'handleMicrosoftCallback']);

