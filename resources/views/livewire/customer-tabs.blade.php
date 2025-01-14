<div x-show="tab === 'prospect'">
    <x-filament::card>
        <h2 class="text-lg font-bold">Leads</h2>
        <div class="mt-4">
            {{ $this->table() }} <!-- Render the leads table -->
        </div>
    </x-filament::card>
</div>
