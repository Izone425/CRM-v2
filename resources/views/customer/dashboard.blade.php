{{-- filepath: /var/www/html/timeteccrm/resources/views/customer/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - TimeTec CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles

    <style>
        body {
            font-family: 'Inter', sans-serif;
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
            margin-left: 200px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
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
    <header class="relative overflow-hidden shadow-xl main-header gradient-bg">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        <div class="relative px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow-lg">
                        <i class="text-2xl text-indigo-600 fas fa-user-circle"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white drop-shadow-lg">Customer Portal</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        @php
                            $customer = Auth::guard('customer')->user();
                            $companyName = $customer->company_name ?? 'Not Available';

                            // Get the latest software handover based on lead_id
                            $implementerName = 'Not Available';
                            $hasProjectPlan = false;

                            if ($customer->lead_id) {
                                $latestHandover = \App\Models\SoftwareHandover::where('lead_id', $customer->lead_id)
                                    ->orderBy('id', 'desc')
                                    ->first();

                                if ($latestHandover) {
                                    $implementerName = $latestHandover->implementer ?? 'Not Assigned';

                                    // Check if there are any project plans for this lead
                                    $projectPlansCount = \App\Models\ProjectPlan::where('lead_id', $customer->lead_id)
                                        ->where('sw_id', $latestHandover->id)
                                        ->count();

                                    $hasProjectPlan = $projectPlansCount > 0;
                                }
                            }
                        @endphp

                        <p class="font-semibold text-white">Company Name: {{ $companyName }}</p>
                        <p class="text-sm font-medium text-indigo-200">Implementer: {{ $implementerName }}</p>
                    </div>
                    <form method="POST" action="{{ route('customer.logout') }}">
                        @csrf
                        <button type="submit" class="px-6 py-3 font-semibold text-white transition-all duration-300 bg-red-500 rounded-full shadow-lg hover:bg-red-600 hover:shadow-xl hover:scale-105">
                            <i class="mr-2 fas fa-sign-out-alt"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Left Sidebar (Below Header) -->
    <div class="sidebar">
        <div class="sidebar-menu">
            <button onclick="switchTab('calendar')"
                    id="calendar-tab"
                    class="menu-item active">
                <i class="fas fa-calendar-alt"></i>
                <span>Meeting Schedule</span>
            </button>

            @if($hasProjectPlan)
                <button onclick="switchTab('project')"
                        id="project-tab"
                        class="menu-item">
                    <i class="fas fa-tasks"></i>
                    <span>Project Plan</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Main Content -->
        <main class="relative">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Calendar Tab Content -->
                <div id="calendar-content" class="p-8 tab-content active">
                    @livewire('customer-calendar')
                </div>

                <!-- Project Plan Tab Content -->
                @if($hasProjectPlan)
                    <div id="project-content" class="p-8 tab-content">
                        @livewire('customer-project-plan')
                    </div>
                @endif
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
        const hasProjectPlan = @json($hasProjectPlan);

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
                localStorage.setItem('activeTab', tab);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            let activeTab = localStorage.getItem('activeTab') || 'calendar';

            // If project plan doesn't exist and user tries to access it, fallback to calendar
            if (activeTab === 'project' && !hasProjectPlan) {
                activeTab = 'calendar';
                localStorage.setItem('activeTab', 'calendar');
            }

            if (activeTab !== 'calendar') {
                switchTab(activeTab);
            }

            // Smooth scroll to calendar
            const calendarLink = document.querySelector('a[href="#calendar"]');
            if (calendarLink) {
                calendarLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('calendar').scrollIntoView({
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
