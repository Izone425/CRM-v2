<div class="space-y-4">
    @php
        $lead = $this->getRecord();
        $hardwareHandovers = $lead->hardwareHandover()
            ->orderBy('created_at', 'desc')
            ->get();
    @endphp

    @if($hardwareHandovers->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm border border-gray-200 rounded-lg shadow-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">SalesPerson</th>
                        <th class="p-3 text-left">Implementer</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">TC10</th>
                        <th class="p-3 text-left">TC20</th>
                        <th class="p-3 text-left">FaceID 5</th>
                        <th class="p-3 text-left">FaceID 6</th>
                        <th class="p-3 text-left">TA100C / R</th>
                        <th class="p-3 text-left">TA100C / MF</th>
                        <th class="p-3 text-left">TA100C / HID</th>
                        <th class="p-3 text-left">TA100C / R / W</th>
                        <th class="p-3 text-left">TA100C / MF / W</th>
                        <th class="p-3 text-left">TA100C / HID / R</th>
                        <th class="p-3 text-left">Time Beacon</th>
                        <th class="p-3 text-left">NFC Tag</th>
                        <th class="p-3 text-left">Date Submit</th>
                        <th class="p-3 text-left">Date Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hardwareHandovers as $handover)
                        <tr class="border-t border-gray-200 hover:bg-gray-50">
                            {{-- <td class="p-3">
                                <a href="{{ route('filament.admin.resources.hardware-handovers.edit', $handover->id) }}"
                                   class="font-semibold text-primary-600 hover:underline"
                                   target="_blank">
                                    HW_250{{ str_pad($handover->id, 3, '0', STR_PAD_LEFT) }}
                                </a>
                            </td> --}}
                            <td class="p-3">
                                @php
                                    $salesperson = \App\Models\User::find($lead->salesperson);
                                @endphp
                                {{ $salesperson ? $salesperson->name : '-' }}
                            </td>
                            <td class="p-3">{{ $handover->implementer ?? '-' }}</td>
                            <td class="p-3">{{ strtoupper($handover->status ?? '-') }}</td>
                            <td class="p-3 text-center">{{ $handover->tc10_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->tc20_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->face_id5_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->face_id6_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->ta100cr_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->ta100cmf_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->ta100chid_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->ta100crw_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->ta100cmfw_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->ta100chidw_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->time_beacon_quantity ?? '0' }}</td>
                            <td class="p-3 text-center">{{ $handover->nfc_tag_quantity ?? '0' }}</td>
                            <td class="p-3">
                                {{ $handover->created_at ? $handover->created_at->format('d M Y') : '-' }}
                            </td>
                            <td class="p-3">
                                {{ $handover->completed_at ? \Carbon\Carbon::parse($handover->completed_at)->format('d M Y') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-4 text-center text-gray-500 border border-gray-200 rounded-md bg-gray-50">
            No hardware handover records found for this lead.
        </div>
    @endif
</div>
