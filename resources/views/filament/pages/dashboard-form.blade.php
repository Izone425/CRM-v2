<x-filament::page>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        /* Hide content until Livewire is fully initialized */
        [x-cloak],
        .livewire-loading {
            display: none !important;
        }

        /* Add a loading state class */
        .tabs-container {
            opacity: 0;
            transition: opacity 0.1s ease-in-out;
        }

        .tabs-container.initialized {
            opacity: 1;
        }

        /* Progressive loading animations */
        .dashboard-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, transparent 37%, #f0f0f0 63%);
            background-size: 400% 100%;
            animation: shimmer 1.5s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% { background-position: 100% 50%; }
            100% { background-position: -100% 50%; }
        }

        .content-loading {
            min-height: 200px;
            border-radius: 8px;
        }

        /* Optimized badge styles */
        .badge-container {
            display: inline-flex;
            align-items: center;
            background: #ef4444;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
            padding: 0.125rem 0.375rem;
            min-width: 1.25rem;
            height: 1.25rem;
            justify-content: center;
        }
    </style>

    @php
        // Get cached counts for better performance
        $cachedCounts = $this->getCachedCounts();
        $managerTotal = $cachedCounts['manager_total'] ?? 0;
        $adminSoftwareTotal = $cachedCounts['admin_software_total'] ?? 0;
        $adminHardwareTotal = $cachedCounts['admin_hardware_total'] ?? 0;
        $renewalCounts = $cachedCounts['admin_renewal_counts'] ?? [];

        // Get other counts more efficiently
        $adminHrdfAttLogTotal = Cache::remember('admin_hrdf_att_log_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\AdminHRDFAttendanceLog\HrdfAttLogNewTable::class)
                    ->getNewHrdfAttendanceLogs()
                    ->count();
            } catch (Exception $e) {
                return 0;
            }
        });

        $adminGeneralTotal = Cache::remember('admin_general_' . auth()->id(), 300, function() {
            return \App\Models\InternalTicket::where('status', 'new')->count();
        });

        $adminUSDInvoiceTotal = Cache::remember('admin_usd_invoice_' . auth()->id(), 300, function() {
            return DB::connection('frontenddb')
                ->table('crm_invoice_details')
                ->whereIn('f_currency', ['MYR', 'USD'])
                ->where('f_status', 0)
                ->whereNull('f_auto_count_inv')
                ->where('f_id', '>', '0000040131')
                ->where('f_id', '!=', '0000042558')
                ->distinct('f_invoice_no')
                ->count('f_invoice_no');
        });

        // Set default values for counts that will be loaded lazily
        $followUpTodayMYR = $renewalCounts['followUpTodayMYR'] ?? 0;
        $followUpOverdueMYR = $renewalCounts['followUpOverdueMYR'] ?? 0;
        $followUpTodayUSD = $renewalCounts['followUpTodayUSD'] ?? 0;
        $followUpOverdueUSD = $renewalCounts['followUpOverdueUSD'] ?? 0;

        // Other counts set to 0 initially (will be loaded on demand)
        $followUpFutureMYR = 0;
        $followUpAllMYR = 0;
        $followUpTodayMYRNonReseller = 0;
        $followUpOverdueMYRNonReseller = 0;
        $followUpFutureMYRNonReseller = 0;
        $followUpAllMYRNonReseller = 0;
        $followUpFutureUSD = 0;
        $followUpAllUSD = 0;
        $followUpTodayUSDNonReseller = 0;
        $followUpOverdueUSDNonReseller = 0;
        $followUpFutureUSDNonReseller = 0;
        $followUpAllUSDNonReseller = 0;
        $followUpTodayMYRv2 = 0;
        $followUpOverdueMYRv2 = 0;
        $followUpFutureMYRv2 = 0;
        $followUpAllMYRv2 = 0;
        $followUpTodayMYRv2NonReseller = 0;
        $followUpOverdueMYRv2NonReseller = 0;
        $followUpFutureMYRv2NonReseller = 0;
        $followUpAllMYRv2NonReseller = 0;
        $followUpTodayUSDv2 = 0;
        $followUpOverdueUSDv2 = 0;
        $followUpFutureUSDv2 = 0;
        $followUpAllUSDv2 = 0;
        $followUpTodayUSDv2NonReseller = 0;
        $followUpOverdueUSDv2NonReseller = 0;
        $followUpFutureUSDv2NonReseller = 0;
        $followUpAllUSDv2NonReseller = 0;

        // Get minimal counts needed for initial display
        $adminHeadcountTotal = Cache::remember('admin_headcount_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\AdminHeadcountDashboard\HeadcountNewTable::class)
                    ->getNewHeadcountHandovers()
                    ->count();
            } catch (Exception $e) {
                return 0;
            }
        });

        $adminHrdfTotal = Cache::remember('admin_hrdf_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\AdminHRDFDashboard\HrdfNewTable::class)
                    ->getNewHrdfHandovers()
                    ->count();
            } catch (Exception $e) {
                return 0;
            }
        });

        $adminResellerTotal = Cache::remember('admin_reseller_' . auth()->id(), 300, function() {
            return \App\Models\ResellerHandover::whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license'])
                ->count();
        });

        // Hardware V2 counts - only get what's needed
        $newTaskCount = Cache::remember('hardware_v2_new_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2NewTable::class)
                    ->getNewHardwareHandovers()
                    ->count();
            } catch (Exception $e) {
                return 0;
            }
        });

        $pendingStockCount = Cache::remember('hardware_v2_pending_stock_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingStockTable::class)
                    ->getHardwareHandoverCount();
            } catch (Exception $e) {
                return 0;
            }
        });

        $pendingCourierCount = Cache::remember('hardware_v2_pending_courier_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingCourierTable::class)
                    ->getHardwareHandoverCount();
            } catch (Exception $e) {
                return 0;
            }
        });

        $pendingAdminPickUpCount = Cache::remember('hardware_v2_pending_admin_pickup_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingAdminSelfPickUpTable::class)
                    ->getHardwareHandoverCount();
            } catch (Exception $e) {
                return 0;
            }
        });

        $pendingExternalInstallationCount = Cache::remember('hardware_v2_pending_external_installation_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingExternalInstallationTable::class)
                    ->getHardwareHandoverCount();
            } catch (Exception $e) {
                return 0;
            }
        });

        $pendingInternalInstallationCount = Cache::remember('hardware_v2_pending_internal_installation_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingInternalInstallationTable::class)
                    ->getHardwareHandoverCount();
            } catch (Exception $e) {
                return 0;
            }
        });

        $initialStageTotal = $newTaskCount + $pendingStockCount + $pendingCourierCount + $pendingAdminPickUpCount + $pendingExternalInstallationCount + $pendingInternalInstallationCount;

        // Software V2 counts - simplified
        $softwareV2NewCount = Cache::remember('software_v2_new_' . auth()->id(), 300, function() {
            try {
                return app(\App\Livewire\SalespersonDashboard\SoftwareHandoverV2New::class)
                    ->getNewSoftwareHandovers()
                    ->count();
            } catch (Exception $e) {
                return 0;
            }
        });

        $softwareV2PendingKickOffCount = 0;
        $softwareV2PendingLicenseCount = 0;
        $adminSoftwareV2Total = $softwareV2NewCount;

        // Calculate minimal total for initial display
        $adminTotal = $adminSoftwareTotal + $adminSoftwareV2Total + $adminHeadcountTotal +
                     $adminHrdfTotal + $initialStageTotal + $adminUSDInvoiceTotal +
                     $adminHrdfAttLogTotal + $adminGeneralTotal;
    @endphp

    <div
        x-data="{
            initialized: false,
            currentTab: '{{ $currentDashboard }}',
            loadingStage: 'initial',
            contentLoaded: false,
            init() {
                document.querySelector('.tabs-container').classList.add('livewire-loading');

                // Progressive loading
                setTimeout(() => {
                    this.loadingStage = 'layout';
                    this.initialized = true;
                    document.querySelector('.tabs-container').classList.remove('livewire-loading');
                    document.querySelector('.tabs-container').classList.add('initialized');
                }, 50);

                // Load content after layout is ready
                setTimeout(() => {
                    this.loadingStage = 'content';
                    this.contentLoaded = true;
                }, 200);
            }
        }"
        x-init="init()"
        class="tabs-container"
        :class="initialized ? 'initialized' : ''"
    >
        <!-- Your existing tab buttons, but add x-cloak to initially hide them -->
        <div x-cloak x-show="initialized">
            @if (auth()->user()->role_id == 1)
                {{-- Common heading for all role_id=1 users --}}
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                    @if (auth()->user()->additional_role == 1)
                        <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px;">
                            <button
                                wire:click="toggleDashboard('LeadOwner')"
                                style="
                                    padding: 10px 15px;
                                    font-size: 14px;
                                    font-weight: bold;
                                    border: none;
                                    border-radius: 20px;
                                    background: {{ $currentDashboard === 'LeadOwner' ? '#431fa1' : 'transparent' }};
                                    color: {{ $currentDashboard === 'LeadOwner' ? '#ffffff' : '#555' }};
                                    cursor: pointer;
                                "
                            >
                                Lead Owner
                            </button>

                            <div class="admin-dropdown admin-dropdown-3" id="adminDropdown2" style="position: relative; display: inline-block;">
                                <button
                                    class="admin-dropdown-button"
                                    style="
                                        padding: 10px 15px;
                                        font-size: 14px;
                                        font-weight: bold;
                                        border: none;
                                        border-radius: 20px;
                                        background: {{ in_array($currentDashboard, ['MainAdminDashboard', 'SoftwareAdmin', 'HardwareAdmin', 'HardwareAdminV2', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalEndUser', 'AdminRenewalv2', 'AdminHRDF', 'AdminHRDFAttLog', 'AdminHeadcount']) ? '#431fa1' : 'transparent' }};
                                        color: {{ in_array($currentDashboard, ['MainAdminDashboard', 'SoftwareAdmin', 'HardwareAdmin', 'HardwareAdminV2', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalEndUser', 'AdminRenewalv2', 'AdminHRDF', 'AdminHRDFAttLog', 'AdminHeadcount']) ? '#ffffff' : '#555' }};
                                        cursor: pointer;
                                        display: flex;
                                        align-items: center;
                                        gap: 4px;
                                    "
                                >
                                    Admin <i class="fas fa-caret-down" style="font-size: 12px;"></i>
                                </button>

                                <!-- This is the bridge element that covers the gap -->
                                <div class="dropdown-bridge" style="
                                    position: absolute;
                                    height: 20px;
                                    left: 0;
                                    right: 0;
                                    bottom: -10px;
                                    background: transparent;
                                    z-index: 10;
                                "></div>

                                <div class="admin-dropdown-content" style="
                                    display: none;
                                    position: absolute;
                                    background-color: white;
                                    min-width: 250px;
                                    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                    z-index: 1000;
                                    border-radius: 6px;
                                    overflow: hidden;
                                    top: 100%; /* Position at the bottom of the button */
                                    left: 0;
                                    margin-top: 5px; /* Add a small gap */
                                ">
                                    <button
                                        wire:click="toggleDashboard('MainAdminDashboard')"
                                        style="
                                            display: block;
                                            width: 250px;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'MainAdminDashboard' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Admin - Dashboard
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('SoftwareAdmin')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'SoftwareAdmin' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Admin - Software
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('HardwareAdminV2')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'HardwareAdminV2' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Admin - Hardware v2
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminHeadcount')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminHeadcount' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Admin - Headcount
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminHRDFAttLog')"
                                        style="
                                            display: flex;
                                            justify-content: space-between;
                                            align-items: center;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminHRDFAttLog' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        <span>Admin - HRDF Att Log</span>
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminHRDF')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminHRDF' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Admin - HRDF Claim
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminRepair')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminRepair' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Admin - Onsite Repair
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('Debtor')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'Debtor' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Admin - InHouse Repair
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminRenewalv1')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminRenewalv1' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Renewal v1 – Reseller
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminRenewalEndUser')"
                                        onclick="console.log('AdminRenewalEndUser clicked')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminRenewalEndUser' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Renewal v1 – End User
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <br>

                {{-- Two-column grid for all role_id=1 users --}}
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @if (auth()->user()->additional_role == 1)
                        @if ($currentDashboard === 'LeadOwner')
                            @include('filament.pages.leadowner')
                        @elseif ($currentDashboard === 'SoftwareAdmin')
                            @include('filament.pages.softwarehandover')
                        @elseif ($currentDashboard === 'HardwareAdmin')
                            @include('filament.pages.hardwarehandover')
                        @elseif ($currentDashboard === 'HardwareAdminV2')
                            @include('filament.pages.hardwarehandoverv2')
                        @elseif ($currentDashboard === 'AdminRepair')
                            @include('filament.pages.adminrepair')
                        @endif
                    @else
                        <!-- Regular Lead Owner view for role_id=1 users without additional_role=1 -->
                        @include('filament.pages.leadowner')
                    @endif
                </div>
            @elseif (auth()->user()->role_id == 1 && auth()->user()->additional_role == 2)
                {{-- Admin Repair Dashboard for role_id=1 with additional_role=2 --}}
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Repair Admin Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.implementer')
                </div>
            @elseif (auth()->user()->role_id == 2)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.salesperson')
                </div>
            @elseif(auth()->user()->role_id == 5)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Implementer Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center mb-6">
                        <div>
                            <select
                                wire:model.live="selectedUser"
                                id="userFilter"
                                class="border-gray-300 rounded-md shadow-sm"
                            >
                                @if(auth()->id() == 26)
                                    <option value="all-implementer">All Implementers</option>
                                    <option value="{{ auth()->id() }}">Dashboard</option>
                                @else
                                    <option value="{{ auth()->id() }}">Dashboard</option>
                                    <option value="all-implementer">All Implementers</option>
                                @endif

                                <optgroup label="Implementer">
                                    @foreach ($users->whereIn('role_id', [4,5])->where('id', '!=', auth()->id()) as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.implementer')
                </div>
            @elseif (auth()->user()->role_id == 4)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Implementer Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.implementer')
                </div>
            @elseif (auth()->user()->role_id == 10)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Finance Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.finance')
                </div>
            @elseif (auth()->user()->role_id == 9)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Technician Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Add toggle buttons for Technician with Hardware Badge -->
                    <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px;">
                        <button
                            wire:click="toggleDashboard('Technician')"
                            style="
                                padding: 10px 15px;
                                font-size: 14px;
                                font-weight: bold;
                                border: none;
                                border-radius: 20px;
                                background: {{ $currentDashboard === 'Technician' ? '#431fa1' : 'transparent' }};
                                color: {{ $currentDashboard === 'Technician' ? '#ffffff' : '#555' }};
                                cursor: pointer;
                            "
                        >
                            Technician Dashboard
                        </button>

                        <button
                            wire:click="toggleDashboard('HardwareAdminV2')"
                            style="
                                padding: 10px 15px;
                                font-size: 14px;
                                font-weight: bold;
                                border: none;
                                border-radius: 20px;
                                background: {{ $currentDashboard === 'HardwareAdminV2' ? '#431fa1' : 'transparent' }};
                                color: {{ $currentDashboard === 'HardwareAdminV2' ? '#ffffff' : '#555' }};
                                cursor: pointer;
                                display: flex;
                                align-items: center;
                                gap: 4px;
                            "
                        >
                            Admin Hardware
                            @if($pendingInternalInstallationCount > 0)
                                <span style="
                                    background: #ef4444;
                                    color: white;
                                    border-radius: 12px;
                                    padding: 2px 8px;
                                    font-size: 12px;
                                    font-weight: bold;
                                    min-width: 20px;
                                    text-align: center;
                                    margin-left: 4px;
                                ">{{ $pendingInternalInstallationCount }}</span>
                            @endif
                        </button>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @if ($currentDashboard === 'HardwareAdminV2')
                        @include('filament.pages.hardwarehandoverv2')
                    @else
                        @include('filament.pages.technician')
                    @endif
                </div>
            @elseif (auth()->user()->role_id == 3)
                <div class="space-y-4">
                    <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                        <div class="flex items-center space-x-2" x-data="{ showRefresh: false }"
                            @mouseenter="showRefresh = true"
                            @mouseleave="showRefresh = false">
                            <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">
                                Dashboard
                            </h1>
                            <div class="relative ml-2" x-cloak>
                                <button
                                    x-show="showRefresh"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 transform scale-100"
                                    x-transition:leave-end="opacity-0 transform scale-95"
                                    wire:click="refreshTable"
                                    wire:loading.attr="disabled"
                                    class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    title="Last refreshed: {{ $lastRefreshTime }}"
                                >
                                    <span wire:loading.remove wire:target="refreshTable">
                                        <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </span>
                                    <span wire:loading wire:target="refreshTable">
                                        <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center mb-6">
                            <div>
                                <select
                                    wire:model.live="selectedUser"
                                    id="userFilter"
                                    class="border-gray-300 rounded-md shadow-sm"
                                >
                                    <option value="{{ auth()->id() }}">Dashboard</option>

                                    <optgroup label="All Groups">
                                        <option value="all-lead-owners">All Lead Owners</option>
                                        <option value="all-implementer">All Implementer</option>
                                        <option value="all-salespersons">All Salespersons</option>
                                    </optgroup>

                                    <optgroup label="Lead Owner">
                                        @foreach ($users->where('role_id', 1) as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>

                                    <optgroup label="Implementer">
                                        @foreach ($users->whereIn('role_id', [4, 5]) as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>

                                    <optgroup label="Salesperson">
                                        @foreach ($users->where('role_id', 2) as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>

                                    <optgroup label="Technician">
                                        @foreach ($users->where('role_id', 9) as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                            &nbsp;&nbsp;
                            <!-- Toggle Buttons (conditionally shown) -->
                            @if ($selectedUser == 1 || $selectedUser == 14 || $selectedUser == null)
                                <div style="display: flex; align-items: center;">
                                    <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px;">
                                        <button
                                            wire:click="toggleDashboard('Manager')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Manager' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Manager' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                                display: flex;
                                                align-items: center;
                                                gap: 4px;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Manager')">
                                                Manager
                                                @if($managerTotal > 0)
                                                    <span style="
                                                        background: #ef4444;
                                                        color: white;
                                                        border-radius: 12px;
                                                        padding: 2px 8px;
                                                        font-size: 12px;
                                                        font-weight: bold;
                                                        min-width: 20px;
                                                        text-align: center;
                                                        margin-left: 4px;
                                                    ">{{ $managerTotal }}</span>
                                                @endif
                                            </span>
                                            <span wire:loading wire:target="toggleDashboard('Manager')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>

                                        @if (auth()->user()->role_id == 3 && auth()->user()->additional_role == 1)
                                        <!-- Lead Owner Button -->
                                        <button
                                            wire:click="toggleDashboard('LeadOwner')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'LeadOwner' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'LeadOwner' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('LeadOwner')">Lead Owner</span>
                                            <span wire:loading wire:target="toggleDashboard('LeadOwner')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>

                                        <!-- Salesperson Button -->
                                        <button
                                            wire:click="toggleDashboard('Salesperson')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Salesperson' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Salesperson' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Salesperson')">Salesperson</span>
                                            <span wire:loading wire:target="toggleDashboard('Salesperson')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>
                                        @endif

                                        <!-- Admin Dropdown -->
                                        <div class="admin-dropdown admin-dropdown-1" id="adminDropdown1" style="position: relative; display: inline-block;">
                                            <button
                                                class="admin-dropdown-button"
                                                style="
                                                    padding: 10px 15px;
                                                    font-size: 14px;
                                                    font-weight: bold;
                                                    border: none;
                                                    border-radius: 20px;
                                                    background: {{ in_array($currentDashboard, ['MainAdminDashboard','SoftwareAdmin', 'SoftwareAdminV2', 'HardwareAdmin', 'HardwareAdminV2', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalEndUser', 'AdminRenewalv2', 'AdminHRDF', 'AdminHRDFAttLog', 'AdminHeadcount', 'AdminGeneral']) ? '#431fa1' : 'transparent' }};
                                                    color: {{ in_array($currentDashboard, ['MainAdminDashboard','SoftwareAdmin','SoftwareAdminV2', 'HardwareAdmin', 'HardwareAdminV2', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalEndUser', 'AdminRenewalv2', 'AdminHRDF', 'AdminHRDFAttLog', 'AdminHeadcount', 'AdminGeneral']) ? '#ffffff' : '#555' }};
                                                    cursor: pointer;
                                                    display: flex;
                                                    align-items: center;
                                                    gap: 4px;
                                                "
                                            >
                                                Admin
                                                @if($adminTotal > 0)
                                                    <span style="
                                                        background: #ef4444;
                                                        color: white;
                                                        border-radius: 12px;
                                                        padding: 2px 8px;
                                                        font-size: 12px;
                                                        font-weight: bold;
                                                        min-width: 20px;
                                                        text-align: center;
                                                    ">{{ $adminTotal }}</span>
                                                @endif
                                                <i class="fas fa-caret-down" style="font-size: 12px;"></i>
                                            </button>

                                            <!-- This is the bridge element that covers the gap -->
                                            <div class="dropdown-bridge" style="
                                                position: absolute;
                                                height: 20px;
                                                left: 0;
                                                right: 0;
                                                bottom: -10px;
                                                background: transparent;
                                                z-index: 10;
                                            "></div>

                                            <div class="admin-dropdown-content" style="
                                                display: none;
                                                position: absolute;
                                                background-color: white;
                                                min-width: 250px;
                                                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                                z-index: 1000;
                                                border-radius: 6px;
                                                overflow: hidden;
                                                top: 100%;
                                                left: 0;
                                                margin-top: 5px;
                                            ">
                                                <button
                                                    wire:click="toggleDashboard('AdminGeneral')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminGeneral' ? '#f3f3f3' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminGeneral')">Admin - General</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminGeneral')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminGeneralTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminGeneralTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('SoftwareAdmin')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'SoftwareAdmin' ? '#f3f3f3' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('SoftwareAdmin')">Admin - Software v1</span>
                                                    <span wire:loading wire:target="toggleDashboard('SoftwareAdmin')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminSoftwareTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminSoftwareTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('HardwareAdminV2')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'HardwareAdminV2' ? '#f3f3f3' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('HardwareAdminV2')">Admin - Hardware v2</span>
                                                    <span wire:loading wire:target="toggleDashboard('HardwareAdminV2')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($initialStageTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $initialStageTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('AdminHeadcount')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminHeadcount' ? '#f3f3f3' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminHeadcount')">Admin - Headcount</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminHeadcount')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminHeadcountTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminHeadcountTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('AdminHRDFAttLog')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminHRDFAttLog' ? '#f3f3f3' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminHRDFAttLog')">Admin - HRDF Att Log</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminHRDFAttLog')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminHrdfAttLogTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminHrdfAttLogTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('AdminHRDF')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminHRDF' ? '#f3f3f3' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminHRDF')">Admin - HRDF Claim</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminHRDF')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminHrdfTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminHrdfTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('AdminReseller')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminReseller' ? '#f3f3f3' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminReseller')">Admin - Reseller</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminReseller')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminResellerTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminResellerTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('AdminUSDInvoice')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminUSDInvoice' ? '#f3f3f3' : 'white' }};
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminUSDInvoice')">Admin - MYR/USD Invoice</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminUSDInvoice')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminUSDInvoiceTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminUSDInvoiceTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('AdminRepair')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: block;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminRepair' ? '#f3f3f3' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminRepair')">Admin - Onsite Repair</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminRepair')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('Debtor')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: block;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'Debtor' ? '#f3f3f3' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('Debtor')">Admin - InHouse Repair</span>
                                                    <span wire:loading wire:target="toggleDashboard('Debtor')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                </button>
                                            </div>
                                        </div>

                                        @if (auth()->user()->role_id == 3 && auth()->user()->additional_role == 1)
                                        <!-- Finance Button -->
                                        <button
                                            wire:click="toggleDashboard('Finance')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Finance' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Finance' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Finance')">Finance</span>
                                            <span wire:loading wire:target="toggleDashboard('Finance')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>

                                        <!-- Trainer Button -->
                                        <button
                                            wire:click="toggleDashboard('Trainer')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Trainer' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Trainer' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Trainer')">Trainer</span>
                                            <span wire:loading wire:target="toggleDashboard('Trainer')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>

                                        <!-- Implementer Button -->
                                        <button
                                            wire:click="toggleDashboard('Implementer')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Implementer' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Implementer' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Implementer')">Implementer</span>
                                            <span wire:loading wire:target="toggleDashboard('Implementer')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>

                                        <!-- Support Button -->
                                        <button
                                            wire:click="toggleDashboard('Support')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Support' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Support' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Support')">Support</span>
                                            <span wire:loading wire:target="toggleDashboard('Support')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>
                                        @endif

                                        <!-- Technician Button -->
                                        <button
                                            wire:click="toggleDashboard('Technician')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Technician' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Technician' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Technician')">Technician</span>
                                            <span wire:loading wire:target="toggleDashboard('Technician')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <br>
                <div class="grid grid-cols-1 gap-6">
                    @if ($selectedUserRole == 1)
                        @if (isset($selectedUserModel) && $selectedUserModel && $selectedUserModel->role_id == 1 && $selectedUserModel->additional_role == 1)
                            @if ($currentDashboard === 'LeadOwner')
                                @include('filament.pages.leadowner')
                            @elseif ($currentDashboard === 'SoftwareHandover')
                                @include('filament.pages.softwarehandover')
                            @elseif ($currentDashboard === 'HardwareHandover')
                                @include('filament.pages.hardwarehandover')
                            @else
                                @include('filament.pages.leadowner')
                            @endif
                        @else
                            @include('filament.pages.leadowner')
                        @endif
                    @elseif ($selectedUserRole == 2)
                        @include('filament.pages.salesperson')
                    @elseif ($selectedUserRole == 3)
                        @include('filament.pages.manager')
                    @elseif ($selectedUserRole == 4 || $selectedUserRole == 5)
                        @include('filament.pages.implementer')
                    @elseif ($selectedUserRole == 9)
                        @include('filament.pages.technician')
                    @else
                        @if ($currentDashboard === 'LeadOwner')
                            @include('filament.pages.leadowner')
                        @elseif ($currentDashboard === 'Salesperson')
                            @include('filament.pages.salesperson')
                        @elseif ($currentDashboard === 'Manager')
                            @include('filament.pages.manager')
                        @elseif ($currentDashboard === 'MainAdminDashboard')
                            @include('filament.pages.admin-main-dashboard')
                        @elseif ($currentDashboard === 'SoftwareHandover')
                            @include('filament.pages.softwarehandover')
                        @elseif ($currentDashboard === 'HardwareHandover')
                            @include('filament.pages.hardwarehandover')
                        @elseif ($currentDashboard === 'AdminGeneral')
                            @include('filament.pages.admingeneral')
                        @elseif ($currentDashboard === 'AdminRepair')
                            @include('filament.pages.adminrepair')
                        {{-- @elseif ($currentDashboard === 'AdminRenewalv1')
                            @include('filament.pages.adminrenewal')
                        @elseif ($currentDashboard === 'AdminRenewalEndUser')
                            @include('filament.pages.adminrenewalnonreseller') --}}
                        @elseif ($currentDashboard === 'AdminHRDF')
                            @include('filament.pages.adminhrdf')
                        @elseif ($currentDashboard === 'AdminReseller')
                            @include('filament.pages.admin-reseller')
                        @elseif ($currentDashboard === 'AdminHRDFAttLog')
                            @include('filament.pages.adminhrdfattlog')
                        @elseif ($currentDashboard === 'AdminHeadcount')
                            @include('filament.pages.adminheadcount')
                        @elseif ($currentDashboard === 'SoftwareAdmin')
                            @include('filament.pages.softwarehandover')
                        @elseif ($currentDashboard === 'SoftwareAdminV2')
                            @include('filament.pages.softwarehandoverv2')
                        @elseif ($currentDashboard === 'Debtor')
                            {{-- @include('filament.pages.admindebtor') --}}
                        @elseif ($currentDashboard === 'HardwareAdminV2')
                            @include('filament.pages.hardwarehandoverv2')
                        @elseif ($currentDashboard === 'Finance')
                            @include('filament.pages.finance')
                        @elseif ($currentDashboard === 'Trainer')
                            {{-- @include('filament.pages.trainer') --}}
                        @elseif ($currentDashboard === 'Implementer')
                            @include('filament.pages.implementer')
                        @elseif ($currentDashboard === 'Support')
                            {{-- @include('filament.pages.support') --}}
                        @elseif ($currentDashboard === 'Technician')
                            @include('filament.pages.technician')
                        @else
                            @include('filament.pages.manager')
                        @endif
                    @endif
                </div>
            @endif
            <!-- JavaScript for dropdown behavior -->
            <script>
                // Function to initialize all dropdowns
                function initializeDropdowns() {
                    const adminDropdowns = document.querySelectorAll('.admin-dropdown');

                    // Don't clear existing event listeners on wire:click elements
                    adminDropdowns.forEach(function(dropdown) {
                        const button = dropdown.querySelector('.admin-dropdown-button');

                        // Only replace non-wire elements
                        if (button && !button.hasAttribute('wire:click')) {
                            const newButton = button.cloneNode(true);
                            if (button.parentNode) {
                                button.parentNode.replaceChild(newButton, button);
                            }
                        }
                    });

                    // Re-attach event listeners
                    adminDropdowns.forEach(function(dropdown) {
                        const button = dropdown.querySelector('.admin-dropdown-button');
                        const content = dropdown.querySelector('.admin-dropdown-content');
                        const bridge = dropdown.querySelector('.dropdown-bridge');

                        if (button && content) {
                            // Show dropdown on mouseenter for button
                            button.addEventListener('mouseenter', function() {
                                content.style.display = 'block';
                            });

                            // Keep dropdown open when hovering over dropdown content
                            content.addEventListener('mouseenter', function() {
                                content.style.display = 'block';
                            });

                            // Keep dropdown open when hovering over bridge
                            if (bridge) {
                                bridge.addEventListener('mouseenter', function() {
                                    content.style.display = 'block';
                                });
                            }

                            // Hide dropdown when mouse leaves entire component
                            dropdown.addEventListener('mouseleave', function() {
                                content.style.display = 'none';
                            });

                            // MODIFIED: Don't prevent default for the button - allow events to bubble
                            button.addEventListener('click', function() {
                                if (content.style.display === 'block') {
                                    content.style.display = 'none';
                                } else {
                                    // Close all other dropdowns first
                                    document.querySelectorAll('.admin-dropdown-content').forEach(function(otherContent) {
                                        if (otherContent !== content) {
                                            otherContent.style.display = 'none';
                                        }
                                    });
                                    content.style.display = 'block';
                                }
                            });
                        }

                        // Add specific handling for menu items with wire:click
                        const menuItems = dropdown.querySelectorAll('.admin-dropdown-content button[wire\\:click]');
                        menuItems.forEach(function(item) {
                            // Don't replace wire:click elements, just add a simple click handler
                            // that closes the dropdown without interfering with Livewire
                            item.addEventListener('click', function(e) {
                                // Allow the wire:click to execute first, then close dropdown
                                setTimeout(function() {
                                    content.style.display = 'none';
                                }, 100);
                            });
                        });
                    });

                    // Only close dropdowns when clicking outside (but not on dropdown menu items)
                    document.addEventListener('click', function(event) {
                        // Check if the click was on a wire:click element in a dropdown
                        const clickedWireElement = event.target.closest('button[wire\\:click]');
                        const clickedInDropdown = event.target.closest('.admin-dropdown');

                        if (clickedWireElement && clickedInDropdown) {
                            // For wire:click elements inside dropdowns, just close the dropdown after a delay
                            setTimeout(function() {
                                const dropdown = clickedWireElement.closest('.admin-dropdown');
                                const content = dropdown.querySelector('.admin-dropdown-content');
                                if (content) {
                                    content.style.display = 'none';
                                }
                            }, 150);
                            return;
                        }

                        // For other clicks outside dropdowns, close all dropdowns
                        adminDropdowns.forEach(function(dropdown) {
                            if (!dropdown.contains(event.target)) {
                                const content = dropdown.querySelector('.admin-dropdown-content');
                                if (content) {
                                    content.style.display = 'none';
                                }
                            }
                        });
                    }, { capture: true });
                }

                // Initialize on DOMContentLoaded
                document.addEventListener('DOMContentLoaded', initializeDropdowns);

                // Re-initialize on Livewire updates (less frequently)
                document.addEventListener('livewire:navigated', initializeDropdowns);
                document.addEventListener('livewire:load', initializeDropdowns);

                // Only re-initialize when specifically needed, not on every update
                let initTimeout;
                document.addEventListener('livewire:update', function() {
                    clearTimeout(initTimeout);
                    initTimeout = setTimeout(initializeDropdowns, 500);
                });
            </script>
        </div>
    </div>
</x-filament::page>
