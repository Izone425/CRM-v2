<!-- filepath: /var/www/html/timeteccrm/resources/views/customer/dashboard.blade.php -->
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

        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .pulse-ring {
            animation: pulse-ring 1.25s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(.33);
            }
            80%, 100% {
                opacity: 0;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header with Gradient -->
    <header class="relative overflow-hidden shadow-xl gradient-bg">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        <div class="relative px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow-lg">
                        <i class="text-2xl text-indigo-600 fas fa-user-circle"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white drop-shadow-lg">Customer Portal</h1>
                        <p class="text-sm text-indigo-100">Manage your appointments and account</p>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        @php
                            $customer = Auth::guard('customer')->user();
                            $companyName = $customer->company_name ?? 'Not Available';

                            // Get the latest software handover based on lead_id
                            $projectCode = 'Not Available';
                            if ($customer->lead_id) {
                                $latestHandover = \App\Models\SoftwareHandover::where('lead_id', $customer->lead_id)
                                    ->orderBy('id', 'desc')
                                    ->first();

                                if ($latestHandover) {
                                    $projectCode = 'SW_250' . str_pad($latestHandover->id, 3, '0', STR_PAD_LEFT);
                                }
                            }
                        @endphp

                        <p class="font-semibold text-white">Company Name: {{ $companyName }}</p>
                        <p class="text-sm text-indigo-100">Project Code: {{ $projectCode }}</p>
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

    <!-- Main Content -->
    <main class="relative py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Calendar Section -->
            <div id="calendar" class="overflow-hidden bg-white border border-gray-100 shadow-xl rounded-3xl">
                <div class="px-8 py-6 bg-gradient-to-r from-indigo-600 to-purple-600">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 mr-4 bg-white bg-opacity-20 rounded-xl">
                            <i class="text-xl text-white fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white">Schedule Your Kick-Off Meeting</h2>
                            <p class="text-indigo-100">Choose your preferred date and time</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    @livewire('customer-calendar')
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative overflow-hidden text-white bg-gray-900">
        <div class="absolute inset-0 opacity-50 bg-gradient-to-r from-indigo-900 to-purple-900"></div>
        <div class="relative px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-white rounded-full bg-opacity-10">
                    <i class="text-2xl fas fa-clock"></i>
                </div>
                <h3 class="mb-2 text-2xl font-bold">TimeTec CRM</h3>
                <p class="mb-4 text-gray-300">Your trusted HR Solutions partner</p>
                <p class="text-sm text-gray-400">
                    © {{ date('Y') }} TimeTec CRM. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts

    <!-- Enhanced JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
