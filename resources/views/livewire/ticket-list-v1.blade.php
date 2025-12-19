{{-- filepath: /var/www/html/timeteccrm/resources/views/livewire/ticket-list-v1.blade.php --}}
<div>
    {{-- Table --}}
    {{ $this->table }}

    {{-- Ticket Modal --}}
    @if($showTicketModal && $selectedTicket)
        @include('filament.pages.partials.ticket-modal')
    @endif

    {{-- Create Ticket Action Modal --}}
    <x-filament-actions::modals />
</div>
