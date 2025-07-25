<x-filament-panels::page>
    @if($openCreateModal)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    // Find the create button and trigger a click on it
                    document.querySelector('[data-action="create"], [wire\\:click*="create"]')?.click();
                }, 200);
            });
        </script>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
