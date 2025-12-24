<div class="space-y-4">
    @if ($activities->count() > 0)
        <div class="w-full overflow-hidden bg-white border border-gray-200 rounded-lg shadow">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Salesperson Activity History</h3>
                <p class="mt-1 text-sm text-gray-500">Total activities: {{ $totalActivities }}</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full border border-collapse border-gray-200 table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-r border-gray-200">
                                No
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-r border-gray-200">
                                Date & Time
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-r border-gray-200">
                                Salesperson
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-r border-gray-200">
                                Activity
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-r border-gray-200">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-gray-200">
                                Remarks
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach ($activities as $index => $activity)
                            @php
                                // Decode properties to get additional information
                                $properties = is_array($activity->properties) 
                                    ? $activity->properties 
                                    : json_decode($activity->properties, true);
                                
                                $leadStatus = $properties['attributes']['lead_status'] ?? '-';
                                $stage = $properties['attributes']['stage'] ?? '-';
                                $remark = $properties['attributes']['remark'] ?? '-';
                                
                                // Clean up remark
                                if (trim($remark) !== '' && $remark !== '-') {
                                    $remark = str_replace('&nbsp;', ' ', $remark);
                                    $remark = str_replace('&amp;nbsp;', ' ', $remark);
                                    $remark = str_replace('&amp;', '&', $remark);
                                    $remark = html_entity_decode($remark, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                } else {
                                    $remark = 'No remarks';
                                }
                            @endphp
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <!-- Activity Number Column -->
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-blue-600">{{ $totalActivities - $index }}</span>
                                    </div>
                                </td>

                                <!-- Date & Time Column -->
                                <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $activity->created_at->format('d M Y') }}</span>
                                        <span class="text-xs text-gray-500">{{ $activity->created_at->format('h:i A') }}</span>
                                    </div>
                                </td>

                                <!-- Salesperson Column -->
                                <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $activity->causer ? $activity->causer->name : 'System' }}</span>
                                        @if ($activity->causer && $activity->causer->role_id == 2)
                                            <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                Salesperson
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Activity Column -->
                                <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $activity->description }}</span>
                                    </div>
                                </td>

                                <!-- Status Column -->
                                <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        @if ($stage !== '-' && $leadStatus !== '-')
                                            <span class="font-medium text-green-600">{{ $stage }}</span>
                                            <span class="text-xs text-gray-500">{{ $leadStatus }}</span>
                                        @elseif ($leadStatus !== '-')
                                            <span class="font-medium">{{ $leadStatus }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Remarks Column -->
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs">
                                        @if ($remark !== 'No remarks' && strlen($remark) > 100)
                                            <div class="prose-sm prose max-w-none">
                                                <div class="truncate" title="{{ strip_tags($remark) }}">
                                                    {!! Str::limit(strip_tags($remark), 100) !!}
                                                </div>
                                            </div>
                                        @elseif ($remark !== 'No remarks')
                                            <div class="prose-sm prose max-w-none">
                                                {!! $remark !!}
                                            </div>
                                        @else
                                            <span class="italic text-gray-400">No remarks</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="flex items-center justify-center p-8 text-gray-500 rounded-lg bg-gray-50">
            <div class="text-center">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No salesperson activities found</h3>
                <p class="mt-1 text-sm text-gray-500">No activities from salespersons have been recorded for this lead yet.</p>
            </div>
        </div>
    @endif
</div>