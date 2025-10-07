{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/sales-pricing-management.blade.php --}}
<x-filament-panels::page>
    <style>
        .pricing-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .pricing-container {
                flex-direction: row;
            }

            .pricing-sidebar {
                width: 30%;
                flex-shrink: 0;
            }

            .pricing-content-wrapper {
                width: 70%;
                flex-grow: 1;
            }
        }

        .pricing-sidebar {
            background-color: white;
            border-radius: 0.5rem;
            border-right: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .pricing-search {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            background-color: #fcfcfc;
        }

        .pricing-search input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            background-color: white;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }

        .pricing-search input:focus {
            outline: none;
            ring: 2px;
            ring-color: #3b82f6;
            border-color: #93c5fd;
        }

        .pricing-list {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .pricing-list-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.875rem 1rem;
            border-left: 3px solid transparent;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
        }

        .pricing-list-item:hover {
            background-color: #f9fafb;
        }

        .pricing-list-item.active {
            background-color: #fef3c7;
            border-left-color: #f59e0b;
        }

        .pricing-list-title {
            font-weight: 600;
            font-size: 0.9375rem;
            color: #1f2937;
            margin-bottom: 0.25rem;
            line-height: 1.25;
        }

        .pricing-list-meta {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .pricing-content-wrapper {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 150px);
        }

        .pricing-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .pricing-header {
            padding-bottom: 1.25rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .pricing-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.25;
        }

        .pricing-page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1rem;
        }

        .pricing-metadata {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 640px) {
            .pricing-metadata {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        .pricing-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pricing-meta-label {
            font-weight: 600;
            color: #4b5563;
        }

        .pricing-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .pricing-status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .pricing-status-inactive {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .pricing-status-expired {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .pricing-summary {
            background-color: #fef7cd;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #fbbf24;
        }

        .pricing-summary-title {
            font-weight: 600;
            color: #92400e;
            margin-bottom: 0.5rem;
        }

        .pricing-body {
            line-height: 1.625;
            color: #374151;
            flex-grow: 1;
        }

        .pricing-body h1, .pricing-body h2, .pricing-body h3 {
            color: #111827;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .pricing-body h1 {
            font-size: 1.5rem;
        }

        .pricing-body h2 {
            font-size: 1.25rem;
        }

        .pricing-body h3 {
            font-size: 1.125rem;
        }

        .pricing-body p {
            margin-bottom: 1rem;
        }

        .pricing-body ul, .pricing-body ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .pricing-body ul {
            list-style-type: disc;
        }

        .pricing-body ol {
            list-style-type: decimal;
        }

        .pricing-body li {
            margin-bottom: 0.5rem;
        }

        .pricing-body ul > li::marker {
            color: #000000 !important;
        }

        .pricing-body ol > li::marker {
            color: #000000 !important;
        }

        .pricing-body table th {
            background-color: #fef7cd !important;
            color: #92400e !important;
            font-weight: 600 !important;
            border: 1px solid #fbbf24 !important;
        }

        .pricing-body table {
            border-collapse: collapse !important;
            width: 100% !important;
        }

        .pricing-body table td {
            border: 1px solid #fbbf24 !important;
            padding: 0.5rem !important;
        }

        .pricing-footer {
            padding-top: 0.5rem;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.875rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: auto;
        }

        .pricing-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 350px;
            text-align: center;
            color: #6b7280;
            padding: 1.5rem;
        }

        .pricing-empty-icon {
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .pricing-empty-title {
            font-size: 1.125rem;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .pricing-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .pricing-nav-button {
            padding: 0.5rem 1rem;
            background-color: #f59e0b;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .pricing-nav-button:hover {
            background-color: #d97706;
        }

        .pricing-nav-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>

    <div class="pricing-container">
        <!-- Left sidebar - Sales Pricing List -->
        <div class="pricing-sidebar">
            <div class="pricing-search">
                <input type="text" wire:model.live="search" placeholder="Search sales pricing..." />
            </div>

            <div class="pricing-list">
                @forelse($pricings as $pricing)
                    <button wire:click="selectPricing({{ $pricing->id }})"
                        class="pricing-list-item {{ $selectedPricing && $selectedPricing->id === $pricing->id ? 'active' : '' }}">
                        <div class="pricing-list-title">{{ $pricing->title }}</div>
                        <div class="pricing-list-meta">
                            Effective: {{ $pricing->effective_date->format('M d, Y') }}
                            @if($pricing->expiry_date)
                                • Expires: {{ $pricing->expiry_date->format('M d, Y') }}
                            @endif
                            @if($pricing->pages && $pricing->pages->count() > 0)
                                • {{ $pricing->pages->count() }} page(s)
                            @endif
                        </div>
                    </button>
                @empty
                    <div class="p-4 text-center text-gray-500">
                        No sales pricing found
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right content area - Sales Pricing details -->
        <div class="pricing-content-wrapper">
            @if($selectedPricing)
                <div class="pricing-content">
                    <div class="pricing-header">
                        <h1 class="pricing-title">{{ $selectedPricing->title }}</h1>
                    </div>

                    <div class="pricing-metadata">
                        <div class="pricing-meta-item">
                            <span class="pricing-meta-label">Effective Date:</span>
                            <span>{{ $selectedPricing->effective_date->format('M d, Y') }}</span>
                        </div>
                        <div class="pricing-meta-item">
                            <span class="pricing-meta-label">Status:</span>
                            <span class="pricing-status-badge {{ $selectedPricing->status === 'Active' ? 'pricing-status-active' : ($selectedPricing->status === 'Expired' ? 'pricing-status-expired' : 'pricing-status-inactive') }}">
                                {{ $selectedPricing->status }}
                            </span>
                        </div>
                        @if($selectedPricing->expiry_date)
                            <div class="pricing-meta-item">
                                <span class="pricing-meta-label">Expiry Date:</span>
                                <span>{{ $selectedPricing->expiry_date->format('M d, Y') }}</span>
                            </div>
                        @endif
                    </div>

                    @if($selectedPricing->summary)
                        <div class="pricing-summary">
                            <div class="pricing-summary-title">Summary</div>
                            <div>{{ $selectedPricing->summary }}</div>
                        </div>
                    @endif

                    @if($selectedPricing->pages && $selectedPricing->pages->count() > 0)
                        <h2 class="pricing-page-title">{{ $selectedPage->title }}</h2>

                        <div class="prose pricing-body max-w-none">
                            {!! $selectedPage->content !!}
                        </div>

                        @if($selectedPricing->pages->count() > 1)
                            <div class="pricing-navigation">
                                <button wire:click="prevPage"
                                    class="pricing-nav-button"
                                    {{ $currentPageIndex === 0 ? 'disabled' : '' }}>
                                    <span>&larr; Previous</span>
                                </button>

                                <div class="text-sm text-gray-500">
                                    Page {{ $currentPageIndex + 1 }} of {{ $selectedPricing->pages->count() }}
                                </div>

                                <button wire:click="nextPage"
                                    class="pricing-nav-button"
                                    {{ $currentPageIndex >= $selectedPricing->pages->count() - 1 ? 'disabled' : '' }}>
                                    <span>Next &rarr;</span>
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="p-4 text-center text-gray-500">
                            No pages found for this sales pricing.
                        </div>
                    @endif

                    <div class="pricing-footer">
                        <div>
                            <div>Created by: {{ $selectedPricing->createdByUser->name ?? 'Unknown' }}</div>
                            <div>Created on: {{ $selectedPricing->created_at->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <div>Last updated by: {{ $selectedPricing->lastUpdatedByUser->name ?? 'Unknown' }}</div>
                            <div>Last updated: {{ $selectedPricing->updated_at->format('M d, Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="pricing-empty">
                    <div class="pricing-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                    </div>
                    <h3 class="pricing-empty-title">Select a sales pricing</h3>
                    <p>Choose a sales pricing from the list to view its details</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
