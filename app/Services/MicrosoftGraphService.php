<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MicrosoftGraphService
{
    public static function getAccessToken()
    {
        $clientId = 'd22318e0-2016-4445-8640-00370bb6ff9a';
        $clientSecret = 'sWh8Q~isxqGh0KymgmBQfaqjgkx-5Hg1rfuQkctQ';
        $tenantId = 'db45ae30-3921-4816-bd84-98cf14d5a17b';

        $url = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

        $response = Http::asForm()->post($url, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to retrieve access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }
}
