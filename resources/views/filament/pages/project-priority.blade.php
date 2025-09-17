<x-filament::page>
    <!-- All styles consolidated in the style tag -->
    <style>
        /* Box styles */
        .priority-box {
            transition: all 0.2s ease;
            flex: 1;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            min-width: 250px;
            background-color: white;
        }
        .priority-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .priority-box-high {
            border: 1px solid rgba(220, 38, 38, 0.3);
            background-color: #ffeeee;
        }
        .priority-box-medium {
            border: 1px solid rgba(217, 119, 6, 0.3);
            background-color: #fff9f0;
        }
        .priority-box-low {
            border: 1px solid rgba(16, 185, 129, 0.3);
            background-color: #f2fff0;
        }

        /* Box header styles */
        .priority-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .priority-title-high {
            color: rgb(220, 38, 38);
            font-weight: 700;
            font-size: 1.125rem;
        }
        .priority-title-medium {
            color: rgb(217, 119, 6);
            font-weight: 700;
            font-size: 1.125rem;
        }
        .priority-title-low {
            color: rgb(16, 185, 129);
            font-weight: 700;
            font-size: 1.125rem;
        }

        /* Count badge styles */
        .priority-count {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            color: white;
            font-weight: 600;
        }
        .priority-count-high {
            background-color: rgb(220, 38, 38);
        }
        .priority-count-medium {
            background-color: rgb(217, 119, 6);
        }
        .priority-count-low {
            background-color: rgb(16, 185, 129);
        }

        /* Box description text */
        .priority-description {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: rgb(75, 85, 99);
        }

        /* Box indicator line */
        .priority-indicator {
            width: 100%;
            height: 0.25rem;
            margin-top: 0.75rem;
            border-radius: 9999px;
        }
        .priority-indicator-high {
            background-color: rgb(220, 38, 38);
        }
        .priority-indicator-medium {
            background-color: rgb(217, 119, 6);
        }
        .priority-indicator-low {
            background-color: rgb(16, 185, 129);
        }

        /* Layout styles */
        .priority-boxes {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 768px) {
            .page-header {
                flex-direction: row;
            }
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem 0.5rem;
        }

        .refresh-info {
            margin-left: 1rem;
            font-size: 0.875rem;
            color: rgb(107, 114, 128);
        }

        .refresh-button {
            padding: 0.25rem 0.5rem;
            margin-left: 0.5rem;
            font-size: 0.75rem;
            color: white;
            background-color: rgb(59, 130, 246);
            border-radius: 0.25rem;
        }

        .refresh-button:hover {
            background-color: rgb(37, 99, 235);
        }

        /* Slide-over styles */
        .slide-over-modal {
            height: 100vh !important;
            display: flex;
            flex-direction: column;
            background-color: white;
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 28rem;
        }

        .slide-over-header {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 50;
            border-bottom: 1px solid #e5e7eb;
            padding: 1.25rem 1.5rem;
            min-height: 70px;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 12px 0 0 0;
        }

        .slide-over-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .slide-over-close {
            padding: 0.25rem;
            font-size: 1.5rem;
            line-height: 1;
            color: rgb(107, 114, 128);
        }

        .slide-over-close:hover {
            color: rgb(55, 65, 81);
        }

        .slide-over-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            height: calc(100vh - 64px);
            padding-bottom: 80px;
        }

        .slide-over-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            text-align: center;
        }

        .slide-over-empty-icon {
            width: 4rem;
            height: 4rem;
            margin-bottom: 1rem;
            color: rgb(156, 163, 175);
        }

        .slide-over-total-high {
            background-color: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .slide-over-total-medium {
            background-color: rgba(217, 119, 6, 0.1);
            border: 1px solid rgba(217, 119, 6, 0.2);
        }

        .slide-over-total-low {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .slide-over-total {
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            border-radius: 0.375rem;
        }

        .slide-over-total-label {
            font-size: 0.875rem;
            color: rgb(107, 114, 128);
        }

        .slide-over-total-value {
            margin-left: 0.25rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: rgb(55, 65, 81);
        }

        .implementer-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            transition: all 0.2s;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .implementer-item:hover {
            background-color: #f3f4f6;
        }

        .implementer-name {
            font-weight: 500;
            color: rgb(55, 65, 81);
            display: flex;
            align-items: center;
        }

        .implementer-name svg {
            margin-left: 0.5rem;
            width: 0.875rem;
            height: 0.875rem;
            transition: transform 0.15s ease;
        }

        .implementer-name.expanded svg {
            transform: rotate(180deg);
        }

        .implementer-count {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            background-color: #e0f2fe;
            color: #0284c7;
            font-weight: 600;
            border-radius: 9999px;
        }

        /* Company list styles */
        .company-list {
            margin-top: 0.5rem;
            margin-bottom: 1rem;
            margin-left: 1rem;
            padding-left: 0.5rem;
            border-left: 2px solid #e5e7eb;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .company-list.expanded {
            max-height: 1000px;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .company-item {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
            color: #4b5563;
            background-color: #f9fafb;
            margin-bottom: 0.25rem;
            border-radius: 0.25rem;
            word-break: break-word;
        }

        /* Modal overlay */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 200;
            display: flex;
            justify-content: flex-end;
            transition-property: opacity;
            transition-duration: 200ms;
            transition-timing-function: ease-out;
        }
    </style>

    <!-- Title & Filters in One Line -->
    <div class="page-header">
        <!-- Title -->
        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">
            Project Priority Dashboard
        </h1>

        <!-- Filters -->
        <div class="filter-group">
            <!-- Implementer Filter -->
            <div>
                <label for="implementer-filter" class="sr-only">Select Implementer</label>
                <select id="implementer-filter"
                        wire:model.live="selectedImplementer"
                        class="px-4 py-2 border-gray-300 rounded-md shadow-sm">
                    <option value="">All Implementers&nbsp;&nbsp;&nbsp;&nbsp;</option>
                    @foreach (\App\Models\User::whereIn('role_id', [4, 5])->pluck('name', 'id') as $id => $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Priority Boxes -->
    <div class="priority-boxes">
        <!-- High Priority Box -->
        <div class="priority-box priority-box-high"
             wire:click="openPriorityBreakdownSlideOver('High')">
            <div class="priority-title">
                <h3 class="priority-title-high">High Priority</h3>
                <span class="priority-count priority-count-high">{{ $totals['high'] ?? 0 }}</span>
            </div>
            <div class="priority-indicator priority-indicator-high"></div>
        </div>

        <!-- Medium Priority Box -->
        <div class="priority-box priority-box-medium"
             wire:click="openPriorityBreakdownSlideOver('Medium')">
            <div class="priority-title">
                <h3 class="priority-title-medium">Medium Priority</h3>
                <span class="priority-count priority-count-medium">{{ $totals['medium'] ?? 0 }}</span>
            </div>
            <div class="priority-indicator priority-indicator-medium"></div>
        </div>

        <!-- Low Priority Box -->
        <div class="priority-box priority-box-low"
             wire:click="openPriorityBreakdownSlideOver('Low')">
            <div class="priority-title">
                <h3 class="priority-title-low">Low Priority</h3>
                <span class="priority-count priority-count-low">{{ $totals['low'] ?? 0 }}</span>
            </div>
            <div class="priority-indicator priority-indicator-low"></div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="w-full min-h-screen">
        <livewire:project-priority-table
            :selectedImplementer="$selectedImplementer" />
    </div>

    <!-- Slide-over Modal -->
    <div
        x-data="{ open: @entangle('showSlideOver') }"
        x-show="open"
        @keydown.window.escape="open = false"
        class="modal-overlay"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="slide-over-modal"
            @click.away="open = false"
        >
            <!-- Header -->
            <div class="slide-over-header">
                <div class="slide-over-title">
                    <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                    <button @click="open = false" class="slide-over-close">&times;</button>
                </div>
            </div>

            <!-- Scrollable content -->
            <div class="slide-over-content">
                @if (count($implementerProjects) === 0)
                    <div class="slide-over-empty">
                        <div class="slide-over-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-gray-500">No projects found for this priority.</p>
                    </div>
                @else
                    <!-- Total count -->
                    <div class="slide-over-total slide-over-total-{{ strtolower(str_replace(' Priority Projects', '', $slideOverTitle)) }}">
                        <span class="slide-over-total-label">Total Projects:</span>
                        <span class="slide-over-total-value">
                            {{ collect($implementerProjects)->sum('count') }}
                        </span>
                    </div>

                    <!-- Implementer list with expandable company names -->
                    <div class="space-y-0">
                        @foreach ($implementerProjects as $project)
                            @php
                                $implementer = $project['implementer'] ?? 'Unassigned';
                                $isExpanded = in_array($implementer, $expandedImplementers);
                            @endphp

                            <!-- Implementer item -->
                            <div class="implementer-item" wire:click="toggleImplementer('{{ $implementer }}')">
                                <div class="implementer-name {{ $isExpanded ? 'expanded' : '' }}">
                                    {{ $implementer }}

                                    <!-- Chevron icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="implementer-count">{{ $project['count'] }}</div>
                            </div>

                            <!-- Company list (hidden by default) -->
                            <div class="company-list {{ $isExpanded ? 'expanded' : '' }}">
                                @if ($isExpanded && isset($implementerCompanies[$implementer]))
                                    @foreach ($implementerCompanies[$implementer] as $companyData)
                                        @php
                                            $company = is_array($companyData) ? $companyData['name'] : $companyData;
                                            $encryptedId = is_array($companyData) ? $companyData['encrypted_id'] : null;
                                            $url = $encryptedId ? url('admin/leads/' . $encryptedId) : '#';
                                            $shortened = strtoupper(\Illuminate\Support\Str::limit($company, 25, '...'));
                                        @endphp

                                        <div class="company-item">
                                            @if($encryptedId)
                                                <a href="{{ $url }}"
                                                target="_blank"
                                                title="{{ $company }}"
                                                style="color: #338cf0; text-decoration: none; display: block; width: 100%;"
                                                onmouseover="this.style.textDecoration='underline'"
                                                onmouseout="this.style.textDecoration='none'">
                                                    {{ $shortened }}
                                                </a>
                                            @else
                                                {{ $shortened }}
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament::page>
