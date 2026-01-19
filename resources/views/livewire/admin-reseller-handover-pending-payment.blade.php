<div>
    @if($showFilesModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-semibold">Files for {{ $selectedHandover->fb_id }}</h3>
                        <button wire:click="closeFilesModal" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        @foreach($handoverFiles as $category => $files)
                            <div>
                                <h4 class="text-md font-semibold mb-3 text-gray-700">{{ $category }}</h4>
                                @if(count($files) > 0)
                                    <div class="grid grid-cols-2 gap-4">
                                        @foreach($files as $file)
                                            <a href="{{ $file['url'] }}" target="_blank"
                                               class="flex items-center p-3 border rounded-lg hover:bg-gray-50 transition">
                                                <svg class="w-8 h-8 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $file['name'] }}</p>
                                                    <p class="text-xs text-gray-500">Click to view</p>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 italic">No files uploaded</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="sticky bottom-0 bg-gray-50 px-6 py-4 border-t">
                        <button wire:click="closeFilesModal"
                                class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{ $this->table }}
</div>
