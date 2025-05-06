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

<div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px;"
     class="grid grid-cols-3 gap-6">

    @if($reseller)
        @foreach ($rows as $row)
            @foreach ($row as $item)
                <div style="--col-span-default: span 1 / span 1;" class="col-[--col-span-default]">
                    <div data-field-wrapper="" class="fi-fo-field-wrp">
                        <div class="grid gap-y-2">
                            <div class="flex items-center justify-between gap-x-3">
                                <div class="inline-flex items-center fi-fo-field-wrp-label gap-x-3">
                                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                        {{ $item['label'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="grid auto-cols-fr gap-y-2">
                                <div class="text-sm leading-6 text-gray-900 fi-fo-placeholder dark:text-white">
                                    {{ $item['value'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    @else
        <div style="--col-span-default: span 3 / span 3;" class="col-[--col-span-default]">
            <div class="flex items-center justify-center p-4 rounded-lg border border-dashed border-gray-300">
                <p class="text-sm text-gray-500">No reseller assigned to this lead</p>
            </div>
        </div>
    @endif
</div>