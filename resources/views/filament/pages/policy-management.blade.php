<x-filament-panels::page>
    <style>
        .policy-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .policy-container {
                flex-direction: row;
            }

            .policy-sidebar {
                width: 30%;
                flex-shrink: 0;
            }

            .policy-content-wrapper {
                width: 70%;
                flex-grow: 1;
            }
        }

        .policy-sidebar {
            background-color: white;
            border-radius: 0.5rem;
            border-right: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .policy-search {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            background-color: #fcfcfc;
        }

        .policy-search input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            background-color: white;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }

        .policy-search input:focus {
            outline: none;
            ring: 2px;
            ring-color: #3b82f6;
            border-color: #93c5fd;
        }

        .policy-list {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .policy-list-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.875rem 1rem;
            border-left: 3px solid transparent;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
        }

        .policy-list-item:hover {
            background-color: #f9fafb;
        }

        .policy-list-item.active {
            background-color: #eff6ff;
            border-left-color: #3b82f6;
        }

        .policy-list-title {
            font-weight: 600;
            font-size: 0.9375rem;
            color: #1f2937;
            margin-bottom: 0.25rem;
            line-height: 1.25;
        }

        .policy-list-meta {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .policy-content-wrapper {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05);
        }

        .policy-content {
            padding: 1.5rem;
        }

        .policy-header {
            padding-bottom: 1.25rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .policy-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.25;
        }

        .policy-page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1rem;
        }

        .policy-metadata {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 640px) {
            .policy-metadata {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        .policy-meta-item {
            display: flex;
            align-items: center;
        }

        .policy-meta-label {
            font-weight: 600;
            color: #4b5563;
            width: 130px;
            flex-shrink: 0;
        }

        .policy-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .policy-status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .policy-status-inactive {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .policy-body {
            line-height: 1.625;
            color: #374151;
        }

        .policy-body h1, .policy-body h2, .policy-body h3 {
            color: #111827;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .policy-body h1 {
            font-size: 1.5rem;
        }

        .policy-body h2 {
            font-size: 1.25rem;
        }

        .policy-body h3 {
            font-size: 1.125rem;
        }

        .policy-body p {
            margin-bottom: 1rem;
        }

        .policy-body ul, .policy-body ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .policy-body ul {
            list-style-type: disc;
        }

        .policy-body ol {
            list-style-type: decimal;
        }

        .policy-body li {
            margin-bottom: 0.5rem;
        }

        .policy-footer {
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.875rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .policy-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 350px;
            text-align: center;
            color: #6b7280;
            padding: 1.5rem;
        }

        .policy-empty-icon {
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .policy-empty-title {
            font-size: 1.125rem;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .policy-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .policy-page-nav {
            display: flex;
            gap: 0.5rem;
        }

        .policy-nav-button {
            padding: 0.5rem 1rem;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            color: #4b5563;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .policy-nav-button:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .policy-nav-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .policy-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .policy-tab {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem 0.375rem 0 0;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
        }

        .policy-tab:hover {
            color: #4b5563;
        }

        .policy-tab.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }

        .policy-summary {
            background-color: #f9fafb;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .policy-summary-title {
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.5rem;
        }
    </style>

    <div class="policy-container">
        <!-- Left sidebar - Policy List -->
        <div class="policy-sidebar">
            <div class="policy-search">
                <input type="text" wire:model.live="search" placeholder="Search policies..." />
            </div>

            <div class="policy-list">
                @forelse($policies as $policy)
                    <button wire:click="selectPolicy({{ $policy->id }})"
                        class="policy-list-item {{ $selectedPolicy && $selectedPolicy->id === $policy->id ? 'active' : '' }}">
                        <div class="policy-list-title">{{ $policy->title }}</div>
                        <div class="policy-list-meta">
                            {{ $policy->category->name ?? 'Uncategorized' }}
                            @if($policy->pages && $policy->pages->count() > 0)
                                • {{ $policy->pages->count() }} page(s)
                            @endif
                        </div>
                    </button>
                @empty
                    <div class="p-4 text-center text-gray-500">
                        No policies found
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right content area - Policy details -->
        <div class="policy-content-wrapper">
            @if($selectedPolicy)
                <div class="policy-content">
                    <div class="policy-header">
                        <h1 class="policy-title">{{ $selectedPolicy->title }}</h1>
                    </div>

                    <div class="policy-metadata">
                        <div class="policy-meta-item">
                            <span class="policy-meta-label">Effective Date:</span>
                            <span>{{ $selectedPolicy->effective_date->format('M d, Y') }}</span>
                        </div>
                        <div class="policy-meta-item">
                            <span class="policy-meta-label">Status:</span>
                            <span class="policy-status-badge {{ $selectedPolicy->status === 'Active' ? 'policy-status-active' : 'policy-status-inactive' }}">
                                {{ $selectedPolicy->status }}
                            </span>
                        </div>
                        <div class="policy-meta-item">
                            <span class="policy-meta-label">Category:</span>
                            <span>{{ $selectedPolicy->category->name ?? 'Uncategorized' }}</span>
                        </div>
                    </div>

                    @if($selectedPolicy->summary)
                        <div class="policy-summary">
                            <div class="policy-summary-title">Summary</div>
                            <div class="policy-body">{{ $selectedPolicy->summary }}</div>
                        </div>
                    @endif

                    @if($selectedPolicy->pages && $selectedPolicy->pages->count() > 0)
                        @if($selectedPolicy->pages->count() > 1)
                            <div class="policy-tabs">
                                @foreach($selectedPolicy->pages as $index => $page)
                                    <button wire:click="goToPage({{ $index }})"
                                        class="policy-tab {{ $currentPageIndex === $index ? 'active' : '' }}">
                                        {{ $page->title }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if($selectedPage)
                            <h2 class="policy-page-title">{{ $selectedPage->title }}</h2>

                            <div class="prose policy-body max-w-none">
                                {!! $selectedPage->content !!}
                            </div>

                            @if($selectedPolicy->pages->count() > 1)
                                <div class="policy-navigation">
                                    <button wire:click="prevPage"
                                        class="policy-nav-button"
                                        {{ $currentPageIndex === 0 ? 'disabled' : '' }}>
                                        <span>&larr; Previous</span>
                                    </button>

                                    <div class="text-sm text-gray-500">
                                        Page {{ $currentPageIndex + 1 }} of {{ $selectedPolicy->pages->count() }}
                                    </div>

                                    <button wire:click="nextPage"
                                        class="policy-nav-button"
                                        {{ $currentPageIndex >= $selectedPolicy->pages->count() - 1 ? 'disabled' : '' }}>
                                        <span>Next &rarr;</span>
                                    </button>
                                </div>
                            @endif
                        @else
                            <div class="p-4 text-center text-gray-500">
                                No content available for this page.
                            </div>
                        @endif
                    @else
                        <div class="p-4 text-center text-gray-500">
                            No pages found for this policy.
                        </div>
                    @endif

                    <div class="policy-footer">
                        <div>
                            <div>Created by: {{ $selectedPolicy->createdByUser->name ?? 'Unknown' }}</div>
                            <div>Created on: {{ $selectedPolicy->created_at->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <div>Last updated by: {{ $selectedPolicy->lastUpdatedByUser->name ?? 'Unknown' }}</div>
                            <div>Last updated: {{ $selectedPolicy->updated_at->format('M d, Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="policy-empty">
                    <div class="policy-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="policy-empty-title">Select a policy</h3>
                    <p>Choose a policy from the list to view its details</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
