<style>
    .company-size-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
        border: 2px solid #374151;
    }

    .company-size-table th,
    .company-size-table td {
        padding: 8px 12px;
        text-align: center;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .company-size-table th {
        background-color: #0f9ed5;
        font-weight: 600;
        color: white;
    }

    .company-size-table tbody tr:hover {
        background-color: rgba(243, 244, 246, 0.5);
    }

    .month-column {
        text-align: left !important;
        font-weight: 500;
    }

    .total-row {
        font-weight: 600;
        background-color: #f2f25e;
    }

    /* Custom column colors */
    .total-column { background-color: #f2f25e; }
    .small-column { background-color: #fbe2d5; }
    .medium-column { background-color: #fbe2d5; }
    .large-column { background-color: #caedfb; }
    .enterprise-column { background-color: #caedfb; }

    .month-header {
        width: 15%;
    }

    .size-header {
        width: 17%;
    }

    .clickable {
        cursor: pointer;
        transition: all 0.2s;
    }

    .clickable:hover {
        background-color: rgba(59, 130, 246, 0.1);
    }

    /* Slide-over styles (copied from project-analysis) */
    .slide-over-modal {
        height: 100vh !important;
        display: flex;
        flex-direction: column;
        background-color: white;
        box-shadow: -4px 0 24px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
        margin-top: 55px;
        max-height: calc(100vh - 55px);
        border-radius: 12px 0 0 0;
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

    .slide-over-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        height: calc(100vh - 64px);
        padding-bottom: 80px;
    }

    .company-item {
        display: block;
        padding: 0.75rem 1rem;
        margin-bottom: 0.75rem;
        background-color: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        transition: all 0.2s;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .company-item.has-lead {
        color: #2563eb;
    }

    .company-item.no-lead {
        color: #111827;
    }

    .company-item:hover {
        transform: translateY(-2px);
        background-color: #f9fafb;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1.5rem;
        text-align: center;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px dashed #d1d5db;
        color: #6b7280;
    }

    .group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        margin-top: 0.75rem;
        background: linear-gradient(to right, #2563eb, #3b82f6);
        border-radius: 0.375rem 0.375rem 0 0;
        color: white;
        font-weight: 500;
        cursor: pointer;
    }

    .group-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        background-color: white;
        color: #2563eb;
        font-weight: 600;
        font-size: 0.75rem;
        border-radius: 9999px;
        margin-right: 0.5rem;
    }

    .group-content {
        padding: 1rem;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-top: none;
        border-radius: 0 0 0.375rem 0.375rem;
    }
</style>

<div class="mb-6 overflow-hidden bg-white rounded-lg shadow">
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                Implementer Department - New Projects by Company Size ({{ $selectedYear }})
            </h2>
            <div class="flex items-center gap-2">
                <select wire:model.live="selectedYear"
                        class="text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    @for ($year = date('Y'); $year >= 2023; $year--)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="company-size-table">
            <thead>
                <tr>
                    <th class="month-header">Month</th>
                    <th class="size-header">Total New<br>Project</th>
                    <th class="size-header">Small<br>(Below 25)</th>
                    <th class="size-header">Medium<br>(25-99)</th>
                    <th class="size-header">Large<br>(100-500)</th>
                    <th class="size-header">Enterprise<br>(Above 500)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $monthlyData = $this->getMonthlyProjectsByCompanySize();
                    $totals = $this->getYearlyTotalsByCompanySize();
                @endphp

                @foreach($monthlyData as $data)
                    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                        <td class="month-column">{{ $data['month'] }}</td>
                        <td class="clickable" wire:click="openCompanySizeMonthlyHandovers('{{ $data['month'] }}', 'all')">
                            {{ $data['total'] }}
                        </td>
                        <td class="clickable" wire:click="openCompanySizeMonthlyHandovers('{{ $data['month'] }}', 'small')">
                            {{ $data['small'] }}
                        </td>
                        <td class="clickable" wire:click="openCompanySizeMonthlyHandovers('{{ $data['month'] }}', 'medium')">
                            {{ $data['medium'] }}
                        </td>
                        <td class="clickable" wire:click="openCompanySizeMonthlyHandovers('{{ $data['month'] }}', 'large')">
                            {{ $data['large'] }}
                        </td>
                        <td class="clickable" wire:click="openCompanySizeMonthlyHandovers('{{ $data['month'] }}', 'enterprise')">
                            {{ $data['enterprise'] }}
                        </td>
                    </tr>
                @endforeach

                <!-- Totals row -->
                <tr class="total-row">
                    <td class="month-column">Total</td>
                    <td class="clickable" wire:click="openCompanySizeYearlyHandovers('all')">
                        {{ $totals['total'] }}
                    </td>
                    <td class="clickable" wire:click="openCompanySizeYearlyHandovers('small')">
                        {{ $totals['small'] }}
                    </td>
                    <td class="clickable" wire:click="openCompanySizeYearlyHandovers('medium')">
                        {{ $totals['medium'] }}
                    </td>
                    <td class="clickable" wire:click="openCompanySizeYearlyHandovers('large')">
                        {{ $totals['large'] }}
                    </td>
                    <td class="clickable" wire:click="openCompanySizeYearlyHandovers('enterprise')">
                        {{ $totals['enterprise'] }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Slide-over panel -->
<div
    x-data="{ open: @entangle('showSlideOver'), expandedGroups: {} }"
    x-show="open"
    @keydown.window.escape="open = false"
    class="fixed inset-0 z-[200] flex justify-end bg-black/40 backdrop-blur-sm transition-opacity duration-200"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display: none;"
>
    <div
        class="w-full h-full max-w-md overflow-hidden slide-over-modal"
        @click.away="open = false"
    >
        <!-- Header -->
        <div class="slide-over-header">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                <button @click="open = false" class="p-1 text-2xl leading-none text-gray-500 hover:text-gray-700">&times;</button>
            </div>
        </div>

        <!-- Scrollable content -->
        <div class="slide-over-content">
            @if (empty($handoverList) || count($handoverList) === 0)
                <div class="empty-state">
                    <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 14h.01M20 4v7a4 4 0 01-4 4H8a4 4 0 01-4-4V4m0 0h16M4 4v2m16-2v2" />
                    </svg>
                    <p>No handovers found for this selection.</p>
                </div>
            @elseif ($handoverList instanceof \Illuminate\Support\Collection && $handoverList->first() instanceof \Illuminate\Support\Collection)
                <!-- Grouped display -->
                @foreach ($handoverList as $companySize => $handovers)
                    <div class="mb-4">
                        <!-- Group header -->
                        <div
                            class="group-header"
                            x-on:click="expandedGroups['{{ $companySize }}'] = !expandedGroups['{{ $companySize }}']"
                        >
                            <div class="flex items-center">
                                <span class="group-badge">{{ $handovers->count() }}</span>
                                <span>{{ $companySize }}</span>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform"
                                :class="expandedGroups['{{ $companySize }}'] ? 'transform rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <!-- Group content (collapsible) -->
                        <div class="group-content" x-show="expandedGroups['{{ $companySize }}']" x-collapse>
                            @foreach ($handovers as $handover)
                                @php
                                    try {
                                        $companyName = $handover->company_name ?? 'N/A';
                                        $shortened = strtoupper(\Illuminate\Support\Str::limit($companyName, 40, '...'));
                                        $encryptedId = $handover->lead_id ? \App\Classes\Encryptor::encrypt($handover->lead_id) : null;
                                    } catch (\Exception $e) {
                                        $shortened = 'Error loading company';
                                        $encryptedId = null;
                                        $companyName = 'Error: ' . $e->getMessage();
                                    }
                                @endphp

                                @if ($encryptedId)
                                    <a href="{{ url('admin/leads/' . $encryptedId) }}"
                                        target="_blank"
                                        title="{{ $companyName }}"
                                        class="company-item has-lead">
                                        {{ $shortened }}
                                        <i class="ml-1 text-xs fas fa-external-link-alt"></i>
                                    </a>
                                @else
                                    <div class="company-item no-lead">
                                        {{ $shortened }}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                <!-- Legacy non-grouped display as fallback -->
                @foreach ($handoverList as $handover)
                    @php
                        try {
                            $companyName = $handover->company_name ?? 'N/A';
                            $shortened = strtoupper(\Illuminate\Support\Str::limit($companyName, 40, '...'));
                            $encryptedId = $handover->lead_id ? \App\Classes\Encryptor::encrypt($handover->lead_id) : null;
                        } catch (\Exception $e) {
                            $shortened = 'Error loading company';
                            $encryptedId = null;
                            $companyName = 'Error: ' . $e->getMessage();
                        }
                    @endphp

                    @if ($encryptedId)
                        <a href="{{ url('admin/leads/' . $encryptedId) }}"
                            target="_blank"
                            title="{{ $companyName }}"
                            class="company-item has-lead">
                            {{ $shortened }}
                            <i class="ml-1 text-xs fas fa-external-link-alt"></i>
                        </a>
                    @else
                        <div class="company-item no-lead">
                            {{ $shortened }}
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</div>
