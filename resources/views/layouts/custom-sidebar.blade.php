@auth
    <link rel="stylesheet" href="{{ asset('css/custom-sidebar.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* Main sidebar container */
        .sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            display: flex;
            z-index: 1000;
            transition: all 0.3s ease;
            background-color: #fff;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.05);
            overflow: visible; /* Allow tooltips to show outside */
        }

        /* Icon sidebar - slim version */
        .icon-sidebar {
            width: 60px;
            min-width: 50px;
            height: 100vh;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.05);
            transition: width 0.3s ease;
            overflow: visible; /* Changed to visible */
            z-index: 1010;
        }

        /* Icon sidebar header */
        .icon-header {
            padding: 1rem 0.5rem 1rem 0.5rem;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .icon-logo {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, #7e57c2, #5e35b1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: white;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .icon-logo:hover {
            opacity: 0.8;
        }

        /* Icon sidebar content */
        .icon-content {
            flex: 1;
            overflow-y: auto;
            overflow-x: visible; /* Changed to visible */
            padding: 1rem 0.5rem 1rem 0.5rem;
            display: flex;
            flex-direction: column;
            gap: clamp(0.5rem, 2vh, 1rem);
            max-height: calc(100vh - 80px);

            /* Hide scrollbar completely */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        /* Hide webkit scrollbar completely */
        .icon-content::-webkit-scrollbar {
            width: 0px;
            background: transparent;
            display: none;
        }

        .icon-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .icon-content::-webkit-scrollbar-thumb {
            background: transparent;
        }

        /* Icon section separator */
        .icon-separator {
            height: 1px;
            background-color: rgba(229, 231, 235, 0.5);
            margin: 0.75rem 0;
        }

        /* Icon links */
        .icon-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 48px;
            position: relative;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .icon-link:hover {
            background-color: #f3f4f6;
        }

        .icon-link.active {
            background-color: #4F46E5;
        }

        .icon-wrapper {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .icon-link:hover .icon-wrapper {
            background-color: #e5e7eb;
        }

        .icon-link.active .icon-wrapper {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .icon {
            color: #6C5CE7;
            font-size: 1rem;
        }

        .icon-link.active .icon {
            color: white;
        }

        .icon-link.dashboard .icon-wrapper {
            background-color: #4F46E5;
        }

        .icon-link.dashboard .icon {
            color: white;
        }

        .icon-tooltip {
            visibility: hidden;
            position: fixed;
            left: 70px; /* Position outside the 60px sidebar */
            top: auto; /* Will be set by JavaScript */
            transform: translateY(-50%);
            background-color: #000000;
            color: #ffffff;
            text-align: center;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
            z-index: 10000; /* High z-index to appear above everything */
            opacity: 0;
            transition: opacity 0.1s ease-out, visibility 0.1s ease-out;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        /* Arrow pointing to the icon */
        .icon-tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: #000000;
        }

        /* Enhanced hover effect with positioning */
        .icon-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 48px;
            position: relative;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .icon-link:hover .icon-tooltip {
            visibility: visible;
            opacity: 1;
            transition-delay: 0.05s;
        }

        /* Expanded sidebar */
        .expanded-sidebar {
            width: 0;
            height: 100vh;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.05);
            transition: width 0.3s ease;
            overflow: hidden;
        }

        .expanded-sidebar.active {
            width: 280px;
        }

        /* Expanded sidebar header */
        .expanded-header {
            padding: 12px;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .app-logo {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, #7e57c2, #5e35b1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .app-name {
            display: flex;
            flex-direction: column;
        }

        .app-title {
            font-weight: 600;
            font-size: 1.125rem;
            color: #111827;
        }

        .app-subtitle {
            font-size: 0.75rem;
            color: #6B7280;
            font-weight: 500;
        }

        /* Back button / collapse button */
        .back-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            color: #6B7280;
            cursor: pointer;
            transition: background 0.2s ease;
            background: transparent;
            border: none;
        }

        .back-button:hover {
            background-color: #F3F4F6;
            color: #111827;
        }

        /* Content area with scroll */
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .sidebar-content::-webkit-scrollbar {
            display: none;
        }

        /* Section Headings */
        .section-heading {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6B7280;
            font-weight: 600;
        }

        /* Menu Items */
        .menu-block {
            margin-bottom: 0.5rem;
        }

        .menu-item {
            padding: 0.5rem;
            display: flex;
            align-items: center;
            border-radius: 0.5rem;
            color: #4B5563;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            height: 2rem;
            width: 100%;
            justify-content: flex-start;
            position: relative;
            font-size: 1rem;
        }

        .menu-item:hover {
            background-color: #F3F4F6;
            color: #111827;
        }

        .menu-item.active {
            background-color: #4F46E5;
            color: white;
        }

        .menu-icon-wrapper {
            margin-right: 0.75rem;
            width: 2rem;
            height: 2rem;
            /* background-color: #F3F4F6; */
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        /* .menu-item:hover .menu-icon-wrapper {
            background-color: #E5E7EB;
        } */

        .menu-item.active .menu-icon-wrapper {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .menu-icon {
            color: #6C5CE7;
            font-size: 1rem;
        }

        .menu-item.active .menu-icon {
            color: white;
        }

        .menu-text {
            flex-grow: 1;
            text-align: left;
            font-weight: 500;
        }

        .menu-arrow {
            color: #D1D5DB;
            font-size: 0.875rem;
        }

        /* Separator */
        .sidebar-separator {
            height: 1px;
            background-color: rgba(229, 231, 235, 0.5);
            margin: 0.75rem 0;
        }

        /* Submenu items (second level) */
        .submenu {
            padding-left: 1rem;
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.3s ease;
        }

        .submenu.active {
            max-height: 500px; /* Arbitrary large value */
        }

        .submenu-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem 0.5rem 1.5rem;
            color: #4B5563;
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .submenu-item:hover {
            background-color: #F3F4F6;
            color: #111827;
        }

        .submenu-item.active {
            background-color: #EEF2FF;
            color: #4F46E5;
        }

        /* Section content */
        .section-content {
            display: none; /* Hide all section contents by default */
        }

        .section-content.active {
            display: flex;
            flex-direction: column;
        }
        .nested-dropdown-trigger {
            cursor: pointer;
            user-select: none;
        }

        /* Make sure submenus animate smoothly */
        .submenu {
            transition: max-height 0.3s ease-in-out;
        }

        /* Ensure active submenus display properly */
        .submenu.active {
            display: block;
            max-height: 500px; /* Large enough to fit all content */
        }

        .submenu-item {
            position: relative;
            padding-left: 2.25rem !important;
        }

        .submenu-item::before {
            content: "•";
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6C5CE7;
            font-size: 1rem;
        }

        /* Better styling for nested dropdowns */
        .submenu {
            padding-left: 0.5rem;
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
        }

        /* Improve submenu item spacing */
        .submenu-item {
            height: 2rem;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
        }

        /* Make submenu active state more visible */
        .submenu-item.active {
            background-color: #EEF2FF;
            color: #4F46E5;
            font-weight: 600;
        }

        .submenu-item.active::before {
            color: #4F46E5;
        }

        /* Ensure nested dropdown triggers have proper styling */
        .nested-dropdown-trigger {
            padding: 0.5rem;
            border-radius: 0.5rem;
        }

        .nested-dropdown-trigger:hover {
            background-color: #F3F4F6;
        }

        /* Make dropdown arrow animation smoother */
        .menu-arrow {
            transition: transform 0.3s ease;
        }

        .nested-dropdown-trigger[aria-expanded="true"] .menu-arrow {
            transform: rotate(180deg);
        }

        .submenu {
            padding-left: 0.5rem;
            /* margin-top: 0.25rem; */
            margin-bottom: 0.25rem;
            border-left: 2px solid #E5E7EB;
            margin-left: 1rem;
        }

        /* Improve submenu item spacing and add bottom separator */
        .submenu-item {
            height: 2rem;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            position: relative;
            padding-left: 2.25rem !important;
        }

        /* Remove border from last item */
        .submenu-item:last-child {
            border-bottom: none;
        }

        /* Style the dots better */
        .submenu-item::before {
            content: "•";
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6C5CE7;
            font-size: 0.85rem;
        }

        /* Make the submenu background slightly different for better contrast */
        .submenu {
            border-radius: 0.375rem;
        }

        /* Make sure the submenu expands/contracts smoothly */
        .submenu {
            transition: max-height 0.3s ease-in-out, opacity 0.2s ease-in-out;
            opacity: 0;
        }

        .submenu.active {
            opacity: 1;
        }

        .module-font {
            font-size: 12px;
        }

        .submenu-item:has(.submenu-icon)::before {
            display: none;
        }

        /* Style the submenu icons */
        .submenu-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.85rem;
        }

        /* Make the submenu item with icon have the same padding */
        .submenu-item .submenu-icon + .module-font {
            padding-left: 0;
        }

        .icon-link[data-section="dashboard"] .icon { color: #6366F1; /* icon-gradient-1 */ }
        .icon-link[data-section="salesadmin"] .icon { color: #0EA5E9; /* icon-gradient-2 */ }
        .icon-link[data-section="salesperson"] .icon { color: #06B6D4; /* icon-gradient-3 */ }
        .icon-link[data-section="handover"] .icon { color: #14B8A6; /* icon-gradient-4 */ }
        .icon-link[data-section="ticketing"] .icon { color: #10B981; /* icon-gradient-5 */ }
        .icon-link[data-section="system-portal"] .icon { color: #22C55E; /* icon-gradient-6 */ }
        .icon-link[data-section="admin"] .icon { color: #84CC16; /* icon-gradient-7 */ }
        .icon-link[data-section="implementer"] .icon { color: #EAB308; /* icon-gradient-8 */ }
        .icon-link[data-section="support"] .icon { color: #F59E0B; /* icon-gradient-9 */ }
        .icon-link[data-section="trainer"] .icon { color: #F97316; /* Add this line for trainer */ }
        .icon-link[data-section="technician"] .icon { color: #DC2626; /* Updated to next color */ }
        .icon-link[data-section="marketing"] .icon { color: #C026D3; /* Updated */ }
        .icon-link[data-section="internal"] .icon { color: #A855F7; /* Updated */ }
        .icon-link[data-section="external"] .icon { color: #8B5CF6; /* Updated */ }
        .icon-link[data-section="settings"] .icon { color: #7C3AED; /* Updated */ }
    </style>

    <!-- Main Sidebar Container -->
    <div class="sidebar-container">
        <!-- Icon Sidebar - Collapsed by Default -->
        <div class="icon-sidebar">
            <!-- Icon Sidebar Header with App Logo -->
            <div class="icon-header">
                <a href="{{ route('filament.admin.pages.dashboard-form') }}" class="icon-logo" id="expand-sidebar">
                    <i class="bi bi-grid"></i>
                    <span class="icon-tooltip">Dashboard</span>
                </a>
            </div>

            <div class="icon-content">
                @if(auth()->user()->role_id == 1 || auth()->user()->role_id == 3)
                    <div class="icon-link" data-section="salesadmin">
                        <div class="icon-wrapper">
                            <i class="bi bi-people icon"></i>
                        </div>
                        <span class="icon-tooltip">Sales Admin</span>
                    </div>
                @endif

                @if(auth()->user()->role_id == 2 || auth()->user()->role_id == 3)
                    <div class="icon-link" data-section="salesperson">
                        <div class="icon-wrapper">
                            <i class="bi bi-rocket-takeoff icon"></i>
                        </div>
                        <span class="icon-tooltip">SalesPerson</span>
                    </div>
                @endif

                @if(auth()->user()->additional_role == 1 || in_array(auth()->user()->role_id, [1,2,3,4,5,6,7,8]))
                    <div class="icon-link" data-section="handover">
                        <div class="icon-wrapper">
                            <i class="bi bi-arrow-left-right icon"></i>
                        </div>
                        <span class="icon-tooltip">Handover</span>
                    </div>
                @endif

                <div class="icon-link" data-section="ticketing">
                    <div class="icon-wrapper">
                        <i class="bi bi-ticket-perforated icon"></i>
                    </div>
                    <span class="icon-tooltip">Ticketing</span>
                </div>

                <div class="icon-link" data-section="system-portal">
                    <div class="icon-wrapper">
                        <i class="bi bi-grid-3x3-gap icon"></i>
                    </div>
                    <span class="icon-tooltip">System Portal</span>
                </div>

                @if(auth()->user()->additional_role == 1 || auth()->user()->role_id == 3)
                    <div class="icon-link" data-section="admin">
                        <div class="icon-wrapper">
                            <i class="bi bi-layout-text-window icon"></i>
                        </div>
                        <span class="icon-tooltip">Admin</span>
                    </div>
                @endif

                @if(in_array(auth()->user()->role_id, [3,4,5]) || auth()->user()->id == 43)
                    <div class="icon-link" data-section="implementer">
                        <div class="icon-wrapper">
                            <i class="bi bi-person-workspace icon"></i>
                        </div>
                        <span class="icon-tooltip">Implementer</span>
                    </div>
                @endif

                @if(auth()->user()->role_id === 8 || auth()->user()->role_id === 3 || auth()->user()->role_id === 5 || auth()->user()->id == 35)
                    <div class="icon-link" data-section="support">
                        <div class="icon-wrapper">
                            <i class="bi bi-headset icon"></i>
                        </div>
                        <span class="icon-tooltip">Support</span>
                    </div>
                @endif

                {{-- @if(in_array(auth()->user()->role_id, [6,7]) || auth()->user()->role_id == 3)
                    <div class="icon-link" data-section="trainer">
                        <div class="icon-wrapper">
                            <i class="bi bi-person-video3 icon"></i>
                        </div>
                        <span class="icon-tooltip">Trainer</span>
                    </div>
                @endif --}}

                @if(auth()->user()->role_id == 9 || auth()->user()->role_id == 3)
                    <div class="icon-link" data-section="technician">
                        <div class="icon-wrapper">
                            <i class="bi bi-wrench icon"></i>
                        </div>
                        <span class="icon-tooltip">Technician</span>
                    </div>
                @endif

                @if(auth()->user()->hasRouteAccess('filament.admin.pages.marketing-analysis'))
                    <div class="icon-link" data-section="marketing">
                        <div class="icon-wrapper">
                            <i class="bi bi-fire icon"></i>
                        </div>
                        <span class="icon-tooltip">Marketing</span>
                    </div>
                @endif

                <div class="icon-link" data-section="internal">
                    <div class="icon-wrapper">
                        <i class="bi bi-box-arrow-in-down-right icon"></i>
                    </div>
                    <span class="icon-tooltip">TimeTec HR - Internal</span>
                </div>

                <div class="icon-link" data-section="external">
                    <div class="icon-wrapper">
                        <i class="bi bi-box-arrow-up-right icon"></i>
                    </div>
                    <span class="icon-tooltip">TimeTec HR - External</span>
                </div>

                <!-- Settings Icon -->
                @if(auth()->user()->hasAccessToAny([
                    'filament.admin.resources.products.index',
                    'filament.admin.resources.industries.index',
                    'filament.admin.resources.lead-sources.index',
                    'filament.admin.resources.invalid-lead-reasons.index',
                    'filament.admin.resources.resellers.index',
                    'filament.admin.resources.users.index'
                ]))
                    <div class="icon-link" data-section="settings">
                        <div class="icon-wrapper">
                            <i class="bi bi-gear icon"></i>
                        </div>
                        <span class="icon-tooltip">Settings</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Expanded Sidebar - Hidden by Default -->
        <div class="expanded-sidebar" id="expanded-sidebar">
            <!-- Header with App Logo and Title -->
            <div class="expanded-header">
                <div class="header-left">
                    <div class="app-name">
                        <span class="app-title">TimeTec HR</span>
                    </div>
                </div>
                <button class="back-button" id="collapse-sidebar">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </div>

            <!-- Scrollable Content Area -->
            <div class="sidebar-content">
                <!-- Sales Admin Section -->
                <div id="salesadmin-section" class="section-content">
                    <!-- Calendar Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-calendar-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-calendar3"></i>
                            </div>
                            <span class="menu-text">Calendar</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-calendar-submenu">
                            <a href="{{ route('filament.admin.pages.salesperson-calendar-v1') }}" class="submenu-item">
                                <span class="module-font">Version 1</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.salesperson-calendar-v2') }}" class="submenu-item">
                                <span class="module-font">Version 2</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.calendar') }}" class="submenu-item">
                                <span class="module-font">Department Calendar</span>
                            </a>
                        </div>
                    </div>

                    <!-- Salesperson Audit List Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-audit-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Audit List</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-audit-submenu">
                            <a href="{{ route('filament.admin.pages.salesperson-lead-sequence') }}" class="submenu-item">
                                <span class="module-font">Lead Sequence</span>
                            </a>
                        </div>
                    </div>

                    <!-- Prospects Automation Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-prospects-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Automation</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-prospects-submenu">
                            <a href="{{ route('filament.admin.pages.whatsapp') }}" class="submenu-item">
                                <span class="module-font">WhatsApp</span>
                            </a>
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Email</span>
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Analysis Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-analysis-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Analysis</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-analysis-submenu">
                            <a href="{{ route('filament.admin.pages.sales-admin-analysis-v1') }}" class="submenu-item">
                                <span class="module-font">Leads</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.sales-admin-analysis-v2') }}" class="submenu-item">
                                <span class="module-font">Performance</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.sales-admin-analysis-v3') }}" class="submenu-item">
                                <span class="module-font">Action Task</span>
                            </a>
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.demo-ranking'))
                            <a href="{{ route('filament.admin.pages.demo-ranking') }}" class="submenu-item">
                                <span class="module-font">Demo Ranking</span>
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Information Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-information-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Information</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-information-submenu">
                            <a href="{{ route('filament.admin.pages.sales-admin-call-log') }}" class="submenu-item">
                                <span class="module-font">Sales Admin - Call Log</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.sales-admin-closed-deal') }}" class="submenu-item">
                                <span class="module-font">Sales Admin - Closed Deal</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.sales-admin-invoices') }}" class="submenu-item">
                                <span class="module-font">Sales Admin - Invoice</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Sales Admin - Commission</span>
                            </a>
                        </div>
                    </div>

                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="leads-submenu">
                            <div class="menu-icon-wrapper">
                               <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Leads</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="leads-submenu">
                            <a href="{{ route('filament.admin.resources.leads.index') }}" class="submenu-item">
                                <span class="module-font">All Leads</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.search-lead') }}" class="submenu-item">
                                <span class="module-font">Search Leads</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.search-license') }}" class="submenu-item">
                                <span class="module-font">Search License</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- SalesPerson Section -->
                <div id="salesperson-section" class="section-content">
                    <!-- Calendar Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="calendar-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-calendar3"></i>
                            </div>
                            <span class="menu-text">Calendar</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="calendar-submenu">
                            <a href="{{ route('filament.admin.pages.salesperson-calendar-v1') }}" class="submenu-item">
                                <span class="module-font">SalesPerson Calendar</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.implementer-calendar') }}" class="submenu-item">
                                <span class="module-font">Implementer Calendar</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.support-calendar') }}" class="submenu-item">
                                <span class="module-font">Support Calendar</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.technician-calendar') }}" class="submenu-item">
                                <span class="module-font">Technician Calendar</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.calendar') }}" class="submenu-item">
                                <span class="module-font">Department Calendar</span>
                            </a>
                        </div>
                    </div>

                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="leads-submenu-salesperson">
                            <div class="menu-icon-wrapper">
                               <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Leads</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="leads-submenu-salesperson">
                            <a href="{{ route('filament.admin.resources.leads.index') }}" class="submenu-item">
                                <span class="module-font">All Leads</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.search-lead') }}" class="submenu-item">
                                <span class="module-font">Search Leads</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.search-license') }}" class="submenu-item">
                                <span class="module-font">Search License</span>
                            </a>
                        </div>
                    </div>

                    <!-- Commercial Part Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="commercial-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Commercial Item</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="commercial-submenu">
                            <a href="{{ route('filament.admin.resources.quotations.index') }}" class="submenu-item">
                                <span class="module-font">Quotation</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.proforma-invoices') }}" class="submenu-item">
                                <span class="module-font">Proforma Invoice</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Purchase Order</span>
                            </a>
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [2]))
                                <a href="{{ route('filament.admin.pages.invoices') }}" class="submenu-item">
                                    <span class="module-font">Sales Invoice</span>
                                </a>
                            @endif
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [2]))
                                <a href="{{ route('filament.admin.pages.sales-debtor') }}" class="submenu-item">
                                    <span class="module-font">Sales Debtor</span>
                                </a>
                            @endif
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [2]))
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Sales Order</span>
                                </a>
                            @endif
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Sales Commission</span>
                            </a>
                        </div>
                    </div>

                    <!-- Analysis Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="analysis-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Leads & Demo</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="analysis-submenu">
                            <a href="{{ route('filament.admin.pages.lead-analysis') }}" class="submenu-item">
                                <span class="module-font">Lead Analysis</span>
                            </a>
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Lead Analysis - Summary</span>
                                </a>
                            @endif
                            <a href="{{ route('filament.admin.pages.demo-analysis') }}" class="submenu-item">
                                <span class="module-font">Demo Analysis</span>
                            </a>
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Demo Analysis - Summary</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Forecast Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="forecast-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Forecast & Revenue</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="forecast-submenu">
                            <a href="{{ route('filament.admin.pages.sales-forecast') }}" class="submenu-item">
                                <span class="module-font">Forecast</span>
                            </a>
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                                <a href="{{ route('filament.admin.pages.forecast-analysis') }}" class="submenu-item">
                                    <span class="module-font">Forecast Analysis</span>
                                </a>
                            @endif
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                                <a href="{{ route('filament.admin.pages.revenue') }}" class="submenu-item">
                                    <span class="module-font">Revenue</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.revenue-analysis') }}" class="submenu-item">
                                    <span class="module-font">Revenue Analysis</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Salesperson Request Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="request-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Sales Request</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="request-submenu">
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.demo-ranking'))
                            <a href="{{ route('filament.admin.pages.salesperson-appointment') }}" class="submenu-item">
                                <span class="module-font">Internal Task Request</span>
                            </a>
                            @endif
                            <a href="{{ route('filament.admin.pages.sales.site-survey-request') }}" class="submenu-item">
                                <span class="module-font">Site Survey Request</span>
                            </a>
                        </div>
                    </div>

                    <div class="menu-block">
                        <a href="{{ route('filament.admin.pages.policy-management') }}" class="menu-item">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Sales Policy</span>
                        </a>
                        <div class="submenu" id="policy-submenu">
                        </div>
                    </div>

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                        <div class="menu-block">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="menu-item">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags"></i>
                                </div>
                                <span class="menu-text">Sales Pricing</span>
                            </a>
                            <div class="submenu" id="policy-submenu">
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Handover Section -->
                <div id="handover-section" class="section-content">
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [1,2,3,4,5,6,7,8]))
                        <!-- Software Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="software-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags"></i>
                                </div>
                                <span class="menu-text">Software Handover</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="software-submenu">
                                <a href="{{ route('filament.admin.resources.software.index') }}" class="submenu-item">
                                    <span class="module-font">Project List</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.project-priority') }}" class="submenu-item">
                                    <span class="module-font">Project Priority</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.software.project-analysis') }}" class="submenu-item">
                                    <span class="module-font">Project Analysis</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Project Plan</span>
                                </a>
                            </div>
                        </div>

                        <!-- Hardware Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="hardware-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags submenu-icon"></i>
                                </div>
                                <span class="menu-text">Hardware Handover</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="hardware-submenu">
                                <a href="{{ route('filament.admin.pages.hardware-dashboard-all') }}" class="submenu-item">
                                    <span class="module-font">Dashboard - All</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.hardware-dashboard-pending-stock') }}" class="submenu-item">
                                    <span class="module-font">Dashboard - Pending Stock</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.device-stock-information') }}" class="submenu-item">
                                    <span class="module-font">Device Stock Information</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.device-purchase-information') }}" class="submenu-item">
                                    <span class="module-font">Device Purchase Information</span>
                                </a>
                            </div>
                        </div>

                        <!-- HRDF Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="hrdf-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags submenu-icon"></i>
                                </div>
                                <span class="menu-text">HRDF Handover</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="hrdf-submenu">
                                <a href="{{ route('filament.admin.pages.hrdf-id') }}" class="submenu-item">
                                    <span class="module-font">HRDF ID</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.hrdf-claim-tracker') }}" class="submenu-item">
                                    <span class="module-font">HRDF Claim Tracker</span>
                                </a>
                            </div>
                        </div>

                        <!-- Headcount Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="headcount-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags submenu-icon"></i>
                                </div>
                                <span class="menu-text">Headcount Handover</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="headcount-submenu">
                                <a href="{{ route('filament.admin.pages.headcount-id') }}" class="submenu-item">
                                    <span class="module-font">Headcount ID</span>
                                </a>
                            </div>
                        </div>

                        <!-- Reseller Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="reseller-handover-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags submenu-icon"></i>
                                </div>
                                <span class="menu-text">Reseller Handover</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="reseller-handover-submenu">
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Reseller ID</span>
                                </a>
                            </div>
                        </div>

                        <!-- Finance Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="finance-handover-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags submenu-icon"></i>
                                </div>
                                <span class="menu-text">Finance Handover</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="finance-handover-submenu">
                                <a href="{{ route('filament.admin.pages.finance-handover-list') }}" class="submenu-item">
                                    <span class="module-font">Finance ID</span>
                                </a>
                            </div>
                        </div>

                        <!-- Repair Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="repair-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags submenu-icon"></i>
                                </div>
                                <span class="menu-text">Repair Handover</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="repair-submenu">
                                <a href="{{ route('filament.admin.pages.repair.onsite-repair-list') }}" class="submenu-item">
                                    <span class="module-font">OnSite Repair List</span>
                                </a>
                                @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                                    <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                        <span class="module-font">InHouse Repair List</span>
                                    </a>
                                @endif
                                <a href="{{ route('filament.admin.pages.technician-calendar') }}" class="submenu-item">
                                    <span class="module-font">Technician Calendar</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Admin Section -->
                <div id="admin-section" class="section-content">

                    <!-- Admin Software Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-software-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - Software</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-software-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Hardware Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-hardware-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - Hardware</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-hardware-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin OnSite Repair Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-onsite-repair-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - OnSite Repair</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-onsite-repair-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin InHouse Repair Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-inhouse-repair-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - InHouse Repair</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-inhouse-repair-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Renewal Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-renewal-v1-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - Renewal v1</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-renewal-v1-submenu">
                            <a href="{{ route('filament.admin.pages.admin-renewal-raw-data') }}" class="submenu-item">
                                <span class="module-font">Raw Data</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.admin-renewal-process-data') }}" class="submenu-item">
                                <span class="module-font">Process Data</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.admin-renewal-process-data-myr') }}" class="submenu-item">
                                <span class="module-font">Process Data - MYR</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.admin-renewal-process-data-usd') }}" class="submenu-item">
                                <span class="module-font">Process Data - USD</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.renewal-data-analysis') }}" class="submenu-item">
                                <span class="module-font">Process Data - Analysis</span>
                            </a>
                        </div>
                    </div>

                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-renewal-v2-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - Renewal v2</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-renewal-v2-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Debtor Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-debtor-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - Debtor</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-debtor-submenu">
                            <a href="{{ route('filament.admin.pages.debtor-aging-raw-data') }}" class="submenu-item">
                                <span class="module-font">Raw Data</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.debtor-aging-process-data') }}" class="submenu-item">
                                <span class="module-font">Process Data</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin Training Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-training-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - Training</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-training-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin HRDF Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-hrdf-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - HRDF</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-hrdf-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Finance Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-finance-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - Finance</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-finance-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin General Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-general-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin - General</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-general-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trainer Section -->
                <div id="trainer-section" class="section-content">
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="trainer-dashboard-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Training Management</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="trainer-dashboard-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Implementer Section -->
                <div id="implementer-section" class="section-content">
                    <!-- Implementer Calendar Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-calendar-submenu-2">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-calendar3 submenu-icon"></i>
                            </div>
                            <span class="menu-text">Calendar</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-calendar-submenu-2">
                            <a href="{{ route('filament.admin.pages.implementer-calendar') }}" class="submenu-item">
                                <span class="module-font">Implementer Calendar</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.calendar') }}" class="submenu-item">
                                <span class="module-font">Department Calendar</span>
                            </a>
                        </div>
                    </div>

                    <!-- Implementer Information Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-information-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Implementer - Info</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-information-submenu">
                            <a href="{{ route('filament.admin.pages.implementer-audit-list') }}" class="submenu-item">
                                <span class="module-font">Project Sequence</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.policy-management') }}" class="submenu-item">
                                <span class="module-font">Implementer Policy</span>
                            </a>
                        </div>
                    </div>

                    <!-- Implementer Audit List Section -->
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.implementer-request-count'))
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-audit-list-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags"></i>
                                </div>
                                <span class="menu-text">Implementer - Audit List</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="implementer-audit-list-submenu">
                                <a href="{{ route('filament.admin.pages.implementer-request-count') }}" class="submenu-item">
                                    <span class="module-font">Request Count</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.implementer-request-list') }}" class="submenu-item">
                                    <span class="module-font">Request List</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.demo-ranking'))
                        <!-- Follow Up Template Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-followup-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags"></i>
                                </div>
                                <span class="menu-text">Follow Up Template</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="implementer-followup-submenu">
                                <a href="{{ route('filament.admin.resources.email-templates.index') }}" class="submenu-item">
                                    <span class="module-font">By Default</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Support Section -->
                <div id="support-section" class="section-content">
                    <!-- Repair Handover Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="support-repair-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Support Call Log</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="support-repair-submenu">
                            <a href="{{ route('filament.admin.pages.call-logs') }}" class="submenu-item">
                                <span class="module-font">Call Log - List</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.call-log-analysis') }}" class="submenu-item">
                                <span class="module-font">Call Log - Analysis</span>
                            </a>
                            <a href="{{ route('filament.admin.resources.call-categories.index') }}" class="submenu-item">
                                <span class="module-font">Call Log - Category</span>
                            </a>
                        </div>
                    </div>

                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="support-information-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Support Information</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="support-information-submenu">
                            <a href="{{ route('filament.admin.pages.support-calendar') }}" class="submenu-item">
                                <span class="module-font">Support Calendar</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Technician Section -->
                <div id="technician-section" class="section-content">
                    <!-- Repair Handover Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="technician-repair-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Repair Handover</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="technician-repair-submenu">
                            <a href="{{ route('filament.admin.pages.repair.onsite-repair-list') }}" class="submenu-item">
                                <span class="module-font">OnSite Repair List</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">InHouse Repair List</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.technician-calendar') }}" class="submenu-item">
                                <span class="module-font">Technician Calendar</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.technician-appointment') }}" class="submenu-item">
                                <span class="module-font">Technician Appointment</span>
                            </a>
                        </div>
                    </div>
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="technician-door-access-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Door Access Handover</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="technician-door-access-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Marketing Section -->
                <div id="marketing-section" class="section-content">
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.marketing-analysis'))
                        <!-- Analysis Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="marketing-analysis-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags"></i>
                                </div>
                                <span class="menu-text">Analysis</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="marketing-analysis-submenu">
                                <a href="{{ route('filament.admin.pages.marketing-analysis') }}" class="submenu-item">
                                    <span class="module-font">Marketing Analysis</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.demo-analysis-table-form') }}" class="submenu-item">
                                    <span class="module-font">Demo Analysis</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.revenue') }}" class="submenu-item">
                                    <span class="module-font">Revenue</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.revenue-analysis') }}" class="submenu-item">
                                    <span class="module-font">Revenue Analysis</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="menu-block">
                            <a href="#" class="menu-item">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-megaphone menu-icon"></i>
                                </div>
                                <span class="menu-text">Marketing Dashboard</span>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Internal Section -->
                <div id="internal-section" class="section-content">
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="internal-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi-box-arrow-in-down-right"></i>
                            </div>
                            <span class="menu-text">TimeTec HR - Internal</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="internal-submenu">
                            <a href="{{ route('filament.admin.pages.calendar') }}" class="submenu-item">
                                <span class="module-font">Department Calendar</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- External Section -->
                <div id="external-section" class="section-content">
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="external-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </div>
                            <span class="menu-text">TimeTec HR - External</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="external-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Knowledge Base</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Ticketing System Section -->
                <div id="ticketing-section" class="section-content">
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="ticketing-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Ticketing System</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="ticketing-submenu">
                            <a href="{{ route('filament.admin.pages.ticket-dashboard') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.ticket-list') }}" class="submenu-item">
                                <span class="module-font">Ticket List</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Portal Section -->
                <div id="system-portal-section" class="section-content">
                    <!-- Admin Portal HR Version 2 -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-portal-hr-v2-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Admin Portal HR V2</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-portal-hr-v2-submenu">
                            <a href="{{ route('filament.admin.pages.admin-portal-hr-v2') }}" class="submenu-item">
                                <span class="module-font">Raw Data</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Customer Portal Stage 1 -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="customer-portal-stage1-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Customer Portal Stage 1</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="customer-portal-stage1-submenu">
                            <a href="{{ route('filament.admin.pages.customer-portal-raw-data') }}" class="submenu-item">
                                <span class="module-font">Raw Data</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Customer Portal Stage 2 -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="customer-portal-stage2-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Customer Portal Stage 2</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="customer-portal-stage2-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Raw Data</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Reseller Portal -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="reseller-portal-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags"></i>
                            </div>
                            <span class="menu-text">Reseller Portal</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="reseller-portal-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Raw Data</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Settings Section -->
                <div id="settings-section" class="section-content">
                    @if(auth()->user()->hasAccessToAny([
                        'filament.admin.resources.products.index',
                        'filament.admin.resources.industries.index',
                        'filament.admin.resources.lead-sources.index',
                        'filament.admin.resources.invalid-lead-reasons.index',
                        'filament.admin.resources.resellers.index',
                        'filament.admin.resources.users.index'
                    ]))
                        <!-- System Label Section -->
                        @if(auth()->user()->hasAccessToAny([
                            'filament.admin.resources.products.index',
                            'filament.admin.resources.industries.index',
                            'filament.admin.resources.lead-sources.index',
                            'filament.admin.resources.invalid-lead-reasons.index',
                            'filament.admin.resources.resellers.index',
                            'filament.admin.resources.installers.index'
                        ]))
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="settings-system-label-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags"></i>
                                </div>
                                <span class="menu-text">System Label</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="settings-system-label-submenu">
                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.device-models.index'))
                                <a href="{{ route('filament.admin.resources.device-models.index') }}" class="submenu-item">
                                    <span class="module-font">Device Model</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.shipping-device-models.index'))
                                <a href="{{ route('filament.admin.resources.shipping-device-models.index') }}" class="submenu-item">
                                    <span class="module-font">Shipping Device Model</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.products.index'))
                                <a href="{{ route('filament.admin.resources.products.index') }}" class="submenu-item">
                                    <span class="module-font">Product</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.industries.index'))
                                <a href="{{ route('filament.admin.resources.industries.index') }}" class="submenu-item">
                                    <span class="module-font">Industries</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.lead-sources.index'))
                                <a href="{{ route('filament.admin.resources.lead-sources.index') }}" class="submenu-item">
                                    <span class="module-font">Lead Source</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.invalid-lead-reasons.index'))
                                <a href="{{ route('filament.admin.resources.invalid-lead-reasons.index') }}" class="submenu-item">
                                    <span class="module-font">Invalid Lead Source</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.resellers.index'))
                                <a href="{{ route('filament.admin.resources.resellers.index') }}" class="submenu-item">
                                    <span class="module-font">Reseller</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.installers.index'))
                                <a href="{{ route('filament.admin.resources.installers.index') }}" class="submenu-item">
                                    <span class="module-font">Installers</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.spare-parts.index'))
                                <a href="{{ route('filament.admin.resources.spare-parts.index') }}" class="submenu-item">
                                    <span class="module-font">SparePart</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.policy-categories.index'))
                                <a href="{{ route('filament.admin.resources.policy-categories.index') }}" class="submenu-item">
                                    <span class="module-font">Policy Category</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.policy.index'))
                                <a href="{{ route('filament.admin.resources.policies.index') }}" class="submenu-item">
                                    <span class="module-font">Policy</span>
                                </a>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Access Right Section -->
                        @if(auth()->user()->hasAccessToAny(['filament.admin.resources.users.index', 'filament.admin.pages.future-enhancement']))
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="settings-access-right-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tags"></i>
                                </div>
                                <span class="menu-text">Access Right</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="settings-access-right-submenu">
                                @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">System Role</span>
                                </a>
                                @endif

                                @if(auth()->user()->hasRouteAccess('filament.admin.resources.users.index'))
                                <a href="{{ route('filament.admin.resources.users.index') }}" class="submenu-item">
                                    <span class="module-font">System Admin</span>
                                </a>
                                @endif
                            </div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const expandSidebarButton = document.getElementById('expand-sidebar');
            const collapseSidebarButton = document.getElementById('collapse-sidebar');
            const expandedSidebar = document.getElementById('expanded-sidebar');
            const iconLinks = document.querySelectorAll('.icon-link');
            const sectionContents = document.querySelectorAll('.section-content');
            const dashboardIcon = document.querySelector('.icon-logo');

            // Enhanced tooltip positioning for all icon links
            const iconLinksWithTooltips = document.querySelectorAll('.icon-link, .icon-logo');

            iconLinksWithTooltips.forEach(link => {
                const tooltip = link.querySelector('.icon-tooltip');
                if (tooltip) {
                    let isHovering = false;

                    // Function to update tooltip position
                    function updateTooltipPosition() {
                        if (isHovering) {
                            const rect = link.getBoundingClientRect();
                            tooltip.style.top = (rect.top + (rect.height / 2)) + 'px';
                            tooltip.style.left = '70px'; // Fixed position outside sidebar
                        }
                    }

                    // On mouse enter
                    link.addEventListener('mouseenter', function() {
                        isHovering = true;
                        updateTooltipPosition();
                    });

                    // On mouse leave
                    link.addEventListener('mouseleave', function() {
                        isHovering = false;
                    });

                    // Update position on scroll (both window and sidebar scroll)
                    window.addEventListener('scroll', updateTooltipPosition);

                    const iconContent = document.querySelector('.icon-content');
                    if (iconContent) {
                        iconContent.addEventListener('scroll', updateTooltipPosition);
                    }
                }
            });

            // Function to show a specific section and hide others
            function showSection(sectionId) {
                // Hide all sections
                sectionContents.forEach(section => {
                    section.classList.remove('active');
                });

                // Show the selected section
                const selectedSection = document.getElementById(sectionId + '-section');
                if (selectedSection) {
                    selectedSection.classList.add('active');
                }
            }

            // Expand sidebar when clicking the logo
            expandSidebarButton.addEventListener('click', function() {
                expandedSidebar.classList.add('active');
                showSection('dashboard');
            });

            // Collapse sidebar when clicking the back button
            collapseSidebarButton.addEventListener('click', function() {
                expandedSidebar.classList.remove('active');
                iconLinks.forEach(link => link.classList.remove('active'));
            });

            // Dashboard icon behavior
            dashboardIcon.addEventListener('click', function(e) {
                // Add active class to show blue background
                iconLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                // If using modifier keys or right-click, let the default browser behavior happen
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) {
                    return true;
                }

                // For normal left-click, prevent default and handle the navigation manually
                e.preventDefault();
                window.location.href = this.getAttribute('href');
            });

            // Icon links behavior
            iconLinks.forEach(link => {
                if (!link.classList.contains('dashboard')) {
                    link.addEventListener('click', function() {
                        const sectionId = this.getAttribute('data-section');
                        iconLinks.forEach(l => l.classList.remove('active'));
                        this.classList.add('active');
                        expandedSidebar.classList.add('active');
                        showSection(sectionId);
                    });
                }
            });

            // COMPLETE REWRITE OF DROPDOWN FUNCTIONALITY
            // Use direct click handlers for all nested dropdown triggers
            const allDropdownTriggers = document.querySelectorAll('.nested-dropdown-trigger');

            allDropdownTriggers.forEach(trigger => {
                // Get the submenu ID from the data attribute
                const submenuId = trigger.getAttribute('data-submenu');
                const submenu = document.getElementById(submenuId);

                if (submenu) {
                    // Add click event listener
                    trigger.onclick = function(e) {
                        // Stop propagation and prevent default behavior
                        e.preventDefault();
                        e.stopPropagation();

                        // Toggle the submenu visibility
                        if (submenu.style.maxHeight) {
                            submenu.style.maxHeight = null;
                            submenu.classList.remove('active');

                            // Reset arrow rotation
                            const arrow = this.querySelector('.menu-arrow');
                            if (arrow) {
                                arrow.style.transform = '';
                            }
                        } else {
                            submenu.style.maxHeight = submenu.scrollHeight + "px";
                            submenu.classList.add('active');

                            // Rotate arrow
                            const arrow = this.querySelector('.menu-arrow');
                            if (arrow) {
                                arrow.style.transform = 'rotate(180deg)';
                            }
                        }
                    };
                }
            });

            // URL hash handling
            const urlHash = window.location.hash.substring(1);
            if (urlHash && document.getElementById(urlHash + '-section')) {
                expandedSidebar.classList.add('active');
                showSection(urlHash);

                const icon = document.querySelector(`.icon-link[data-section="${urlHash}"]`);
                if (icon) {
                    icon.classList.add('active');
                }
            }
        });
    </script>
@endauth
