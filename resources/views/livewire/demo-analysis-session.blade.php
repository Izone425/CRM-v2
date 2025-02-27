<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Container */
        .session-container {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 8px;
        }

        /* Left Section */
        .session-count {
            flex: 1;
            text-align: center;
        }
        .session-number {
            font-size: 3rem;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
        }
        .session-label {
            font-size: 0.9rem;
            color: #777;
        }

        /* Middle Divider */
        .session-divider {
            flex: 0.02;
            height: 150px;
            background: #ccc;
            width: 0.5px;
        }

        /* Right Section */
        .session-bars {
            flex: 3;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 32px; /* Space between bars */
        }

        .bar-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 60px;
            position: relative;
        }

        .percentage-label {
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: bold;
            color: #333;
        }

        .bar-wrapper {
            width: 40px;
            height: 100px;
            background-color: #E5E7EB; /* Light gray */
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        .bar-fill {
            position: absolute;
            bottom: 0;
            width: 100%;
            border-radius: 8px;
            transition: height 0.5s ease-in-out;
        }

        .session-type {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            color: #374151;
        }

        /* Tooltip (Hover Message) */
        .hover-message {
            position: absolute;
            bottom: 110%;
            background-color: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 5px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out;
        }

        .bar-group:hover .hover-message {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>

<div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s wire:key="demo-analysis-{{ $selectedUser ?? 'all' }}-{{ $selectedMonth ?? 'current' }}">
    <div class="flex items-center mb-6 space-x-2">
        <i class="text-lg text-gray-500 fa fa-bookmark"></i>&nbsp;&nbsp;
        <h3 class="text-xl font-bold text-gray-800">Total Session</h3>
    </div>

    <div class="session-container">
        <!-- Left Section: Total Sessions -->
        <div class="session-count">
            <p class="session-number">{{ $totalAppointments }}</p>
            <p class="session-label">Sessions</p>
        </div>

        <!-- Middle Divider -->
        <div class="session-divider"></div>

        <!-- Right Section: Vertical Bar Chart -->
        <div class="session-bars">
            @foreach ($typeData as $type => $count)
                @php
                    $percentage = $totalAppointments > 0 ? round(($count / $totalAppointments) * 100, 2) : 0;
                    $barColor = match($loop->index) {
                        0 => '#FACC15',   // New Demo - Yellow
                        1 => '#FB923C',   // Webinar Demo - Orange
                        2 => '#EF4444',   // HRMS Demo - Red
                        3 => '#10B981',   // System Discussion - Green
                        default => '#3B82F6', // HRDF Discussion - Blue
                    };
                @endphp

                <div class="bar-group">
                    <!-- Hover Message for Count -->
                    <div class="hover-message">{{ $count }} Sessions</div>

                    <p class="percentage-label">{{ $percentage }}%</p>
                    <div class="bar-wrapper">
                        <div class="bar-fill" style="height: {{ $percentage }}%; background-color: {{ $barColor }};"></div>
                    </div>
                    <p class="session-type">{{ $type }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
