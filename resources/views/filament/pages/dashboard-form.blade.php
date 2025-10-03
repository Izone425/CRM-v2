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
    </style>

    <div
        x-data="{
            initialized: false,
            currentTab: '{{ $currentDashboard }}',
            init() {
                document.querySelector('.tabs-container').classList.add('livewire-loading');
                setTimeout(() => {
                    this.initialized = true;
                    document.querySelector('.tabs-container').classList.remove('livewire-loading');
                    document.querySelector('.tabs-container').classList.add('initialized');
                }, 50);
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
                                        background: {{ in_array($currentDashboard, ['MainAdminDashboard', 'SoftwareAdmin', 'HardwareAdmin', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalv2', 'AdminHRDF', 'AdminHeadcount']) ? '#431fa1' : 'transparent' }};
                                        color: {{ in_array($currentDashboard, ['MainAdminDashboard', 'SoftwareAdmin', 'HardwareAdmin', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalv2', 'AdminHRDF', 'AdminHeadcount']) ? '#ffffff' : '#555' }};
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
                                    z-index: 999;
                                "></div>

                                <div class="admin-dropdown-content" style="
                                    display: none;
                                    position: absolute;
                                    background-color: white;
                                    min-width: 200px;
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
                                            width: 200px;
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
                                        wire:click="toggleDashboard('HardwareAdmin')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'HardwareAdmin' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Admin - Hardware
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
                                        Admin - HRDF
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
                                        Admin - Renewal v1
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminRenewalv2')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminRenewalv2' ? '#f3f3f3' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Admin - Renewal v2
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
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.technician')
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
                                        style="
                                            padding: 10px 15px;
                                            font-size: 14px;
                                            font-weight: bold;
                                            border: none;
                                            border-radius: 20px;
                                            background: {{ $currentDashboard === 'Manager' ? '#431fa1' : 'transparent' }};
                                            color: {{ $currentDashboard === 'Manager' ? '#ffffff' : '#555' }};
                                            cursor: pointer;
                                        "
                                    >
                                        Manager
                                    </button>

                                        <!-- Lead Owner Button -->
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

                                    <!-- Salesperson Button -->
                                    <button
                                        wire:click="toggleDashboard('Salesperson')"
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
                                        Salesperson
                                    </button>

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
                                                background: {{ in_array($currentDashboard, ['MainAdminDashboard','SoftwareAdmin', 'HardwareAdmin', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalv2', 'AdminHRDF', 'AdminHeadcount']) ? '#431fa1' : 'transparent' }};
                                                color: {{ in_array($currentDashboard, ['MainAdminDashboard','SoftwareAdmin', 'HardwareAdmin', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalv2', 'AdminHRDF', 'AdminHeadcount']) ? '#ffffff' : '#555' }};
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
                                            z-index: 999;
                                        "></div>

                                        <div class="admin-dropdown-content" style="
                                            display: none;
                                            position: absolute;
                                            background-color: white;
                                            min-width: 200px;
                                            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                            z-index: 1000;
                                            border-radius: 6px;
                                            overflow: hidden;
                                            top: 100%; /* Position at the bottom of the button */
                                            left: 0;
                                            margin-top: 5px; /* Add a small gap */
                                        ">
                                            <!-- <button
                                                wire:click="toggleDashboard('MainAdminDashboard')"
                                                style="
                                                    display: block;
                                                    width: 200px;
                                                    padding: 10px 16px;
                                                    text-align: left;
                                                    border: none;
                                                    background: {{ $currentDashboard === 'MainAdminDashboard' ? '#f3f3f3' : 'white' }};
                                                    cursor: pointer;
                                                    font-size: 14px;
                                                "
                                            >
                                                Admin - Dashboard
                                            </button> -->

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
                                                wire:click="toggleDashboard('HardwareAdmin')"
                                                style="
                                                    display: block;
                                                    width: 100%;
                                                    padding: 10px 16px;
                                                    text-align: left;
                                                    border: none;
                                                    background: {{ $currentDashboard === 'HardwareAdmin' ? '#f3f3f3' : 'white' }};
                                                    cursor: pointer;
                                                    font-size: 14px;
                                                "
                                            >
                                                Admin - Hardware
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
                                                Admin - HRDF
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
                                                Admin - Renewal v1
                                            </button>

                                            <button
                                                wire:click="toggleDashboard('AdminRenewalv2')"
                                                style="
                                                    display: block;
                                                    width: 100%;
                                                    padding: 10px 16px;
                                                    text-align: left;
                                                    border: none;
                                                    background: {{ $currentDashboard === 'AdminRenewalv2' ? '#f3f3f3' : 'white' }};
                                                    cursor: pointer;
                                                    font-size: 14px;
                                                "
                                            >
                                                Admin - Renewal v2
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Trainer Button -->
                                    <button
                                        wire:click="toggleDashboard('Trainer')"
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
                                        Trainer
                                    </button>

                                    <!-- Implementer Button -->
                                    <button
                                        wire:click="toggleDashboard('Implementer')"
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
                                        Implementer
                                    </button>

                                    <!-- Support Button -->
                                    <button
                                        wire:click="toggleDashboard('Support')"
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
                                        Support
                                    </button>
                                        <!-- Support Button -->
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
                                        Technician
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- Additional toggle for users with role_id=1 and additional_role=1 -->
                        @if ((auth()->user()->role_id == 1 && auth()->user()->additional_role == 1) ||
                            (isset($selectedUserModel) && $selectedUserModel && $selectedUserModel->role_id == 1 && $selectedUserModel->additional_role == 1))
                                <div class="admin-dropdown admin-dropdown-2" id="adminDropdown2" style="position: relative; display: inline-block;">
                                    <button
                                        class="admin-dropdown-button"
                                        style="
                                            padding: 10px 15px;
                                            font-size: 14px;
                                            font-weight: bold;
                                            border: none;
                                            border-radius: 20px;
                                            background: {{ in_array($currentDashboard, ['MainAdminDashboard','SoftwareAdmin', 'HardwareAdmin', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalv2', 'AdminHRDF', 'AdminHeadcount']) ? '#431fa1' : 'transparent' }};
                                            color: {{ in_array($currentDashboard, ['SoftwareAdmin', 'HardwareAdmin', 'AdminRepair', 'AdminRenewalv1', 'AdminRenewalv2', 'AdminHRDF', 'AdminHeadcount']) ? '#ffffff' : '#555' }};
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
                                        z-index: 999;
                                    "></div>

                                    <div class="admin-dropdown-content" style="
                                        display: none;
                                        position: absolute;
                                        background-color: white;
                                        min-width: 200px;
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
                                                width: 200px;
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
                                            wire:click="toggleDashboard('HardwareAdmin')"
                                            style="
                                                display: block;
                                                width: 100%;
                                                padding: 10px 16px;
                                                text-align: left;
                                                border: none;
                                                background: {{ $currentDashboard === 'HardwareAdmin' ? '#f3f3f3' : 'white' }};
                                                cursor: pointer;
                                                font-size: 14px;
                                            "
                                        >
                                            Admin - Hardware
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
                                            Admin - HRDF
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
                                            Admin - Renewal v1
                                        </button>

                                        <button
                                            wire:click="toggleDashboard('AdminRenewalv2')"
                                            style="
                                                display: block;
                                                width: 100%;
                                                padding: 10px 16px;
                                                text-align: left;
                                                border: none;
                                                background: {{ $currentDashboard === 'AdminRenewalv2' ? '#f3f3f3' : 'white' }};
                                                cursor: pointer;
                                                font-size: 14px;
                                            "
                                        >
                                            Admin - Renewal v2
                                        </button>
                                    </div>
                                </div>
                            @endif
                    </div>
                </div>
            </div>

            <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
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
                        @elseif ($currentDashboard === 'AdminRepair')
                            @include('filament.pages.adminrepair')
                        @elseif ($currentDashboard === 'AdminRenewalv1')
                            @include('filament.pages.adminrenewal')
                        @elseif ($currentDashboard === 'AdminRenewalv2')
                            @include('filament.pages.adminrenewal')
                        @elseif ($currentDashboard === 'AdminHRDF')
                            @include('filament.pages.adminhrdf')
                        @elseif ($currentDashboard === 'AdminHeadcount')
                            @include('filament.pages.adminheadcount')
                        @elseif ($currentDashboard === 'SoftwareAdmin')
                            @include('filament.pages.softwarehandover')
                        @elseif ($currentDashboard === 'Debtor')
                            {{-- @include('filament.pages.admindebtor') --}}
                        @elseif ($currentDashboard === 'HardwareAdmin')
                            @include('filament.pages.hardwarehandover')
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

                    // Clear existing event listeners
                    adminDropdowns.forEach(function(dropdown) {
                        const button = dropdown.querySelector('.admin-dropdown-button');
                        if (button) {
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
                            // Remove existing click handlers
                            const newItem = item.cloneNode(true);
                            item.parentNode.replaceChild(newItem, item);

                            // Add new click handler that closes the dropdown but allows the wire:click to function
                            newItem.addEventListener('click', function() {
                                // Hide the dropdown after a short delay to allow the wire:click to process
                                setTimeout(function() {
                                    content.style.display = 'none';
                                }, 50);
                            });
                        });
                    });

                    // Only close dropdowns when clicking outside (but not on dropdown menu items)
                    document.addEventListener('click', function(event) {
                        // Check if the click was on a wire:click element in a dropdown
                        const clickedWireElement = event.target.closest('button[wire\\:click]');
                        if (clickedWireElement) {
                            // Don't interfere with wire:click elements
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

                // Re-initialize on Livewire updates
                document.addEventListener('livewire:navigated', initializeDropdowns);
                document.addEventListener('livewire:load', initializeDropdowns);
                document.addEventListener('livewire:update', initializeDropdowns);

                // Re-initialize periodically to ensure dropdowns work
                setInterval(initializeDropdowns, 2000);
            </script>
        </div>
    </div>
</x-filament::page>
