<x-filament::page>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        /* Import Inter font for modern typography */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        * {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

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

        /* Slide-in animation with stagger */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Fade in with scale */
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Number counter animation */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Pulse glow effect */
        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
                opacity: 1;
            }
            50% {
                box-shadow: 0 0 0 8px rgba(59, 130, 246, 0);
                opacity: 0.8;
            }
        }

        /* Shimmer effect */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        /* Gradient border animation */
        @keyframes gradientBorder {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Loading skeleton */
        @keyframes skeleton {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* Chart draw animation */
        @keyframes chartDraw {
            from { stroke-dashoffset: 1000; }
            to { stroke-dashoffset: 0; }
        }

        /* Metric cards with staggered animation */
        .metric-card {
            animation: slideInUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            opacity: 0;
            animation-fill-mode: forwards;
            will-change: transform, opacity;
        }

        .metric-card:nth-child(1) { animation-delay: 0.1s; }
        .metric-card:nth-child(2) { animation-delay: 0.2s; }
        .metric-card:nth-child(3) { animation-delay: 0.3s; }
        .metric-card:nth-child(4) { animation-delay: 0.4s; }

        /* Chart containers */
        .chart-container {
            animation: fadeInScale 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.5s;
            animation-fill-mode: both;
        }

        /* Glassmorphism with backdrop blur */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* Gradient border effect */
        .gradient-border {
            position: relative;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, #3B82F6, #8B5CF6) border-box;
            border: 2px solid transparent;
        }

        .gradient-border-animated {
            background-size: 200% 200%;
            animation: gradientBorder 3s ease infinite;
        }

        /* Hover glow effect */
        .hover-glow {
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-glow::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .hover-glow:hover::before {
            width: 300px;
            height: 300px;
        }

        .hover-glow:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                        0 10px 10px -5px rgba(0, 0, 0, 0.04),
                        0 0 20px rgba(59, 130, 246, 0.2);
        }

        /* Number counter */
        .counter {
            display: inline-block;
            font-variant-numeric: tabular-nums;
            animation: countUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* Pulse dot indicator */
        .pulse-dot {
            animation: pulseGlow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Skeleton loader */
        .skeleton {
            background: linear-gradient(90deg,
                #f0f0f0 0%,
                #e0e0e0 50%,
                #f0f0f0 100%);
            background-size: 200% 100%;
            animation: skeleton 1.5s ease-in-out infinite;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: linear-gradient(to bottom, #F8FAFC, #F1F5F9);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #3B82F6, #8B5CF6);
            border-radius: 10px;
            border: 2px solid #F8FAFC;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #2563EB, #7C3AED);
        }

        /* Icon badge animation */
        .icon-badge {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .icon-badge:hover {
            transform: rotate(15deg) scale(1.1);
        }

        /* Sparkle effect on hover */
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0); }
            50% { opacity: 1; transform: scale(1); }
        }

        .sparkle-container {
            position: relative;
        }

        .sparkle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: white;
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
        }

        .hover-glow:hover .sparkle {
            animation: sparkle 0.8s ease-in-out;
        }

        .sparkle:nth-child(1) { top: 20%; right: 20%; animation-delay: 0s; }
        .sparkle:nth-child(2) { top: 40%; right: 10%; animation-delay: 0.2s; }
        .sparkle:nth-child(3) { top: 60%; right: 30%; animation-delay: 0.4s; }

        /* Loading spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        /* Focus states for accessibility */
        *:focus-visible {
            outline: 2px solid #3B82F6;
            outline-offset: 2px;
            border-radius: 8px;
        }

        /* Transition utilities */
        .transition-all-smooth {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>

    @php
        // Get cached counts for badge display
        $cachedCounts = $this->getCachedCounts();
        $rawDataTotal = $cachedCounts['raw_data_total'] ?? 0;
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
        <!-- Header with Tabs and Refresh Button -->
        <div x-cloak x-show="initialized">
            <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                <div class="flex items-center space-x-2">
                    <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">
                        Admin Portal Dashboard
                    </h1>
                    <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                        <button
                            wire:click="refreshTable"
                            wire:loading.attr="disabled"
                            class="flex items-center px-3 py-1 text-sm font-medium text-white transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
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

                <!-- Tab Buttons -->
                <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px; margin-top: 10px;">
                    <button
                        wire:click="toggleDashboard('Dashboard')"
                        style="
                            padding: 10px 20px;
                            font-size: 14px;
                            font-weight: bold;
                            border: none;
                            border-radius: 20px;
                            background: {{ $currentDashboard === 'Dashboard' ? '#431fa1' : 'transparent' }};
                            color: {{ $currentDashboard === 'Dashboard' ? '#ffffff' : '#555' }};
                            cursor: pointer;
                            transition: all 0.3s ease;
                        "
                    >
                        <span wire:loading.remove wire:target="toggleDashboard('Dashboard')">
                            <i class="bi bi-speedometer2 mr-1"></i> Dashboard
                        </span>
                        <span wire:loading wire:target="toggleDashboard('Dashboard')">
                            <span class="spinner">⟳</span> Loading...
                        </span>
                    </button>

                    <button
                        wire:click="toggleDashboard('RawData')"
                        style="
                            padding: 10px 20px;
                            font-size: 14px;
                            font-weight: bold;
                            border: none;
                            border-radius: 20px;
                            background: {{ $currentDashboard === 'RawData' ? '#431fa1' : 'transparent' }};
                            color: {{ $currentDashboard === 'RawData' ? '#ffffff' : '#555' }};
                            cursor: pointer;
                            transition: all 0.3s ease;
                            position: relative;
                        "
                    >
                        <span wire:loading.remove wire:target="toggleDashboard('RawData')">
                            <i class="bi bi-table mr-1"></i> Raw Data
                            @if($rawDataTotal > 0)
                                <span class="badge-container" style="position: absolute; top: -5px; right: -5px;">
                                    {{ $rawDataTotal }}
                                </span>
                            @endif
                        </span>
                        <span wire:loading wire:target="toggleDashboard('RawData')">
                            <span class="spinner">⟳</span> Loading...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Content Area (Conditionally Rendered Based on Current Tab) -->
            <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 -m-6 p-6">
                @if ($currentDashboard === 'Dashboard')
                    @include('filament.pages.hr-dashboard-main')
                @elseif ($currentDashboard === 'RawData')
                    @include('filament.pages.hr-raw-data')
                @endif
            </div>
        </div>
    </div>
</x-filament::page>
