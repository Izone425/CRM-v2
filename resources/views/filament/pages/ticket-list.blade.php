{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/ticket-list.blade.php --}}
<x-filament-panels::page>
    {{ $this->table }}

    {{-- ✅ Add the modal (same as ticket-dashboard) --}}
    @if($showTicketModal && $selectedTicket)
        @include('filament.pages.partials.ticket-modal')
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
