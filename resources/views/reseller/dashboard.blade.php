{{-- filepath: /var/www/html/timeteccrm/resources/views/reseller/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Dashboard - TimeTec CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        html {
            overflow-y: scroll; /* Always show scrollbar space to prevent layout shift */
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stats-card-2 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stats-card-3 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        /* Header Styles */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        /* Sidebar Styles */
        .sidebar {
            position: absolute;
            left: 0;
            top: 140px;
            width: 260px;
            z-index: 50;
        }

        .sidebar-menu {
            padding: 20px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 8px;
            border-radius: 10px;
            color: #64748b;
            transition: all 0.3s ease;
            cursor: pointer;
            font-weight: 500;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
        }

        .menu-item:hover {
            background: #f1f5f9;
            color: #667eea;
            transform: translateX(4px);
        }

        .menu-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .menu-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        /* Main Content with Sidebar */
        .main-wrapper {
            margin-left: 260px;
            margin-top: 40px;
            min-height: calc(100vh - 125px - 125px); /* viewport height - header - footer */
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        .handover-subtab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .handover-subtab:hover {
            color: #667eea;
        }

        .handover-subtab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .handover-subtab.pending-timetec-active {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #f97316;
            box-shadow: 0 2px 4px rgba(249, 115, 22, 0.2);
        }

        .tab-separator {
            width: 3px;
            height: 3rem;
            background: linear-gradient(to bottom, transparent, #d1d5db, transparent);
            margin: 0 2rem;
        }

        .handover-subtab i {
            font-size: 14px;
        }

        .handover-subtab-content {
            display: none;
        }

        .handover-subtab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Fixed Header with Gradient -->
    <div class="relative overflow-hidden shadow-xl main-header gradient-bg">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        <div class="relative px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    {{-- <div class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow-lg">
                        <i class="text-2xl text-indigo-600 fas fa-handshake"></i>
                    </div> --}}
                    <div>
                        <h1 class="text-3xl font-bold text-white drop-shadow-lg">Reseller Portal</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        @php
                            $reseller = Auth::guard('reseller')->user();
                        @endphp

                        <p class="font-semibold text-white">{{ $resellerName }}</p>
                        <p class="text-sm font-medium text-indigo-200">{{ $companyName }}</p>
                    </div>
                    <form method="POST" action="{{ route('reseller.logout') }}">
                        @csrf
                        <button type="submit" class="px-6 py-3 font-semibold text-white transition-all duration-300 bg-red-500 rounded-full shadow-lg hover:bg-red-600 hover:shadow-xl hover:scale-105">
                            <i class="mr-2 fas fa-sign-out-alt"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <div class="sidebar-menu">
            <!-- Renewal Request Button -->
            <div class="mb-8">
                @livewire('reseller-renewal-request')
            </div>

            <button onclick="switchTab('customers')"
                    id="customers-tab"
                    class="menu-item active">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </button>

            <button onclick="switchTab('expired')"
                    id="expired-tab"
                    class="menu-item">
                <i class="fas fa-calendar-times"></i>
                <span>Expired Licenses</span>
            </button>

            <button onclick="switchTab('handover')"
                    id="handover-tab"
                    class="menu-item">
                <i class="fas fa-exchange-alt"></i>
                <span>Renewal Handover</span>
            </button>
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Main Content -->
        <main class="relative">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Active Customers Tab Content -->
                <div id="customers-content" class="p-8 tab-content active">
                    @livewire('reseller-active-customer-list')
                </div>

                <!-- Expired Licenses Tab Content -->
                <div id="expired-content" class="p-8 tab-content">
                    @livewire('reseller-expired-license')
                </div>

                <!-- Renewal Handover Tab Content -->
                @php
                    $reseller = Auth::guard('reseller')->user();
                    $resellerId = $reseller ? $reseller->reseller_id : null;

                    $pendingConfirmationCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'pending_confirmation')->count() : 0;
                    $pendingResellerCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'pending_reseller_invoice')->count() : 0;
                    $pendingPaymentCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'pending_reseller_payment')->count() : 0;
                    $pendingTimetecActionCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->whereIn('status', ['pending_timetec_license', 'new', 'pending_timetec_invoice'])->count() : 0;
                    $completedCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'completed')->count() : 0;
                    $allItemsCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->count() : 0;
                @endphp

                <div id="handover-content" class="pt-8 pb-32 pl-8 pr-8 tab-content"
                     x-data="{
                         pendingConfirmationCount: {{ $pendingConfirmationCount }},
                         pendingResellerCount: {{ $pendingResellerCount }},
                         pendingPaymentCount: {{ $pendingPaymentCount }},
                         pendingTimetecActionCount: {{ $pendingTimetecActionCount }},
                         completedCount: {{ $completedCount }},
                         allItemsCount: {{ $allItemsCount }}
                     }"
                     @handover-completed-notification.window="
                         setTimeout(() => {
                             window.dispatchEvent(new CustomEvent('handover-updated'));
                         }, 2500);
                     "
                     @handover-updated.window="
                         fetch('{{ route('reseller.handover.counts') }}')
                             .then(response => response.json())
                             .then(data => {
                                 pendingConfirmationCount = data.pending_confirmation;
                                 pendingResellerCount = data.pending_reseller_invoice;
                                 pendingPaymentCount = data.pending_payment;
                                 pendingTimetecActionCount = data.pending_timetec_license;
                                 completedCount = data.completed;
                                 allItemsCount = data.all_items || (data.pending_confirmation + data.pending_reseller_invoice + data.pending_payment + data.pending_timetec_license + data.completed);
                             })
                     ">

                    <div class="mb-6">
                        <div class="inline-flex p-1 space-x-1 bg-gray-100 rounded-lg" role="tablist">
                            <button
                                onclick="switchHandoverSubTab('pending-confirmation')"
                                id="pending-confirmation-subtab"
                                class="handover-subtab active"
                                role="tab">
                                Pending Confirmation
                                <span x-show="pendingConfirmationCount > 0" class="px-2 py-1 ml-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="pendingConfirmationCount"></span>
                            </button>
                            <button
                                onclick="switchHandoverSubTab('pending-reseller')"
                                id="pending-reseller-subtab"
                                class="handover-subtab"
                                role="tab">
                                Pending Invoice
                                <span x-show="pendingResellerCount > 0" class="px-2 py-1 ml-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="pendingResellerCount"></span>
                            </button>
                            <button
                                onclick="switchHandoverSubTab('pending-payment')"
                                id="pending-payment-subtab"
                                class="handover-subtab"
                                role="tab">
                                Pending Payment
                                <span x-show="pendingPaymentCount > 0" class="px-2 py-1 ml-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="pendingPaymentCount"></span>
                            </button>
                            <button
                                onclick="switchHandoverSubTab('completed')"
                                id="completed-subtab"
                                class="handover-subtab"
                                role="tab">
                                Completed
                                <span x-show="completedCount > 0" class="px-2 py-1 ml-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="completedCount"></span>
                            </button>
                            <div class="tab-separator"></div>
                            <button
                                onclick="switchHandoverSubTab('pending-timetec-action')"
                                id="pending-timetec-action-subtab"
                                class="handover-subtab"
                                role="tab">
                                Pending TimeTec Action
                                <span x-show="pendingTimetecActionCount > 0" class="px-2 py-1 ml-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="pendingTimetecActionCount"></span>
                            </button>
                            <button
                                onclick="switchHandoverSubTab('all-items')"
                                id="all-items-subtab"
                                class="handover-subtab"
                                role="tab">
                                All Items
                                <span x-show="allItemsCount > 0" class="px-2 py-1 ml-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="allItemsCount"></span>
                            </button>
                        </div>
                    </div>

                    <div id="pending-confirmation-subtab-content" class="handover-subtab-content active">
                        @livewire('reseller-handover-pending-confirmation')
                    </div>

                    <div id="pending-reseller-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-pending-reseller')
                    </div>

                    <div id="pending-payment-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-pending-payment')
                    </div>

                    <div id="completed-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-completed')
                    </div>

                    <div id="pending-timetec-action-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-pending-timetec-action')
                    </div>

                    <div id="all-items-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-all-items')
                    </div>
                </div>

                {{-- <!-- Project Plan Tab Content -->
                @if($hasProjectPlan)
                    <div id="project-content" class="p-8 tab-content">
                        @livewire('customer-project-plan')
                    </div>
                @endif --}}
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="relative overflow-hidden text-white bg-gray-900">
        <div class="absolute inset-0 opacity-50 bg-gradient-to-r from-indigo-900 to-purple-900"></div>
        <div class="relative px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-sm text-gray-400">
                    © {{ date('Y') }} TimeTec CRM. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts

    <!-- Enhanced JavaScript -->
    <script>
        function switchTab(tab) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all buttons
            document.querySelectorAll('.menu-item').forEach(button => {
                button.classList.remove('active');
            });

            // Show selected tab content
            const tabContent = document.getElementById(tab + '-content');
            const tabButton = document.getElementById(tab + '-tab');

            if (tabContent && tabButton) {
                tabContent.classList.add('active');
                tabButton.classList.add('active');

                // Store active tab in localStorage
                localStorage.setItem('resellerActiveTab', tab);
            }
        }

        function switchHandoverSubTab(subtab) {
            // Hide all subtab contents
            document.querySelectorAll('.handover-subtab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all subtab buttons
            document.querySelectorAll('.handover-subtab').forEach(button => {
                button.classList.remove('active');
                button.classList.remove('pending-timetec-active');
            });

            // Show selected subtab content
            const subtabContent = document.getElementById(subtab + '-subtab-content');
            const subtabButton = document.getElementById(subtab + '-subtab');

            if (subtabContent && subtabButton) {
                subtabContent.classList.add('active');

                // Add special class for pending-timetec-action
                if (subtab === 'pending-timetec-action') {
                    subtabButton.classList.add('pending-timetec-active');
                } else {
                    subtabButton.classList.add('active');
                }

                // Store active subtab in localStorage
                localStorage.setItem('resellerActiveHandoverSubTab', subtab);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            let activeTab = localStorage.getItem('resellerActiveTab') || 'customers';
            let activeHandoverSubTab = localStorage.getItem('resellerActiveHandoverSubTab') || 'pending-confirmation';

            if (activeTab !== 'customers') {
                switchTab(activeTab);
            }

            // Restore handover subtab state
            if (activeTab === 'handover' && activeHandoverSubTab !== 'pending-confirmation') {
                switchHandoverSubTab(activeHandoverSubTab);
            }

            // Smooth scroll to customers
            const customersLink = document.querySelector('a[href="#customers"]');
            if (customersLink) {
                customersLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('customers').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            }

            // Add loading animation for action cards
            const actionCards = document.querySelectorAll('.group');
            actionCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add pulse animation to notification dot
            const statsCards = document.querySelectorAll('[class*="stats-card"]');
            statsCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html>
