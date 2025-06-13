<x-filament::page>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <div>
        @if (auth()->user()->role_id == 1)
            {{-- Common heading for all role_id=1 users --}}
            <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Dashboard</h1>

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

                        <!-- Software Handover Button -->
                        <button
                            wire:click="toggleDashboard('SoftwareHandover')"
                            style="
                                padding: 10px 15px;
                                font-size: 14px;
                                font-weight: bold;
                                border: none;
                                border-radius: 20px;
                                background: {{ $currentDashboard === 'SoftwareHandover' ? '#431fa1' : 'transparent' }};
                                color: {{ $currentDashboard === 'SoftwareHandover' ? '#ffffff' : '#555' }};
                                cursor: pointer;
                            "
                        >
                            Software Handover
                        </button>

                        <!-- Hardware Handover Button -->
                        <button
                            wire:click="toggleDashboard('HardwareHandover')"
                            style="
                                padding: 10px 15px;
                                font-size: 14px;
                                font-weight: bold;
                                border: none;
                                border-radius: 20px;
                                background: {{ $currentDashboard === 'HardwareHandover' ? '#431fa1' : 'transparent' }};
                                color: {{ $currentDashboard === 'HardwareHandover' ? '#ffffff' : '#555' }};
                                cursor: pointer;
                            "
                        >
                            Hardware Handover
                        </button>
                    </div>
                @endif
            </div>
            <br>

            {{-- Two-column grid for all role_id=1 users --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @if (auth()->user()->additional_role == 1)
                    @if ($currentDashboard === 'LeadOwner')
                        @include('filament.pages.leadowner')
                    @elseif ($currentDashboard === 'SoftwareHandover')
                        @include('filament.pages.softwarehandover')
                    @elseif ($currentDashboard === 'HardwareHandover')
                        @include('filament.pages.hardwarehandover')
                    @endif
                @else
                    <!-- Regular Lead Owner view for role_id=1 users without additional_role=1 -->
                    @include('filament.pages.leadowner')
                @endif
            </div>

        @elseif (auth()->user()->role_id == 2)
            <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Dashboard</h1>
            </div>
            <br>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @include('filament.pages.salesperson')
            </div>
        @elseif (auth()->user()->role_id == 3)
        <div class="space-y-4">
            <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Dashboard</h1>

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
                                <option value="all-salespersons">All Salespersons</option>
                            </optgroup>

                            <optgroup label="Lead Owner">
                                @foreach ($users->where('role_id', 1) as $user)
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
                                <div class="admin-dropdown" style="position: relative; display: inline-block;">
                                    <button
                                        class="admin-dropdown-button"
                                        style="
                                            padding: 10px 15px;
                                            font-size: 14px;
                                            font-weight: bold;
                                            border: none;
                                            border-radius: 20px;
                                            background: {{ in_array($currentDashboard, ['SoftwareAdmin', 'HardwareAdmin']) ? '#431fa1' : 'transparent' }};
                                            color: {{ in_array($currentDashboard, ['SoftwareAdmin', 'HardwareAdmin']) ? '#ffffff' : '#555' }};
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
                                        min-width: 160px;
                                        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                        z-index: 1000;
                                        border-radius: 6px;
                                        overflow: hidden;
                                        top: 100%; /* Position at the bottom of the button */
                                        left: 0;
                                        margin-top: 5px; /* Add a small gap */
                                    ">
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
                                            wire:click="toggleDashboard('Training')"
                                            style="
                                                display: block;
                                                width: 100%;
                                                padding: 10px 16px;
                                                text-align: left;
                                                border: none;
                                                background: {{ $currentDashboard === 'TRAINING' ? '#f3f3f3' : 'white' }};
                                                cursor: pointer;
                                                font-size: 14px;
                                            "
                                        >
                                            Admin - Training
                                        </button>

                                        <button
                                            wire:click="toggleDashboard('Finance')"
                                            style="
                                                display: block;
                                                width: 100%;
                                                padding: 10px 16px;
                                                text-align: left;
                                                border: none;
                                                background: {{ $currentDashboard === 'TRAINING' ? '#f3f3f3' : 'white' }};
                                                cursor: pointer;
                                                font-size: 14px;
                                            "
                                        >
                                            Admin - Finance
                                        </button>

                                        <button
                                            wire:click="toggleDashboard('HRDF')"
                                            style="
                                                display: block;
                                                width: 100%;
                                                padding: 10px 16px;
                                                text-align: left;
                                                border: none;
                                                background: {{ $currentDashboard === 'HRDF' ? '#f3f3f3' : 'white' }};
                                                cursor: pointer;
                                                font-size: 14px;
                                            "
                                        >
                                            Admin - HRDF
                                        </button>

                                        <button
                                            wire:click="toggleDashboard('Renewal')"
                                            style="
                                                display: block;
                                                width: 100%;
                                                padding: 10px 16px;
                                                text-align: left;
                                                border: none;
                                                background: {{ $currentDashboard === 'Renewal' ? '#f3f3f3' : 'white' }};
                                                cursor: pointer;
                                                font-size: 14px;
                                            "
                                        >
                                            Admin - Renewal
                                        </button>

                                        <button
                                            wire:click="toggleDashboard('General')"
                                            style="
                                                display: block;
                                                width: 100%;
                                                padding: 10px 16px;
                                                text-align: left;
                                                border: none;
                                                background: {{ $currentDashboard === 'General' ? '#f3f3f3' : 'white' }};
                                                cursor: pointer;
                                                font-size: 14px;
                                            "
                                        >
                                            Admin - General
                                        </button>

                                        <button
                                            wire:click="toggleDashboard('Credit Controller')"
                                            style="
                                                display: block;
                                                width: 100%;
                                                padding: 10px 16px;
                                                text-align: left;
                                                border: none;
                                                background: {{ $currentDashboard === 'Credit Controller' ? '#f3f3f3' : 'white' }};
                                                cursor: pointer;
                                                font-size: 14px;
                                            "
                                        >
                                            Admin - Credit Controller
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
                            </div>
                        </div>

                        <!-- JavaScript for dropdown behavior -->
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const adminDropdown = document.querySelector('.admin-dropdown');
                                const adminDropdownButton = document.querySelector('.admin-dropdown-button');
                                const adminDropdownContent = document.querySelector('.admin-dropdown-content');
                                const bridge = document.querySelector('.dropdown-bridge');

                                if (adminDropdown && adminDropdownButton && adminDropdownContent) {
                                    // Show dropdown on mouseenter for button
                                    adminDropdownButton.addEventListener('mouseenter', function() {
                                        adminDropdownContent.style.display = 'block';
                                    });

                                    // Keep dropdown open when hovering over dropdown content
                                    adminDropdownContent.addEventListener('mouseenter', function() {
                                        adminDropdownContent.style.display = 'block';
                                    });

                                    // Keep dropdown open when hovering over bridge
                                    if (bridge) {
                                        bridge.addEventListener('mouseenter', function() {
                                            adminDropdownContent.style.display = 'block';
                                        });
                                    }

                                    // Hide dropdown when mouse leaves entire component
                                    adminDropdown.addEventListener('mouseleave', function(e) {
                                        // Check if mouse moves to dropdown content
                                        if (!e.relatedTarget ||
                                            (!adminDropdownContent.contains(e.relatedTarget) &&
                                            !bridge.contains(e.relatedTarget))) {
                                            adminDropdownContent.style.display = 'none';
                                        }
                                    });

                                    // Hide dropdown when mouse leaves dropdown content
                                    adminDropdownContent.addEventListener('mouseleave', function(e) {
                                        // Check if mouse returns to button or bridge
                                        if (!e.relatedTarget ||
                                            (!adminDropdownButton.contains(e.relatedTarget) &&
                                            !bridge.contains(e.relatedTarget))) {
                                            adminDropdownContent.style.display = 'none';
                                        }
                                    });

                                    // Handle click for mobile devices
                                    adminDropdownButton.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();

                                        if (adminDropdownContent.style.display === 'block') {
                                            adminDropdownContent.style.display = 'none';
                                        } else {
                                            adminDropdownContent.style.display = 'block';
                                        }
                                    });

                                    // Close when clicking elsewhere
                                    document.addEventListener('click', function(e) {
                                        if (!adminDropdown.contains(e.target)) {
                                            adminDropdownContent.style.display = 'none';
                                        }
                                    });
                                }
                            });
                        </script>
                    @endif

                    <!-- Additional toggle for users with role_id=1 and additional_role=1 -->
                    @if ((auth()->user()->role_id == 1 && auth()->user()->additional_role == 1) ||
                        (isset($selectedUserModel) && $selectedUserModel && $selectedUserModel->role_id == 1 && $selectedUserModel->additional_role == 1))
                            <div style="display: flex; align-items: center;">
                                <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px;">
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

                                    <!-- Software Handover Button -->
                                    <button
                                        wire:click="toggleDashboard('SoftwareHandover')"
                                        style="
                                            padding: 10px 15px;
                                            font-size: 14px;
                                            font-weight: bold;
                                            border: none;
                                            border-radius: 20px;
                                            background: {{ $currentDashboard === 'SoftwareHandover' ? '#431fa1' : 'transparent' }};
                                            color: {{ $currentDashboard === 'SoftwareHandover' ? '#ffffff' : '#555' }};
                                            cursor: pointer;
                                        "
                                    >
                                        Software Handover
                                    </button>

                                    <!-- Hardware Handover Button -->
                                    <button
                                        wire:click="toggleDashboard('HardwareHandover')"
                                        style="
                                            padding: 10px 15px;
                                            font-size: 14px;
                                            font-weight: bold;
                                            border: none;
                                            border-radius: 20px;
                                            background: {{ $currentDashboard === 'HardwareHandover' ? '#431fa1' : 'transparent' }};
                                            color: {{ $currentDashboard === 'HardwareHandover' ? '#ffffff' : '#555' }};
                                            cursor: pointer;
                                        "
                                    >
                                        Hardware Handover
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
                @else
                    @if ($currentDashboard === 'LeadOwner')
                        @include('filament.pages.leadowner')
                    @elseif ($currentDashboard === 'Salesperson')
                        @include('filament.pages.salesperson')
                    @elseif ($currentDashboard === 'Manager')
                        @include('filament.pages.manager')
                    @elseif ($currentDashboard === 'SoftwareHandover')
                        @include('filament.pages.softwarehandover')
                    @elseif ($currentDashboard === 'HardwareHandover')
                        @include('filament.pages.hardwarehandover')
                    @elseif ($currentDashboard === 'SoftwareAdmin')
                        @include('filament.pages.softwarehandover')
                    @elseif ($currentDashboard === 'HardwareAdmin')
                        @include('filament.pages.hardwarehandover')
                    @elseif ($currentDashboard === 'Trainer')
                        {{-- @include('filament.pages.trainer') --}}
                    @elseif ($currentDashboard === 'Implementer')
                        @include('filament.pages.implementer')
                    @elseif ($currentDashboard === 'Support')
                        {{-- @include('filament.pages.support') --}}
                    @else
                        @include('filament.pages.manager')
                    @endif
                @endif
            </div>
        @endif
    </div>
    <script>
        // Create a global direct reset function that doesn't rely on component references
        window.forceResetDashboards = function() {
            console.log('Force resetting all dashboards');

            // Reset software dashboard
            const softwareContainer = document.getElementById('software-handover-container');
            if (softwareContainer && softwareContainer.__x) {
                softwareContainer.__x.$data.selectedStat = null;
                console.log('Software container found and reset');
            } else {
                console.log('Software container not available');
            }

            // Reset hardware dashboard
            const hardwareContainer = document.getElementById('hardware-handover-container');
            if (hardwareContainer && hardwareContainer.__x) {
                hardwareContainer.__x.$data.selectedStat = null;
                console.log('Hardware container found and reset');
            } else {
                console.log('Hardware container not available');
            }
        };

        // Create global non-Alpine event listener for Livewire events
        document.addEventListener('livewire:initialized', function() {
            window.Livewire.on('dashboard-changed', function(data) {
                console.log('Dashboard changed directly via Livewire:', data.dashboard);

                // Force immediate reset regardless of dashboard manager
                setTimeout(window.forceResetDashboards, 100);

                // Trigger both dashboard resets
                window.dispatchEvent(new CustomEvent('reset-software-dashboard'));
                window.dispatchEvent(new CustomEvent('reset-hardware-dashboard'));
            });
        });
    </script>
</x-filament::page>
