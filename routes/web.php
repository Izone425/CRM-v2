<?php

use App\Livewire\AcceptInvitation;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuotePdfController;
use App\Models\LeadSource;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Http\Controllers\GenerateProformaInvoicePdfController;
use App\Http\Controllers\GenerateQuotationPdfController;
use App\Http\Controllers\GenerateSoftwareHandoverPdfController;
use App\Http\Controllers\MicrosoftAuthController;
use App\Http\Controllers\PrintPdfController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Livewire\DemoRequest;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Lead;
use App\Models\CompanyDetail;
use App\Models\UtmDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

Route::get('software-handover/{softwareHandover}/pdf', GenerateSoftwareHandoverPdfController::class)
    ->name('software-handover.pdf')
    ->middleware(['auth']);

Route::get('hardware-handover/{hardwareHandover}/pdf', GenerateHardwareHandoverPdfController::class)
    ->name('hardware-handover.pdf')
    ->middleware(['auth']);

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
    if (!str_contains($sender, 'unknown') && !str_contains($receiver, 'unknown')) {
        ChatMessage::create([
            'sender' => preg_replace('/^\+|^whatsapp:/', '', $sender),
            'receiver' => preg_replace('/^\+|^whatsapp:/', '', $receiver),
            'message' => $message,
            'twilio_message_id' => $twilioMessageId,
            'profile_name' => $profileName,
            'is_from_customer' => true,
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'reply_to_sid' => $request->input('OriginalRepliedMessageSid') ?? null,
        ]);
    } else {
        Log::warning('Skipped saving WhatsApp message due to missing sender or receiver.', [
            'sender' => $sender,
            'receiver' => $receiver
        ]);
    }
});

// Route::get('/zoho/auth', function (Request $request) {
//     $clientId = env('ZOHO_CLIENT_ID');
//     $clientSecret = env('ZOHO_CLIENT_SECRET');
//     $redirectUri = env('ZOHO_REDIRECT_URI');

//     // ✅ Check if a valid access token exists
//     if (Cache::has('zoho_access_token')) {
//         return response()->json([
//             'message' => 'Using cached Zoho access token',
//             'access_token' => Cache::get('zoho_access_token')
//         ]);
//     }

//     // ✅ If no access token, check if a refresh token exists to refresh it
//     if (Cache::has('zoho_refresh_token')) {
//         $refreshToken = Cache::get('zoho_refresh_token');
//         $tokenResponse = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
//             'refresh_token' => $refreshToken,
//             'client_id'     => $clientId,
//             'client_secret' => $clientSecret,
//             'grant_type'    => 'refresh_token',
//         ]);

//         $tokenData = $tokenResponse->json();
//         Log::info('Zoho Token Refresh Response:', $tokenData);

//         if (isset($tokenData['access_token'])) {
//             Cache::put('zoho_access_token', $tokenData['access_token'], now()->addMinutes(55));
//             return response()->json([
//                 'message' => 'Zoho access token refreshed',
//                 'access_token' => $tokenData['access_token']
//             ]);
//         }
//     }

//     // ✅ If no refresh token, redirect user to Zoho authentication
//     $authUrl = "https://accounts.zoho.com/oauth/v2/auth?" . http_build_query([
//         'client_id'     => $clientId,
//         'response_type' => 'code',
//         'scope'         => 'ZohoCRM.modules.all',
//         'redirect_uri'  => $redirectUri,
//         'access_type'   => 'offline',
//         'prompt'        => 'consent',
//     ]);

//     return redirect()->away($authUrl);
// });

// Route::get('/zoho/callback', function (Request $request) {
//     Log::info('Incoming Zoho Callback Data:', $request->all());

//     $code = $request->query('code');
//     if (!$code) {
//         return response()->json(['error' => 'No authorization code received'], 400);
//     }

