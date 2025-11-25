<?php
// filepath: /var/www/html/timeteccrm/app/Console/Commands/TestMetaConversionsApi.php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\MetaConversionsApiService;
use Illuminate\Console\Command;

class TestMetaConversionsApi extends Command
{
    protected $signature = 'meta:test-conversion {lead_id}';
    protected $description = 'Test Meta Conversions API with a specific lead';

    public function handle()
    {
        $leadId = $this->argument('lead_id');
        $lead = Lead::find($leadId);

        if (!$lead) {
            $this->error("Lead #{$leadId} not found");
            return 1;
        }

        $this->info("Testing Meta Conversions API with Lead #{$leadId}");

        $metaService = new MetaConversionsApiService();

        $leadData = [
            'id' => $lead->id,
            'email' => $lead->email,
            'phone_number' => $lead->phone_number,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'city' => $lead->city,
            'state' => $lead->state,
            'zip' => $lead->zip,
            'country' => $lead->country,
            'meta_lead_id' => $lead->meta_lead_id,
            'fbclid' => $lead->fbclid,
        ];

        $result = $metaService->sendTestEvent($leadData);

        if ($result['success']) {
            $this->info('✅ Test event sent successfully!');
            $this->info('Check Events Manager > Test Events tab');
            $this->line(json_encode($result['response'], JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ Failed to send test event');
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        }

        return 0;
    }
}
