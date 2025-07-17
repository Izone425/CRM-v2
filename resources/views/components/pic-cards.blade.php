<div>
    @if(isset($getRecord()->companyDetail->additional_pic))
        @php
            $additionalPics = json_decode($getRecord()->companyDetail->additional_pic, true);
        @endphp

        @if(is_array($additionalPics) && count($additionalPics) > 0)
            <div class="mt-6">
                <h2 class="text-lg font-medium text-gray-900">Current Additional Contacts</h2>

                <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($additionalPics as $index => $pic)
                        <div class="overflow-hidden transition bg-white border border-gray-200 rounded-lg shadow hover:shadow-md">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
                                <h3 class="font-semibold text-gray-900 text-md">
                                    {{ $pic['name'] ?? 'N/A' }}
                                </h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $pic['position'] ?? 'No Position' }}
                                </span>
                            </div>
                            <div class="px-4 py-3">
                                <div class="flex items-center mb-2 text-sm">
                                    <svg class="flex-shrink-0 w-5 h-5 mr-2 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                    </svg>
                                    <span class="text-gray-700">{{ $pic['hp_number'] ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <svg class="flex-shrink-0 w-5 h-5 mr-2 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    </svg>
                                    <span class="text-gray-700 break-all">{{ $pic['email'] ?? 'N/A' }}</span>
                                </div>
                            </div>
                            {{-- <div class="flex justify-end px-4 py-2 space-x-2 border-t border-gray-200 bg-gray-50">
                                <a href="mailto:{{ $pic['email'] }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    Email
                                </a>
                                <span class="text-gray-300">|</span>
                                <a href="tel:{{ $pic['hp_number'] }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    Call
                                </a>
                            </div> --}}
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-6 italic text-gray-500">No additional contacts have been added yet.</div>
        @endif
    @else
        <div class="mt-6 italic text-gray-500">No additional contacts have been added yet.</div>
    @endif
</div>
