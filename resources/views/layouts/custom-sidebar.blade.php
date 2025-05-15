<!-- filepath: /var/www/html/timeteccrm/resources/views/layouts/custom-sidebar.blade.php -->
@auth
    <link rel="stylesheet" href="{{ asset('css/custom-sidebar.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

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
            title="Dashboard"
            class="sidebar-item {{ request()->routeIs('filament.admin.pages.dashboard-form*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <i class="bi bi-house"></i>
                </div>
            </a>

            <!-- Leads Section -->
            @if(auth()->user()->hasRouteAccess('filament.admin.resources.leads.index'))
            <a href="{{ route('filament.admin.resources.leads.index') }}"
            title="Leads"
            class="sidebar-item {{ request()->routeIs('filament.admin.resources.leads.*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
            </a>
            @endif

            <!-- Quotations Section -->
            @if(auth()->user()->hasRouteAccess('filament.admin.resources.quotations.index'))
            <a href="{{ route('filament.admin.resources.quotations.index') }}"
            title="Quotations"
            class="sidebar-item {{ request()->routeIs('filament.admin.resources.quotations.*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <i class="bi bi-collection-fill"></i>
                </div>
            </a>
            @endif

            <!-- Proforma Invoices Section -->
            @if(auth()->user()->hasRouteAccess('filament.admin.pages.proforma-invoices'))
            <a href="{{ route('filament.admin.pages.proforma-invoices') }}"
            title="Proforma Invoices"
            class="sidebar-item {{ request()->routeIs('filament.admin.pages.proforma-invoices*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <i class="bi bi-file-earmark-richtext"></i>
                </div>
            </a>
            @endif

            <!-- Whatsapp Section -->
            @if(auth()->user()->hasRouteAccess('filament.admin.pages.chat-room'))
            <a href="{{ route('filament.admin.pages.chat-room') }}"
            title="Whatsapp"
            class="sidebar-item {{ request()->routeIs('filament.admin.pages.chat-room*') ? 'active' : '' }}">
                <div class="sidebar-icon">
                    <i class="bi bi-chat-square-text"></i>
                </div>
            </a>
            @endif

            <!-- Sales Forecast Section -->
            @if(auth()->user()->hasAccessToAny(['filament.admin.pages.sales-forecast', 'filament.admin.pages.sales-forecast-summary']))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger">
                    <div class="sidebar-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>

                <!-- Level 2 dropdown content -->
                <div class="dropdown-content">
                    <div class="dropdown-category-heading">Sales Forecast</div>

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.sales-forecast'))
                    <a href="{{ route('filament.admin.pages.sales-forecast') }}" class="sidebar-item {{ request()->routeIs('filament.admin.pages.sales-forecast*') ? 'active' : '' }}">
                        <i class="bi bi-hourglass-split"></i>
                        <span>Sales Forecast - Salesperson</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.sales-forecast-summary'))
                    <a href="{{ route('filament.admin.pages.sales-forecast-summary') }}" class="sidebar-item {{ request()->routeIs('filament.admin.pages.sales-forecast-summary*') ? 'active' : '' }}">
                        <i class="bi bi-bullseye"></i>
                        <span>Sales Forecast - Summary</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Calendar Section -->
            @if(auth()->user()->hasAccessToAny([
                'filament.admin.pages.calendar',
                'filament.admin.pages.weekly-calendar-v2',
                'filament.admin.pages.monthly-calendar',
                'filament.admin.pages.demo-ranking'
            ]))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger">
                    <div class="sidebar-icon">
                        <i class="bi bi-calendar2-week"></i>
                    </div>
                </div>

                <!-- Level 2 dropdown content -->
                <div class="dropdown-content">
                    <div class="dropdown-category-heading">Calendar</div>

                    <!-- Level 2 nested dropdown -->
                    @if(auth()->user()->hasAccessToAny(['filament.admin.pages.calendar', 'filament.admin.pages.weekly-calendar-v2']))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-calendar4-week"></i>
                            <span>Weekly Calendar</span> &nbsp; <i class="bi bi-chevron-right"></i>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.calendar'))
                            <a href="{{ route('filament.admin.pages.calendar') }}" class="dropdown-item {{ request()->routeIs('filament.admin.pages.calendar*') ? 'active' : '' }}">
                                Weekly Calendar V1
                            </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.weekly-calendar-v2'))
                            <a href="{{ route('filament.admin.pages.weekly-calendar-v2') }}" class="dropdown-item {{ request()->routeIs('filament.admin.pages.weekly-calendar-v2*') ? 'active' : '' }}">
                                Weekly Calendar V2
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.monthly-calendar'))
                    <a href="{{ route('filament.admin.pages.monthly-calendar') }}" class="sidebar-item {{ request()->routeIs('filament.admin.pages.monthly-calendar*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-month"></i>
                        <span>Monthly Calendar</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.demo-ranking'))
                    <a href="{{ route('filament.admin.pages.demo-ranking') }}" class="sidebar-item {{ request()->routeIs('filament.admin.pages.demo-ranking*') ? 'active' : '' }}">
                        <i class="bi bi-award"></i>
                        <span>Demo Ranking</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Analysis Section - Level 1 -->
            @if(auth()->user()->hasAccessToAny([
                'filament.admin.pages.lead-analysis',
                'filament.admin.pages.demo-analysis',
                'filament.admin.pages.marketing-analysis',
                'filament.admin.pages.sales-admin-analysis-v1',
                'filament.admin.pages.sales-admin-analysis-v2',
                'filament.admin.pages.sales-admin-analysis-v3'
            ]))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger">
                    <div class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>

                <!-- Level 2 dropdown content -->
                <div class="dropdown-content">
                    <div class="dropdown-category-heading">ANALYSIS</div>

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.lead-analysis'))
                    <a href="{{ route('filament.admin.pages.lead-analysis') }}" class="sidebar-item {{ request()->routeIs('filament.admin.pages.lead-analysis*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-data"></i>
                        <span>Lead Analysis</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.demo-analysis'))
                    <a href="{{ route('filament.admin.pages.demo-analysis') }}" class="sidebar-item {{ request()->routeIs('filament.admin.pages.demo-analysis*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart"></i>
                        <span>Demo Analysis</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.pages.marketing-analysis'))
                    <a href="{{ route('filament.admin.pages.marketing-analysis') }}" class="sidebar-item {{ request()->routeIs('filament.admin.pages.marketing-analysis*') ? 'active' : '' }}">
                        <i class="bi bi-pie-chart-fill"></i>
                        <span>Marketing Analysis</span>
                    </a>
                    @endif

                    <!-- Level 2 nested dropdown -->
                    @if(auth()->user()->hasAccessToAny([
                        'filament.admin.pages.sales-admin-analysis-v1',
                        'filament.admin.pages.sales-admin-analysis-v2',
                        'filament.admin.pages.sales-admin-analysis-v3'
                    ]))
                    <div class="nested-dropdown">
                        <div class="sidebar-item nested-dropdown-trigger">
                            <i class="bi bi-bar-chart-steps"></i>
                            <span>Sales Admin Analysis <i class="bi bi-chevron-right"></i></span>
                        </div>

                        <!-- Level 3 dropdown content -->
                        <div class="nested-dropdown-content">
                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.sales-admin-analysis-v1'))
                            <a href="{{ route('filament.admin.pages.sales-admin-analysis-v1') }}" class="dropdown-item {{ request()->routeIs('filament.admin.pages.sales-admin-analysis-v1*') ? 'active' : '' }}">
                                Sales Admin Analysis V1
                            </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.sales-admin-analysis-v2'))
                            <a href="{{ route('filament.admin.pages.sales-admin-analysis-v2') }}" class="dropdown-item {{ request()->routeIs('filament.admin.pages.sales-admin-analysis-v2*') ? 'active' : '' }}">
                                Sales Admin Analysis V2
                            </a>
                            @endif

                            @if(auth()->user()->hasRouteAccess('filament.admin.pages.sales-admin-analysis-v3'))
                            <a href="{{ route('filament.admin.pages.sales-admin-analysis-v3') }}" class="dropdown-item {{ request()->routeIs('filament.admin.pages.sales-admin-analysis-v3*') ? 'active' : '' }}">
                                Sales Admin Analysis V3
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Admin Section - Dropdown -->
            @if(auth()->user()->hasAccessToAny([
                'filament.admin.resources.products.index',
                'filament.admin.resources.users.index',
                'filament.admin.resources.industries.index',
                'filament.admin.resources.lead-sources.index',
                'filament.admin.resources.invalid-lead-reasons.index',
                'filament.admin.resources.resellers.index'
            ]))
            <div class="sidebar-dropdown">
                <div class="sidebar-item dropdown-trigger">
                    <div class="sidebar-icon">
                        <i class="bi bi-sliders"></i>
                    </div>
                </div>

                <div class="dropdown-content">
                    <div class="dropdown-category-heading">SETTINGS</div>

                    @if(auth()->user()->hasRouteAccess('filament.admin.resources.products.index'))
                    <a href="{{ route('filament.admin.resources.products.index') }}" class="dropdown-item {{ request()->routeIs('filament.admin.resources.products.*') ? 'active' : '' }}">
                        Products
                    </a>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.resources.users.index'))
                    <a href="{{ route('filament.admin.resources.users.index') }}" class="dropdown-item {{ request()->routeIs('filament.admin.resources.users.*') ? 'active' : '' }}">
                        Users
                    </a>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.resources.industries.index'))
                    <a href="{{ route('filament.admin.resources.industries.index') }}" class="dropdown-item {{ request()->routeIs('filament.admin.resources.industries.*') ? 'active' : '' }}">
                        Industries
                    </a>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.resources.lead-sources.index'))
                    <a href="{{ route('filament.admin.resources.lead-sources.index') }}" class="dropdown-item {{ request()->routeIs('filament.admin.resources.lead-sources.*') ? 'active' : '' }}">
                        Lead Sources
                    </a>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.resources.invalid-lead-reasons.index'))
                    <a href="{{ route('filament.admin.resources.invalid-lead-reasons.index') }}" class="dropdown-item {{ request()->routeIs('filament.admin.resources.invalid-lead-reasons.*') ? 'active' : '' }}">
                        Invalid Lead Sources
                    </a>
                    @endif

                    @if(auth()->user()->hasRouteAccess('filament.admin.resources.resellers.index'))
                    <a href="{{ route('filament.admin.resources.resellers.index') }}" class="dropdown-item {{ request()->routeIs('filament.admin.resources.resellers.*') ? 'active' : '' }}">
                        Resellers
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Position the dropdown menus correctly
            const dropdownTriggers = document.querySelectorAll('.dropdown-trigger');
            const nestedDropdownTriggers = document.querySelectorAll('.nested-dropdown-trigger');

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
