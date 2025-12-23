<div class="p-6">
    <div class="mb-6 text-xl font-bold">Site Survey Handover ID: {{ $record->formatted_handover_id ?? 'SS_25' . str_pad($record->id, 4, '0', STR_PAD_LEFT) }}</div>

    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4">
            <div>
                <span class="font-medium text-gray-500">Date Submitted:</span>
                <span>{{ $record->created_at }}</span>
            </div>

            <div>
                <span class="font-medium text-gray-500">Submitted by:</span>
                <span>{{ \App\Models\User::find($record->causer_id)->name ?? 'Unknown' }}</span>
            </div>

            <div>
                <span class="font-medium text-gray-500">Company Name:</span>
                <span>
                    @php
                        $companyName = 'Unknown Company';
                        if ($record->lead_id) {
                            $companyDetail = \App\Models\CompanyDetail::where('lead_id', $record->lead_id)->first();
                            if ($companyDetail) {
                                $companyName = $companyDetail->company_name;
                            }
                        }
                    @endphp
                    {{ $companyName }}
                </span>
            </div>

            <div>
                <span class="font-medium text-gray-500">Device Model:</span>
                <span>
                    @if(is_array($record->device_model))
                        {{ implode(', ', $record->device_model) }}
                    @elseif(is_string($record->device_model) && Str::startsWith($record->device_model, '['))
                        @php
                            // Try to decode JSON string to array
                            try {
                                $decodedModels = json_decode($record->device_model, true);
                                echo is_array($decodedModels) ? implode(', ', $decodedModels) : $record->device_model;
                            } catch (\Exception $e) {
                                echo $record->device_model;
                            }
                        @endphp
                    @else
                        {{ $record->device_model }}
                    @endif
                </span>
            </div>
            <div>
                <span class="font-medium text-gray-500">Date:</span>
                <span>{{ \Carbon\Carbon::parse($record->date)->format('d M Y') }}</span>
            </div>

            <div>
                <span class="font-medium text-gray-500">Start Time - End Time:</span>
                <span>{{ \Carbon\Carbon::parse($record->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($record->end_time)->format('h:i A') }}</span>
            </div>

            <div>
                <span class="font-medium text-gray-500">Technician:</span>
                <span>{{ $record->technician }}</span>
            </div>

            <div>
                <span class="font-medium text-gray-500">SalesPerson Remark:</span>
                <div class="p-2 mt-1 rounded-md bg-gray-50">{!! nl2br(e($record->remarks)) !!}</div>
            </div>
        </div>
    </div>
</div>
