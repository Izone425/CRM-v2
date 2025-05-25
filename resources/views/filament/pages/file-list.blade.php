<div>
    @if(!empty($getState()))
        <div class="space-y-1">
            @foreach((array)$getState() as $file)
                <div class="flex items-center space-x-2">
                    <a
                        href="{{ Storage::disk('public')->url($file) }}"
                        target="_blank"
                        class="text-sm underline text-primary-500 hover:text-primary-700"
                    >
                        {{ basename($file) }}
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <span class="text-gray-400">No files</span>
    @endif
</div>
