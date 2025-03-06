<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Lead;
use App\Models\CompanyDetail;
use App\Models\UtmDetail;

class FetchZohoLeads extends Command
{
    protected $signature = 'zoho:fetch-leads'; // ✅ Command name
    protected $description = 'Fetch leads from Zoho CRM and update database';

    public function handle()
    {
        $this->refreshZohoAccessToken(); // ✅ Ensure token is valid before fetching leads
        $this->fetchZohoLeads(); // ✅ Fetch and store leads
    }

    private function refreshZohoAccessToken()
    {
        info('Token Get ' . now());

        $clientId = env('ZOHO_CLIENT_ID');
        $clientSecret = env('ZOHO_CLIENT_SECRET');

        if (Cache::has('zoho_access_token')) {
            $this->info('Using cached Zoho access token.');
            return;
        }

        if (Cache::has('zoho_refresh_token')) {
            $refreshToken = Cache::get('zoho_refresh_token');
            $tokenResponse = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
                'refresh_token' => $refreshToken,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'grant_type'    => 'refresh_token',
            ]);

            $tokenData = $tokenResponse->json();
            Log::info('Zoho Token Refresh Response:', $tokenData);

            if (isset($tokenData['access_token'])) {
                Cache::put('zoho_access_token', $tokenData['access_token'], now()->addMinutes(55));
                $this->info('Zoho access token refreshed.');
                return;
            }

            $this->error('Failed to refresh Zoho access token.');
        }
    }

    private function fetchZohoLeads()
    {
        info('Zoho Lead Fetched ' . now());

        $accessToken = Cache::get('zoho_access_token');
        $apiDomain = 'https://www.zohoapis.com';

        if (!$accessToken) {
            $this->error('No access token available. Please authenticate first.');
            return;
        }

        // ✅ Get the latest created_at lead from the database
        $latestLead = Lead::orderBy('created_at', 'desc')->first();
        $latestCreatedAt = $latestLead ? Carbon::parse($latestLead->created_at)->format('Y-m-d\TH:i:sP') : '2025-03-01T00:00:00+00:00';

        $allLeads = [];
        $perPage = 50;
        $page = 1;
        $pageToken = null;

        while (true) {
            $queryParams = [
                'per_page' => $perPage,
                'criteria' => "(Created_Time:after:$latestCreatedAt)", // ✅ Fetch leads after the latest created_at
                //'startDateTime' => '>2025-03-04T18:07:16'
            ];

            if ($pageToken) {
                $queryParams['page_token'] = $pageToken;
            } else {
                $queryParams['page'] = $page;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->get($apiDomain . '/crm/v2/Leads', $queryParams);

            $leadsData = $response->json();

            if (!isset($leadsData['data']) || empty($leadsData['data'])) {
                break;
            }

            foreach ($leadsData['data'] as $lead) {
                if (!in_array('HR (Attendance, Leave, Claim, Payroll, Hire, Profile)', $lead['TimeTec_Products'])) {
                    continue; // ✅ Skip leads that don't match
                }

                $phoneNumber = isset($lead['Phone']) ? preg_replace('/^\+/', '', $lead['Phone']) : null;

                $leadCreatedTime = isset($lead['Created_Time'])
                    ? Carbon::parse($lead['Created_Time'])->format('Y-m-d H:i:s')
                    : null;
                $leadUpdatedTime = isset($lead['Modified_Time'])
                    ? Carbon::parse($lead['Modified_Time'])->format('Y-m-d H:i:s')
                    : null;

                $newLead = Lead::updateOrCreate(
                    ['zoho_id' => $lead['id'] ?? null],
                    [
                        'name'         => $lead['Full_Name'] ?? null,
                        'email'        => $lead['Email'] ?? null,
                        'country'      => $lead['Country'] ?? null,
                        'company_size' => $this->normalizeCompanySize($lead['Company_Size'] ?? null), // ✅ Normalize before storing
                        'phone'        => $phoneNumber,
                        'lead_code'    => $lead['Lead_Source'] ?? null,
                        'products'     => isset($lead['TimeTec_Products']) ? json_encode($lead['TimeTec_Products']) : null,
                        'lead_source'  => $lead['Lead_Source'] ?? null,
                        'created_at'   => $leadCreatedTime,
                        'updated_at'   => $leadUpdatedTime,
                    ]
                );

                if (!empty($lead['Company'])) {
                    $companyDetail = CompanyDetail::updateOrCreate(
                        ['company_name' => $lead['Company']],
                        [
                            'company_name' => $lead['Company'],
                            'lead_id'      => $newLead->id,
                        ]
                    );

                    $newLead->update([
                        'company_id' => $companyDetail->id ?? null,
                    ]);
                }

                $newLead->update([
                    'company_name' => $companyDetail->id ?? null, // ✅ Store company ID in lead table
                ]);

                UtmDetail::updateOrCreate(
                    ['lead_id' => $newLead->id],
                    [
                        'utm_campaign'  => $lead['utm_campaign'] ?? null,
                        'utm_adgroup'   => $lead['utm_adgroup'] ?? null,
                        'utm_creative'  => $lead['utm_creative'] ?? null,
                        'utm_term'      => $lead['utm_term'] ?? null,
                        'utm_matchtype' => $lead['utm_matchtype'] ?? null,
                        'device'        => $lead['device'] ?? null,
                        'social_lead_id'=> $lead['leadchain0__Social_Lead_ID'] ?? null,
                        'gclid'         => $lead['GCLID'] ?? null,
                        'referrername'  => $lead['referrername2'] ?? null,
                    ]
                );
            }

            if (isset($leadsData['info']['next_page_token'])) {
                $pageToken = $leadsData['info']['next_page_token'];
            } else {
                break;
            }

            $page++;
        }
    }

    private function normalizeCompanySize($size)
    {
        if (!$size) {
            return null;
        }

        // Remove extra spaces and normalize the value
        $normalizedSize = str_replace(' ', '', $size);

        switch ($normalizedSize) {
            case '1-24':
            case '1- 24':
            case '1 -24':
            case '1 - 24':
                return '1-24'; // ✅ Normalized as Small

            case '25-99':
            case '25- 99':
            case '25 -99':
            case '25 - 99':
                return '25-99'; // ✅ Normalized as Medium

            case '100-500':
            case '100- 500':
            case '100 -500':
            case '100 - 500':
                return '100-500'; // ✅ Normalized as Large

            case '501andAbove':
            case '501-and-Above':
            case '501 and Above':
                return '501 and Above'; // ✅ Normalized as Enterprise

            default:
                return 'Unknown'; // ✅ Fallback if not recognized
        }
    }
}
