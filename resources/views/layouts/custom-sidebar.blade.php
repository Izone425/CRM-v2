@auth
    <link rel="stylesheet" href="{{ asset('css/custom-sidebar.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

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
        }

        /* Icon sidebar - slim version */
        .icon-sidebar {
            width: 60px;
            min-width: 60px;
            height: 100vh;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.05);
            transition: width 0.3s ease;
            overflow: hidden;
            z-index: 1010;
        }

        /* Icon sidebar header */
        .icon-header {
            padding: 1rem;
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
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        .icon-content::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
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
            padding: 0;
            height: 44px;
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
            width: 32px;
            height: 32px;
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
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.01s ease, transform 0.01s ease;
            margin-left: 10px;
            z-index: 1020;
        }

        .icon-link:hover .icon-tooltip {
            opacity: 1;
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
            background-color: #F3F4F6;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .menu-item:hover .menu-icon-wrapper {
            background-color: #E5E7EB;
        }

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
            margin-top: 0.25rem;
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

        .icon-link[data-section="dashboard"] .icon { color: #6366F1; /* icon-gradient-1 */ }
        .icon-link[data-section="leads"] .icon { color: #0EA5E9; /* icon-gradient-2 */ }
        .icon-link[data-section="hrcalendar"] .icon { color: #0EA5E9; /* icon-gradient-2 */ }
        .icon-link[data-section="salesadmin"] .icon { color: #06B6D4; /* icon-gradient-3 */ }
        .icon-link[data-section="salesperson"] .icon { color: #14B8A6; /* icon-gradient-4 */ }
        .icon-link[data-section="admin"] .icon { color: #10B981; /* icon-gradient-5 */ }
        .icon-link[data-section="trainer"] .icon { color: #22C55E; /* icon-gradient-6 */ }
        .icon-link[data-section="implementer"] .icon { color: #84CC16; /* icon-gradient-7 */ }
        .icon-link[data-section="support"] .icon { color: #EAB308; /* icon-gradient-8 */ }
        .icon-link[data-section="technician"] .icon { color: #F59E0B; /* icon-gradient-9 */ }
        .icon-link[data-section="marketing"] .icon { color: #F97316; /* icon-gradient-10 */ }
        .icon-link[data-section="settings"] .icon { color: #EA580C; /* icon-gradient-21 */ }
    </style>

    <!-- Main Sidebar Container -->
    <div class="sidebar-container">
        <!-- Icon Sidebar - Collapsed by Default -->
        <div class="icon-sidebar">
            <!-- Icon Sidebar Header with App Logo -->
            <div class="icon-header">
                <div class="icon-logo" id="expand-sidebar">
                    <i class="bi bi-people"></i>
                </div>
            </div>

            <!-- Icon Sidebar Content -->
            <div class="icon-content">
                <!-- Dashboard Icon -->
                <div title="Dashboard">
                    <a href="{{ route('filament.admin.pages.dashboard-form') }}" class="icon-link dashboard" title="Dashboard" data-section="dashboard">
                        <i class="bi bi-grid icon"></i>
                    </a>
                </div>

                @if(in_array(auth()->user()->role_id, [1, 2, 3]))
                    <div title="Leads">
                        <a href="{{ route('filament.admin.resources.leads.index') }}" class="icon-link dashboard" title="Leads" data-section="leads">
                            <i class="bi bi-bullseye icon"></i>
                        </a>
                    </div>
                @endif

                <div title="HR Calendar">
                    <a href="{{ route('filament.admin.pages.department-calendar') }}" class="icon-link dashboard" title="HR Calendar" data-section="hrcalendar">
                        <i class="bi bi-calendar-check icon"></i>
                    </a>
                </div>

                @if(
                    (auth()->user()->hasAccessToAny([
                        'filament.admin.pages.monthly-calendar',
                        'filament.admin.pages.weekly-calendar-v2',
                        'filament.admin.pages.chat-room',
                        'filament.admin.pages.sales-admin-analysis-v1',
                        'filament.admin.pages.sales-admin-analysis-v2',
                        'filament.admin.pages.sales-admin-analysis-v3',
                        'filament.admin.pages.demo-ranking',
                        'filament.admin.pages.future-enhancement'
                    ]) && (auth()->user()->role_id == 1 || auth()->user()->role_id == 3))
                )
                    <div class="icon-link" data-section="salesadmin" title="Sales Admin">
                        <div class="icon-wrapper">
                            <i class="bi bi-people icon"></i>
                        </div>
                    </div>
                @endif

                @if(
                    (auth()->user()->hasAccessToAny([
                        'filament.admin.pages.monthly-calendar',
                        'filament.admin.resources.quotations.index',
                        'filament.admin.pages.proforma-invoices',
                        'filament.admin.pages.future-enhancement',
                        'filament.admin.pages.lead-analysis',
                        'filament.admin.pages.demo-analysis',
                        'filament.admin.pages.sales-forecast',
                        'filament.admin.pages.sales-forecast-summary'
                    ]) && (auth()->user()->role_id == 2 || auth()->user()->role_id == 3))
                )
                    <div class="icon-link" data-section="salesperson" title="Sales Person">
                        <div class="icon-wrapper">
                            <i class="bi bi-currency-dollar icon"></i>
                        </div>
                    </div>
                @endif

                @if(
                    (auth()->user()->hasAccessToAny([
                        'filament.admin.pages.future-enhancement',
                        'filament.admin.resources.software-handovers.index',
                        'filament.admin.resources.hardware-handovers.index'
                    ]) && (auth()->user()->additional_role == 1 || auth()->user()->role_id == 3))
                )
                    <div class="icon-link" data-section="admin" title="Admin">
                        <div class="icon-wrapper">
                            <i class="bi bi-shield icon"></i>
                        </div>
                    </div>
                @endif

                @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [6]))
                    <div class="icon-link" data-section="trainer" title="Trainer">
                        <div class="icon-wrapper">
                            <i class="bi bi-mortarboard icon"></i>
                        </div>
                    </div>
                @endif

                @if(auth()->user()->hasAccessToAny([
                    // 'filament.admin.resources.software-handovers.index',
                    // 'filament.admin.resources.hardware-handovers.index',
                    'filament.admin.pages.future-enhancement'
                ]) || in_array(auth()->user()->role_id, [4,5]) || auth()->user()->id == 43)
                    <div class="icon-link" data-section="implementer" title="Implementer">
                        <div class="icon-wrapper">
                            <i class="bi bi-wrench icon"></i>
                        </div>
                    </div>
                @endif

                @if(auth()->user()->role_id === 8 || auth()->user()->role_id === 3)
                    <div class="icon-link" data-section="support" title="Support">
                        <div class="icon-wrapper">
                            <i class="bi bi-headset icon"></i>
                        </div>
                    </div>
                @endif

                @if(auth()->user()->role_id == 9 || auth()->user()->role_id == 3)
                    <div class="icon-link" data-section="technician" title="Technician">
                        <div class="icon-wrapper">
                            <i class="bi bi-gear icon"></i>
                        </div>
                    </div>
                @endif

                @if(auth()->user()->hasRouteAccess('filament.admin.pages.marketing-analysis'))
                    <div class="icon-link" data-section="marketing" title="Marketing">
                        <div class="icon-wrapper">
                            <i class="bi bi-megaphone icon"></i>
                        </div>
                    </div>
                @endif

                <!-- Settings Icon -->
                @if(auth()->user()->hasAccessToAny([
                    'filament.admin.resources.products.index',
                    'filament.admin.resources.industries.index',
                    'filament.admin.resources.lead-sources.index',
                    'filament.admin.resources.invalid-lead-reasons.index',
                    'filament.admin.resources.resellers.index',
                    'filament.admin.resources.users.index'
                ]))
                    <div class="icon-link" data-section="settings" title="Settings">
                        <div class="icon-wrapper">
                            <i class="bi bi-gear icon"></i>
                        </div>
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
                        <span class="app-title">HR CRM</span>
                        <span class="app-subtitle">Management System</span>
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
                    <div class="section-heading">Sales Admin</div>

                    <!-- Calendar Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-calendar-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-calendar-range menu-icon"></i>
                            </div>
                            <span class="menu-text">Calendar</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-calendar-submenu">
                            <a href="{{ route('filament.admin.pages.calendar') }}" class="submenu-item">
                                <span class="module-font">SalesPerson - Calendar V1</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.weekly-calendar-v2') }}" class="submenu-item">
                                <span class="module-font">SalesPerson - Calendar V2</span>
                            </a>
                        </div>
                    </div>

                    <!-- Prospects Automation Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-prospects-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-chat-square-text menu-icon"></i>
                            </div>
                            <span class="menu-text">Prospects Automation</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-prospects-submenu">
                            <a href="{{ route('filament.admin.pages.chat-room') }}" class="submenu-item">
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
                                <i class="bi bi-graph-up menu-icon"></i>
                            </div>
                            <span class="menu-text">Analysis</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-analysis-submenu">
                            <a href="{{ route('filament.admin.pages.sales-admin-analysis-v1') }}" class="submenu-item">
                                <span class="module-font">Sales Admin - Leads</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.sales-admin-analysis-v2') }}" class="submenu-item">
                                <span class="module-font">Sales Admin - Performance</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.sales-admin-analysis-v3') }}" class="submenu-item">
                                <span class="module-font">Sales Admin - Action Task</span>
                            </a>
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.demo-ranking'))
                            <a href="{{ route('filament.admin.pages.demo-ranking') }}" class="submenu-item">
                                <span class="module-font">Demo Ranking</span>
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Partnership Section -->
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-partnership-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-people menu-icon"></i>
                            </div>
                            <span class="menu-text">Partnership</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-partnership-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Tele-Marketing Section -->
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-telemarketing-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-telephone menu-icon"></i>
                            </div>
                            <span class="menu-text">Tele-Marketing</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-telemarketing-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Salesperson Audit List Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="salesadmin-audit-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-list-ol menu-icon"></i>
                            </div>
                            <span class="menu-text">Salesperson Audit List</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="salesadmin-audit-submenu">
                            <a href="{{ route('filament.admin.pages.salesperson-audit-list') }}" class="submenu-item">
                                <span class="module-font">Lead Sequence</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- SalesPerson Section -->
                <div id="salesperson-section" class="section-content">
                    <div class="section-heading">Sales Person</div>
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.search-lead'))
                        <div class="menu-block">
                            <a href="{{ route('filament.admin.pages.search-lead') }}" class="menu-item">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-search menu-icon"></i>
                                </div>
                                <span class="menu-text">Search Lead</span>
                            </a>
                        </div>
                    @endif

                    <!-- Calendar Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="calendar-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-calendar-range menu-icon"></i>
                            </div>
                            <span class="menu-text">Calendar</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="calendar-submenu">
                            <a href="{{ route('filament.admin.pages.calendar') }}" class="submenu-item">
                                <span class="module-font">SalesPerson - Calendar V1</span>
                            </a>
                        </div>
                    </div>

                    <!-- Commercial Part Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="commercial-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-briefcase menu-icon"></i>
                            </div>
                            <span class="menu-text">Commercial Part</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="commercial-submenu">
                            <a href="{{ route('filament.admin.resources.quotations.index') }}" class="submenu-item">
                                <span class="module-font">Quotation</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.proforma-invoices') }}" class="submenu-item">
                                <span class="module-font">Proforma Invoice</span>
                            </a>
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [2]))
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Invoice</span>
                                </a>
                            @endif
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [2]))
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Sales Order</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Analysis Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="analysis-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-graph-up menu-icon"></i>
                            </div>
                            <span class="menu-text">Analysis</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="analysis-submenu">
                            <a href="{{ route('filament.admin.pages.lead-analysis') }}" class="submenu-item">
                                <span class="module-font">Lead Analysis</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.demo-analysis') }}" class="submenu-item">
                                <span class="module-font">Demo Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Forecast Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="forecast-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-graph-up-arrow menu-icon"></i>
                            </div>
                            <span class="menu-text">Forecast</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="forecast-submenu">
                            <a href="{{ route('filament.admin.pages.sales-forecast') }}" class="submenu-item">
                                <span class="module-font">Forecast - Salesperson</span>
                            </a>
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                                <a href="{{ route('filament.admin.pages.sales-forecast-summary') }}" class="submenu-item">
                                    <span class="module-font">Forecast - Summary</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [2]))
                        <!-- Software Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="software-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-box-arrow-right menu-icon"></i>
                                </div>
                                <span class="menu-text">Software Handover</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="software-submenu">
                                <a href="{{ route('filament.admin.resources.software-handovers.index') }}" class="submenu-item">
                                    <span class="module-font">Dashboard</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.software-handover-analysis') }}" class="submenu-item">
                                    <span class="module-font">Analysis</span>
                                </a>
                            </div>
                        </div>

                        <!-- Hardware Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="hardware-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-cpu menu-icon"></i>
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
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Device Stock Information</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Device Purchase Information</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Analysis</span>
                                </a>
                            </div>
                        </div>

                        <!-- Repair Handover Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="repair-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-tools menu-icon"></i>
                                </div>
                                <span class="menu-text">Repair Handover</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="repair-submenu">
                                <a href="{{ route('filament.admin.pages.admin-repair-dashboard') }}" class="submenu-item">
                                    <span class="module-font">Dashboard</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.technician-calendar') }}" class="submenu-item">
                                    <span class="module-font">Technician Calendar</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Analysis</span>
                                </a>
                            </div>
                        </div>

                        <!-- Salesperson Request Section -->
                        @if(auth()->user()->hasRouteAccess('filament.admin.pages.demo-ranking'))
                            <div class="menu-block">
                                <div class="menu-item nested-dropdown-trigger" data-submenu="request-submenu">
                                    <div class="menu-icon-wrapper">
                                        <i class="bi bi-diagram-3-fill menu-icon"></i>
                                    </div>
                                    <span class="menu-text">Salesperson Request</span>
                                    <i class="bi bi-chevron-down menu-arrow"></i>
                                </div>

                                <div class="submenu" id="request-submenu">
                                    <a href="{{ route('filament.admin.pages.salesperson-appointment') }}" class="submenu-item">
                                        <span class="module-font">Internal Task Request</span>
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Sales Pricing Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="pricing-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-coin menu-icon"></i>
                                </div>
                                <span class="menu-text">Sales Pricing</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="pricing-submenu">
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Software</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Hardware</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Door Access Accessories</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Others</span>
                                </a>
                            </div>
                        </div>

                        <!-- Sales Information Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="info-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-info-circle-fill menu-icon"></i>
                                </div>
                                <span class="menu-text">Sales Information</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="info-submenu">
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Sales Policy</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                    <span class="module-font">Hardware Handover Flow</span>
                                </a>
                            </div>
                        </div>

                        <!-- Implementer Audit List Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="audit-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-list-ol menu-icon"></i>
                                </div>
                                <span class="menu-text">Implementer Audit List</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="audit-submenu">
                                <a href="{{ route('filament.admin.pages.implementer-audit-list') }}" class="submenu-item">
                                    <span class="module-font">Project Sequence</span>
                                </a>
                            </div>
                        </div>

                        <!-- Implementer Calendar Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-calendar-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-calendar-week-fill menu-icon"></i>
                                </div>
                                <span class="menu-text">Implementer - Calendar</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="implementer-calendar-submenu">
                                <a href="{{ route('filament.admin.pages.implementer-calendar') }}" class="submenu-item">
                                    <span class="module-font">Implementer - Session</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.implementer-request-count') }}" class="submenu-item">
                                    <span class="module-font">Implementer - Request Count</span>
                                </a>
                                <a href="{{ route('filament.admin.pages.implementer-request-list') }}" class="submenu-item">
                                    <span class="module-font">Implementer - Request List</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Admin Section -->
                <div id="admin-section" class="section-content">
                    <div class="section-heading">Admin</div>

                    <!-- Admin Software Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-software-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-app-indicator menu-icon"></i>
                            </div>
                            <span class="menu-text">Admin - Software</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-software-submenu">
                            <a href="{{ route('filament.admin.resources.software-handovers.index') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.software-handover-analysis') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin Hardware Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-hardware-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-cpu menu-icon"></i>
                            </div>
                            <span class="menu-text">Admin - Hardware</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-hardware-submenu">
                            <a href="{{ route('filament.admin.pages.hardware-dashboard-all') }}" class="submenu-item">
                                <span class="module-font">Dashboard - All</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.hardware-dashboard-pending-stock') }}" class="submenu-item">
                                <span class="module-font">Dashboard - Pending Stock</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Device Stock Information</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Device Purchase Information</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin Repair Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-repair-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tools menu-icon"></i>
                            </div>
                            <span class="menu-text">Admin - Repair</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-repair-submenu">
                            <a href="{{ route('filament.admin.pages.admin-repair-dashboard') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.technician-calendar') }}" class="submenu-item">
                                <span class="module-font">Technician Calendar</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin Training Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-training-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-mortarboard menu-icon"></i>
                            </div>
                            <span class="menu-text">Admin - Training</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-training-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Online Webinar Training</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Online HRDF Training</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Onsite Training</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin Finance Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-finance-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-cash-coin menu-icon"></i>
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

                    <!-- Admin HRDF Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-hrdf-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-people-fill menu-icon"></i>
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

                    <!-- Admin Renewal Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-renewal-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-arrow-repeat menu-icon"></i>
                            </div>
                            <span class="menu-text">Admin - Renewal</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-renewal-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin General Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-general-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-gear-wide menu-icon"></i>
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

                    <!-- Admin Credit Controller Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="admin-credit-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-credit-card menu-icon"></i>
                            </div>
                            <span class="menu-text">Admin - Credit Controller</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="admin-credit-submenu">
                            <div class="submenu-item">
                                <span class="module-font"><i class="bi bi-stars"></i> Future Enhancement</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trainer Section -->
                <div id="trainer-section" class="section-content">
                    <div class="section-heading">Trainer</div>

                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="trainer-dashboard-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-mortarboard menu-icon"></i>
                            </div>
                            <span class="menu-text">Training Management</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="trainer-dashboard-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Online Webinar Training</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Onsite Training</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Implementer Section -->
                <div id="implementer-section" class="section-content">
                    <div class="section-heading">Implementer</div>

                    <!-- Software Handover Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-software-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-app-indicator menu-icon"></i>
                            </div>
                            <span class="menu-text">Software Handover</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-software-submenu">
                            <a href="{{ route('filament.admin.resources.software-handovers.index') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.software-handover-analysis') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Hardware Handover Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-hardware-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-cpu menu-icon"></i>
                            </div>
                            <span class="menu-text">Hardware Handover</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-hardware-submenu">
                            <a href="{{ route('filament.admin.pages.hardware-dashboard-all') }}" class="submenu-item">
                                <span class="module-font">Dashboard - All</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.hardware-dashboard-pending-stock') }}" class="submenu-item">
                                <span class="module-font">Dashboard - Pending Stock</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Device Stock Information</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Device Purchase Information</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Repair Handover Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-repair-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tools menu-icon"></i>
                            </div>
                            <span class="menu-text">Repair Handover</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-repair-submenu">
                            <a href="{{ route('filament.admin.pages.admin-repair-dashboard') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.technician-calendar') }}" class="submenu-item">
                                <span class="module-font">Technician Calendar</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Project Category Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-project-category-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-bookmarks-fill menu-icon"></i>
                            </div>
                            <span class="menu-text">Project Category</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-project-category-submenu">
                            <a href="{{ route('filament.admin.pages.project-category-open') }}" class="submenu-item">
                                <span class="module-font">Open</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.project-category-delay') }}" class="submenu-item">
                                <span class="module-font">Delay</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.project-category-inactive') }}" class="submenu-item">
                                <span class="module-font">Inactive</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.project-category-closed') }}" class="submenu-item">
                                <span class="module-font">Closed</span>
                            </a>
                        </div>
                    </div>

                    <!-- Project Request Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-project-request-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-three-dots menu-icon"></i>
                            </div>
                            <span class="menu-text">Project Request</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-project-request-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Paid Customization</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Free Enhancement</span>
                            </a>
                        </div>
                    </div>

                    <!-- Implementer Audit List Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-audit-list-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-list-ol menu-icon"></i>
                            </div>
                            <span class="menu-text">Implementer - Audit List</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-audit-list-submenu">
                            <a href="{{ route('filament.admin.pages.implementer-audit-list') }}" class="submenu-item">
                                <span class="module-font">Project Sequence</span>
                            </a>
                        </div>
                    </div>

                    <!-- Implementer Calendar Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-calendar-submenu-2">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-calendar-week-fill menu-icon"></i>
                            </div>
                            <span class="menu-text">Implementer - Calendar</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-calendar-submenu-2">
                            <a href="{{ route('filament.admin.pages.implementer-calendar') }}" class="submenu-item">
                                <span class="module-font">Implementer - Session</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.implementer-request-count') }}" class="submenu-item">
                                <span class="module-font">Implementer - Request Count</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.implementer-request-list') }}" class="submenu-item">
                                <span class="module-font">Implementer - Request List</span>
                            </a>
                        </div>
                    </div>

                    <!-- Follow Up Template Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="implementer-followup-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-envelope-check menu-icon"></i>
                            </div>
                            <span class="menu-text">Follow Up Template</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="implementer-followup-submenu">
                            <a href="{{ route('filament.admin.resources.email-templates.index') }}" class="submenu-item">
                                <span class="module-font">By Default</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.implementer-request-count') }}" class="submenu-item">
                                <span class="module-font">By Implementer</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Support Section -->
                <div id="support-section" class="section-content">
                    <div class="section-heading">Support</div>

                    <!-- Ticket Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="support-ticket-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-ticket-perforated menu-icon"></i>
                            </div>
                            <span class="menu-text">Ticket</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="support-ticket-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Ticket Category Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="support-category-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-tags menu-icon"></i>
                            </div>
                            <span class="menu-text">Ticket Category</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="support-category-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">New</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Working Support</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Working RND</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Awaiting Reply</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Closed</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Junk</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Other</span>
                            </a>
                        </div>
                    </div>

                    <!-- Other Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="support-other-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-three-dots menu-icon"></i>
                            </div>
                            <span class="menu-text">Other</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="support-other-submenu">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Paid Customization</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Free Enhancement</span>
                            </a>
                        </div>
                    </div>

                    <!-- Repair Handover Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="support-repair-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-cpu menu-icon"></i>
                            </div>
                            <span class="menu-text">Repair Handover</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="support-repair-submenu">
                            <a href="{{ route('filament.admin.pages.admin-repair-dashboard') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.technician-calendar') }}" class="submenu-item">
                                <span class="module-font">Technician Calendar</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Technician Section -->
                <div id="technician-section" class="section-content">
                    <div class="section-heading">Technician</div>

                    <!-- Repair Handover Section -->
                    <div class="menu-block">
                        <div class="menu-item nested-dropdown-trigger" data-submenu="technician-repair-submenu">
                            <div class="menu-icon-wrapper">
                                <i class="bi bi-cpu menu-icon"></i>
                            </div>
                            <span class="menu-text">Repair Handover</span>
                            <i class="bi bi-chevron-down menu-arrow"></i>
                        </div>

                        <div class="submenu" id="technician-repair-submenu">
                            <a href="{{ route('filament.admin.pages.admin-repair-dashboard') }}" class="submenu-item">
                                <span class="module-font">Dashboard</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.technician-appointment') }}" class="submenu-item">
                                <span class="module-font">Technician Appointment</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="submenu-item">
                                <span class="module-font">Analysis</span>
                            </a>
                            <a href="{{ route('filament.admin.pages.technician-calendar') }}" class="submenu-item">
                                <span class="module-font">Technician Calendar</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Marketing Section -->
                <div id="marketing-section" class="section-content">
                    <div class="section-heading">Marketing</div>

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.marketing-analysis'))
                        <!-- Analysis Section -->
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="marketing-analysis-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-graph-up menu-icon"></i>
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

                <!-- Settings Section -->
                <div id="settings-section" class="section-content">
                    <div class="section-heading">Settings</div>

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
                                    <i class="bi bi-tag menu-icon"></i>
                                </div>
                                <span class="menu-text">System Label</span>
                                <i class="bi bi-chevron-down menu-arrow"></i>
                            </div>

                            <div class="submenu" id="settings-system-label-submenu">
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
                            </div>
                        </div>
                        @endif

                        <!-- Access Right Section -->
                        @if(auth()->user()->hasAccessToAny(['filament.admin.resources.users.index', 'filament.admin.pages.future-enhancement']))
                        <div class="menu-block">
                            <div class="menu-item nested-dropdown-trigger" data-submenu="settings-access-right-submenu">
                                <div class="menu-icon-wrapper">
                                    <i class="bi bi-shield-lock menu-icon"></i>
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
            const dashboardIcon = document.querySelector('.icon-link.dashboard');

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
