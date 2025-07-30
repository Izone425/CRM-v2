<!-- filepath: /var/www/html/timeteccrm/resources/views/verification-notification.blade.php -->
@php
    // Check if we're in a demo action or RFQ action context
    $lead = null;

    // Try to access the lead from the Livewire component if available
    if (isset($this) && method_exists($this, 'getRecord')) {
        $lead = $this->getRecord();
    }

    // Check if lead is directly available as $record
    if (!$lead && isset($record) && $record instanceof \App\Models\Lead) {
        $lead = $record;
    }

    // Try to access through parent component if it's in a nested view
    if (!$lead && isset($this) && isset($this->record) && $this->record instanceof \App\Models\Lead) {
        $lead = $this->record;
    }

    // Check for "Refer & Earn" lead code
    $isReferAndEarn = $lead && $lead->lead_code === 'Refer & Earn';
@endphp

@if($isReferAndEarn)
<div class="p-4 mb-4 text-red-800 border-red-300 rounded-md" style="color:red;">
    <p class="mb-2 font-bold">⚠️ IMPORTANT REFERRAL WARNING:</p>
    <p class="pl-5 space-y-2 font-bold">Are you certain that you've assigned the leads to the correct salesperson who originally closed it? If not, you may face consequences from the Vice President!</p>
</div>
@endif

<div class="p-4 mb-4 text-blue-800 border-blue-200 rounded-md bg-blue-50">
    <h3 class="mb-2 font-bold">⚠️ Important Verification Steps:</h3>
    <ol class="pl-5 space-y-2 list-decimal">
        <li>Are you sure this lead is not an existing customer?</li>
        <li>Are you sure this lead is not a duplicate with another salesperson?</li>
        <li>If you have verified both scenarios, you may click the Submit button to proceed to the next step.</li>
    </ol>
</div>
