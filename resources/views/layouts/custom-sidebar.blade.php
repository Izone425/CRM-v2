<!-- filepath: /var/www/html/timeteccrm/resources/views/layouts/custom-sidebar.blade.php -->
@auth
    <link rel="stylesheet" href="{{ asset('css/custom-sidebar.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* Additional CSS for future enhancement sections */
        .future-enhancement {
            color: #6b7280;
            padding: 8px 15px;
            display: flex;
            align-items: center;
        }

        .future-enhancement i {
            margin-right: 10px;
        }

        .dropdown-subcategory-heading {
            padding: 8px 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #e8daf0;  /* Lighter purple/pink for text */
            letter-spacing: 0.05em;
            background: linear-gradient(to right, #7d5fd6, #5d47a6);  /* Purple gradient background */
            border-left: 3px solid #c8ceee;  /* Light blue-purple accent */
        }
        /* Add to your <style> section */
        .dropdown-subcategory-heading.collapsible {
            cursor: pointer;
            position: relative;
        }

        .dropdown-subcategory-heading.collapsible::after {
            content: "\F282"; /* Bootstrap icon for chevron-down */
            font-family: "bootstrap-icons";
            position: absolute;
            right: 15px;
            transition: transform 0.3s ease;
        }

        .dropdown-subcategory-heading.collapsible.expanded::after {
            transform: rotate(180deg);
        }

        .subcategory-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .subcategory-content.expanded {
            max-height: 500px; /* Adjust based on your content */
        }

        /* Initially expand the first subcategory */
        .nested-dropdown-content .subcategory-content:first-of-type {
            max-height: 500px;
        }

        .sidebar-nav > .sidebar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            padding: 12px 0;
            position: relative;
            text-align: center;
        }

        .sidebar-nav > .sidebar-item .sidebar-icon {
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.25rem;
            margin-bottom: 4px;
        }

        .sidebar-nav > .sidebar-item .sidebar-label {
            font-size: 0.6rem;
            color: #8eb2e4;
            line-height: 1;
        }

        .sidebar-nav > .sidebar-item:hover .sidebar-label,
        .sidebar-nav > .sidebar-item.active .sidebar-label {
            color: #3b82f6;
        }

        /* For dropdown triggers which are direct children of sidebar-nav */
        .sidebar-nav > .sidebar-dropdown > .dropdown-trigger .sidebar-icon {
            margin-bottom: 4px;
        }
    </style>

    <div class="edge-trigger"></div>

    <!-- Main Sidebar (Level 1) -->
    <div class="custom-sidebar">
        <!-- Logo Section -->
        <div class="sidebar-logo">
            <a href="{{ route('filament.admin.pages.dashboard-form') }}">
                <img src="{{ asset('storage/img/logo-timetec.svg') }}" alt="TimeTec CRM Logo">
            </a>
        </div>

        <!-- Main Navigation -->
        <div class="sidebar-nav">
            <!-- Dashboard - Always visible for all users -->
            <a href="{{ route('filament.admin.pages.dashboard-form') }}"
                class="sidebar-item {{ request()->routeIs('filament.admin.pages.dashboard-form*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <i class="bi bi-house"></i>
                </div>
                <div class="sidebar-label">Dashboard</div>
            </a>

            <!-- Leads Section -->
            @if(auth()->user()->hasRouteAccess('filament.admin.resources.leads.index'))
            <a href="{{ route('filament.admin.resources.leads.index') }}"
                title="Leads"
                class="sidebar-item {{ request()->routeIs('filament.admin.resources.leads.*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="sidebar-label">Leads</div>
            </a>
            @endif

            <!-- Lead Owner Section (with nested dropdowns) -->

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
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; padding: 12px 0; position: relative; text-align: center;">
                    <div class="sidebar-icon" style="display: flex; justify-content: center; align-items: center; font-size: 1.25rem; margin-bottom: 4px;">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div class="sidebar-label" style="font-size: 0.6rem; color: #8eb2e4; line-height: 1;">Lead Owner</div>
                </div>

                <!-- Level 2 dropdown content -->
                <div class="dropdown-content">
                    <div class="dropdown-category-heading">LEAD OWNER</div>

                    <!-- Sales Admin Section -->
                    @if(auth()->user()->hasAccessToAny([
                        'filament.admin.pages.monthly-calendar',
                        'filament.admin.pages.weekly-calendar-v2',
                        'filament.admin.pages.chat-room',
                        'filament.admin.pages.sales-admin-analysis-v1',
                        'filament.admin.pages.sales-admin-analysis-v2',
                        'filament.admin.pages.sales-admin-analysis-v3',
                        'filament.admin.pages.demo-ranking'
                    ]))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-briefcase"></i>
                            <span>Sales Admin</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <div class="dropdown-subcategory-heading collapsible">
                                <i class="bi bi-dot"></i>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Calendar</div>
                            <div class="subcategory-content">
                                <a href="{{ route('filament.admin.pages.calendar') }}" class="sidebar-item">
                                    <i class="bi bi-calendar"></i>
                                    <span>SalesPerson - Calendar V1</span>
                                </a>

                                <a href="{{ route('filament.admin.pages.weekly-calendar-v2') }}" class="sidebar-item">
                                    <i class="bi bi-calendar-week"></i>
                                    <span>SalesPerson - Calendar V2</span>
                                </a>
                            </div>

                            <div class="dropdown-subcategory-heading collapsible">
                                <i class="bi bi-dot"></i>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Prospects Automation</div>
                            <div class="subcategory-content">
                                <a href="{{ route('filament.admin.pages.chat-room') }}" class="sidebar-item">
                                    <i class="bi bi-chat-square-text"></i>
                                    <span>WhatsApp</span>
                                </a>
                                @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-envelope"></i>
                                    <span>Email</span>
                                </a>
                                @endif
                            </div>

                            <div class="dropdown-subcategory-heading collapsible">
                                <i class="bi bi-dot"></i>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Analysis</div>
                            <div class="subcategory-content">
                                <a href="{{ route('filament.admin.pages.sales-admin-analysis-v1') }}" class="sidebar-item">
                                    <i class="bi bi-clipboard-data"></i>
                                    <span>Sales Admin - Leads</span>
                                </a>

                                <a href="{{ route('filament.admin.pages.sales-admin-analysis-v2') }}" class="sidebar-item">
                                    <i class="bi bi-graph-up"></i>
                                    <span>Sales Admin - Performance</span>
                                </a>

                                <a href="{{ route('filament.admin.pages.sales-admin-analysis-v3') }}" class="sidebar-item">
                                    <i class="bi bi-check2-square"></i>
                                    <span>Sales Admin - Action Task</span>
                                </a>

                                @if(auth()->user()->hasRouteAccess('filament.admin.pages.demo-ranking'))
                                    <a href="{{ route('filament.admin.pages.demo-ranking') }}" class="sidebar-item {{ request()->routeIs('filament.admin.pages.demo-ranking*') ? 'active' : '' }}">
                                        <i class="bi bi-award"></i>
                                        <span>Demo Ranking</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Partnership Section -->
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                    <div class="nested-dropdown" id="partnership-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-people"></i>
                            <span>Partnership</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <div class="future-enhancement">
                                <i class="bi bi-stars"></i>
                                <span>Future Enhancement</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Tele-Marketing Section -->
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-telephone"></i>
                            <span>Tele-Marketing</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <div class="future-enhancement">
                                <i class="bi bi-stars"></i>
                                <span>Future Enhancement</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Salesperson Section (with nested dropdowns) -->
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
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; padding: 12px 0; position: relative; text-align: center;">
                    <div class="sidebar-icon" style="display: flex; justify-content: center; align-items: center; font-size: 1.25rem; margin-bottom: 4px;">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div class="sidebar-label" style="font-size: 0.6rem; color: #8eb2e4; line-height: 1;">Salesperson</div>
                </div>

                <!-- Level 2 dropdown content -->
                <div class="dropdown-content">
                    <div class="dropdown-category-heading">SalesPerson</div>

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.search-lead'))
                        <a href="{{ route('filament.admin.pages.search-lead') }}" class="sidebar-item">
                            <i class="bi bi-search"></i>
                            <span>Search Lead</span>
                        </a>
                    @endif

                    <!-- Calendar Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-calendar-range"></i>
                            <span>Calendar</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.calendar') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>SalesPerson - Calendar V1</span>
                            </a>
                        </div>
                    </div>

                    <!-- Commercial Part Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-briefcase"></i>
                            <span>Commercial Part</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.resources.quotations.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Quotation</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.proforma-invoices') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Proforma Invoice</span>
                            </a>

                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [2]))
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Invoice</span>
                                </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [2]))
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Sales Order</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Analysis Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-graph-up"></i>
                            <span>Analysis</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.lead-analysis') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Lead Analysis</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.demo-analysis') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Demo Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Forecast Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>Forecast</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.sales-forecast') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Forecast - Salesperson</span>
                            </a>

                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                                <a href="{{ route('filament.admin.pages.sales-forecast-summary') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Forecast - Summary</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Software Handover Section -->
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement') || in_array(auth()->user()->role_id, [2]))
                        <div class="nested-dropdown">
                            <div class="sidebar-item nested-dropdown-trigger">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Software Handover</span>
                                <i class="bi bi-chevron-down ms-auto"></i>
                            </div>

                            <!-- Level 3 dropdown content -->
                            <div class="nested-dropdown-content">
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Dashboard</span>
                                </a>

                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Analysis</span>
                                </a>

                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Attachment</span>
                                </a>
                            </div>
                        </div>

                        <!-- Hardware Handover Section -->
                        <div class="nested-dropdown">
                            <div class="sidebar-item nested-dropdown-trigger">
                                <i class="bi bi-cpu"></i>
                                <span>Hardware Handover</span>
                                <i class="bi bi-chevron-down ms-auto"></i>
                            </div>

                            <!-- Level 3 dropdown content -->
                            <div class="nested-dropdown-content">
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Dashboard</span>
                                </a>

                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Analysis</span>
                                </a>

                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Attachment</span>
                                </a>
                            </div>
                        </div>

                        <!-- HRDF Section -->
                        <div class="nested-dropdown">
                            <div class="sidebar-item nested-dropdown-trigger">
                                <i class="bi bi-people-fill"></i>
                                <span>HRDF</span>
                                <i class="bi bi-chevron-down ms-auto"></i>
                            </div>

                            <!-- Level 3 dropdown content -->
                            <div class="nested-dropdown-content">
                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Dashboard</span>
                                </a>

                                <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                    <i class="bi bi-dot"></i>
                                    <span>Analysis</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Admin Section (with nested dropdowns) -->
            @if(auth()->user()->hasAccessToAny([
                'filament.admin.pages.future-enhancement',
                'filament.admin.resources.software-handovers.index',
                'filament.admin.resources.hardware-handovers.index'
            ]))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; padding: 12px 0; position: relative; text-align: center;">
                    <div class="sidebar-icon" style="display: flex; justify-content: center; align-items: center; font-size: 1.25rem; margin-bottom: 4px;">
                        <i class="bi bi-gear"></i>
                    </div>
                    <div class="sidebar-label" style="font-size: 0.6rem; color: #8eb2e4; line-height: 1;">Admin</div>
                </div>

                <!-- Level 2 dropdown content -->
                <div class="dropdown-content">
                    <div class="dropdown-category-heading">ADMIN</div>

                    <!-- Admin Software Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-app-indicator"></i>
                            <span>Admin - Software</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.resources.software-handovers.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Analysis</span>
                            </a>

                            <a href="{{ route('filament.admin.resources.software-attachments.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Attachment</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin Hardware Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-cpu"></i>
                            <span>Admin - Hardware</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.resources.hardware-handovers.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Analysis</span>
                            </a>

                            <a href="{{ route('filament.admin.resources.hardware-attachments.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Attachment</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin Training Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-mortarboard"></i>
                            <span>Admin - Training</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Analysis</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Online Webinar Training</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Online HRDF Training</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Onsite Training</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin Finance Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-cash-coin"></i>
                            <span>Admin - Finance</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <div class="future-enhancement">
                                <i class="bi bi-stars"></i>
                                <span>Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin HRDF Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-people-fill"></i>
                            <span>Admin - HRDF</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <div class="future-enhancement">
                                <i class="bi bi-stars"></i>
                                <span>Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Renewal Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-arrow-repeat"></i>
                            <span>Admin - Renewal</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Admin General Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-gear-wide"></i>
                            <span>Admin - General</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <div class="future-enhancement">
                                <i class="bi bi-stars"></i>
                                <span>Future Enhancement</span>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Credit Controller Section -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-credit-card"></i>
                            <span>Admin - Credit Controller</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            <div class="future-enhancement">
                                <i class="bi bi-stars"></i>
                                <span>Future Enhancement</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Trainer Section (with nested dropdowns) -->
            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; padding: 12px 0; position: relative; text-align: center;">
                    <div class="sidebar-icon" style="display: flex; justify-content: center; align-items: center; font-size: 1.25rem; margin-bottom: 4px;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="sidebar-label" style="font-size: 0.6rem; color: #8eb2e4; line-height: 1;">Trainer</div>
                </div>

                <!-- Level 2 dropdown content -->
                <div class="dropdown-content">
                    <div class="dropdown-category-heading">TRAINER</div>

                    <!-- Trainer Dashboard -->
                    <div class="nested-dropdown">
                        <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                            <i class="bi bi-dot"></i>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                            <i class="bi bi-dot"></i>
                            <span>Analysis</span>
                        </a>

                        <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                            <i class="bi bi-dot"></i>
                            <span>Online Webinar Training</span>
                        </a>

                        <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                            <i class="bi bi-dot"></i>
                            <span>Online HRDF Training</span>
                        </a>

                        <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                            <i class="bi bi-dot"></i>
                            <span>Onsite Training</span>
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Implementer Section -->
            @if(auth()->user()->hasAccessToAny([
                // 'filament.admin.resources.software-handovers.index',
                // 'filament.admin.resources.hardware-handovers.index',
                'filament.admin.pages.future-enhancement'
            ]))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; padding: 12px 0; position: relative; text-align: center;">
                    <div class="sidebar-icon" style="display: flex; justify-content: center; align-items: center; font-size: 1.25rem; margin-bottom: 4px;">
                        <i class="bi bi-tools"></i>
                    </div>
                    <div class="sidebar-label" style="font-size: 0.6rem; color: #8eb2e4; line-height: 1;">Implementer</div>
                </div>

                <!-- Level 2 dropdown content -->
                <div class="dropdown-content">
                    <div class="dropdown-category-heading">IMPLEMENTER</div>

                    <!-- Software Handover -->
                    @if(auth()->user()->hasAccessToAny(['filament.admin.resources.software-handovers.index', 'filament.admin.pages.future-enhancement']))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-app-indicator"></i>
                            <span>Software Handover</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            @if(auth()->user()->hasRouteAccess('filament.admin.resources.software-handovers.index'))
                            <a href="{{ route('filament.admin.resources.software-handovers.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Dashboard</span>
                            </a>
                            @endif

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Analysis</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Attachment</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Hardware Handover -->
                    @if(auth()->user()->hasAccessToAny(['filament.admin.resources.hardware-handovers.index', 'filament.admin.pages.future-enhancement']))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-cpu"></i>
                            <span>Hardware Handover</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            @if(auth()->user()->hasRouteAccess('filament.admin.resources.hardware-handovers.index'))
                            <a href="{{ route('filament.admin.resources.hardware-handovers.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Dashboard</span>
                            </a>
                            @endif

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Analysis</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Attachment</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Project -->
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-kanban"></i>
                            <span>Project</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Analysis</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-bookmarks-fill"></i>
                            <span>Project Category</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Open</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Delay</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Inactive</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Closed</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Other -->
                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-three-dots"></i>
                            <span>Other</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Paid Customization</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Free Enhancement</span>
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Support Section -->
            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; padding: 12px 0; position: relative; text-align: center;">
                    <div class="sidebar-icon" style="display: flex; justify-content: center; align-items: center; font-size: 1.25rem; margin-bottom: 4px;">
                        <i class="bi bi-headset"></i>
                    </div>
                    <div class="sidebar-label" style="font-size: 0.6rem; color: #8eb2e4; line-height: 1;">Support</div>
                </div>

                <div class="dropdown-content">
                    <div class="dropdown-category-heading">SUPPORT</div>

                    <!-- Ticket -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-ticket-perforated"></i>
                            <span>Ticket</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Analysis</span>
                            </a>
                        </div>
                    </div>

                    <!-- Ticket Category -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-tags"></i>
                            <span>Ticket Category</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>New</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Working Support</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Working RND</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Awaiting Reply</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Closed</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Junk</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Other</span>
                            </a>
                        </div>
                    </div>

                    <!-- Other -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-three-dots"></i>
                            <span>Other</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Paid Customization</span>
                            </a>

                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Free Enhancement</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Marketing Section -->
            @if(auth()->user()->hasRouteAccess('filament.admin.pages.marketing-analysis'))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; padding: 12px 0; position: relative; text-align: center;">
                    <div class="sidebar-icon" style="display: flex; justify-content: center; align-items: center; font-size: 1.25rem; margin-bottom: 4px;">
                        <i class="bi bi-megaphone"></i>
                    </div>
                    <div class="sidebar-label" style="font-size: 0.6rem; color: #8eb2e4; line-height: 1;">Marketing</div>
                </div>

                <div class="dropdown-content">
                    <div class="dropdown-category-heading">MARKETING</div>

                    <!-- Analysis -->
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-graph-up"></i>
                            <span>Analysis</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            <a href="{{ route('filament.admin.pages.marketing-analysis') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Marketing Analysis</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Setting Section -->
            @if(auth()->user()->hasAccessToAny([
                'filament.admin.resources.products.index',
                'filament.admin.resources.industries.index',
                'filament.admin.resources.lead-sources.index',
                'filament.admin.resources.invalid-lead-reasons.index',
                'filament.admin.resources.resellers.index',
                'filament.admin.resources.users.index'
            ]))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; padding: 12px 0; position: relative; text-align: center;">
                    <div class="sidebar-icon" style="display: flex; justify-content: center; align-items: center; font-size: 1.25rem; margin-bottom: 4px;">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                    <div class="sidebar-label" style="font-size: 0.6rem; color: #8eb2e4; line-height: 1;">Setting</div>
                </div>

                <div class="dropdown-content">
                    <div class="dropdown-category-heading">SETTING</div>

                    <!-- System Label -->
                    @if(auth()->user()->hasAccessToAny([
                        'filament.admin.resources.products.index',
                        'filament.admin.resources.industries.index',
                        'filament.admin.resources.lead-sources.index',
                        'filament.admin.resources.invalid-lead-reasons.index',
                        'filament.admin.resources.resellers.index',
                        'filament.admin.resources.installers.index'
                    ]))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-tag"></i>
                            <span>System Label</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            @if(auth()->user()->hasRouteAccess('filament.admin.resources.products.index'))
                            <a href="{{ route('filament.admin.resources.products.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Product</span>
                            </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.resources.industries.index'))
                            <a href="{{ route('filament.admin.resources.industries.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Industries</span>
                            </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.resources.lead-sources.index'))
                            <a href="{{ route('filament.admin.resources.lead-sources.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Lead Source</span>
                            </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.resources.invalid-lead-reasons.index'))
                            <a href="{{ route('filament.admin.resources.invalid-lead-reasons.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Invalid Lead Source</span>
                            </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.resources.resellers.index'))
                            <a href="{{ route('filament.admin.resources.resellers.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Reseller</span>
                            </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.resources.installers.index'))
                            <a href="{{ route('filament.admin.resources.installers.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>Installers</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Access Right -->
                    @if(auth()->user()->hasAccessToAny(['filament.admin.resources.users.index', 'filament.admin.pages.future-enhancement']))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-shield-lock"></i>
                            <span>Access Right</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>

                        <div class="nested-dropdown-content">
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.future-enhancement'))
                            {{-- <a href="{{ route('filament.admin.resources.roles.index') }}" class="sidebar-item"> --}}
                            <a href="{{ route('filament.admin.pages.future-enhancement') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>System Role</span>
                            </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.resources.users.index'))
                            <a href="{{ route('filament.admin.resources.users.index') }}" class="sidebar-item">
                                <i class="bi bi-dot"></i>
                                <span>System Admin</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Keep your existing script code here
            // Position the dropdown menus correctly
            const dropdownTriggers = document.querySelectorAll('.dropdown-trigger');
            const nestedDropdownTriggers = document.querySelectorAll('.nested-dropdown-trigger');
            const collapsibleHeadings = document.querySelectorAll('.dropdown-subcategory-heading.collapsible');

            // Set up click behavior for mobile
            collapsibleHeadings.forEach(heading => {
                heading.addEventListener('click', function() {
                    // Toggle expanded class
                    this.classList.toggle('expanded');

                    // Find the next subcategory-content
                    const content = this.nextElementSibling;
                    if (content.classList.contains('subcategory-content')) {
                        content.classList.toggle('expanded');
                    }

                    // Close other subcategories in same dropdown
                    const subcategories = this.parentElement.querySelectorAll('.subcategory-content');
                    subcategories.forEach(subcategory => {
                        if (subcategory !== content && subcategory.classList.contains('expanded')) {
                            subcategory.classList.remove('expanded');
                            subcategory.previousElementSibling.classList.remove('expanded');
                        }
                    });
                });
            });

            // Set up hover behavior for desktop
            if (window.innerWidth > 768) {
                collapsibleHeadings.forEach(heading => {
                    heading.addEventListener('mouseenter', function() {
                        // Expand this subcategory
                        this.classList.add('expanded');
                        const content = this.nextElementSibling;
                        if (content.classList.contains('subcategory-content')) {
                            content.classList.add('expanded');
                        }
                    });
                });

                // Add hover behavior to the entire dropdown content
                const nestedDropdownContents = document.querySelectorAll('.nested-dropdown-content');
                nestedDropdownContents.forEach(dropdown => {
                    dropdown.addEventListener('mouseleave', function() {
                        // Collapse all except the first one
                        const subcategories = this.querySelectorAll('.subcategory-content');
                        const headings = this.querySelectorAll('.dropdown-subcategory-heading.collapsible');

                        subcategories.forEach((subcategory, index) => {
                            if (index !== 0) {
                                subcategory.classList.remove('expanded');
                                if (headings[index]) {
                                    headings[index].classList.remove('expanded');
                                }
                            }
                        });
                    });
                });
            }

            function positionDropdowns() {
                dropdownTriggers.forEach(trigger => {
                    const dropdown = trigger.closest('.sidebar-dropdown');
                    const content = dropdown.querySelector('.dropdown-content');

                    // Get trigger's position relative to viewport
                    const triggerRect = trigger.getBoundingClientRect();

                    // Set dropdown top position to match trigger
                    content.style.top = triggerRect.top + 'px';
                });
            }

            // Position dropdowns on hover
            dropdownTriggers.forEach(trigger => {
                trigger.addEventListener('mouseenter', positionDropdowns);
            });

            // Mobile dropdown toggle
            if (window.innerWidth <= 768) {
                // Level 1 dropdowns
                dropdownTriggers.forEach(trigger => {
                    trigger.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const dropdown = this.closest('.sidebar-dropdown');
                        dropdown.classList.toggle('open');

                        // Close other dropdowns
                        dropdownTriggers.forEach(otherTrigger => {
                            const otherDropdown = otherTrigger.closest('.sidebar-dropdown');
                            if (otherDropdown !== dropdown && otherDropdown.classList.contains('open')) {
                                otherDropdown.classList.remove('open');
                            }
                        });
                    });
                });

                // Level 2/3 nested dropdowns
                nestedDropdownTriggers.forEach(trigger => {
                    trigger.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const nestedDropdown = this.closest('.nested-dropdown');
                        nestedDropdown.classList.toggle('open');

                        // Close other nested dropdowns at the same level
                        const parent = nestedDropdown.parentNode;
                        const siblings = parent.querySelectorAll('.nested-dropdown');
                        siblings.forEach(sibling => {
                            if (sibling !== nestedDropdown && sibling.classList.contains('open')) {
                                sibling.classList.remove('open');
                            }
                        });
                    });
                });
            }
        });
    </script>
@endauth