//     $clientId = env('ZOHO_CLIENT_ID');
//     $clientSecret = env('ZOHO_CLIENT_SECRET');
//     $redirectUri = env('ZOHO_REDIRECT_URI');

//     // Exchange Code for Access Token
//     $tokenResponse = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
//         'code'          => $code,
//         'client_id'     => $clientId,
//         'client_secret' => $clientSecret,
//         'redirect_uri'  => $redirectUri,
//         'grant_type'    => 'authorization_code',
//     ]);

//     $tokenData = $tokenResponse->json();
//     Log::info('Zoho Token Response:', $tokenData);

//     if (!isset($tokenData['access_token'])) {
//         return response()->json(['error' => 'Failed to get access token', 'details' => $tokenData], 400);
//     }

//     // ✅ Store access token & refresh token
//     Cache::put('zoho_access_token', $tokenData['access_token'], now()->addMinutes(55));
//     if (isset($tokenData['refresh_token'])) {
//         Cache::forever('zoho_refresh_token', $tokenData['refresh_token']);
//     }

//     return response()->json([
//         'message' => 'Zoho authentication successful',
//         'access_token' => $tokenData['access_token'],
//         'refresh_token' => $tokenData['refresh_token'] ?? 'Already stored',
//     ]);
// });

// Route::get('/zoho/leads', function (Request $request) {
//     $accessToken = Cache::get('zoho_access_token');
//     $apiDomain = 'https://www.zohoapis.com';

//     if (!$accessToken) {
//         return response()->json(['error' => 'No access token available. Please authenticate first.'], 400);
//     }

//     // ✅ Get the sorting parameter from the request (default to 'id')
//     $sortBy = $request->query('sort_by', 'id'); // Possible values: id, Created_Time, Modified_Time

//     // Fetch Leads from Zoho with sorting
//     $response = Http::withHeaders([
//         'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
//         'Content-Type'  => 'application/json',
//     ])->get($apiDomain . '/crm/v2/Leads', [
//         'page'     => 1,
//         'per_page' => 200, // ✅ Max limit is 200, not 300
//         'criteria' => '(Created_Time:after:2025-03-01)'
//     ]);

//     return($leadsData = $response->json());
// });

// Route::get('/zoho/deals', function () {
//     $accessToken = Cache::get('zoho_access_token');
//     $apiDomain = 'https://www.zohoapis.com';

//     if (!$accessToken) {
//         return response()->json(['error' => 'No access token available. Please authenticate first.'], 400);
//     }

//     $allDeals = [];
//     $perPage = 200;
//     $page = 1;
//     $pageToken = null;

//     while (true) {
//         // ✅ API query parameters
//         $queryParams = [
//             'per_page' => $perPage,
//             'criteria' => '(Created_Time:after:2025-03-01)',
//         ];

//         if ($pageToken) {
//             $queryParams['page_token'] = $pageToken; // ✅ Use page_token for large data
//         } else {
//             $queryParams['page'] = $page; // ✅ Use normal page-based pagination first
//         }

//         // ✅ Fetch Deals from Zoho
//         $response = Http::withHeaders([
//             'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
//             'Content-Type'  => 'application/json',
//         ])->get($apiDomain . '/crm/v2/Deals', $queryParams);

//         $dealsData = $response->json();

//         if (!isset($dealsData['data']) || empty($dealsData['data'])) {
//             break; // ✅ Stop if no more deals
//         }

//         // ✅ Merge deals into $allDeals
//         $allDeals = array_merge($allDeals, $dealsData['data']);

//         // ✅ Check if next_page_token exists
//         if (isset($dealsData['info']['next_page_token'])) {
//             $pageToken = $dealsData['info']['next_page_token'];
//         } else {
//             break; // ✅ Stop if no next page
//         }

//         $page++;
//     }

//     return response()->json([
//         'message' => 'All deals retrieved successfully',
//         'total_deals' => count($allDeals),
//         'deals' => $allDeals
//     ]);
// });

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

