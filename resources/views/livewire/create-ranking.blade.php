<div>
    {{-- Because she competes with no one, no one can compete with her. --}}

    <div>
        <form wire:submit="create">
            {{ $this->form }}
            
            <button type="submit">
                Submit
            </button>
        </form>
        
        <x-filament-actions::modals />
    </div>
</div>
