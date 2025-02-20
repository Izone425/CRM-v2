<?php
namespace App\Livewire;

use App\Classes\Encryptor;
use App\Mail\NewLeadNotification;
use App\Models\ActivityLog;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class DemoRequest extends Component
{
    public $name;
    public $email;
    public $phoneNumber;
    public $company_name;
    public $company_size = '';
    public $products = [];
    public $state;
    public $country;
    public $countries = [];
    public $leadSource;
    public $lead_code;
    public $country_code;

    protected $listeners = ['updatePhone'];

    public function updatePhone($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber; // Store the phone number
    }

    public function mount($lead_code)
    {
        $site = LeadSource::where('lead_code', $lead_code)->first();
        if (!$site) {
            abort(404);
        }

        $this->lead_code = $lead_code;

        if ($lead_code) {
            // $site = Encryptor::decrypt($lead_code);
            $site = LeadSource::where('lead_code', $lead_code)->first();

            if ($site) {
                $this->leadSource = $site->lead_code;
            }
        }

        $this->countries = [];
        if (file_exists(storage_path('app/public/json/CountryCodes.json'))) {
            $countriesContent = file_get_contents(storage_path('app/public/json/CountryCodes.json'));
            $this->countries = json_decode(str_replace(PHP_EOL, '', $countriesContent), true);
        }
        $this->country = 'MYS';
    }

    public function submit()
    {
        $phoneNumber = str_replace(['-', ' ', '+'], '', $this->phoneNumber);
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns',
            // 'phone' => 'required',
            'company_name' => 'required|string|max:255',
            'company_size' => 'required|string',
            'country' => 'required|string',
            'products' => 'required',
        ]);

        $countryName = 'Unknown Country';
        if (file_exists(storage_path('app/public/json/CountryCodes.json'))) {
            $countriesContent = file_get_contents(storage_path('app/public/json/CountryCodes.json'));
            $countries = json_decode($countriesContent, true);

            // Find the country by code
            foreach ($countries as $country) {
                if ($country['Code'] === $this->country) {
                    $countryName = ucfirst(strtolower($country['Country']));
                    break;
                }
            }
        }

        $lead = Lead::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $phoneNumber,
            'company_name' => null, // Temporary value
            'company_size' => $this->company_size,
            'country' => $countryName,
            'products' => json_encode($this->products),
            'lead_code' => $this->lead_code,
        ]);

        // Step 2: Create the CompanyDetail with the lead_id
        $company = CompanyDetail::create([
            'lead_id' => $lead->id,
            'company_name' => $this->company_name,
            'name' => $this->name,
            'email' => $this->email,
            'contact_no' => $phoneNumber,
            'company_address' => null, // Temporary value
            'state' => null, // Temporary value
            'position' => null, // Temporary value
            'industry' => null, // Temporary value
        ]);

        // Step 3: Update the Lead with the company_id
        $lead->withoutEvents(function () use ($lead, $company) {
            $lead->update([
                'company_name' => $company->id,
            ]);
        });

        $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();

        if ($latestActivityLog) {
            $latestActivityLog->update([
                'description' => 'New lead created',
                'causer_id' => 0
            ]);
        }

        try {
            $viewName = 'emails.new_lead'; // Replace with a valid default view
            $recipients = User::whereIn('id', [9, 10, 11, 12])->get(['email', 'name']);
            foreach ($recipients as $recipient) {
                $emailContent = [
                    'leadOwnerName' => $recipient->name ?? 'Unknown Person', // Lead Owner/Manager Name
                    'lead' => [
                        'lead_code' => isset($lead->lead_code) ? 'https://crm.timeteccloud.com:8082/demo-request/' . $lead->lead_code : 'N/A',
                        'lastName' => $lead->name ?? 'N/A', // Lead's Last Name
                        'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                        'companySize' => $lead->company_size ?? 'N/A', // Company Size
                        'phone' => $lead->phone ?? 'N/A', // Lead's Phone
                        'email' => $lead->email ?? 'N/A', // Lead's Email
                        'country' => $lead->country ?? 'N/A', // Lead's Country
                        'products' => $lead->products ?? 'N/A', // Products
                        // 'solutions' => $lead->solutions ?? 'N/A', // Solutions
                    ],
                    'remark' => $data['remark'] ?? 'No remarks provided', // Custom Remark
                    'formatted_products' => $lead->formatted_products, // Add formatted products
                ];
                if (!empty($recipients)) {
                    Mail::mailer('smtp')
                        ->to($recipient->email)
                        ->send(new NewLeadNotification($emailContent, $viewName));
                } else {
                    Log::info('No recipients with role_id = 2 found.');
                }
            }
        } catch (\Exception $e) {
            // Handle email sending failure
            Log::error("Error: {$e->getMessage()}");
        }

        $this->reset();  // Clear fields after submission
        return redirect()->to('https://www.timetecmaintenance.com/request_portal_thank');
    }

    public function show($lead_code)
    {
        // Check if the lead_code exists in the database
        $site = LeadSource::where('lead_code', $lead_code)->first();

        if (!$site) {
            // Return a 404 response if the lead_code is not found
            abort(404);
        }

        return view('livewire.demo-request', ['lead_code' => $lead_code]);
    }

    protected function getRecipientsBasedOnProducts($products, $country)
    {
        $recipients = ['cheechan@timeteccloud.com'];

        if ($country !== 'MYS') {
            $recipients[] = 'bizdev@timeteccloud.com';
        }else{
            // Add recipients if HR is in products
            if (in_array('hr', $products)) {
                $recipients[] = 'faiz@timeteccloud.com';
            }

            // Add recipients if Property Management is in products, but HR is not
            if (in_array('property_management', $products) && !in_array('hr', $products)) {
                $recipients[] = 'info@i-neighbour.com';
            }

            // Add recipients for other products with conditions
            if (in_array('smart_parking', $products) && !in_array('hr', $products) && !in_array('property_management', $products)) {
                $recipients[] = 'parking@timeteccloud.com';
            }

            if (in_array('security_people_flow', $products) && !in_array('hr', $products) && !in_array('property_management', $products)) {
                $recipients[] = 'info@i-neighbour.com';
            }

            if (in_array('smart_city', $products) && !in_array('hr', $products)) {
                $recipients[] = 'info@i-neighbour.com';
            }
        }
        return array_unique($recipients); // Avoid duplicate recipients
    }

}
