<div class="space-y-4">
    @php
        $lead = $this->getRecord();
        $softwareHandovers = $lead->softwareHandover()
            ->orderBy('created_at', 'desc')
            ->get();
    @endphp

    @if($softwareHandovers->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm border border-gray-200 rounded-lg shadow-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">SalesPerson</th>
                        <th class="p-3 text-left">Implementer</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">TA</th>
                        <th class="p-3 text-left">TL</th>
                        <th class="p-3 text-left">TC</th>
                        <th class="p-3 text-left">TP</th>
                        <th class="p-3 text-left">Company Size</th>
                        <th class="p-3 text-left">Headcount</th>
                        <th class="p-3 text-left">DB Creation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($softwareHandovers as $handover)
                        <tr class="border-t border-gray-200 hover:bg-gray-50">
                            <td class="p-3">
                                <a href="{{ route('filament.admin.resources.software-handovers.edit', $handover->id) }}"
                                   class="font-semibold text-primary-600 hover:underline"
                                   target="_blank">
                                    {{ $handover->formatted_handover_id }}
                                </a>
                            </td>
                            <td class="p-3">{{ $handover->salesperson ?? '-' }}</td>
                            <td class="p-3">{{ $handover->implementer ?? '-' }}</td>
                            <td class="p-3">{{ strtoupper($handover->status_handover ?? 'OPEN') }}</td>
                            <td class="p-3 text-center">
                                @if($handover->ta)
                                    <span class="text-green-500">✓</span>
                                @else
                                    <span class="text-red-500">✗</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @if($handover->tl)
                                    <span class="text-green-500">✓</span>
                                @else
                                    <span class="text-red-500">✗</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @if($handover->tc)
                                    <span class="text-green-500">✓</span>
                                @else
                                    <span class="text-red-500">✗</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @if($handover->tp)
                                    <span class="text-green-500">✓</span>
                                @else
                                    <span class="text-red-500">✗</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @php
                                    $categoryService = app(\App\Services\CategoryService::class);
                                    $category = $handover->headcount ? $categoryService->retrieve($handover->headcount) : 'N/A';
                                @endphp
                                {{ $category }}
                            </td>
                            <td class="p-3">{{ $handover->headcount ?? '0' }}</td>
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
            No software handover records found for this lead.
        </div>
    @endif
</div>
