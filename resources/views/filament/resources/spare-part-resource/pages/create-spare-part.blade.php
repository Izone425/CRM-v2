<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        <div class="space-y-6">
            <div class="p-6 bg-white rounded-lg shadow">
                {{ $this->form }}

                <div class="flex justify-between mt-6">
                    <x-filament::button
                        wire:click="addFiveRows"
                        type="button"
                        color="secondary"
                    >
                        Add 5 Empty Rows
                    </x-filament::button>

                    <div>
                        <x-filament::button
                            wire:click="$dispatch('close-modal')"
                            type="button"
                            color="gray"
                        >
                            Cancel
                        </x-filament::button>

                        <x-filament::button
                            type="submit"
                            class="ml-2"
                        >
                            Create All Spare Parts
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
