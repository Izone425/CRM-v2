<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/device-purchase-information.blade.php -->
<x-filament-panels::page>
    <!-- Status Filter Section -->
    <div class="p-4 mb-6 border border-blue-200 rounded-lg bg-gradient-to-r from-blue-50 to-indigo-50">
        <div class="flex items-center justify-between mb-3">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
                Filters
            </h3>
            <!-- Filter Toggle and Quick Actions -->
            <div class="flex items-center gap-2">
                <!-- Filter Toggle Button -->
                <button type="button"
                        class="flex items-center gap-2 px-3 py-1 text-xs transition-colors border border-gray-300 rounded-full hover:bg-gray-50"
                        wire:click="$toggle('showFilters')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m0 0h0M7.5 6v0m7.5 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0H7.5m0 0h0" />
                    </svg>
                    {{ $showFilters ? 'Hide Filters' : 'Show Filters' }}
                    @if($filterYear || $filterMonth || $filterStatus || $filterModel || !in_array('All', $selectedStatuses))
                        <span class="inline-flex items-center justify-center w-4 h-4 text-xs text-white bg-red-500 rounded-full">
                            {{ collect([$filterYear, $filterMonth, $filterStatus, $filterModel])->filter()->count() + (in_array('All', $selectedStatuses) ? 0 : 1) }}
                        </span>
                    @endif
                </button>

                <!-- Quick Actions -->
                <button type="button"
                        class="px-3 py-1 text-xs text-blue-700 transition-colors bg-blue-100 rounded-full hover:bg-blue-200"
                        wire:click="clearAllFilters">
                    Clear All
                </button>
                <button type="button"
                        class="px-3 py-1 text-xs text-green-700 transition-colors bg-green-100 rounded-full hover:bg-green-200"
                        wire:click="updateStatusFilter(['Completed Order', 'Completed Shipping', 'Completed Delivery'])">
                    Select All Statuses
                </button>
            </div>
        </div>

        <!-- Collapsible Filter Content -->
        @if($showFilters)
            <div class="space-y-4 animate-fade-in">
                <!-- Status Filter Pills -->
                <div>
                    <div class="status-filter-container">
                        @foreach($this->getStatusOptions() as $statusValue => $statusLabel)
                            <div class="status-filter-pill {{ in_array($statusValue, $selectedStatuses) ? 'active' : 'inactive' }}"
                                wire:click="updateStatusFilter({{ $statusValue === 'All' ? "['All']" :
                                    (in_array($statusValue, $selectedStatuses) ?
                                        json_encode(array_values(array_diff($selectedStatuses, [$statusValue]))) :
                                        json_encode(array_merge(array_diff($selectedStatuses, ['All']), [$statusValue]))) }})">

                                @if($statusValue === 'All')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h4.125M8.25 8.25V6.108" />
                                    </svg>
                                @elseif($statusValue === 'Completed Order')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                @elseif($statusValue === 'Completed Shipping')
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                @endif

                                {{ $statusLabel }}

                                @if(in_array($statusValue, $selectedStatuses))
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 ml-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($isRawView)
                    <!-- Raw Data Additional Filters -->
                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <!-- Filter Grid -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-3">
                            <!-- Year Filter -->
                            <div class="form-group">
                                <label class="block mb-1 text-sm font-medium text-gray-700">Year</label>
                                <select class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        wire:model.live="filterYear" wire:change="updateRawDataFilters">
                                    <option value="">All Years</option>
                                    @foreach($this->getAvailableYears() as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Month Filter -->
                            <div class="form-group">
                                <label class="block mb-1 text-sm font-medium text-gray-700">Month</label>
                                <select class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        wire:model.live="filterMonth" wire:change="updateRawDataFilters">
                                    <option value="">All Months</option>
                                    @foreach(range(1, 12) as $month)
                                        <option value="{{ $month }}">{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Model Filter -->
                            <div class="form-group">
                                <label class="block mb-1 text-sm font-medium text-gray-700">Model</label>
                                <select class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        wire:model.live="filterModel" wire:change="updateRawDataFilters">
                                    <option value="">All Models</option>
                                    @foreach($this->getAvailableModels() as $model)
                                        <option value="{{ $model }}">{{ $model }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Active Filters Display -->
                @if($filterYear || $filterMonth || $filterStatus || $filterModel || !in_array('All', $selectedStatuses))
                    <div class="flex flex-wrap gap-2 p-3 rounded-lg bg-gray-50">
                        <span class="text-sm font-medium text-gray-600">Active Filters:</span>

                        @if(!in_array('All', $selectedStatuses))
                            @foreach($selectedStatuses as $status)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Status: {{ $status }}
                                    <button type="button" class="ml-1 text-green-600 hover:text-green-800"
                                            wire:click="updateStatusFilter({{ json_encode(array_values(array_diff($selectedStatuses, [$status]))) }})">×</button>
                                </span>
                            @endforeach
                        @endif

                        @if($filterYear)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Year: {{ $filterYear }}
                                <button type="button" class="ml-1 text-blue-600 hover:text-blue-800" wire:click="$set('filterYear', '')" wire:click="updateRawDataFilters">×</button>
                            </span>
                        @endif

                        @if($filterMonth)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                Month: {{ date('F', mktime(0, 0, 0, $filterMonth, 1)) }}
                                <button type="button" class="ml-1 text-purple-600 hover:text-purple-800" wire:click="$set('filterMonth', '')" wire:click="updateRawDataFilters">×</button>
                            </span>
                        @endif

                        @if($filterModel)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                Model: {{ $filterModel }}
                                <button type="button" class="ml-1 text-orange-600 hover:text-orange-800" wire:click="$set('filterModel', '')" wire:click="updateRawDataFilters">×</button>
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
    <style>
        /* Modern card styling */
        .month-section {
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .month-header {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            color: white;
            font-weight: 600;
            padding: 1rem 1.25rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .month-header:hover {
            background: linear-gradient(90deg, #1d4ed8, #2563eb);
        }

        .month-details {
            padding: 0;
            background-color: #fff;
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }

        /* Table styling */
        .month-table-container {
            overflow-x: auto;
            padding: 0.5rem;
        }

        .month-table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
            border: none;
        }

        .month-table th {
            background-color: #f8fafc;
            color: #334155;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .month-table td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.875rem;
            vertical-align: middle;
        }

        .month-table tbody tr:hover {
            background-color: #f1f5f9;
        }

        /* Special rows */
        .totals-row {
            background-color: #eff6ff;
            font-weight: 600;
        }

        .totals-row td {
            border-top: 2px solid #bfdbfe;
            border-bottom: 2px solid #bfdbfe;
        }

        /* Enhanced Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
            transition: all 0.3s ease;
        }

        .modal-content {
            background-color: white;
            border-radius: 0.75rem;
            max-width: 850px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            background: linear-gradient(to right, #f9fafb, #f3f4f6);
        }

        .modal-body {
            padding: 1.75rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            border-top: 1px solid #f3f4f6;
            background: linear-gradient(to right, #f9fafb, #f3f4f6);
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-title-icon {
            width: 1.75rem;
            height: 1.75rem;
            padding: 0.375rem;
            background-color: #e0f2fe;
            color: #0284c7;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: #374151;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            line-height: 1.5;
            transition: all 0.2s ease;
            background-color: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .form-input:hover {
            border-color: #93c5fd;
        }

        /* Section dividers in modal */
        .form-section {
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
        }

        .form-section-title {
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .form-section-icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        /* Form grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* Enhanced buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            gap: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: #9ca3af;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #6b7280;
            transform: translateY(-1px);
        }

        /* Close button */
        .modal-close-btn {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            background-color: #f3f4f6;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            background-color: #e5e7eb;
            color: #374151;
        }

        /* Rest of your existing styles */
        .btn-danger {
            background-color: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .btn-success {
            background-color: #10b981;
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .edit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            color: #3b82f6;
            background-color: #dbeafe;
            transition: all 0.2s ease;
        }

        .edit-btn:hover {
            background-color: #bfdbfe;
            transform: scale(1.05);
        }

        .delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            color: #ef4444;
            background-color: #fee2e2;
            transition: all 0.2s ease;
        }

        .delete-btn:hover {
            background-color: #fecaca;
            transform: scale(1.05);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .expand-icon {
            width: 1.5rem;
            height: 1.5rem;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            padding: 0.25rem;
            transition: transform 0.2s ease;
        }

        /* Stats box */
        .month-stats {
            display: flex;
            margin-left: 1rem;
            gap: 1rem;
        }

        .stat-box {
            padding: 0.25rem 0.75rem;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .stat-icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        /* Cell styling */
        .cell-feature {
            max-width: 250px;
        }

        .cell-number {
            text-align: center;
        }

        .cell-model {
            font-weight: 500;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .status-completed-order {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-completed-shipping {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-completed-delivery {
            background-color: #d1fae5;
            color: #065f46;
        }

        /* Status modal */
        .status-option {
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .status-option:hover {
            border-color: #93c5fd;
            background-color: #f0f9ff;
        }

        .status-option.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .status-icon {
            margin-right: 0.75rem;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
        }

        .status-update-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            color: #6366f1;
            background-color: #e0e7ff;
            transition: all 0.2s ease;
        }

        .status-update-btn:hover {
            background-color: #c7d2fe;
            transform: scale(1.05);
        }

        .action-buttons {
            display: flex;
            /* flex-direction: column;  */
            gap: 0.5rem;            /* Maintain the gap between buttons */
            align-items: left;    /* Center the buttons horizontally */
        }

        .action-buttons-raw {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;            /* Maintain the gap between buttons */
            align-items: left;    /* Center the buttons horizontally */
        }

        /* Make the buttons slightly smaller to fit better in a column */
        .edit-btn, .delete-btn, .status-update-btn {
            width: 1.75rem;
            height: 1.75rem;
        }

        /* Add tooltip for better usability */
        .action-button-wrapper {
            position: relative;
            display: inline-block;
        }

        .action-tooltip {
            visibility: hidden;
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 8px;
            background-color: rgba(55, 65, 81, 0.9);
            color: white;
            text-align: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            white-space: nowrap;
            z-index: 10;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .action-button-wrapper:hover .action-tooltip {
            visibility: visible;
            opacity: 1;
        }
        .months-row-wrapper {
            position: relative;
        }

        .months-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.5rem;
            overflow-x: auto;
            padding: 0.5rem 0;
            scrollbar-width: thin;
            -ms-overflow-style: none; /* IE and Edge */
        }

        .months-row::-webkit-scrollbar {
            height: 4px;
        }

        .months-row::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .months-row::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 20px;
        }

        .month-pill {
            flex: 1 0 auto;
            min-width: calc(100% / 12 - 0.5rem);
            max-width: calc(100% / 6);
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 2rem;
            padding: 0.75rem 1.25rem;
            color: #0284c7;
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0f2fe;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .month-pill:hover {
            background: linear-gradient(135deg, #bae6fd, #7dd3fc);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .month-pill.active {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: white;
            border-color: #0284c7;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(2, 132, 199, 0.4);
        }

        .month-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .month-count {
            background-color: rgba(253, 56, 56, 0.856);
            color: white;
            border-radius: 9999px;
            padding: 0.125rem 0.5rem;
            font-size: 0.75rem;
            min-width: 1.5rem;
            text-align: center;
        }

        .month-arrow {
            width: 1rem;
            height: 1rem;
            transition: transform 0.2s ease;
        }

        .month-arrow.rotated {
            transform: rotate(180deg);
        }

        /* For screens smaller than 768px */
        @media (max-width: 768px) {
            .month-pill {
                min-width: calc(100% / 6 - 0.5rem);
                padding: 0.5rem 0.75rem;
            }
        }

        /* For screens smaller than 640px */
        @media (max-width: 640px) {
            .month-pill {
                min-width: calc(100% / 3 - 0.5rem);
            }
        }

        .status-filter-container {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .status-filter-pill {
            border-radius: 1rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .status-filter-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .status-filter-pill.active {
            background-color: #10b981;
            color: white;
            border-color: #10b981;
        }

        .status-filter-pill.inactive {
            background-color: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .filter-indicator {
            display: inline-flex;
            align-items: center;
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #16a34a;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .filter-indicator-icon {
            width: 1rem;
            height: 1rem;
            margin-right: 0.25rem;
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Filter toggle button styling */
        .filter-toggle-btn {
            transition: all 0.2s ease;
            position: relative;
        }

        .filter-toggle-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Filter badge styling */
        .filter-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        /* Form group spacing for filters */
        .form-group {
            margin-bottom: 0;
        }

        /* Active filter indicators */
        .active-filters {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: 1px solid #e2e8f0;
        }
    </style>

    <!-- Modal for editing or creating items -->
    @if($isStatusModalOpen)
    <div class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 class="modal-title">
                    <div class="modal-title-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    Update Status: <span class="text-blue-600">{{ $statusModel }}</span>
                </h2>
                <button type="button" class="modal-close-btn" wire:click="closeStatusModal">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-4 text-gray-600">Select the current status for this model:</p>

                <div class="space-y-2">
                    <div class="status-option {{ $updatingStatus === 'Completed Shipping' ? 'selected' : '' }}" wire:click="$set('updatingStatus', 'Completed Shipping')">
                        <div class="status-icon bg-amber-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-amber-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium">Completed Shipping</div>
                            <div class="text-sm text-gray-500">Items have been shipped</div>
                        </div>
                    </div>

                    <div class="status-option {{ $updatingStatus === 'Completed Delivery' ? 'selected' : '' }}" wire:click="$set('updatingStatus', 'Completed Delivery')">
                        <div class="bg-green-100 status-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-green-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium">Completed Delivery</div>
                            <div class="text-sm text-gray-500">Items have been delivered to the destination</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeStatusModal">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" wire:click="updateStatus">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Update Status
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Your existing modal code -->
    @if($isModalOpen)
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">
                        @if($editingModel)
                            <div class="modal-title-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                </svg>
                            </div>
                            Edit Model: <span class="text-blue-600">{{ $editingModel }}</span>
                        @else
                            <div class="modal-title-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            Add New Order
                        @endif
                    </h2>
                    <button type="button" class="modal-close-btn" wire:click="closeModal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <!-- Model Information Section -->
                        <div class="form-section">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="model">Model Name*</label>
                                    <select id="model" class="form-input" wire:model.defer="editingData.model" required>
                                        <option value="">-- Select Model --</option>
                                        @foreach($this->getDeviceModels() as $modelName)
                                            <option value="{{ $modelName }}">{{ $modelName }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="qty">Quantity*</label>
                                    <input type="number" id="qty" class="form-input" wire:model.defer="editingData.qty" required min="1">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="order_no">Order No.</label>
                                    <input type="text" id="order_no" class="uppercase form-input" wire:model.defer="editingData.order_no"
                                        placeholder="Order reference number" style="text-transform: uppercase;">
                                </div>

                                {{-- <div class="form-group">
                                    <label class="form-label" for="languages">Languages*</label>
                                    <input type="text" id="languages" class="uppercase form-input" wire:model.defer="editingData.languages"
                                        placeholder="e.g. ENGLISH, SPANISH" style="text-transform: uppercase;" required>
                                </div> --}}
                            </div>
                        </div>

                        {{-- <div class="form-section">
                            <div class="form-section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="form-section-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                                Power Plug Configuration
                            </div>
                            <div class="form-grid" style= 'grid-template-columns: repeat(4, 1fr);'>
                                <div class="form-group">
                                    <label class="form-label" for="england">England Plug</label>
                                    <input type="number" id="england" class="form-input" wire:model.defer="editingData.england">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="america">America Plug</label>
                                    <input type="number" id="america" class="form-input" wire:model.defer="editingData.america">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="europe">Europe Plug</label>
                                    <input type="number" id="europe" class="form-input" wire:model.defer="editingData.europe">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="australia">Australia Plug</label>
                                    <input type="number" id="australia" class="form-input" wire:model.defer="editingData.australia">
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="border-bottom: none; margin-bottom: 0;">
                            <div class="form-section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="form-section-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                                </svg>
                                Serial Numbers & Order Details
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="sn_no_from">SN No. From</label>
                                    <input type="text" id="sn_no_from" class="form-input" wire:model.defer="editingData.sn_no_from" placeholder="Starting serial number">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="sn_no_to">SN No. To</label>
                                    <input type="text" id="sn_no_to" class="form-input" wire:model.defer="editingData.sn_no_to" placeholder="Ending serial number">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="po_no">PO No.</label>
                                    <input type="text" id="po_no" class="uppercase form-input" wire:model.defer="editingData.po_no"
                                        placeholder="Purchase order number" style="text-transform: uppercase;">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="balance_not_order">Balance Not Order</label>
                                    <input type="number" id="balance_not_order" class="form-input" wire:model.defer="editingData.balance_not_order">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="rfid_card_foc">RFID/MF Card (F.O.C)</label>
                                    <input type="number" id="rfid_card_foc" class="form-input" wire:model.defer="editingData.rfid_card_foc">
                                </div>
                            </div>
                        </div> --}}
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="saveModalData">
                        @if($editingModel)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                            </svg>
                            Update
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Create
                        @endif
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Only show the selected month's data -->
    @if($isRawView)
        <div class="mb-4 overflow-hidden bg-white rounded-lg shadow card">
            <!-- Results Count -->
            <div class="flex items-center justify-between px-4 py-3 border-b bg-gray-50">
                <div class="text-sm text-gray-600">
                    <span class="font-medium">{{ count($rawData) }}</span> records found
                    @if($filterYear || $filterMonth || $filterStatus || $filterModel)
                        <span class="text-blue-600">(filtered)</span>
                    @endif
                </div>
                <button type="button" class="btn btn-success" wire:click="openCreateModal({{ date('n') }})">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add New Order
                </button>
            </div>

            <!-- Raw Data Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Year</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Month</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Model</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Quantity</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Order Number</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Date Completed Order</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Date Completed Shipping</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Date Completed Delivery</th>
                            <th class="px-4 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($rawData as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['year'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $item['month_name'] }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['model'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ number_format($item['qty']) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $item['order_no'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                @php
                                    $statusClass = match($item['status']) {
                                        'Completed Order' => 'status-completed-order',
                                        'Completed Shipping' => 'status-completed-shipping',
                                        'Completed Delivery' => 'status-completed-delivery',
                                        default => ''
                                    };
                                @endphp
                                <span class="status-badge {{ $statusClass }}">
                                    {{ $item['status'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($item['date_completed_order'])
                                    <span class="px-2 py-1 text-blue-800 bg-blue-100 rounded-full">
                                        {{ \Carbon\Carbon::parse($item['date_completed_order'])->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($item['date_completed_shipping'])
                                    <span class="px-2 py-1 rounded-full text-amber-800 bg-amber-100">
                                        {{ \Carbon\Carbon::parse($item['date_completed_shipping'])->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($item['date_completed_delivery'])
                                    <span class="px-2 py-1 text-green-800 bg-green-100 rounded-full">
                                        {{ \Carbon\Carbon::parse($item['date_completed_delivery'])->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <!-- Single line action buttons -->
                                <div class="flex items-center gap-1">
                                    <div class="action-button-wrapper">
                                        <button type="button" class="status-update-btn" wire:click="openStatusModal({{ $item['month'] }}, '{{ $item['model'] . '_' . $item['id'] }}')">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <span class="action-tooltip">Update Status</span>
                                    </div>

                                    <div class="action-button-wrapper">
                                        <button type="button" class="edit-btn" wire:click="openEditModal({{ $item['month'] }}, '{{ $item['model'] . '_' . $item['id'] }}')">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <span class="action-tooltip">Edit</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mb-2 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                    <p class="text-lg font-medium">No records found</p>
                                    <p class="text-sm">Try adjusting your filters or add a new order</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="mb-8 months-row-wrapper">
            <div class="months-row">
                @foreach($months as $monthNum => $month)
                    <div class="month-pill {{ $selectedMonth === $monthNum ? 'active' : '' }}" wire:click="selectMonth({{ $monthNum }})">
                        <span>{{ substr($month['name'], 0, 3) }}</span>
                        <div class="month-indicator">
                            @if(count($purchaseData[$monthNum]) > 0)
                                <span class="month-count">{{ count($purchaseData[$monthNum]) }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Only show the selected month's data -->
        @if($selectedMonth !== null)
            <div class="mb-4 month-section">
                <div class="month-header">
                    <div>{{ $months[$selectedMonth]['name'] }}</div>
                    <div class="month-stats">
                        <div class="stat-box">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="stat-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            {{ count($purchaseData[$selectedMonth]) }} models
                        </div>
                        <div class="stat-box">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="stat-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            {{ $months[$selectedMonth]['totals']['qty'] }} items
                        </div>
                    </div>
                </div>
                <div class="month-details">
                    <div class="flex items-center justify-between px-4 py-3 bg-white">
                        <div class="text-sm text-gray-500">
                            {{ count($purchaseData[$selectedMonth]) }} models found
                        </div>
                        <button type="button" class="btn btn-success" wire:click="openCreateModal({{ $selectedMonth }})">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add New Order
                        </button>
                    </div>
                    <div class="month-table-container">
                        <table class="month-table">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Model</th>
                                    <th style="width: 9%; text-align: center;">Quantity</th>
                                    <th style="width: 20%;">Order Number</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 12%;">Date<br> Completed Order</th>
                                    <th style="width: 12%;">Date<br> Completed Shipping</th>
                                    <th style="width: 12%;">Date<br> Completed Delivery</th>
                                    <th style="width: 10%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Display existing models for the selected month -->
                                @foreach($purchaseData[$selectedMonth] as $uniqueKey => $data)
                                <tr>
                                    <td class="cell-model">{{ $data['model'] }}</td>
                                    <td class="cell-number">{{ number_format($data['qty']) }}</td>
                                    <td>{{ $data['order_no'] ?: '-' }}</td>
                                    <td>
                                        @if(!empty($data['status']))
                                            @php
                                                $statusClass = match($data['status']) {
                                                    'Completed Order' => 'status-completed-order',
                                                    'Completed Shipping' => 'status-completed-shipping',
                                                    'Completed Delivery' => 'status-completed-delivery',
                                                    default => ''
                                                };
                                            @endphp
                                            <span class="status-badge {{ $statusClass }}">
                                                {{ $data['status'] }}
                                            </span>
                                        @else
                                            <span class="italic text-gray-400">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($data['date_completed_order']))
                                            <span class="px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">
                                                {{ \Carbon\Carbon::parse($data['date_completed_order'])->format('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($data['date_completed_shipping']))
                                            <span class="px-2 py-1 text-xs rounded-full text-amber-800 bg-amber-100">
                                                {{ \Carbon\Carbon::parse($data['date_completed_shipping'])->format('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($data['date_completed_delivery']))
                                            <span class="px-2 py-1 text-xs text-green-800 bg-green-100 rounded-full">
                                                {{ \Carbon\Carbon::parse($data['date_completed_delivery'])->format('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <div class="action-button-wrapper">
                                                <button type="button" class="status-update-btn" wire:click="openStatusModal({{ $selectedMonth }}, '{{ $uniqueKey }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                                <span class="action-tooltip">Update Status</span>
                                            </div>
                                            <div class="action-button-wrapper">
                                                <button type="button" class="edit-btn" wire:click="openEditModal({{ $selectedMonth }}, '{{ $uniqueKey }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                    </svg>
                                                </button>
                                                <span class="action-tooltip">Edit</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
