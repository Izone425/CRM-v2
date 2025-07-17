<!-- filepath: /var/www/html/timeteccrm/resources/views/components/service-forms-list.blade.php -->
<div>
    @php
        $lead = $this->record;
        $implementerForms = $lead->implementerForms ?? collect();
    @endphp

    @if($implementerForms->count())
        <div class="space-y-4">
            @foreach($implementerForms as $form)
                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-medium">Service Form</h4>
                                @php
                                    $fileName = basename($form->filepath);
                                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                                @endphp
                                <div class="text-sm text-gray-500">
                                    {{ strtoupper($extension) }} · {{ $form->created_at->format('M d, Y') }}
                                </div>
                            </div>
                        </div>

                        <div class="flex space-x-2">
                            <a href="{{ Storage::url($form->filepath) }}"
                               target="_blank"
                               class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View
                            </a>
                            <a href="{{ Storage::url($form->filepath) }}"
                               download
                               class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>

                    @if($form->notes)
                        <div class="p-3 mt-3 rounded-md bg-gray-50">
                            <div class="mb-1 text-sm font-medium text-gray-700">Notes:</div>
                            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $form->notes }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="py-4 text-center text-gray-500">
            No service forms have been uploaded yet.
        </div>
    @endif
</div>
