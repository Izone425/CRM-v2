{{-- filepath: /var/www/html/timeteccrm/resources/views/livewire/technician-calendar.blade.php --}}

<div x-data="tooltipHandler()">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        :root {
            --bar-color-blue: #F6F8FF;
            --bar-color-orange: #ff9500;
            --bg-color-border: #E5E7EB;
            --bg-color-white: white;
            --icon-color: black;
            --bg-demo-red: #FEE2E2;
            --bg-demo-green: #C6FEC3;
            --bg-demo-yellow: #FEF9C3;
            --text-demo-red: #B91C1C;
            --text-demo-green: #67920E;
            --text-demo-yellow: #92400E;
            --text-hyperlink-blue: #338cf0;
            --sidebar-color: black;
        }

        /* Calendar Styles with Horizontal Scroll - SMALLER BOXES */
        .monthly-calendar-container {
            background: white;
            border-radius: 17px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-top: 20px;
            margin-left: 40px;
        }

        .month-header {
            background: var(--bar-color-blue);
            padding: 20px;
            text-align: center;
        }

        .month-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        .month-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
            color: #1f2937;
        }

        .nav-btn {
            padding: 10px;
            border: none;
            background: white;
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.2s;
            color: #374151;
        }

        .nav-btn:hover {
            background: #f3f4f6;
        }

        /* Calendar Scroll Container */
        .calendar-scroll-container {
            overflow-x: auto;
            overflow-y: hidden;
            position: relative;
        }

        /* Custom Scrollbar Styling */
        .calendar-scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .calendar-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .calendar-scroll-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .calendar-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Firefox Scrollbar */
        .calendar-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }

        /* Calendar Content Wrapper */
        .calendar-content-wrapper {
            min-width: 100%;
            width: max-content;
        }

        /* Days Header - SMALLER */
        .days-header {
            display: grid;
            grid-template-columns: repeat(5, minmax(180px, 1fr)); /* Reduced from 250px to 180px */
            background: var(--bg-color-border);
            gap: 1px;
        }

        .day-header {
            background: var(--bg-color-white);
            padding: 10px 8px; /* Reduced padding */
            text-align: center;
            font-weight: bold;
            color: #374151;
            min-width: 180px; /* Reduced from 250px to 180px */
            font-size: 14px; /* Smaller font */
        }

        /* Monthly Calendar Grid - SMALLER */
        .monthly-calendar-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(180px, 1fr)); /* Reduced from 250px to 180px */
            gap: 1px;
            background: var(--bg-color-border);
        }

        .calendar-day {
            background: var(--bg-color-white);
            padding: 8px; /* Reduced from 12px to 8px */
            min-height: 140px; /* Reduced from 200px to 140px */
            min-width: 180px; /* Reduced from 250px to 180px */
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .calendar-day.other-month {
            background: #f9f9f9;
            opacity: 0.6;
        }

        .calendar-day.today {
            background: #dbeafe;
            border: 2px solid #3b82f6;
        }

        /* Day number positioning - SMALLER */
        .day-header-with-appointments {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px; /* Reduced from 8px */
            flex-shrink: 0;
        }

        .day-number {
            font-weight: bold;
            font-size: 14px; /* Reduced from 16px */
            color: #1f2937;
            flex-shrink: 0;
        }

        .appointment-count {
            font-size: 9px; /* Reduced from 10px */
            color: #6b7280;
            font-style: italic;
            background: #f3f4f6;
            padding: 1px 4px; /* Reduced padding */
            border-radius: 8px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Appointments container - SMALLER */
        .day-appointments {
            display: flex;
            flex-direction: column;
            gap: 2px; /* Reduced from 4px */
            flex: 1;
            overflow-y: auto;
            max-height: 100px; /* Reduced from 160px */
        }

        /* Individual appointment items - SMALLER */
        .mini-appointment {
            padding: 4px 6px; /* Reduced from 8px 10px */
            border-radius: 4px; /* Reduced from 6px */
            font-size: 10px; /* Reduced from 12px */
            margin-bottom: 2px; /* Reduced from 3px */
            border-left: 3px solid rgba(0,0,0,0.2); /* Reduced from 4px */
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            min-height: 32px; /* Reduced from 45px */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .mini-appointment:hover {
            transform: translateY(-1px); /* Reduced from -2px */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12); /* Reduced shadow */
            opacity: 0.9;
        }

        .appointment-mini-info {
            font-weight: 600;
            margin-bottom: 1px; /* Reduced from 2px */
            line-height: 1.1; /* Tighter line height */
            word-wrap: break-word;
            font-size: 9px; /* Smaller font */
        }

        .appointment-mini-details {
            font-size: 8px; /* Reduced from 10px */
            color: rgba(0, 0, 0, 0.7);
            line-height: 1.1; /* Tighter line height */
        }

        .more-appointments {
            font-size: 9px; /* Reduced from 11px */
            color: #6b7280;
            font-style: italic;
            text-align: center;
            padding: 3px; /* Reduced from 6px */
            background: #f3f4f6;
            border-radius: 3px; /* Reduced from 4px */
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 2px; /* Reduced from 4px */
        }

        .more-appointments:hover {
            background: #e5e7eb;
        }

        /* Scroll Hint Overlay */
        .scroll-hint {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 6px 10px; /* Smaller padding */
            border-radius: 16px; /* Smaller border radius */
            font-size: 11px; /* Smaller font */
            z-index: 10;
            animation: fadeInOut 3s ease-in-out;
            pointer-events: none;
        }

        @keyframes fadeInOut {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        /* Enhanced Modal Styles */
        .appointment-modal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 1rem;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 95%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
        }

        .modal-close {
            padding: 0.5rem;
            color: #6b7280;
            border: none;
            background: none;
            border-radius: 0.375rem;
            transition: all 0.2s;
            cursor: pointer;
        }

        .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
        }

        .appointment-technician {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .appointment-status {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .appointment-basic-details {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .appointment-basic-details div {
            margin-bottom: 0.25rem;
        }

        /* Enhanced Detail Sections */
        .appointment-detail-section {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-left: 4px solid #e74c3c;
            border-radius: 6px;
        }

        .section-title {
            font-weight: bold;
            font-size: 1rem;
            margin-bottom: 0.75rem;
            color: #e74c3c;
            text-transform: uppercase;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.5rem;
        }

        .detail-row {
            display: flex;
            margin-bottom: 0.5rem;
            align-items: flex-start;
        }

        .detail-label {
            font-weight: bold;
            min-width: 160px;
            color: #495057;
            flex-shrink: 0;
        }

        .detail-value {
            color: #212529;
            flex: 1;
        }

        .device-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .device-item {
            background: white;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .device-name {
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .device-quantity {
            color: #495057;
            font-size: 0.8rem;
        }

        .remark-content {
            background: white;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
            font-family: inherit;
            line-height: 1.6;
            color: #212529;
        }

        /* Day Modal for showing all appointments */
        .day-modal {
            position: fixed;
            inset: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 1rem;
        }

        .day-modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 95%;
            max-width: 600px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .day-appointment-list {
            padding: 1rem;
        }

        .day-appointment-item {
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .day-appointment-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-color: rgba(0, 0, 0, 0.1);
        }

        /* Filter Styles */
        .filter-badges-container {
            display: flex;
            flex-direction: column;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }

        .filter-row {
            display: flex;
            flex-direction: row;
            gap: 0.25rem;
            width: 60%
        }

        .badges-row {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            gap: 0.25rem;
            width: 60%;
        }

        .badges {
            text-align: start;
            width: 100%;
            color: white;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Container */
        .filter-badges-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 10px;
        }

        /* Filters Section */
        .filter-row {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        /* Individual Filter Boxes */
        .filter-row div {
            position: relative;
            width: 180px;
        }

        .filter-row select,
        .filter-row input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        /* Badges Row */
        .badges-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        /* Individual Badge */
        .badges {
            flex: 1;
            min-width: 150px;
            background-color: #f3f4f6;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Specific Badge Colors */
        .badges:nth-child(1) {
            background-color: #4F46E5;
            color: white;
        }

        .badges:nth-child(2) {
            background-color: #22C55E;
            color: white;
        }

        .badges:nth-child(3) {
            background-color: #FACC15;
            color: black;
        }

        .badges:nth-child(4) {
            background-color: #EF4444;
            color: white;
        }

        /* Demo Type & Status Columns */
        .demo-columns {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* Demo Box */
        .demo-box {
            flex: 1;
            min-width: 250px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .demo-box h3 {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        /* Progress Bar */
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #E5E7EB;
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }

        /* Mobile Responsive Styles - SMALLER BOXES */
        @media (max-width: 768px) {
            .days-header {
                grid-template-columns: repeat(5, minmax(140px, 1fr)); /* Reduced from 180px */
            }

            .monthly-calendar-grid {
                grid-template-columns: repeat(5, minmax(140px, 1fr)); /* Reduced from 180px */
            }

            .day-header {
                padding: 6px 4px; /* Further reduced */
                font-size: 11px; /* Smaller font */
                min-width: 140px; /* Reduced from 180px */
            }

            .calendar-day {
                min-height: 110px; /* Reduced from 140px */
                padding: 6px; /* Further reduced */
                min-width: 140px; /* Reduced from 180px */
            }

            .day-number {
                font-size: 12px; /* Reduced from 14px */
            }

            .mini-appointment {
                font-size: 8px; /* Reduced from 10px */
                padding: 3px 5px; /* Further reduced */
                min-height: 26px; /* Reduced from 32px */
            }

            .appointment-mini-info {
                font-size: 8px; /* Smaller font */
            }

            .appointment-mini-details {
                font-size: 7px; /* Smaller font */
            }

            .month-title {
                font-size: 1.1rem;
            }

            .nav-btn {
                padding: 8px;
            }

            .modal-content {
                width: 98%;
                max-width: none;
                margin: 5px;
                max-height: 90vh;
            }

            .modal-title {
                font-size: 1.1rem;
            }

            .appointment-technician {
                font-size: 1.1rem;
            }

            .appointment-basic-details {
                font-size: 0.8rem;
            }

            .detail-row {
                flex-direction: column;
                gap: 0.25rem;
            }

            .detail-label {
                min-width: auto;
            }

            .device-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 0.9rem;
            }

            .appointment-detail-section {
                padding: 0.75rem;
                margin-bottom: 1rem;
            }

            .filter-row {
                width: 100%;
            }

            .badges-row {
                width: 100%;
            }

            .filter-row div {
                width: 100%;
                margin-bottom: 10px;
            }

            .grid.grid-cols-2 {
                grid-template-columns: 1fr !important;
            }

            .flex.gap-6 {
                flex-direction: column;
            }

            .flex.items-center.gap-2.p-6 {
                flex-direction: column;
                gap: 10px;
                padding: 15px;
            }

            .w-full.max-w-6xl {
                padding: 15px;
            }

            .flex-1.p-4 {
                padding: 15px;
            }

            .grid.w-full.grid-cols-2 {
                padding: 15px;
            }

            button {
                min-height: 44px;
            }
        }

        @media (max-width: 480px) {
            .days-header {
                grid-template-columns: repeat(5, minmax(110px, 1fr)); /* Further reduced */
            }

            .monthly-calendar-grid {
                grid-template-columns: repeat(5, minmax(110px, 1fr)); /* Further reduced */
            }

            .calendar-day {
                min-height: 90px; /* Further reduced */
                padding: 4px; /* Further reduced */
                min-width: 110px; /* Further reduced */
            }

            .day-header {
                padding: 4px 2px; /* Further reduced */
                font-size: 10px; /* Smaller font */
                min-width: 110px; /* Further reduced */
            }

            .day-number {
                font-size: 11px; /* Smaller font */
            }

            .mini-appointment {
                font-size: 7px; /* Smaller font */
                padding: 2px 4px; /* Further reduced */
                min-height: 22px; /* Further reduced */
            }

            .month-title {
                font-size: 1rem;
            }

            .calendar-day.holiday {
                background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%) !important;
                border: 2px solid #f59e0b !important;
                position: relative;
            }

            .calendar-day.holiday.other-month {
                background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%) !important;
                opacity: 0.6 !important;
            }

            .calendar-day.holiday.today {
                background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%) !important;
                border: 3px solid #d97706 !important;
                box-shadow: 0 0 10px rgba(217, 119, 6, 0.3) !important;
            }

            .holiday-info {
                margin-bottom: 6px;
                padding: 4px 6px;
                background: rgba(245, 158, 11, 0.9);
                border-radius: 4px;
                font-size: 9px;
                color: white;
                text-align: center;
                font-weight: bold;
                text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
            }

            .holiday-name {
                font-weight: bold;
                line-height: 1.2;
                word-wrap: break-word;
            }

            /* Holiday + Leave combination styling */
            .calendar-day.holiday.has-leaves {
                background: linear-gradient(45deg,
                    #fbbf24 0%, #fbbf24 50%,
                    #3b82f6 50%, #3b82f6 100%) !important;
                border: 2px solid #6366f1 !important;
            }

            /* Holiday + Today styling */
            .calendar-day.holiday.today {
                background: linear-gradient(135deg, #fef3c7 0%, #d97706 100%) !important;
                border: 3px solid #92400e !important;
                box-shadow: 0 0 15px rgba(146, 64, 14, 0.5) !important;
            }

            /* Holiday indicator in day header */
            .holiday-indicator {
                font-size: 10px;
                margin-left: 3px;
                filter: drop-shadow(1px 1px 1px rgba(0,0,0,0.3));
            }

            /* Adjust appointment container when holiday is present */
            .calendar-day.holiday .day-appointments {
                max-height: 80px; /* Reduced to make room for holiday info */
            }

            .calendar-day.holiday.has-leaves .day-appointments {
                max-height: 60px; /* Further reduced when both holiday and leaves are present */
            }

            /* Mobile responsive holiday styling */
            @media (max-width: 768px) {
                .calendar-day.holiday {
                    background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%) !important;
                    border: 2px solid #f59e0b !important;
                }

                .calendar-day.holiday.other-month {
                    background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%) !important;
                    opacity: 0.5 !important;
                }

                .holiday-info {
                    margin-bottom: 4px;
                    padding: 2px 4px;
                    background: rgba(245, 158, 11, 0.9);
                    border-radius: 3px;
                    font-size: 8px;
                    color: white;
                    text-align: center;
                }

                .holiday-name {
                    font-weight: bold;
                    line-height: 1.1;
                }

                .holiday-indicator {
                    font-size: 8px;
                    margin-left: 2px;
                }

                .calendar-day.holiday .day-appointments {
                    max-height: 60px;
                }

                .calendar-day.holiday.has-leaves .day-appointments {
                    max-height: 40px;
                }
            }

            @media (max-width: 480px) {
                .calendar-day.holiday {
                    background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%) !important;
                    border: 2px solid #f59e0b !important;
                }

                .calendar-day.holiday.other-month {
                    background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%) !important;
                    opacity: 0.4 !important;
                }

                .holiday-info {
                    margin-bottom: 4px;
                    padding: 2px 4px;
                    background: rgba(245, 158, 11, 0.8);
                    border-radius: 3px;
                    font-size: 8px;
                    color: white;
                    text-align: center;
                }

                .holiday-name {
                    font-weight: bold;
                    line-height: 1.1;
                }
            }
        }
    </style>

    <!-- Ribbon Toggle Section -->
    <div x-data="{ filterExpanded: false }" style="margin-left: 50px;">
        <!-- Title and Toggle Button -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Technician Calendar</h2>
            <button @click="filterExpanded = !filterExpanded"
                    class="flex items-center px-3 py-1 text-sm bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
                    <span x-text="filterExpanded ? 'Hide Ribbons' : 'Show Ribbons'"></span>&nbsp;
                <i class="ml-1 fa-solid" :class="filterExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>

        <!-- Filter and Badges Section - Collapsible -->
        <div x-show="filterExpanded" x-transition.duration.300ms>
            <div class="flex items-center gap-2 p-6 mb-6 bg-white shadow-xl rounded-2xl">
                <div class="grid w-full grid-cols-2 gap-8 p-6 mx-auto bg-white shadow-md md:grid-cols-2 max-w-7xl rounded-xl"
                    style="width:70%;">
                    <h3> Filter </h3><br>

                    {{-- Status Filter --}}
                    <div class="relative w-full">
                        <form>
                            <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                                @click.away="open = false" x-data="{
                                    open: false,
                                    selected: @entangle('selectedStatus'),
                                    allSelected: @entangle('allStatusSelected'),
                                    get label() {
                                        if (this.allSelected) return 'All Status'
                                        else if (this.selected.length <= 0) return 'All Status'
                                        else return this.selected.join(',');
                                    }
                                }">
                                <!-- Trigger Button -->
                                <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                                    <span x-text="label" class="truncate"></span>
                                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>

                                <!-- Dropdown List -->
                                <div x-show="open"
                                    class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg "
                                    style="display: none;">
                                    <ul class="py-1">
                                        <!-- Select All Checkbox -->
                                        <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                            <input type="checkbox" wire:model.live="allStatusSelected"
                                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                            <label class="block ml-3 text-sm font-medium text-gray-700"
                                                style="padding-left: 10px;">
                                                All Status
                                            </label>
                                        </li>

                                        <!-- Individual Status Options -->
                                        @foreach ($status as $row)
                                            <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                                <input type="checkbox" wire:model.live="selectedStatus"
                                                    value="{{ $row }}"
                                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                                <label for="checkbox-{{ $row }}"
                                                    class="block ml-3 text-sm font-medium text-gray-700"
                                                    style="padding-left: 10px;">
                                                    {{ $row }}
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Date Picker --}}
                    <div x-data="monthlyPicker()" class="w-36">
                        <input type="text" x-ref="datepicker" wire:model.change='monthDate' placeholder="Select Month"
                            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    {{-- Repair Type Filter --}}
                    <div class="relative w-full">
                        <form>
                            <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                                @click.away="open = false" x-data="{
                                    open: false,
                                    selected: @entangle('selectedRepairType'),
                                    allSelected: @entangle('allRepairTypeSelected'),
                                    get label() {
                                        if (this.allSelected) return 'All Repair Types'
                                        else if (this.selected.length <= 0) return 'All Repair Types'
                                        else return this.selected.join(',');
                                    }
                                }">
                                <!-- Trigger Button -->
                                <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                                    <span x-text="label" class="truncate"></span>
                                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>

                                <!-- Dropdown List -->
                                <div x-show="open"
                                    class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg "
                                    style="display: none;">
                                    <ul class="py-1">
                                        <!-- Select All Checkbox -->
                                        <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                            <input type="checkbox" wire:model.live="allRepairTypeSelected"
                                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                            <label class="block ml-3 text-sm font-medium text-gray-700"
                                                style="padding-left: 10px;">
                                                All Repair Types
                                            </label>
                                        </li>

                                        <!-- Individual Repair Types -->
                                        @foreach ($repairTypes as $row)
                                            <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                                <input type="checkbox" wire:model.live="selectedRepairType"
                                                    value="{{ $row }}"
                                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                                <label for="checkbox-{{ $row }}"
                                                    class="block ml-3 text-sm font-medium text-gray-700"
                                                    style="padding-left: 10px;">
                                                    {{ $row }}
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Technicians Filter --}}
                    <div class="relative w-full">
                        <form>
                            <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                                @click.away="open = false" x-data="{
                                    open: false,
                                    selected: @entangle('selectedTechnicians'),
                                    allSelected: @entangle('allTechniciansSelected'),
                                    get label() {
                                        if (this.allSelected)
                                            return 'All Technicians';
                                        else if (this.selected.length <= 0)
                                            return 'All Technicians';
                                        else
                                            return this.selected.length + ' Technicians';
                                    }
                                }">

                                <!-- Trigger Button -->
                                <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                                    <span x-text="label" class="truncate"></span>
                                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>

                                <!-- Dropdown List -->
                                <div x-show="open"
                                    class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg"
                                    style="display: none; height: 30vh">
                                    <ul class="py-1">
                                        <!-- Select All Checkbox -->
                                        <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                            <input type="checkbox" wire:model.live="allTechniciansSelected"
                                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                                @if (auth()->user()->role_id == 9) disabled @endif />
                                            <label class="block ml-3 text-sm font-medium text-gray-700"
                                                style="padding-left: 10px;">
                                                All Technicians
                                            </label>
                                        </li>

                                        <!-- Individual Technicians -->
                                        @foreach ($technicians as $row)
                                            <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                                <input type="checkbox" wire:model.live="selectedTechnicians"
                                                    value="{{ $row['name'] }}"
                                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                                    @if (auth()->user()->role_id == 9) disabled @endif />
                                                <label for="checkbox-{{ $row['id'] }}"
                                                    class="block ml-3 text-sm font-medium text-gray-700"
                                                    style="padding-left: 10px;">
                                                    {{ $row['name'] }}
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Appointment Type Filter --}}
                    <div class="relative w-full">
                        <form>
                            <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                                @click.away="open = false" x-data="{
                                    open: false,
                                    selected: @entangle('selectedAppointmentType'),
                                    allSelected: @entangle('allAppointmentTypeSelected'),
                                    get label() {
                                        if (this.allSelected)
                                            return 'All Appointment Types'
                                        else if (this.selected.length <= 0)
                                            return 'All Appointment Types'
                                        else
                                            return this.selected.join(',');
                                    }
                                }">
                                <!-- Trigger Button -->
                                <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                                    <span x-text="label" class="truncate"></span>
                                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>

                                <!-- Dropdown List -->
                                <div x-show="open"
                                    class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg "
                                    style="display: none;">
                                    <ul class="py-1">
                                        <!-- Select All Checkbox -->
                                        <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                            <input type="checkbox" wire:model.live="allAppointmentTypeSelected"
                                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                            <label class="block ml-3 text-sm font-medium text-gray-700"
                                                style="padding-left: 10px;">
                                                All Appointment Types
                                            </label>
                                        </li>

                                        <!-- Individual Appointment Types -->
                                        @foreach ($appointmentTypes as $row)
                                            <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                                <input type="checkbox" wire:model.live="selectedAppointmentType"
                                                    value="{{ $row }}"
                                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                                <label for="checkbox-{{ $row }}"
                                                    class="block ml-3 text-sm font-medium text-gray-700"
                                                    style="padding-left: 10px;">
                                                    {{ $row }}
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Repair Breakdown -->
                <div class="w-full max-w-6xl p-6 mx-auto bg-white shadow-md rounded-xl">
                    <div class="flex gap-6">

                        <!-- Repair Type -->
                        <div class="flex-1 p-4 bg-white rounded-lg shadow">
                            <h3 class="text-lg font-semibold">Technician & Reseller (Category)</h3>
                            <p class="text-gray-600">Total Technician & Reseller Session: {{ $totalRepairs['ALL'] }}</p>

                            @foreach ([
                                'NEW INSTALLATION' => '#71eb71',
                                'REPAIR' => '#ffff5cbf',
                                'SITE SURVEY' => '#ffa83c',
                                'INTERNAL TECHNICIAN TASK' => '#60a5fa'
                            ] as $type => $color)
                                @php
                                    $count = $repairBreakdown[$type] ?? 0;
                                    $percentage = $totalRepairs['ALL'] > 0 ? round(($count / $totalRepairs['ALL']) * 100, 2) : 0;
                                @endphp

                                <div class="flex justify-between mt-2 text-sm">
                                    <span>{{ ucfirst(strtolower(str_replace('_', ' ', $type))) }}</span>
                                    <span>{{ $count }} ({{ $percentage }}%)</span>
                                </div>

                                <div class="w-full h-3 mt-1 mb-3 bg-gray-200 rounded-md">
                                    <div class="h-full rounded-md" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Repair Status -->
                        <div class="flex-1 p-4 bg-white rounded-lg shadow">
                            <h3 class="text-lg font-semibold">Technician & Reseller (Status)</h3>
                            <p class="text-gray-600">Total Technician & Reseller Session: {{ $totalRepairs['ALL'] }}</p>

                            @foreach (['NEW' => '#ffff5cbf', 'DONE' => '#71eb71', 'CANCELLED' => '#f86f6f'] as $status => $color)
                                @php
                                    $count = $totalRepairs[$status] ?? 0;
                                    $percentage = $totalRepairs['ALL'] > 0 ? round(($count / $totalRepairs['ALL']) * 100, 2) : 0;
                                @endphp

                                <div class="flex justify-between mt-2 text-sm">
                                    <span>{{ ucfirst(strtolower($status)) }}</span>
                                    <span>{{ $count }} ({{ $percentage }}%)</span>
                                </div>
                                <div class="w-full h-3 mt-1 mb-3 bg-gray-200 rounded-md">
                                    <div class="h-full rounded-md" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Calendar with Horizontal Scroll -->
    <div class="monthly-calendar-container"
         x-data="{
            selectedAppointment: null,
            showAppointmentModal: false,
            dayModalOpen: false,
            selectedDayAppointments: [],
            selectedDate: '',
            showScrollHint: true
         }"
         x-init="setTimeout(() => showScrollHint = false, 5000)" >

        <!-- Month Navigation Header -->
        <div class="month-header">
            <div class="month-nav">
                <button wire:click="prevMonth" class="nav-btn">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <h2 class="month-title">{{ $currentMonth }}</h2>
                <button wire:click="nextMonth" class="nav-btn">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Horizontal Scroll Container -->
        <div class="calendar-scroll-container" style="position: relative;">

            <!-- Scroll Hint -->
            <div x-show="showScrollHint" x-transition class="scroll-hint">
                ← Scroll to see more →
            </div>

            <!-- Calendar Content Wrapper -->
            <div class="calendar-content-wrapper">

                <!-- Days Header (WEEKDAYS ONLY) -->
                <div class="days-header">
                    <div class="day-header">Mon</div>
                    <div class="day-header">Tue</div>
                    <div class="day-header">Wed</div>
                    <div class="day-header">Thu</div>
                    <div class="day-header">Fri</div>
                </div>

                <!-- Monthly Calendar Grid (WEEKDAYS ONLY) -->
                <div class="monthly-calendar-grid">
                    @foreach ($monthlyCalendar as $week)
                        @foreach ($week as $dayData)
                            <div class="calendar-day
                                @if($dayData['isCurrentMonth']) current-month @else other-month @endif
                                @if($dayData['isToday']) today @endif
                                @if($dayData['isHoliday']) holiday @endif
                                @if($dayData['hasLeaves']) has-leaves @endif">

                                <!-- Day number with appointment count -->
                                @if(isset($dayData['appointments']) && count($dayData['appointments']) > 0)
                                    <div class="day-header-with-appointments">
                                        <div class="day-number">{{ $dayData['day'] }}</div>
                                        <div class="appointment-count">
                                            {{ count($dayData['appointments']) }}
                                            @if($dayData['hasLeaves'])
                                                <span class="leave-indicator">🏖️</span>
                                            @endif
                                            @if($dayData['isHoliday'])
                                                <span class="holiday-indicator">🏛️</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="day-number">
                                        {{ $dayData['day'] }}
                                        @if($dayData['hasLeaves'])
                                            <span class="leave-indicator">🏖️</span>
                                        @endif
                                        @if($dayData['isHoliday'])
                                            <span class="holiday-indicator">🏛️</span>
                                        @endif
                                    </div>
                                @endif

                                <!-- ✅ Show holiday information -->
                                @if($dayData['isHoliday'])
                                    <div class="holiday-info">
                                        <div class="holiday-name">{{ $dayData['holidayName'] }}</div>
                                    </div>
                                @endif

                                <!-- ✅ Show leave information -->
                                @if($dayData['hasLeaves'])
                                    <div class="leaves-info">
                                        @foreach($dayData['leaves'] as $leave)
                                            <div class="leave-item">
                                                <div class="leave-name">{{ $leave->user->name ?? 'Unknown' }}</div>
                                                <div class="leave-type">{{ $leave->type ?? 'Leave' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Individual clickable appointments -->
                                @if(isset($dayData['appointments']) && count($dayData['appointments']) > 0)
                                    <div class="day-appointments">
                                        @foreach($dayData['appointments'] as $index => $appointment)
                                            @if($index < 3) {{-- Reduced from 4 to 3 to make room for holidays/leaves --}}
                                                <div class="mini-appointment"
                                                    style="background-color:
                                                        @if($appointment->status === 'Done') var(--bg-demo-green)
                                                        @elseif($appointment->status === 'New') var(--bg-demo-yellow)
                                                        @else var(--bg-demo-red)
                                                        @endif"
                                                    @click.stop="$wire.showAppointment({{ $appointment->id }}, '{{ $dayData['date'] }}')"
                                                    wire:loading.attr="disabled">
                                                    <div class="appointment-mini-info">
                                                        {{ Str::limit($appointment->company_name, 35) }}
                                                    </div>
                                                    <div class="appointment-mini-details">
                                                        {{ Str::limit($appointment->display_type ?? $appointment->type, 15) }} • {{ $appointment->start_time }}
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach

                                        @if(count($dayData['appointments']) > 3) {{-- Updated count --}}
                                            <div class="more-appointments"
                                                @click.stop="
                                                    selectedDayAppointments = {{ collect($dayData['appointments'])->map(function($apt) {
                                                        return [
                                                            'id' => $apt->id,
                                                            'date' => $apt->date,
                                                            'company_name' => $apt->company_name,
                                                            'technician' => $apt->technician,
                                                            'type' => $apt->type,
                                                            'start_time' => $apt->start_time,
                                                            'end_time' => $apt->end_time,
                                                            'status' => $apt->status,
                                                            'display_type' => $apt->display_type ?? $apt->type
                                                        ];
                                                    })->toJson() }};
                                                    selectedDate = '{{ $dayData['carbonDate']->format('l, F j, Y') }}';
                                                    dayModalOpen = true;
                                                ">
                                                +{{ count($dayData['appointments']) - 3 }} more
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Individual Appointment Detail Modal -->
        <div x-show="@this.showAppointmentModal"
            x-transition
            @click.outside="$wire.closeAppointmentModal()"
            @keydown.escape.window="$wire.closeAppointmentModal()"
            class="appointment-modal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3 class="modal-title">Appointment Details</h3>
                    <button type="button"
                            @click="$wire.closeAppointmentModal()"
                            class="modal-close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @if($modalAppointment)
                <div class="modal-body">
                    <!-- Basic Appointment Header -->
                    <div class="appointment-header"
                        style="background-color:
                            @if($modalAppointment->status === 'Done') var(--bg-demo-green)
                            @elseif($modalAppointment->status === 'New') var(--bg-demo-yellow)
                            @else var(--bg-demo-red)
                            @endif">
                        <div>
                            <div class="appointment-technician">{{ $modalAppointment->company_name }}</div>
                            <div class="appointment-basic-details">
                                <div><strong>Technician:</strong> {{ $modalAppointment->technician }}</div>
                                <div><strong>Type:</strong> {{ $modalAppointment->display_type ?? $modalAppointment->type }}</div>
                                <div><strong>Time:</strong> {{ $modalAppointment->start_time }} - {{ $modalAppointment->end_time }}</div>
                                @if($modalAppointment->url && $modalAppointment->url !== '#')
                                    <div><a href="{{ $modalAppointment->url }}" target="_blank" class="text-blue-600 hover:underline">View Lead Details</a></div>
                                @endif
                            </div>
                        </div>
                        <span class="appointment-status"
                            style="background-color:
                                @if($modalAppointment->status === 'Done') var(--text-demo-green)
                                @elseif($modalAppointment->status === 'New') var(--text-demo-yellow)
                                @else var(--text-demo-red)
                                @endif; color: white;">
                            {{ $modalAppointment->status }}
                        </span>
                    </div>

                    <!-- Customer Details Section -->
                    <div class="appointment-detail-section">
                        <div class="section-title">Customer Details</div>
                        <div class="detail-row">
                            <span class="detail-label">Company Name:</span>
                            <span class="detail-value">{{ $modalAppointment->company_name ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">PIC Name:</span>
                            <span class="detail-value">{{ $modalAppointment->pic_name ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">PIC Phone:</span>
                            <span class="detail-value">{{ $modalAppointment->pic_phone ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">PIC Email:</span>
                            <span class="detail-value">{{ $modalAppointment->pic_email ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <!-- Installation Details Section -->
                    <div class="appointment-detail-section">
                        <div class="section-title">Installation Details</div>
                        @if($modalAppointment->hardware_id ?? false)
                            <div class="detail-row">
                                <span class="detail-label">Hardware ID:</span>
                                <span class="detail-value">{{ $modalAppointment->hardware_id }}</span>
                            </div>
                        @endif
                        <div class="detail-row">
                            <span class="detail-label">Appointment Date:</span>
                            <span class="detail-value">{{ $modalAppointment->appointment_date ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Installation Time:</span>
                            <span class="detail-value">{{ $modalAppointment->start_time }} - {{ $modalAppointment->end_time }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Installation Address:</span>
                            <span class="detail-value">{{ $modalAppointment->installation_address ?? $modalAppointment->address ?? 'N/A' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Technician Phone:</span>
                            <span class="detail-value">{{ $modalAppointment->technician_phone ?? '017-380 4549' }}</span>
                        </div>
                        @if($modalAppointment->installation_remark ?? false)
                            <div class="detail-row">
                                <span class="detail-label">Installation Remark:</span>
                                <span class="detail-value">{{ $modalAppointment->installation_remark }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Installation Devices Section -->
                    @if(isset($modalAppointment->devices) && collect($modalAppointment->devices)->sum() > 0)
                        <div class="appointment-detail-section">
                            <div class="section-title">Installation Device</div>
                            <div class="device-grid">
                                @if(($modalAppointment->devices['tc10'] ?? 0) > 0)
                                    <div class="device-item">
                                        <div class="device-name">TC10</div>
                                        <div class="device-quantity">{{ $modalAppointment->devices['tc10'] }} {{ $modalAppointment->devices['tc10'] > 1 ? 'UNITS' : 'UNIT' }}</div>
                                    </div>
                                @endif
                                @if(($modalAppointment->devices['face_id5'] ?? 0) > 0)
                                    <div class="device-item">
                                        <div class="device-name">FACE ID 5</div>
                                        <div class="device-quantity">{{ $modalAppointment->devices['face_id5'] }} {{ $modalAppointment->devices['face_id5'] > 1 ? 'UNITS' : 'UNIT' }}</div>
                                    </div>
                                @endif
                                @if(($modalAppointment->devices['tc20'] ?? 0) > 0)
                                    <div class="device-item">
                                        <div class="device-name">TC20</div>
                                        <div class="device-quantity">{{ $modalAppointment->devices['tc20'] }} {{ $modalAppointment->devices['tc20'] > 1 ? 'UNITS' : 'UNIT' }}</div>
                                    </div>
                                @endif
                                @if(($modalAppointment->devices['face_id6'] ?? 0) > 0)
                                    <div class="device-item">
                                        <div class="device-name">FACE ID 6</div>
                                        <div class="device-quantity">{{ $modalAppointment->devices['face_id6'] }} {{ $modalAppointment->devices['face_id6'] > 1 ? 'UNITS' : 'UNIT' }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Technician Remarks Section -->
                    @if($modalAppointment->remarks ?? false)
                        <div class="appointment-detail-section">
                            <div class="section-title">Technician Remarks</div>
                            <div class="remark-content">{{ $modalAppointment->remarks }}</div>
                        </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Day Modal for "More Appointments" -->
        <div x-show="dayModalOpen"
             x-transition
             @click.outside="dayModalOpen = false"
             @keydown.escape.window="dayModalOpen = false"
             class="day-modal">
            <div class="day-modal-content" @click.stop>
                <div class="modal-header">
                    <h3 class="modal-title" x-text="selectedDate + ' - All Appointments'"></h3>
                    <button type="button"
                            @click="dayModalOpen = false"
                            class="modal-close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="day-appointment-list">
                    <template x-for="appointment in selectedDayAppointments" :key="appointment.id">
                        <div class="day-appointment-item"
                            :style="'background-color: ' + (
                                appointment.status === 'Done' ? 'var(--bg-demo-green)' :
                                appointment.status === 'New' ? 'var(--bg-demo-yellow)' :
                                'var(--bg-demo-red)'
                            )"
                            @click="$wire.showAppointment(appointment.id, appointment.date); dayModalOpen = false;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;" x-text="appointment.company_name"></div>
                                    <div style="font-size: 0.875rem; color: #6b7280;">
                                        <div><strong>Technician:</strong> <span x-text="appointment.technician"></span></div>
                                        <div><strong>Time:</strong> <span x-text="appointment.start_time + ' - ' + appointment.end_time"></span></div>
                                        <div><strong>Type:</strong> <span x-text="appointment.display_type || appointment.type"></span></div>
                                    </div>
                                </div>
                                <span class="appointment-status"
                                      :style="'background-color: ' + (
                                        appointment.status === 'Done' ? 'var(--text-demo-green)' :
                                        appointment.status === 'New' ? 'var(--text-demo-yellow)' :
                                        'var(--text-demo-red)'
                                      ) + '; color: white;'"
                                      x-text="appointment.status">
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Global tooltip container -->
    <div x-show="showTooltip" :style="tooltipStyle"
        class="fixed px-2 py-1 text-sm text-white rounded pointer-events-none tooltip">
        <span x-text="tooltip"></span>
    </div>

    <script>
        function tooltipHandler() {
            return {
                tooltip: '',
                showTooltip: false,
                tooltipX: 0,
                tooltipY: 0,

                show(event) {
                    this.tooltip = event.target.dataset.tooltip;
                    this.showTooltip = true;
                    this.updatePosition(event);
                },

                updatePosition(event) {
                    this.tooltipX = event.clientX;
                    this.tooltipY = event.clientY - 10;
                },

                hide() {
                    this.showTooltip = false;
                },

                get tooltipStyle() {
                    return `left: ${this.tooltipX}px; top: ${this.tooltipY}px; transform: translate(-50%, -100%); background-color:black; z-index: 10000`;
                }
            };
        }

        function monthlyPicker() {
            return {
                init() {
                    flatpickr(this.$refs.datepicker, {
                        plugins: [new monthSelectPlugin({
                            shorthand: true,
                            dateFormat: "m/Y",
                            altFormat: "F Y",
                        })],
                        defaultDate: new Date()
                    })
                }
            }
        }
    </script>
</div>
