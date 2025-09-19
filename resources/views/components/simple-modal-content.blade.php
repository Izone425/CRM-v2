<div class="p-4">
    <div class="flex items-center mb-4">
        @if($icon ?? null)
            <div class="flex-shrink-0 mr-3">
                <div class="p-2 rounded-full bg-{{ $color ?? 'blue' }}-100 dark:bg-{{ $color ?? 'blue' }}-900/20">
                    <x-heroicon-o-building-office class="w-6 h-6 text-{{ $color ?? 'blue' }}-600 dark:text-{{ $color ?? 'blue' }}-400" />
                </div>
            </div>
        @endif

        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ $title }}
        </h3>
    </div>

    @if(is_array($content))
        <div class="space-y-3">
            @foreach($content as $label => $value)
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $label }}:</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-700 dark:text-gray-300">{{ $content }}</p>
    @endif
</div>
