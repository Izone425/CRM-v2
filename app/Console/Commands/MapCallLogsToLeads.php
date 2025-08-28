<?php

namespace App\Console\Commands;

use App\Models\CallLog;
use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MapCallLogsToLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:map-to-leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Map call logs to leads based on phone numbers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting call mapping process...');

        // Get pending call logs without linked leads
        $pendingCalls = CallLog::where('task_status', 'Pending')
            ->whereNull('lead_id')
            ->where('call_status', '!=', 'NO ANSWER')
            ->where(function($query) {
                $query->where('call_duration', '>=', 5)
                    ->orWhereNull('call_duration');
            })
            ->get();

        $mappedCount = 0;
        $this->info("Found {$pendingCalls->count()} pending calls to process");

        foreach ($pendingCalls as $call) {
            // Clean the phone numbers to standardize format
            $callerNumber = $this->cleanPhoneNumber($call->caller_number);
            $receiverNumber = $this->cleanPhoneNumber($call->receiver_number);

            // Skip internal extensions (usually short numbers)
            if (strlen($callerNumber) <= 4 && strlen($receiverNumber) <= 4) {
                continue;
            }

            // Try to find a lead with matching phone number
            $lead = null;

            // Check caller number first (usually the customer calling in)
            if (strlen($callerNumber) > 4) {
                $lead = $this->findLeadByPhone($callerNumber);
            }

            // If no match found, check receiver number (for outgoing calls)
            if (!$lead && strlen($receiverNumber) > 4) {
                $lead = $this->findLeadByPhone($receiverNumber);
            }

            // If a matching lead is found, update the call log
            if ($lead) {
                $call->update([
                    'lead_id' => $lead->id,
                    'task_status' => 'Completed',
                ]);

                $mappedCount++;
                $this->info("✅ Mapped call #{$call->id} to lead #{$lead->id} (" . ($lead->companyDetail->company_name ?? 'Unknown') . ")");
            }
        }

        $this->info("Call mapping completed: {$mappedCount}/{$pendingCalls->count()} calls mapped to leads");
        Log::info("Call mapping job completed: {$mappedCount}/{$pendingCalls->count()} calls mapped to leads");

        return Command::SUCCESS;
    }

    /**
     * Clean and standardize phone number format
     */
    private function cleanPhoneNumber($number)
    {
        // Remove non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        // Handle Malaysian numbers - remove leading 60 if present
        if (str_starts_with($cleaned, '60') && strlen($cleaned) >= 10) {
            $cleaned = substr($cleaned, 2);
        }

        // If starts with 0, remove it (standardize format)
        if (str_starts_with($cleaned, '0') && strlen($cleaned) >= 9) {
            $cleaned = substr($cleaned, 1);
        }

        return $cleaned;
    }

    /**
     * Find a lead based on phone number
     */
    private function findLeadByPhone($phoneNumber)
    {
        // First try exact match
        $lead = Lead::where('phone', 'LIKE', "%{$phoneNumber}%")
            ->first();

        if ($lead) {
            return $lead;
        }

        // If the number is long enough, try matching the last 8 digits
        if (strlen($phoneNumber) >= 8) {
            $lastEightDigits = substr($phoneNumber, -8);

            return Lead::where('phone', 'LIKE', "%{$lastEightDigits}")
                ->first();
        }

        return null;
    }
}
