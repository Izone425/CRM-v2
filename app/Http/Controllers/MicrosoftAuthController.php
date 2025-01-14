<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\GenericProvider;

class MicrosoftAuthController extends Controller
{
    protected function getProvider()
    {
        return new GenericProvider([
            'clientId'                => env('MICROSOFT_CLIENT_ID'),
            'clientSecret'            => env('MICROSOFT_CLIENT_SECRET'),
            'redirectUri'             => env('MICROSOFT_REDIRECT_URI'),
            'urlAuthorize'            => 'https://login.microsoftonline.com/' . env('MICROSOFT_TENANT_ID') . '/oauth2/v2.0/authorize',
            'urlAccessToken'          => 'https://login.microsoftonline.com/' . env('MICROSOFT_TENANT_ID') . '/oauth2/v2.0/token',
            'urlResourceOwnerDetails' => '',
            'scopes'                  => 'OnlineMeetings.ReadWrite Calendars.ReadWrite User.Read'
        ]);
    }

    public function redirectToMicrosoft()
    {
        $provider = $this->getProvider();
        $authorizationUrl = $provider->getAuthorizationUrl();
        session(['oauth2state' => $provider->getState()]);
        return redirect($authorizationUrl);
    }

    public function handleMicrosoftCallback(Request $request)
    {
        if ($request->input('state') !== session('oauth2state')) {
            session()->forget('oauth2state');
            abort(403, 'Invalid state');
        }

        $provider = $this->getProvider();

        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $request->input('code')
            ]);

            // Store the access token in session or database
            session(['microsoft_access_token' => $accessToken->getToken()]);

            return redirect()->route('dashboard'); // Adjust as needed
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
