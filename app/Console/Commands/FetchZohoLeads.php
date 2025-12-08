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
use App\Models\ActivityLog;
use App\Models\ReferralDetail;
use App\Models\SystemQuestion;

class FetchZohoLeads extends Command
{
    protected $signature = 'zoho:fetch-leads'; // ✅ Command name
    protected $description = 'Fetch leads from Zoho CRM and update database';

    public function handle()
    {
        $this->refreshZohoAccessToken();
        $this->fetchZohoLeads();
    }

    private function refreshZohoAccessToken()
    {
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
        $perPage = 200;
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
                if (!empty($lead['Status_Division'])) {
                    continue;
                }

                // ✅ Check if Tag is present
                $hasHRMalaysiaTag = false;
                $hasBDRefereeTag = false;

                if (!empty($lead['Tag']) && is_array($lead['Tag'])) {
                    foreach ($lead['Tag'] as $tag) {
                        if (isset($tag['name'])) {
                            if ($tag['name'] === 'HR Malaysia') {
                                $hasHRMalaysiaTag = true;
                            }
                            if ($tag['name'] === 'BD Referee') {
                                $hasBDRefereeTag = true;
                            }
                        }
                    }
                }

                // ✅ If HR Malaysia tag exists, ACCEPT immediately
                if (!$hasHRMalaysiaTag) {
                    // Otherwise if BD Referee, need extra checking
                    if ($hasBDRefereeTag) {
                        // Must have TimeTec_Products = HR
                        $hasHRProduct = false;
                        if (!empty($lead['TimeTec_Products']) && is_array($lead['TimeTec_Products'])) {
                            $hasHRProduct = in_array('HR (Attendance, Leave, Claim, Payroll, Hire, Profile)', $lead['TimeTec_Products']);
                        }

                        // Must be Malaysia
                        $isMalaysia = ($lead['Country'] ?? '') === 'Malaysia';

                        if (!$hasHRProduct || !$isMalaysia) {
                            continue; // Skip if BD Referee but fail Product/Country check
                        }
                    } else {
                        continue;
                    }
                }

                $phoneNumber = null;

                if (isset($lead['Phone'])) {
                    // Remove everything except digits
                    $cleanedPhone = preg_replace('/[^0-9]/', '', $lead['Phone']);

                    // Replace starting 0 with 6
                    if (preg_match('/^0/', $cleanedPhone)) {
                        $cleanedPhone = '60' . substr($cleanedPhone, 1);
                    }

                    $phoneNumber = $cleanedPhone;
                }

                $leadCreatedTime = isset($lead['Created_Time'])
                    ? Carbon::parse($lead['Created_Time'])->format('Y-m-d H:i:s')
                    : null;

                // ✅ Check if lead already exists; if so, skip creation
                $existingLead = Lead::where('zoho_id', $lead['id'] ?? null)->exists();
                if ($existingLead) {
                    continue; // ✅ Skip if lead exists
                }

                $leadSource = $lead['Lead_Source'] ?? null;

                if (empty($leadSource) && !empty($lead['utm_matchtype'])) {
                    $leadSource = 'Google AdWords';
                }

                if (empty($leadSource) && !empty($lead['referrername2']) && empty($lead['GCLID']) && empty($lead['leadchain0__Social_Lead_ID'])) {
                    $referrerName = $lead['referrername2'];
                    if (strpos($referrerName, 'https://www') !== false) {
                        $leadSource = 'Website';
                    }
                }

                $existingLead = null;

                // First, check for existing lead by email AND phone
                if (!empty($lead['Email']) || !empty($phoneNumber)) {
                    $query = Lead::query();

                    // Group the email OR phone conditions
                    $query->where(function($q) use ($lead, $phoneNumber) {
                        // Check for email match
                        if (!empty($lead['Email'])) {
                            $q->where('email', $lead['Email']);
                        }

                        // Check for phone match
                        if (!empty($phoneNumber)) {
                            if (!empty($lead['Email'])) {
                                // If we already checked for email, add phone as OR condition
                                $q->orWhere('phone', $phoneNumber);
                            } else {
                                // If no email, just check phone
                                $q->where('phone', $phoneNumber);
                            }
                        }
                    });

                    // If we have a creation time for the new lead, use it for time-based checks
                    if (isset($lead['Created_Time'])) {
                        $leadCreationTime = Carbon::parse($lead['Created_Time']);
                        $oneDayAgo = (clone $leadCreationTime)->subDay();
                        $oneDayAfter = (clone $leadCreationTime)->addDay();

                        // Find leads with matching email OR phone that fall within the 1-day window
                        $existingLeadInTimeWindow = (clone $query)
                            ->whereBetween('created_at', [
                                $oneDayAgo->format('Y-m-d H:i:s'),
                                $oneDayAfter->format('Y-m-d H:i:s')
                            ])
                            ->first();
                        // If we find a lead within the time window, use that to prevent duplicate
                        if ($existingLeadInTimeWindow) {
                            $existingLead = $existingLeadInTimeWindow;
                        } else {
                            $existingLead = null;
                        }
                    } else {
                        // No creation time available, check for any match
                        $existingLead = $query->first();
                    }
                } else {
                    $existingLead = null;
                }

                // If we found an existing lead within the time window, skip it
                if ($existingLead) {
                    continue;
                }

                if ($leadSource === 'Google AdWords (CN)' || $leadSource === 'Facebook Ads (CN)') {
                    $newLead = Lead::create([
                        'zoho_id'      => $lead['id'] ?? null,
                        'name'         => $lead['Full_Name'] ?? null,
                        'email'        => $lead['Email'] ?? null,
                        'country'      => $lead['Country'] ?? null,
                        'company_size' => $this->normalizeCompanySize($lead['Company_Size'] ?? '1-24'),
                        'phone'        => $phoneNumber,
                        'lead_code'    => $leadSource,
                        'lead_owner'   => 'Sheena Liew',
                        'products'     => isset($lead['TimeTec_Products']) ? json_encode($lead['TimeTec_Products']) : null,
                        'created_at'   => $leadCreatedTime,
                        'categories'   => 'Active',
                        'stage'        => 'Transfer',
                        'lead_status'  => 'New',
                    ]);

                    $latestActivityLog = ActivityLog::where('subject_id', $newLead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($latestActivityLog) {
                        $latestActivityLog->update([
                            'description' => 'New lead created',
                        ]);
                    }

                    sleep(1);

                    ActivityLog::create([
                        'subject_type' => Lead::class,
                        'subject_id'   => $newLead->id,
                        'causer_type'  => null,
                        'causer_id'    => null,
                        'description'  => 'Lead assigned to Lead Owner: Sheena Liew',
                        'properties'   => json_encode([
                            'action'  => 'lead_owner_assigned',
                            'value'   => 'Sheena Liew',
                            'changes' => [
                                'lead_owner' => [
                                    'old' => null,
                                    'new' => 'Sheena Liew',
                                ],
                            ],
                        ]),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);

                    if (!empty($lead['Company'])) {
                        $companyDetail = CompanyDetail::create([
                            'company_name' => $lead['Company'],
                            'linkedin_url' => $lead['Linkedin_Profile_URL'] ?? null,
                            'lead_id'      => $newLead->id,
                        ]);

                        $newLead->updateQuietly([
                            'company_name' => $companyDetail->id ?? null,
                            'linkedin_url' => $lead['Linkedin_Profile_URL'] ?? null,
                        ]);
                    }
                } elseif ($leadSource === 'Google AdWords (PG)') {
                    // ✅ Handle Google AdWords (PG) - assign to salesperson ID 25
                    $newLead = Lead::create([
                        'zoho_id'      => $lead['id'] ?? null,
                        'name'         => $lead['Full_Name'] ?? null,
                        'email'        => $lead['Email'] ?? null,
                        'country'      => $lead['Country'] ?? null,
                        'company_size' => $this->normalizeCompanySize($lead['Company_Size'] ?? '1-24'),
                        'phone'        => $phoneNumber,
                        'lead_code'    => $leadSource,
                        'salesperson'  => 25,
                        'products'     => isset($lead['TimeTec_Products']) ? json_encode($lead['TimeTec_Products']) : null,
                        'created_at'   => $leadCreatedTime,
                        'categories'   => 'Active',
                        'stage'        => 'Transfer',
                        'lead_status'  => 'RFQ-Transfer',
                    ]);

                    $latestActivityLog = ActivityLog::where('subject_id', $newLead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($latestActivityLog) {
                        $salespersonName = \App\Models\User::find(25)?->name ?? 'Unknown Salesperson';
                        $latestActivityLog->update([
                            'description' => 'New lead created and assigned to Salesperson: ' . $salespersonName,
                        ]);
                    }

                    if (!empty($lead['Company'])) {
                        $companyDetail = CompanyDetail::create([
                            'company_name' => $lead['Company'],
                            'linkedin_url' => $lead['Linkedin_Profile_URL'] ?? null,
                            'lead_id'      => $newLead->id,
                        ]);

                        $newLead->updateQuietly([
                            'company_name' => $companyDetail->id ?? null,
                            'linkedin_url' => $lead['Linkedin_Profile_URL'] ?? null,
                        ]);
                    }
                } elseif (!empty($lead['Salesperson'])) {
                    $salespersonUserId = null;
                    if (!empty($lead['Salesperson'])) {
                        $user = \App\Models\User::where('name', $lead['Salesperson'])->first();
                        $salespersonUserId = $user ? $user->id : null;
                    }

                    $newLead = Lead::create([
                        'zoho_id'      => $lead['id'] ?? null,
                        'name'         => $lead['Full_Name'] ?? null,
                        'email'        => $lead['Email'] ?? null,
                        'country'      => $lead['Country'] ?? null,
                        'company_size' => $this->normalizeCompanySize($lead['Company_Size'] ?? '1-24'),
                        'phone'        => $phoneNumber,
                        'lead_code'    => $leadSource,
                        'salesperson'  => $salespersonUserId,
                        'products'     => isset($lead['TimeTec_Products']) ? json_encode($lead['TimeTec_Products']) : null,
                        'created_at'   => $leadCreatedTime,
                        'categories'   => 'Active',
                        'stage'        => 'Transfer',
                        'lead_status'  => 'RFQ-Transfer',
                    ]);

                    SystemQuestion::create([
                        'lead_id' => $newLead->id,
                        'modules' => is_array($lead['Which_Module_That_You_Are_Looking_For'])
                            ? implode(', ', $lead['Which_Module_That_You_Are_Looking_For'])
                            : $lead['Which_Module_That_You_Are_Looking_For'],
                        'existing_system' => $lead['What_Is_Your_Existing_System_For_Each_Module'],
                        'causer_name' => 'Get from EXPO / 2025 / HRDF NHCCE / 6-8 OCTOBER 2025',
                    ]);

                    $latestActivityLog = ActivityLog::where('subject_id', $newLead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($latestActivityLog) {
                        $latestActivityLog->update([
                            'description' => 'New lead created and assigned to Salesperson: ' . ($lead['Salesperson'] ?? 'Unknown'),
                        ]);
                    }

                    if (!empty($lead['Company'])) {
                        $companyDetail = CompanyDetail::create([
                            'company_name' => $lead['Company'],
                            'linkedin_url' => $lead['Linkedin_Profile_URL'] ?? null,
                            'lead_id'      => $newLead->id,
                        ]);

                        $newLead->updateQuietly([
                            'company_name' => $companyDetail->id ?? null,
                            'linkedin_url' => $lead['Linkedin_Profile_URL'] ?? null,
                        ]);
                    }
                } else {
                    // ✅ Create a new lead (no updates for existing ones)
                    $newLead = Lead::create([
                        'zoho_id'      => $lead['id'] ?? null,
                        'name'         => $lead['Full_Name'] ?? null,
                        'email'        => $lead['Email'] ?? null,
                        'country'      => $lead['Country'] ?? null,
                        'company_size' => $this->normalizeCompanySize($lead['Company_Size'] ?? '1-24'),
                        'phone'        => $phoneNumber,
                        'lead_code'    => $leadSource,
                        'products'     => isset($lead['TimeTec_Products']) ? json_encode($lead['TimeTec_Products']) : null,
                        'created_at'   => $leadCreatedTime,
                    ]);

                    if (!empty($lead['Which_Module_That_You_Are_Looking_For'])) {
                        SystemQuestion::create([
                            'lead_id' => $newLead->id,
                            'modules' => is_array($lead['Which_Module_That_You_Are_Looking_For'])
                                    ? implode(', ', $lead['Which_Module_That_You_Are_Looking_For'])
                                    : $lead['Which_Module_That_You_Are_Looking_For'],
                            'causer_name' => 'Get from EXPO / 2025 / HRDF NHCCE / 6-8 OCTOBER 2025',
                        ]);
                    }

                    $latestActivityLog = ActivityLog::where('subject_id', $newLead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($latestActivityLog) {
                        $latestActivityLog->update([
                            'description' => 'New lead created',
                        ]);
                    }

                    if (!empty($lead['Company'])) {
                        $companyDetail = CompanyDetail::create([
                            'company_name' => $lead['Company'],
                            'linkedin_url' => $lead['Linkedin_Profile_URL'] ?? null,
                            'lead_id'      => $newLead->id,
                        ]);

                        $newLead->updateQuietly([
                            'company_name' => $companyDetail->id ?? null,
                            'linkedin_url' => $lead['Linkedin_Profile_URL'] ?? null,
                        ]);
                    }
                }

                if (isset($lead['Lead_Source']) && $lead['Lead_Source'] === 'Refer & Earn') {
                    ReferralDetail::create([
                        'lead_id'     => $newLead->id,
                        'company'     => $lead['Referee_Company_Name'] ?? null,
                        'name'        => $lead['Referee_Name'] ?? null,
                        'email'       => $lead['Referee_Email'] ?? null,
                        'contact_no'  => $lead['Referee_Phone'] ?? null,
                        'created_at'  => $leadCreatedTime ?? now(),
                        'updated_at'  => now(),
                    ]);
                }else{
                    // ✅ Only create UTM details if a new lead was inserted
                    UtmDetail::create([
                        'lead_id'       => $newLead->id,
                        'utm_campaign'  => $lead['utm_campaign'] ?? null,
                        'utm_adgroup'   => $lead['utm_adgroup'] ?? null,
                        'utm_creative'  => $lead['utm_creative'] ?? null,
                        'utm_term'      => $lead['utm_term'] ?? null,
                        'utm_matchtype' => $lead['utm_matchtype'] ?? null,
                        'device'        => $lead['device'] ?? null,
                        'social_lead_id'=> $lead['leadchain0__Social_Lead_ID'] ?? null,
                        'gclid'         => $lead['GCLID'] ?? null,
                        'referrername'  => $lead['referrername2'] ?? null,
                    ]);
                }
            }

            if (isset($leadsData['info']['next_page_token'])) {
                $pageToken = $leadsData['info']['next_page_token'];
            } else {
                break;
            }

            $page++;
        }
        $this->info('Zoho Leads Fetched Successfully');
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

            case '20-24':
            case '20- 24':
            case '20 -24':
            case '20 - 24':
                return '20-24'; // ✅ Normalized as Small

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
