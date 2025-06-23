<div class="p-6 bg-white rounded-lg">
    <!-- Title -->
    <div class="mb-4 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Repair Ticket Details</h2>
        <p class="text-blue-600">{{ $record->companyDetail->company_name ?? 'Repair Ticket' }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2">
        <div>
            <!-- Company Information -->
            <div class="mb-6">
                <p class="mb-2">
                    <span class="font-semibold">Company Name:</span>
                    {{ $record->companyDetail->company_name ?? 'N/A' }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Contact Person:</span>
                    {{ $record->pic_name }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Phone:</span>
                    {{ $record->pic_phone }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Email:</span>
                    {{ $record->pic_email }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Address:</span>
                    {{ $record->address }}
                </p>
            </div>

            <!-- Separator Line -->
            <hr class="my-4 border-gray-300">

            <!-- Ticket Information -->
            <div class="mb-6">
                <p class="flex mb-2">
                    <span class="mr-2 font-semibold">Status:</span>&nbsp;
                    <span class="
                        @if($record->status == 'Draft') bg-gray-200 text-gray-800
                        @elseif($record->status == 'New') bg-red-100 text-red-800
                        @elseif($record->status == 'In Progress') bg-yellow-100 text-yellow-800
                        @elseif($record->status == 'Awaiting Parts') bg-blue-100 text-blue-800
                        @elseif($record->status == 'Resolved') bg-green-100 text-green-800
                        @elseif($record->status == 'Closed') bg-gray-100 text-gray-800
                        @else bg-gray-100 text-gray-800 @endif
                    ">
                        {{ $record->status }}
                    </span>
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Ticket ID:</span>
                    RP_250{{ str_pad($record->id, 3, '0', STR_PAD_LEFT) }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Submitted Date:</span>
                    {{ $record->created_at->format('d M Y, h:i A') }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Zoho Ticket:</span>
                    {{ $record->zoho_ticket ?? 'N/A' }}
                </p>
            </div>
        </div>

        <div>
            <!-- Devices Section -->
            <div x-data="{ deviceModalOpen: false }">
                <p class="mb-2">
                    <span class="font-semibold">Devices:</span>
                    <a href="#"
                       @click.prevent="deviceModalOpen = true"
                       style="color: #2563EB; text-decoration: none; font-weight: 500;"
                       onmouseover="this.style.textDecoration='underline'"
                       onmouseout="this.style.textDecoration='none'">
                        View Devices
                    </a>
                </p>

                <!-- Devices Modal -->
                <div x-show="deviceModalOpen"
                     x-transition
                     @click.outside="deviceModalOpen = false"
                     class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                    <div class="relative w-full max-w-lg p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="deviceModalOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Device Details</h3>
                            <button type="button" @click="deviceModalOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                        <div>
                            <table class="min-w-full border border-collapse border-gray-300">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-2 text-left border border-gray-300">Device Model</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">Serial Number</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($record->devices)
                                        @php
                                            $devices = is_string($record->devices)
                                                ? json_decode($record->devices, true)
                                                : $record->devices;
                                        @endphp

                                        @if(is_array($devices) && count($devices) > 0)
                                            @foreach($devices as $index => $device)
                                                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                                                    <td class="px-4 py-2 border border-gray-300">{{ $device['device_model'] }}</td>
                                                    <td class="px-4 py-2 border border-gray-300">{{ $device['device_serial'] }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="2" class="px-4 py-2 text-center border border-gray-300">No device information available</td>
                                            </tr>
                                        @endif
                                    @elseif($record->device_model)
                                        <tr>
                                            <td class="px-4 py-2 border border-gray-300">{{ $record->device_model }}</td>
                                            <td class="px-4 py-2 border border-gray-300">{{ $record->device_serial }}</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="2" class="px-4 py-2 text-center border border-gray-300">No device information available</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <div class="mt-4 text-center">
                                <button @click="deviceModalOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks Section -->
            <div x-data="{ remarkOpen: false }">
                <p class="mb-2">
                    <span class="font-semibold">Repair Remarks:</span>
                    <a href="#"
                       @click.prevent="remarkOpen = true"
                       style="color: #2563EB; text-decoration: none; font-weight: 500;"
                       onmouseover="this.style.textDecoration='underline'"
                       onmouseout="this.style.textDecoration='none'">
                        View Remarks
                    </a>
                </p>

                <!-- Remarks Modal -->
                <div x-show="remarkOpen"
                     x-transition
                     @click.outside="remarkOpen = false"
                     class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                    <div class="relative w-full max-w-2xl p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="remarkOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Repair Remarks</h3>
                            <button type="button" @click="remarkOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="max-h-[60vh] overflow-y-auto">
                            @if($record->remarks)
                                @php
                                    $remarks = is_string($record->remarks) ? json_decode($record->remarks, true) : $record->remarks;
                                @endphp

                                @if(is_array($remarks) && count($remarks) > 0)
                                    <div class="space-y-4">
                                        @foreach($remarks as $index => $remark)
                                            <div class="p-4 border border-gray-200 rounded-lg {{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                                                <h4 class="mb-2 font-semibold text-gray-700 text-md">Remark {{ $index + 1 }}</h4>

                                                <div class="p-3 mb-3 text-gray-800 bg-gray-100 rounded">
                                                    <p class="whitespace-pre-line">{{ $remark['remark'] }}</p>
                                                </div>

                                                @if(!empty($remark['attachments']))
                                                    @php
                                                        $attachments = is_string($remark['attachments'])
                                                            ? json_decode($remark['attachments'], true)
                                                            : $remark['attachments'];
                                                    @endphp

                                                    @if(is_array($attachments) && count($attachments) > 0)
                                                        <div class="mt-3">
                                                            <h5 class="mb-2 font-medium">Attachments:</h5>
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach($attachments as $attIndex => $attachment)
                                                                    <a
                                                                        href="{{ asset('storage/' . $attachment) }}"
                                                                        target="_blank"
                                                                        class="inline-flex items-center px-3 py-2 text-sm text-blue-600 border border-blue-200 rounded-md bg-blue-50 hover:bg-blue-100"
                                                                    >
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                                                        </svg>
                                                                        Attachment {{ $attIndex + 1 }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-center text-gray-500">No remarks available</p>
                                @endif
                            @else
                                <p class="text-center text-gray-500">No remarks available</p>
                            @endif
                        </div>

                        <div class="mt-4 text-center">
                            <button @click="remarkOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-data="{ remarkOpen: false }">
                <p class="mb-2">
                    <span class="font-semibold">Technician Remarks:</span>
                    <a href="#"
                       @click.prevent="remarkOpen = true"
                       style="color: #2563EB; text-decoration: none; font-weight: 500;"
                       onmouseover="this.style.textDecoration='underline'"
                       onmouseout="this.style.textDecoration='none'">
                        View Remarks
                    </a>
                </p>

                <!-- Technician Remarks Modal -->
                <div x-show="remarkOpen"
                     x-transition
                     @click.outside="remarkOpen = false"
                     class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                    <div class="relative w-full max-w-2xl p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="remarkOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Technician Remarks</h3>
                            <button type="button" @click="remarkOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="max-h-[60vh] overflow-y-auto">
                            @if($record->repair_remark)
                                @php
                                    $remarks = is_string($record->repair_remark) ? json_decode($record->repair_remark, true) : $record->repair_remark;
                                @endphp

                                @if(is_array($remarks) && count($remarks) > 0)
                                    <div class="space-y-4">
                                        @foreach($remarks as $index => $remark)
                                            <div class="p-4 border border-gray-200 rounded-lg {{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                                                <h4 class="mb-2 font-semibold text-gray-700 text-md">Remark {{ $index + 1 }}</h4>

                                                <div class="p-3 mb-3 text-gray-800 bg-gray-100 rounded">
                                                    <p class="whitespace-pre-line">{{ $remark['remark'] }}</p>
                                                </div>

                                                @if(!empty($remark['attachments']))
                                                    @php
                                                        $attachments = is_string($remark['attachments'])
                                                            ? json_decode($remark['attachments'], true)
                                                            : $remark['attachments'];
                                                    @endphp

                                                    @if(is_array($attachments) && count($attachments) > 0)
                                                        <div class="mt-3">
                                                            <h5 class="mb-2 font-medium">Attachments:</h5>
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach($attachments as $attIndex => $attachment)
                                                                    <a
                                                                        href="{{ asset('storage/' . $attachment) }}"
                                                                        target="_blank"
                                                                        class="inline-flex items-center px-3 py-2 text-sm text-blue-600 border border-blue-200 rounded-md bg-blue-50 hover:bg-blue-100"
                                                                    >
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                                                        </svg>
                                                                        Attachment {{ $attIndex + 1 }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-center text-gray-500">No repair remarks available</p>
                                @endif
                            @else
                                <p class="text-center text-gray-500">No repair remarks available</p>
                            @endif
                        </div>

                        <div class="mt-4 text-center">
                            <button @click="remarkOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Separator Line -->
            <hr class="my-4 border-gray-300">

            <!-- Video Files Section -->
            @if($record->video_files)
                @php
                    $videos = is_string($record->video_files)
                        ? json_decode($record->video_files, true)
                        : $record->video_files;
                @endphp

                @if(is_array($videos) && count($videos) > 0)
                    <div class="mb-6">
                        <p class="mb-2">
                            <span class="font-semibold">Video Files:</span>
                        </p>
                        <ul class="pl-6 list-none">
                            @foreach($videos as $index => $video)
                                <li class="mb-1">
                                    <span class="mr-2">➤</span>
                                    <a
                                        href="{{ asset('storage/' . $video) }}"
                                        target="_blank"
                                        style="color: #2563EB; text-decoration: none; font-weight: 500;"
                                        onmouseover="this.style.textDecoration='underline'"
                                        onmouseout="this.style.textDecoration='none'"
                                    >
                                        Video {{ $index + 1 }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif
            <br>
            <div class="mb-6">
                <p class="mb-2">
                    <span class="font-semibold">Additional Attachments:</span>
                </p>

                @php
                    $newAttachmentFiles = $record->new_attachment_file ? (is_string($record->new_attachment_file) ? json_decode($record->new_attachment_file, true) : $record->new_attachment_file) : [];
                @endphp

                @if(is_array($newAttachmentFiles) && count($newAttachmentFiles) > 0)
                    <ul class="pl-6 list-none">
                        @foreach($newAttachmentFiles as $index => $file)
                            <li class="mb-1">
                                <span class="mr-2">➤</span>
                                <a href="{{ url('storage/' . $file) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Attachment {{ $index + 1 }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span>No additional attachments uploaded</span>
                @endif
            </div>
        </div>
    </div>
</div>
