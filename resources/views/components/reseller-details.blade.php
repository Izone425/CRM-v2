@php
    $lead = $this->record; // Accessing Filament's record
    $reseller = $lead && $lead->reseller_id ? \App\Models\Reseller::find($lead->reseller_id) : null;

    // Get the reseller assignment activity from activity logs
    $resellerAssignmentLog = \Spatie\Activitylog\Models\Activity::where('subject_type', 'App\Models\Lead')
        ->where('subject_id', $lead->id ?? null)
        ->where('description', 'like', 'Assigned to reseller%')
        ->orderByDesc('created_at')
        ->first();

    // Get the causer/user who assigned the reseller
    $assignedBy = $resellerAssignmentLog ? \App\Models\User::find($resellerAssignmentLog->causer_id) : null;

    $resellerDetails = [
        ['label' => 'Reseller Company', 'value' => $reseller->company_name ?? 'Not Assigned'],
        ['label' => 'Assigned On', 'value' => $resellerAssignmentLog ? \Carbon\Carbon::parse($resellerAssignmentLog->created_at)->format('d M Y, H:i') : '-'],
        ['label' => 'Assigned By', 'value' => $assignedBy ? $assignedBy->name : '-'],
    ];

    // Split into rows with a max of 3 items per row
    $rows = array_chunk($resellerDetails, 3);
@endphp

<div class="grid gap-6">
    @if($reseller)
        <div>
            <div class="text-sm leading-6 text-gray-900 dark:text-white">
                <span class="font-medium">Reseller:</span> {{ $item['value'] }}
            </div>
        </div>
    @else
        <div>
            <div class="text-sm leading-6 text-gray-900 dark:text-white">
                <span class="font-medium">Reseller: Not Available</span>
            </div>
        </div>
    @endif
</div>
