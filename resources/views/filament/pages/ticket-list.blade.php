{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/ticket-list.blade.php --}}
<x-filament-panels::page>
    <style>
        .tab-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            border: 2px solid;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            background: none;
            outline: none;
            position: relative;
            overflow: hidden;
        }

        .tab-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .tab-button.active-v1 {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-color: #059669;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4), 0 4px 6px -2px rgba(16, 185, 129, 0.05);
        }

        .tab-button.active-v1:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.5), 0 10px 10px -5px rgba(16, 185, 129, 0.1);
        }

        .tab-button.active-v2 {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-color: #2563eb;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4), 0 4px 6px -2px rgba(59, 130, 246, 0.05);
        }

        .tab-button.active-v2:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.5), 0 10px 10px -5px rgba(59, 130, 246, 0.1);
        }

        .tab-button.active-zoho {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-color: #d97706;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.4), 0 4px 6px -2px rgba(245, 158, 11, 0.05);
        }

        .tab-button.active-zoho:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            box-shadow: 0 20px 25px -5px rgba(245, 158, 11, 0.5), 0 10px 10px -5px rgba(245, 158, 11, 0.1);
        }

        .tab-button.inactive {
            background: white;
            border-color: #d1d5db;
            color: #374151;
        }

        .tab-button.inactive:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #111827;
        }

        .ticket-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .tab-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .content-area {
            padding: 0;
            margin: 0;
        }

        .tab-container {
            display: flex;
            justify-content: flex-start;
            gap: 16px;
        }

        .space-y-6 > * + * {
            margin-top: 24px;
        }

        /* Active tab glow effect */
        .tab-button.active-v1::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
            pointer-events: none;
        }

        .tab-button.active-v2::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
            pointer-events: none;
        }

        .tab-button.active-zoho::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
            pointer-events: none;
        }

        /* Button ripple effect */
        .tab-button:active {
            transform: scale(0.98);
        }

        /* Focus states for accessibility */
        .tab-button:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        .tab-button.active-v1:focus {
            outline-color: #10b981;
        }

        .tab-button.active-v2:focus {
            outline-color: #3b82f6;
        }

        .tab-button.active-zoho:focus {
            outline-color: #f59e0b;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .tab-container {
                flex-direction: column;
                gap: 12px;
            }

            .tab-button {
                padding: 10px 20px;
                font-size: 13px;
                justify-content: center;
            }
        }

        /* Animation for tab switching */
        .ticket-container {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    {{-- Custom Tab Buttons --}}
    <div class="tab-container">
        <button
            wire:click="switchToV1"
            class="tab-button {{ $activeTab === 'v1' ? 'active-v1' : 'inactive' }}"
        >
            <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Version 1 Tickets
        </button>

        <button
            wire:click="switchToV2"
            class="tab-button {{ $activeTab === 'v2' ? 'active-v2' : 'inactive' }}"
        >
            <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Version 2 Tickets
        </button>

        <button
            wire:click="switchToZoho"
            class="tab-button {{ $activeTab === 'zoho' ? 'active-zoho' : 'inactive' }}"
        >
            <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                </path>
            </svg>
            Zoho Status
        </button>
    </div>

    {{-- Content Area --}}
    <div class="space-y-6">
        @if($activeTab === 'v1')
            <div class="ticket-container">
                <div class="content-area">
                    <livewire:ticket-list-v1 />
                </div>
            </div>
        @endif

        @if($activeTab === 'v2')
            <div class="ticket-container">
                <div class="content-area">
                    <livewire:ticket-list-v2 />
                </div>
            </div>
        @endif

        @if($activeTab === 'zoho')
            <div class="ticket-container">
                <div class="content-area">
                    <livewire:ticket-list-zoho />
                </div>
            </div>
        @endif
    </div>

    {{-- Ticket Modal --}}
    @if($showTicketModal && $selectedTicket)
        @include('filament.pages.partials.ticket-modal')
    @endif

    {{-- Reopen Modal --}}
    @if($showReopenModal && $selectedTicket)
        @include('filament.pages.partials.reopen-modal')
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
