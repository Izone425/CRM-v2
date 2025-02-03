<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class FetchFacebookLeads extends Command
{
    protected $signature = 'facebook:fetch-leads';
    protected $description = 'Fetch leads from Facebook and store them in the CRM database';

    public function handle()
    {
        $accessToken = env('FB_PAGE_ACCESS_TOKEN'); // Store token in .env file
        $pageId = env('FB_PAGE_ID'); // Store page ID in .env file

        $this->info('Fetching leads from Facebook...');
        $client = new Client();

        try {
            $formsEndpoint = "https://graph.facebook.com/v21.0/{$pageId}/leadgen_forms";
            $response = $client->get($formsEndpoint, [
                'query' => ['access_token' => $accessToken],
            ]);
            $forms = json_decode($response->getBody(), true);
            foreach ($forms['data'] as $form) {
                $formId = $form['id'];
                $leadsEndpoint = "https://graph.facebook.com/v21.0/{$formId}/leads";

                $response = $client->get($leadsEndpoint, [
                    'query' => ['access_token' => $accessToken],
                ]);
                $leads = json_decode($response->getBody(), true);

                foreach ($leads['data'] as $lead) {
                    $this->saveLeadToDatabase($lead, $formId);
                }
            }

            $this->info('Leads fetched and stored successfully!');
        } catch (\Exception $e) {
            $this->error('Error fetching leads: ' . $e->getMessage());
        }
    }

    private function saveLeadToDatabase($lead, $formId)
    {
        $fieldData = $lead['field_data'];

        $data = [
            'name' => $this->getFieldValue($fieldData, 'full_name'),
            'email' => $this->getFieldValue($fieldData, 'email'),
            'phone' => $this->getFieldValue($fieldData, 'phone_number'),
            'company_name' => $this->getFieldValue($fieldData, 'company_name'),
            'company_size' => $this->getFieldValue($fieldData, 'company_size'),
            'country' => $this->getFieldValue($fieldData, 'country'),
            'products' => $this->getFieldValue($fieldData, 'products_interested'),
            'lead_code' => 'FB-' . $lead['id'],
            'categories' => 'New',
            'stage' => 'New',
            'lead_status' => 'None',
            'remark' => 'Imported from Facebook',
            'salesperson' => null,
            'lead_owner' => null,
            'demo_appointment' => 0,
            'follow_up_needed' => 0,
            'follow_up_counter' => 0,
            'demo_follow_up_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('leads')->insert($data);
    }

    private function getFieldValue($fieldData, $fieldName)
    {
        foreach ($fieldData as $field) {
            if ($field['name'] === $fieldName) {
                return $field['values'][0] ?? null;
            }
        }
        return null;
    }
}
