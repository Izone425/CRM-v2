<div class="space-y-6">
    <div class="p-4 rounded-lg bg-gray-50">
        <h3 class="mb-4 text-lg font-medium text-gray-900">Device Installation Overview</h3>

        <div class="grid grid-cols-4 gap-4 mb-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $record->tc10_quantity ?? 0 }}</div>
                <div class="text-sm text-gray-600">TC10 Units</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ $record->face_id5_quantity ?? 0 }}</div>
                <div class="text-sm text-gray-600">Face ID 5 Units</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ $record->tc20_quantity ?? 'N/A' }}</div>
                <div class="text-sm text-gray-600">TC20 Units</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ $record->face_id6_quantity ?? 'N/A' }}</div>
                <div class="text-sm text-gray-600">Face ID 6 Units</div>
            </div>
        </div>

        @php
            $existingCategory2 = $record->category2 ? json_decode($record->category2, true) : [];
            $existingAppointments = $existingCategory2['installation_appointments'] ?? [];

            $totalAllocated = [
                'tc10' => 0,
                'face_id5' => 0,
                'tc20' => 0,
                'face_id6' => 0,
            ];

            foreach ($existingAppointments as $appointment) {
                $totalAllocated['tc10'] += (int)($appointment['device_allocation']['tc10_units'] ?? 0);
                $totalAllocated['face_id5'] += (int)($appointment['device_allocation']['face_id5_units'] ?? 0);
                $totalAllocated['tc20'] += (int)($appointment['device_allocation']['tc20_units'] ?? 0);
                $totalAllocated['face_id6'] += (int)($appointment['device_allocation']['face_id6_units'] ?? 0);
            }

            $remaining = [
                'tc10' => ($record->tc10_quantity ?? 0) - $totalAllocated['tc10'],
                'face_id5' => ($record->face_id5_quantity ?? 0) - $totalAllocated['face_id5'],
                'tc20' => ($record->tc20_quantity ?? 0) - $totalAllocated['tc20'],
                'face_id6' => ($record->face_id6_quantity ?? 0) - $totalAllocated['face_id6'],
            ];
        @endphp

        <div class="pt-4 border-t">
            <h4 class="mb-2 font-medium text-gray-900">Remaining to Allocate:</h4>
            <div class="grid grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-xl font-bold {{ $remaining['tc10'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $remaining['tc10'] }}
                    </div>
                    <div class="text-sm text-gray-600">TC10 Remaining</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold {{ $remaining['face_id5'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $remaining['face_id5'] }}
                    </div>
                    <div class="text-sm text-gray-600">Face ID 5 Remaining</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold {{ $remaining['tc20'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $record->tc20_quantity ? $remaining['tc20'] : 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-600">TC20 Remaining</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold {{ $remaining['face_id6'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $record->face_id6_quantity ? $remaining['face_id6'] : 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-600">Face ID 6 Remaining</div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($existingAppointments))
        <div class="p-4 rounded-lg bg-blue-50">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Scheduled Appointments</h3>
            <div class="space-y-3">
                @foreach($existingAppointments as $appointment)
                    <div class="p-3 bg-white border rounded">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium">{{ $appointment['appointment_name'] ?? 'Unknown' }}</h4>
                                <p class="text-sm text-gray-600">
                                    TC10: {{ $appointment['device_allocation']['tc10_units'] ?? 0 }},
                                    Face ID 5: {{ $appointment['device_allocation']['face_id5_units'] ?? 0 }},
                                    TC20: {{ $appointment['device_allocation']['tc20_units'] ?? 0 }},
                                    Face ID 6: {{ $appointment['device_allocation']['face_id6_units'] ?? 0 }}
                                </p>
                            </div>
                            <span class="px-2 py-1 text-sm text-green-800 bg-green-100 rounded">
                                {{ $appointment['appointment_status'] ?? 'Scheduled' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="p-4 rounded-lg bg-yellow-50">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Next Steps</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>Use the "Allocate Devices & Book Appointment" button to:</p>
                    <ul class="mt-1 space-y-1 list-disc list-inside">
                        <li>Allocate devices to a new installation appointment</li>
                        <li>Fill in appointment details (date, time, PIC information)</li>
                        <li>Create the repair appointment automatically</li>
                        <li>Repeat until all devices are allocated</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
