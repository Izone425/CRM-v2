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

        .calendar-header {
            display: grid;
            grid-template-columns: 0.5fr repeat(5, 1fr);
            gap: 1px;
            background: var(--bg-color-border);
            border-radius: 17px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        /* Salespersonrow */
        .calendar-body {
            display: grid;
            grid-template-columns: 0.5fr repeat(5, 1fr);
            gap: 1px;
            background: var(--bg-color-border);
            border-radius: 17px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        /* END */

        .header-row {
            display: grid;
            grid-template-columns: 0.5fr repeat(5, 1fr);
            grid-column: 1 / -1;
        }

        .header,
        .time,
        .day,
        .summary-cell {
            background: var(--bg-color-white);
            padding: 10px;
            min-height: 50px;
            text-align: center;
        }

        .header-date {
            font-size: 24px;
        }

        .time {
            font-weight: bold;
        }

        .dropdown-summary {
            grid-column: 1 / -1;
            background-color: var(--bar-color-blue);
            min-height: 0px;
        }

        .summary-cell {
            background-color: var(--bar-color-blue);
            min-height: 30px;
        }

        /* Leave Logo */
        .summary-cell>img {
            height: 40px;
            margin: 0 auto;
        }

        .circle-bg {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background-color: var(--bg-color-white);
            border-radius: 50%;
            color: var(--icon-color);
        }


        /* APPOINTMENT-CARD */
        .appointment-card {
            margin-block: 0.5rem;
            width: 100%;
            display: flex;
            flex-direction: row;
            text-align: left;
        }

        .appointment-card-bar {
            background-color: var(--sidebar-color);
            width: 12px;
        }

        .appointment-card-info {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding-block: 0.25rem;
            padding-inline: 0.5rem;
        }

        .appointment-company-name {
            max-width: 200px;
            font-weight: bold;
            color: var(--text-hyperlink-blue);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
        }

        /* || END || */


        /* For Salesperson Image */
        .flex-container {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            gap: 0.1rem;
            text-align: center;
        }

        .image-container {
            width: 45px;
            /* Set the container width */
            height: 45px;
            /* Set the container height */
            background-color: grey;
            /* Grey background for placeholder */
            border-radius: 50px;
            /* Rounded corners */
            flex-shrink: 0;
        }

        /* END */

        /* Summary Avatarr */

        .demo-avatar {
            display: grid;
            place-items: center;
            grid-template-columns: repeat(6, 1fr);
            grid-auto-rows: 1fr;
            column-gap: 3px;
        }

        .demo-avatar img {
            /* max-width: 40px; */
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        /* || END || Summary Avatar */

        /* public holiday overlay */
        .holiday-overlay {
            position: absolute;
            top: 0;
            /* Adjust dynamically with JS */
            left: calc(1 * (100% / 6));
            /* Start at column 1 */
            width: calc(5 * (100% / 6));
            /* Cover columns 1-5 */
            height: 100%;
            /* Adjust dynamically with JS */
            background: rgba(0, 0, 0, 0.5);
            pointer-events: none;
        }

        /* Initially hide the inner div */
        .hover-content {
            display: none;
            position: absolute;
            padding: 1rem;
            background-color: white;
            flex-direction: column;
            row-gap: 10px;
            right: -5px;
            top: 40px;
            z-index: 10000;
            width: 300px;
            justify-content: space-between;
            overflow-y: auto;
            max-height: 400px;
        }

        .hover-content-flexcontainer {
            display: flex;
            flex-direction: row;
            row-gap: 10px;
            column-gap: 10px;
            justify-content: flex-start;
            align-items: center
        }

        /* When hovering over the container, display the inner div */
        .hover-container:hover .hover-content {
            display: flex;
        }

        .tooltip {
            /* These styles are provided inline by Alpine via :style,
       but you can add additional styling if needed */
            z-index: 100;
        }


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

        @media (max-width:1400px) {
            .filter-row {
                width: 75%;
            }

            .badges-row {
                width: 75%;
            }
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
            /* Align filters to the right */
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

        .session-divider {
            flex: 0.005;
            height: 150px;
            background: #ccc;
            width: 0.5px;
        }

        /* SESSION SLOTS */
        .available-session-card {
            margin-block: 0.5rem;
            width: 100%;
            display: flex;
            flex-direction: row;
            text-align: left;
            background-color: #F3F4F6;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .available-session-card:hover {
            background-color: #E5E7EB;
        }

        .available-session-bar {
            background-color: #9CA3AF;
            width: 12px;
        }

        .available-session-info {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding-block: 0.25rem;
            padding-inline: 0.5rem;
        }

        .available-session-name {
            font-weight: bold;
        }

        .available-session-time {
            font-size: 0.9rem;
            color: #4B5563;
        }
.modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            overflow-y: auto;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        @media (min-width: 640px) {
            .modal-overlay {
                padding: 0;
                align-items: center;
            }
        }

        .modal-container {
            background-color: white;
            border-radius: 0.5rem;
            overflow: hidden;
            text-align: left;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 32rem;
            transform: translateY(0);
            transition: all 0.3s ease-out;
        }

        .modal-body {
            padding: 1.25rem 1rem 1rem;
        }

        @media (min-width: 640px) {
            .modal-body {
                padding: 1.5rem;
            }
        }

        .modal-title {
            margin-bottom: 1rem;
            font-size: 1.125rem;
            font-weight: 500;
            color: #111827;
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 0.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }

        .form-display-text {
            font-size: 0.95rem;
            color: #1f2937;
            background-color: #f3f4f6;
            padding: 0.5rem;
            border-radius: 0.375rem;
            border: 1px solid #e5e7eb;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.375rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
            outline: none;
        }

        .form-error {
            font-size: 0.75rem;
            color: #ef4444;
            margin-top: 0.25rem;
        }

        .modal-footer {
            background-color: #f9fafb;
            padding: 0.75rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        @media (min-width: 640px) {
            .modal-footer {
                flex-direction: row-reverse;
                justify-content: flex-start;
                padding: 0.75rem 1.5rem;
            }
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.375rem;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3b82f6;
        }

        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        }

        .btn-secondary {
            background-color: white;
            color: #374151;
            border-color: #d1d5db;
            margin-top: 0.75rem;
        }

        @media (min-width: 640px) {
            .btn-secondary {
                margin-top: 0;
                margin-left: 0.75rem;
            }
        }

        .btn-secondary:hover {
            background-color: #f9fafb;
        }

        .btn-secondary:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
        }
        .search-container {
            position: relative;
            margin-bottom: 0.5rem;
        }

        .search-icon {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            height: 1.25rem;
            width: 1.25rem;
            color: #9ca3af;
            pointer-events: none;
        }

        .session-available {
            background-color: #C6FEC3; /* Green for available */
            cursor: pointer;
        }

        .session-implementation {
            background-color: #FEE2E2; /* Red for implementation sessions */
        }

        .session-implementer-request {
            background-color: #FEF9C3; /* Yellow for implementer requests */
        }

        .session-past {
            background-color: #C2C2C2; /* Grey for past sessions */
            cursor: not-allowed;
        }

        .session-leave {
            background-color: #E9EBF0; /* Light grey for leave */
        }

        .session-holiday {
            background-color: #C2C2C2; /* Grey for public holiday */
        }

        /* Half-day leave indicators */
        .leave-am {
            position: relative;
        }

        /* .leave-am::before {
            content: "AM LEAVE";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background-color: rgba(233, 235, 240, 0.9);
            padding: 5px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        } */

        .leave-pm {
            position: relative;
        }

        /* .leave-pm::after {
            content: "PM LEAVE";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: rgba(233, 235, 240, 0.9);
            padding: 5px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        } */

        @media (min-width: 768px) {
            .modal-container {
                max-width: 48rem; /* Make the modal wider for the 2-column layout */
            }
        }

        .grid {
            display: grid;
        }

        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .gap-4 {
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .md\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .md\:col-span-2 {
                grid-column: span 2 / span 2;
            }
        }

        .col-span-1 {
            grid-column: span 1 / span 1;
        }

        /* Confirmation alert styling */
        .bg-red-50 {
            background-color: #fef2f2;
        }

        .border-red-300 {
            border-color: #fca5a5;
        }

        .text-red-700 {
            color: #b91c1c;
        }

        .text-red-600 {
            color: #dc2626;
        }

        .bg-red-600 {
            background-color: #dc2626;
        }

        .bg-gray-200 {
            background-color: #e5e7eb;
        }

        .text-gray-800 {
            color: #1f2937;
        }

        .rounded-md {
            border-radius: 0.375rem;
        }

        .mb-2 {
            margin-bottom: 0.5rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .p-4 {
            padding: 1rem;
        }

        .px-3 {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .py-2 {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .flex {
            display: flex;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .justify-end {
            justify-content: flex-end;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .calendar-container {
            position: relative;
            height: calc(140vh - 350px);
            overflow-y: auto;
            margin-bottom: 2rem;
            scrollbar-width: none;
        }

        .calendar-header {
            position: sticky;
            top: 0;
            z-index: 20;
            background: var(--bg-color-white);
            display: grid;
            grid-template-columns: 0.5fr repeat(5, 1fr);
            gap: 1px;
            background: var(--bg-color-border);
            border-radius: 17px 17px 0 0; /* Rounded only at top */
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);
        }

        /* Ensure the header row stays visible */
        .header-row {
            position: sticky;
            top: 0;
            z-index: 20;
            background: white;
        }

        /* Make summary section sticky below header if needed */
        .summary-section {
            position: sticky;
            top: 71px; /* Adjust based on your header height */
            z-index: 19;
            background: white;
        }

        /* Lower z-index for calendar body so headers appear on top */
        .calendar-body {
            position: relative;
            z-index: 1;
        }
    </style>


<div class="flex items-center gap-2 p-6 mb-6 bg-white shadow-xl rounded-2xl" wire:poll.1s>
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
        <div x-data="weeklyPicker()" class="w-36">
            <input type="text" x-ref="datepicker" wire:model.change='weekDate' placeholder="Date"
                class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
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
                            if (this.allSelected) return 'All Appointment Types'
                            else if (this.selected.length <= 0) return 'All Appointment Types'
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

        {{-- Implementers Filter --}}
        <div class="relative w-full">
            <form>
                <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                    @click.away="open = false" x-data="{
                        open: false,
                        selected: @entangle('selectedImplementers'),
                        allSelected: @entangle('allImplementersSelected'),
                        get label() {
                            if (this.allSelected)
                                return 'All Implementers';
                            else if (this.selected.length <= 0)
                                return 'All Implementers';
                            else
                                return this.selected.length + ' Implementers';
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
                                <input type="checkbox" wire:model.live="allImplementersSelected"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                    @if (auth()->user()->role_id != 3 && auth()->user()->id != 26) disabled @endif />
                                <label class="block ml-3 text-sm font-medium text-gray-700"
                                    style="padding-left: 10px;">
                                    All Implementers
                                </label>
                            </li>

                            <!-- Individual Implementers -->
                            @foreach ($implementers as $row)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="selectedImplementers"
                                        value="{{ $row['name'] }}"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                        @if (auth()->user()->role_id != 3 && auth()->user()->id != 26) disabled @endif />
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

        {{-- Session Type Filter --}}
        <div class="relative w-full">
            <form>
                <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                    @click.away="open = false" x-data="{
                        open: false,
                        selected: @entangle('selectedSessionType'),
                        allSelected: @entangle('allSessionTypeSelected'),
                        get label() {
                            if (this.allSelected)
                                return 'All Session Types'
                            else if (this.selected.length <= 0)
                                return 'All Session Types'
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
                                <input type="checkbox" wire:model.live="allSessionTypeSelected"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                <label class="block ml-3 text-sm font-medium text-gray-700"
                                    style="padding-left: 10px;">
                                    All Session Types
                                </label>
                            </li>

                            <!-- Individual Session Types -->
                            @foreach ($sessionTypes as $row)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="selectedSessionType"
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

        {{-- @if(auth()->user()->role_id !== 4 && auth()->user()->role_id !== 5)
            <div style="display:flex;align-items:center; font-size: 0.9rem; gap: 0.3rem;" class="px-2 py-2">
                <input type="checkbox" wire:model.change="showDropdown">
                <span>{{ $showDropdown ? 'Hide Summary' : 'Show Summary' }}</span>
            </div>
        @endif --}}
    </div>

    <!-- Appointment Breakdown -->
    <div class="w-full max-w-6xl p-6 mx-auto bg-white shadow-md rounded-xl">
        <div class="flex gap-6">

            <!-- Appointment Type -->
            <div class="flex-1 p-4 bg-white rounded-lg shadow">
                <h3 class="text-lg font-semibold">Appointment Type</h3>
                <p class="text-gray-600">Total Appointments: {{ $totalAppointments['ALL'] }}</p>

                @foreach ([
                    'KICK OFF MEETING SESSION' => '#71eb71',
                    'REVIEW SESSION' => '#ffff5cbf',
                    'DATA MIGRATION SESSION' => '#f86f6f',
                    'SYSTEM SETTING SESSION' => '#aed6f1',
                    'WEEKLY FOLLOW UP SESSION' => '#d2b4de'
                ] as $type => $color)
                    @php
                        $count = $appointmentBreakdown[$type] ?? 0;
                        $percentage = $totalAppointments['ALL'] > 0 ? round(($count / $totalAppointments['ALL']) * 100, 2) : 0;
                    @endphp

                    <div class="flex justify-between mt-2 text-sm">
                        <span>{{ $type }}</span>
                        <span>{{ $count }} ({{ $percentage }}%)</span>
                    </div>

                    <div class="w-full h-3 mt-1 mb-3 bg-gray-200 rounded-md">
                        <div class="h-full rounded-md" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                    </div>
                @endforeach
            </div>

            <!-- Appointment Status -->
            <div class="flex-1 p-4 bg-white rounded-lg shadow">
                <h3 class="text-lg font-semibold">Appointment Status</h3>
                <p class="text-gray-600">Total Appointments: {{ $totalAppointmentsStatus['ALL'] }}</p>

                @foreach (['NEW' => '#ffff5cbf', 'COMPLETED' => '#71eb71', 'CANCELLED' => '#f86f6f'] as $status => $color)
                    @php
                        $count = $totalAppointmentsStatus[$status] ?? 0;
                        $percentage = $totalAppointmentsStatus['ALL'] > 0 ? round(($count / $totalAppointmentsStatus['ALL']) * 100, 2) : 0;
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
<br>

<!-- Calendar Section -->
<div class="calendar-container">
    <div class="calendar-header">
        <div class="header-row" style="position: sticky; top: 0; z-index: 10; background-color: white;">
            <div class="header"
                style="display:flex; align-items:center; justify-content:center; font-weight:bold; font-size: 1.2rem">
                <div>{{ $currentMonth }}</div>
            </div>
            <div class="header">
                <div class="flex">
                    <button wire:click="prevWeek" style="width: 10%;"><i class="fa-solid fa-chevron-left"></i></button>
                    <span class="flex-1" @if ($weekDays[0]['today']) style="background-color: lightblue;" @endif>
                        <div class="text-center header-date">{{ $weekDays[0]['date'] }}</div>
                        <div>{{ $weekDays[0]['day'] }}</div>
                    </span>
                </div>
            </div>
            <div class="header">
                <div class="header-date">{{ $weekDays[1]['date'] }}</div>
                <div>{{ $weekDays[1]['day'] }}</div>
            </div>
            <div class="header">
                <div class="header-date">{{ $weekDays[2]['date'] }}</div>
                <div>{{ $weekDays[2]['day'] }}</div>
            </div>
            <div class="header">
                <div class="header-date">{{ $weekDays[3]['date'] }}</div>
                <div>{{ $weekDays[3]['day'] }}</div>
            </div>
            <div class="header">
                <div class="flex">
                    <div class="flex-1" @if ($weekDays[4]['today']) style="background-color: lightblue;" @endif>
                        <div class="header-date">{{ $weekDays[4]['date'] }}</div>
                        <div>{{ $weekDays[4]['day'] }}</div>
                    </div>
                    <button wire:click="nextWeek" style="width: 10%;"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="calendar-body">
        <div style="position: absolute; background-color: transparent; left: 0; width: calc(0.5/5.5*100%); height: 0%;"></div>

        @for($day = 1; $day <= 5; $day++)
            @if (isset($holidays[$day]))
                <div style="position: absolute; background-color: #C2C2C2; left: calc(({{ $day - 0.5 }}/5.5)*100%); width: calc((1/5.5)*100%); height: 100%; border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                    <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
                    <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays[$day]['name'] }}</div>
                </div>
            @endif
        @endfor
        <!-- Public Holidays -->
        @if (isset($holidays['1']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((0.5/5.5)* 100%); width: calc((1/5.5)*100%); height: 100%; border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['1']['name'] }}</div>
            </div>
        @endif
        @if (isset($holidays['2']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((1.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['2']['name'] }}</div>
            </div>
        @endif
        @if (isset($holidays['3']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((2.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['3']['name'] }}</div>
            </div>
        @endif
        @if (isset($holidays['4']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((3.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['4']['name'] }}</div>
            </div>
        @endif
        @if (isset($holidays['5']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((4.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['5']['name'] }}</div>
            </div>
        @endif

        <!-- Implementer Rows -->
        @foreach ($rows as $row)
            <div class="time">
                <div class="flex-container">
                    <div class="image-container">
                        <img style="border-radius: 50%;" src="{{ $row['implementerAvatar'] }}"
                            data-tooltip="{{ $row['implementerName'] }}" @mouseover="show($event)"
                            @mousemove="updatePosition($event)" @mouseout="hide()">
                    </div>
                </div>
            </div>

            @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                <div class="day {{ isset($row['leave'][$loop->iteration]) && $row['leave'][$loop->iteration]['session'] === 'full' ? 'full-leave' : '' }}
                        {{ isset($row['leave'][$loop->iteration]) && $row['leave'][$loop->iteration]['session'] === 'am' ? 'leave-am' : '' }}
                        {{ isset($row['leave'][$loop->iteration]) && $row['leave'][$loop->iteration]['session'] === 'pm' ? 'leave-pm' : '' }}">
                    @if (isset($row['leave'][$loop->iteration]) && $row['leave'][$loop->iteration]['session'] === 'full')
                        <div style="padding-block: 1rem; width: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center; margin-block:0.5rem;">
                            <div style="flex:1; text-align: center;">
                                <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                                <div style="font-size: 0.8rem;font-style: italic;">
                                    {{ $row['leave'][$loop->iteration]['leave_type'] }}
                                </div>
                                <div style="font-size: 0.8rem;">
                                    {{ $row['leave'][$loop->iteration]['status'] }} | Full Day
                                </div>
                            </div>
                        </div>
                    @elseif (isset($row['leave'][$loop->iteration]) && $row['leave'][$loop->iteration]['session'] === 'am')
                        <!-- AM Leave Notice -->
                        <div style="padding-block: 1rem; width: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center; margin-block:0.5rem;">
                            <div style="flex:1; text-align: center;">
                                <div style="font-size: 1.2rem; font-weight: bold;">AM Leave</div>
                                <div style="font-size: 0.8rem;font-style: italic;">
                                    {{ $row['leave'][$loop->iteration]['leave_type'] }}
                                </div>
                                <div style="font-size: 0.8rem;">
                                    {{ $row['leave'][$loop->iteration]['status'] }} | Half AM
                                </div>
                            </div>
                        </div>

                        <!-- Display Session Slots (Only PM sessions will be shown) -->
                        @php $daySessionSlots = $day . 'SessionSlots'; @endphp
                        @if(isset($row[$daySessionSlots]))
                            @foreach($row[$daySessionSlots] as $sessionName => $sessionDetails)
                                <!-- Standard session slot display code -->
                                @include('partials.session-slot', ['sessionDetails' => $sessionDetails, 'sessionName' => $sessionName, 'row' => $row, 'weekDays' => $weekDays, 'loop' => $loop])
                            @endforeach
                        @endif

                    @elseif (isset($row['leave'][$loop->iteration]) && $row['leave'][$loop->iteration]['session'] === 'pm')
                        <!-- Display Session Slots (Only AM sessions will be shown) -->
                        @php $daySessionSlots = $day . 'SessionSlots'; @endphp
                        @if(isset($row[$daySessionSlots]))
                            @foreach($row[$daySessionSlots] as $sessionName => $sessionDetails)
                                <!-- Standard session slot display code -->
                                @include('partials.session-slot', ['sessionDetails' => $sessionDetails, 'sessionName' => $sessionName, 'row' => $row, 'weekDays' => $weekDays, 'loop' => $loop])
                            @endforeach
                        @endif

                        <!-- PM Leave Notice -->
                        <div style="padding-block: 1rem; width: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center; margin-block:0.5rem;">
                            <div style="flex:1; text-align: center;">
                                <div style="font-size: 1.2rem; font-weight: bold;">PM Leave</div>
                                <div style="font-size: 0.8rem;font-style: italic;">
                                    {{ $row['leave'][$loop->iteration]['leave_type'] }}
                                </div>
                                <div style="font-size: 0.8rem;">
                                    {{ $row['leave'][$loop->iteration]['status'] }} | Half PM
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Display all Session Slots (no leave) -->
                        @php $daySessionSlots = $day . 'SessionSlots'; @endphp
                        @if(isset($row[$daySessionSlots]))
                            @foreach($row[$daySessionSlots] as $sessionName => $sessionDetails)
                                @if(isset($sessionDetails['status']))
                                    @php
                                        // Determine card style based on session status
                                        $cardStyle = '';
                                        $isClickable = true;

                                        if ($sessionDetails['status'] === 'past') {
                                            $cardStyle = 'background-color: #C2C2C2; cursor: not-allowed;';
                                            $isClickable = false;
                                        } elseif ($sessionDetails['status'] === 'leave') {
                                            $cardStyle = 'background-color: #E9EBF0;';
                                            $isClickable = false;
                                        } elseif ($sessionDetails['status'] === 'holiday') {
                                            $cardStyle = 'background-color: #C2C2C2;';
                                            $isClickable = false;
                                        } elseif ($sessionDetails['status'] === 'available') {
                                            $cardStyle = 'background-color: #C6FEC3;';
                                        } elseif ($sessionDetails['status'] === 'implementation_session') {
                                            $cardStyle = 'background-color: #FEE2E2;';
                                        } elseif ($sessionDetails['status'] === 'implementer_request') {
                                            $cardStyle = 'background-color: #FEF9C3;';
                                        } elseif ($sessionDetails['status'] === 'cancelled') {
                                            // For cancelled appointments after 12am, still show grey
                                            if (Carbon\Carbon::now()->format('Y-m-d') > Carbon\Carbon::parse($weekDays[$loop->parent->iteration - 1]['carbonDate'])->format('Y-m-d')) {
                                                $cardStyle = 'background-color: #C2C2C2; cursor: not-allowed;';
                                                $isClickable = false;
                                            } else {
                                                // For same-day cancellations, check if current time is past the session time
                                                $sessionStartTime = Carbon\Carbon::parse($weekDays[$loop->parent->iteration - 1]['carbonDate'] . ' ' . $sessionDetails['start_time']);
                                                if (Carbon\Carbon::now() > $sessionStartTime) {
                                                    $cardStyle = 'background-color: #C2C2C2; cursor: not-allowed;';
                                                    $isClickable = false;
                                                } else {
                                                    // If it's still in the future, mark as available (green)
                                                    $cardStyle = 'background-color: #C6FEC3;';
                                                    $isClickable = true;
                                                }
                                            }
                                        }
                                    @endphp

                                    @if(isset($sessionDetails['booked']) && $sessionDetails['booked'])
                                        <!-- Display Booked Session with standardized format -->
                                        <div class="appointment-card" style="{{ $cardStyle }}"
                                            wire:click="showAppointmentDetails({{ $sessionDetails['appointment']->id ?? 'null' }})">
                                            <div class="appointment-card-bar"></div>
                                            <div class="appointment-card-info">
                                                <div class="appointment-demo-type">
                                                    {{ str_replace(' SESSION', '', $sessionDetails['appointment']->type) }}
                                                </div>
                                                <div class="appointment-appointment-type">
                                                    {{ $sessionDetails['appointment']->appointment_type }}
                                                    @if($sessionDetails['status'] === 'implementer_request' && $sessionDetails['appointment']->request_status)
                                                        | <span style="text-transform:uppercase">{{ $sessionDetails['appointment']->request_status }}</span>
                                                    @elseif($sessionDetails['status'] === 'implementation_session' && $sessionDetails['appointment']->status)
                                                        | <span style="text-transform:uppercase">{{ $sessionDetails['appointment']->status }}</span>
                                                    @endif
                                                </div>
                                                <div class="appointment-company-name" title="{{ $sessionDetails['appointment']->company_name }}">
                                                    @if($sessionDetails['appointment']->lead_id)
                                                        <a target="_blank" rel="noopener noreferrer" href="{{ $sessionDetails['appointment']->url }}">
                                                            {{ $sessionDetails['appointment']->company_name }}
                                                        </a>
                                                    @else
                                                        {{ $sessionDetails['appointment']->company_name ?? $sessionDetails['appointment']->title ?? 'N/A' }}
                                                    @endif
                                                </div>
                                                <div class="appointment-time">{{ $sessionDetails['appointment']->start_time }} -
                                                    {{ $sessionDetails['appointment']->end_time }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <!-- Display Available Slot -->
                                        <div class="available-session-card" style="{{ $cardStyle }}"
                                            @if($isClickable && auth()->user()->role_id != 2)
                                                wire:click="bookSession('{{ $row['implementerId'] }}', '{{ $weekDays[$loop->parent->iteration - 1]['carbonDate'] }}', '{{ $sessionName }}', '{{ $sessionDetails['start_time'] }}', '{{ $sessionDetails['end_time'] }}')"
                                            @endif>
                                            <div class="available-session-bar"></div>
                                            <div class="available-session-info">
                                                @if($sessionDetails['status'] === 'leave')
                                                    <div class="available-session-name">ON LEAVE</div>
                                                @elseif($sessionDetails['status'] === 'holiday')
                                                    <div class="available-session-name">PUBLIC HOLIDAY</div>
                                                @elseif($sessionDetails['status'] === 'past')
                                                    <div class="available-session-name">PAST SESSION</div>
                                                @elseif($sessionDetails['status'] === 'cancelled' && !$isClickable)
                                                    <div class="available-session-name">CANCELLED SESSION</div>
                                                @elseif(($sessionDetails['status'] === 'available' && isset($sessionDetails['wasCancelled']) && $sessionDetails['wasCancelled']))
                                                    <div class="available-session-name">{{ $sessionName }}<br>AVAILABLE SLOT</div>
                                                @else
                                                    <div class="available-session-name">{{ $sessionName }}<br>AVAILABLE SLOT</div>
                                                @endif
                                                <div class="available-session-time">{{ $sessionDetails['formatted_start'] }} - {{ $sessionDetails['formatted_end'] }}</div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    @endif

                    <div x-data="{ expanded: false }">
                        @if (count($row[$day . 'Appointments']) <= 0)
                            <!-- No extra appointments to display beyond the session slots -->
                        @elseif (count($row[$day . 'Appointments']) <= 4)
                            <!-- Existing appointments that might not be tied to standard session slots -->
                            @foreach ($row[$day . 'Appointments'] as $appointment)
                                @php
                                    // Check if this appointment is already shown in a session slot
                                    $isSessionAppointment = false;
                                    $daySessionSlots = $day . 'SessionSlots';

                                    foreach ($row[$daySessionSlots] ?? [] as $sessionDetails) {
                                        if (isset($sessionDetails['appointment']) &&
                                            $sessionDetails['appointment']->id == $appointment->id) {
                                            $isSessionAppointment = true;
                                            break;
                                        }
                                    }
                                @endphp

                                @if (!$isSessionAppointment)
                                    <div class="available-session-card"
                                        @if ($appointment->status === 'Completed') style="background-color: var(--bg-demo-green)"
                                        @elseif ($appointment->status == 'New') style="background-color: var(--bg-demo-yellow)"
                                        @else style="background-color: var(--bg-demo-red)" @endif
                                        wire:click="showAppointmentDetails({{ $appointment->id ?? 'null' }})">
                                        <div class="available-session-bar"></div>
                                        <div class="available-session-info">
                                            <!-- Appointment content -->
                                            <div class="appointment-demo-type">
                                                {{ str_replace(' SESSION', '', $appointment->type) }}
                                            </div>
                                            <!-- Rest of appointment content -->
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <!-- Keep existing expand/collapse logic for many appointments -->
                            <template x-if="!expanded">
                                <div>
                                    @php $shownCount = 0; @endphp
                                    @foreach ($row[$day . 'Appointments'] as $appointment)
                                        @php
                                            // Check if this appointment is already shown in a session slot
                                            $isSessionAppointment = false;
                                            $daySessionSlots = $day . 'SessionSlots';

                                            foreach ($row[$daySessionSlots] ?? [] as $sessionDetails) {
                                                if (isset($sessionDetails['appointment']) &&
                                                    $sessionDetails['appointment']->id == $appointment->id) {
                                                    $isSessionAppointment = true;
                                                    break;
                                                }
                                            }
                                        @endphp

                                        @if (!$isSessionAppointment && $shownCount < 3)
                                            @php $shownCount++; @endphp
                                            <div class="available-session-card"
                                                @if ($appointment->status === 'Completed') style="background-color: var(--bg-demo-green)"
                                                @elseif ($appointment->status == 'New') style="background-color: var(--bg-demo-yellow)"
                                                @else style="background-color: var(--bg-demo-red)" @endif
                                                wire:click="showAppointmentDetails({{ $appointment->id ?? 'null' }})">
                                                <div class="available-session-bar"></div>
                                                <div class="available-session-info">
                                                    <div class="appointment-demo-type">
                                                        {{ str_replace(' SESSION', '', $appointment->type) }}
                                                    </div>
                                                    <div class="appointment-appointment-type">
                                                        {{ $appointment->appointment_type }}
                                                        @if(isset($appointment->request_status) && $appointment->request_status)
                                                            | <span style="text-transform:uppercase">{{ $appointment->request_status }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="appointment-company-name">
                                                        @if($appointment->lead_id)
                                                            <a target="_blank" rel="noopener noreferrer" href="{{ $appointment->url }}">
                                                                {{ $appointment->company_name }}
                                                            </a>
                                                        @else
                                                            {{ $appointment->company_name ?? $appointment->title ?? 'N/A' }}
                                                        @endif
                                                    </div>
                                                    <div class="appointment-time">{{ $appointment->start_time }} -
                                                        {{ $appointment->end_time }}</div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach

                                    @php
                                        // Count how many non-session slot appointments there are
                                        $nonSessionSlotCount = 0;
                                        foreach ($row[$day . 'Appointments'] as $appointment) {
                                            $isSessionAppointment = false;
                                            foreach ($row[$daySessionSlots] ?? [] as $sessionDetails) {
                                                if (isset($sessionDetails['appointment']) &&
                                                    $sessionDetails['appointment']->id == $appointment->id) {
                                                    $isSessionAppointment = true;
                                                    break;
                                                }
                                            }
                                            if (!$isSessionAppointment) {
                                                $nonSessionSlotCount++;
                                            }
                                        }
                                    @endphp

                                    @if($nonSessionSlotCount > 3)
                                        <div class="p-2 mb-2 text-center bg-gray-200 border rounded cursor-pointer card"
                                            @click="expanded = true">
                                            +{{ $nonSessionSlotCount - 3 }} more
                                        </div>
                                    @endif
                                </div>
                            </template>

                            <template x-if="expanded">
                                <div>
                                    @foreach ($row[$day . 'Appointments'] as $appointment)
                                        @php
                                            // Check if this appointment is already shown in a session slot
                                            $isSessionAppointment = false;
                                            $daySessionSlots = $day . 'SessionSlots';

                                            foreach ($row[$daySessionSlots] ?? [] as $sessionDetails) {
                                                if (isset($sessionDetails['appointment']) &&
                                                    $sessionDetails['appointment']->id == $appointment->id) {
                                                    $isSessionAppointment = true;
                                                    break;
                                                }
                                            }
                                        @endphp

                                        @if (!$isSessionAppointment)
                                            <div class="available-session-card"
                                                @if ($appointment->status === 'Completed') style="background-color: var(--bg-demo-green)"
                                                @elseif ($appointment->status == 'New') style="background-color: var(--bg-demo-yellow)"
                                                @else style="background-color: var(--bg-demo-red)" @endif
                                                wire:click="showAppointmentDetails({{ $appointment->id ?? 'null' }})">
                                                <div class="available-session-bar"></div>
                                                <div class="available-session-info">
                                                    <div class="appointment-demo-type">
                                                        {{ str_replace(' SESSION', '', $appointment->type) }}
                                                    </div>
                                                    <div class="appointment-appointment-type">
                                                        {{ $appointment->appointment_type }}
                                                        @if(isset($appointment->request_status) && $appointment->request_status)
                                                            | <span style="text-transform:uppercase">{{ $appointment->request_status }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="appointment-company-name">
                                                        @if($appointment->lead_id)
                                                            <a target="_blank" rel="noopener noreferrer" href="{{ $appointment->url }}">
                                                                {{ $appointment->company_name }}
                                                            </a>
                                                        @else
                                                            {{ $appointment->company_name ?? $appointment->title ?? 'N/A' }}
                                                        @endif
                                                    </div>
                                                    <div class="appointment-time">{{ $appointment->start_time }} -
                                                        {{ $appointment->end_time }}</div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach

                                    <div class="p-2 mb-2 text-center bg-gray-200 border rounded cursor-pointer card"
                                        @click="expanded = false">
                                        Show less
                                    </div>
                                </div>
                            </template>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
</div>

<!-- Global tooltip container -->
<div x-show="showTooltip" :style="tooltipStyle"
    class="fixed px-2 py-1 text-sm text-white rounded pointer-events-none tooltip">
    <span x-text="tooltip"></span>
</div>

@if($showBookingModal)
    <div class="modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="modal-container">
            <div class="modal-body">
                <h3 class="modal-title">
                    Select Session Type for {{ $bookingSession }} on {{ \Carbon\Carbon::parse($bookingDate)->format('j F Y') }}
                </h3>

                <div class="modal-form">
                    <!-- Time display -->
                    <div class="form-group">
                        <label class="form-label">Session Time</label>
                        <p class="form-display-text">
                            {{ \Carbon\Carbon::parse($bookingStartTime)->format('g:i A') }} -
                            {{ \Carbon\Carbon::parse($bookingEndTime)->format('g:i A') }}
                        </p>
                    </div>

                    <div class="grid gap-4 mt-4 md:grid-cols-3" style="display:column">
                        <!-- Option 1: Implementer Request -->
                        <div class="p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-100" wire:click="selectSessionType('implementer_request')">
                            <h4 class="mb-2 text-lg font-semibold text-center">IMPLEMENTER REQUEST</h4>
                        </div>

                        <!-- Option 2: Implementation Session -->
                        <div class="p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-100" wire:click="selectSessionType('implementation_session')">
                            <h4 class="mb-2 text-lg font-semibold text-center">IMPLEMENTATION SESSION</h4>
                        </div>

                        <!-- Option 3: Onsite Request -->
                        <div class="p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-100" wire:click="selectSessionType('onsite_request')">
                            <h4 class="mb-2 text-lg font-semibold text-center">ONSITE<br> REQUEST</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button wire:click="cancelBooking" type="button" class="btn btn-secondary">
                    Cancel
                </button>
            </div>
        </div>
    </div>
@endif

<!-- Add new Onsite Request Modal -->
@if($showOnsiteRequestModal)
    <div class="modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="modal-container">
            <div class="modal-body">
                <h3 class="modal-title">
                    Onsite Request: {{ $bookingSession }} for {{ \Carbon\Carbon::parse($bookingDate)->format('j F Y') }}
                </h3>

                <div class="modal-form">
                    <!-- Day Type Category -->
                    <div class="form-group">
                        <label for="onsiteDayType" class="form-label">Day Type Category <span class="text-red-600">*</span></label>
                        <select wire:model="onsiteDayType" id="onsiteDayType" class="form-select" wire:change="updateOnsiteSessions">
                            <option value="">-- Select Day Type --</option>
                            @php $availableDayTypes = $this->getAvailableDayTypes(); @endphp

                            @if($availableDayTypes['FULL_DAY'])
                                <option value="FULL_DAY">Full Day (All Sessions)</option>
                            @else
                                <option value="FULL_DAY" disabled>Full Day (Not Available - Sessions Already Booked)</option>
                            @endif

                            @if($availableDayTypes['HALF_DAY_MORNING'])
                                <option value="HALF_DAY_MORNING">Half Day Morning (Sessions 1 & 2)</option>
                            @else
                                <option value="HALF_DAY_MORNING" disabled>Half Day Morning (Not Available - Sessions Already Booked)</option>
                            @endif

                            @if($availableDayTypes['HALF_DAY_EVENING'])
                                <option value="HALF_DAY_EVENING">Half Day Evening (Sessions 3, 4 & 5)</option>
                            @else
                                <option value="HALF_DAY_EVENING" disabled>Half Day Evening (Not Available - Sessions Already Booked)</option>
                            @endif
                        </select>
                        @error('onsiteDayType')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Selected Sessions Display -->
                    <div class="form-group">
                        <label class="form-label">Selected Sessions</label>
                        <div class="p-3 rounded-md bg-gray-50">
                            @if(empty($selectedOnsiteSessions))
                                <p class="italic text-gray-500">Please select a day type first</p>
                            @else
                                @foreach($selectedOnsiteSessions as $session)
                                    <div class="mb-1 last:mb-0">
                                        <span class="font-medium">{{ $session['name'] }}</span>:
                                        {{ $session['start'] }} - {{ $session['end'] }}
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Onsite Category -->
                    <div class="form-group">
                        <label for="onsiteCategory" class="form-label">Onsite Category <span class="text-red-600">*</span></label>
                        <select wire:model="onsiteCategory" id="onsiteCategory" class="form-select">
                            <option value="">-- Select Onsite Category --</option>
                            <option value="ONSITE TRAINING">Onsite Training</option>
                            <option value="ONSITE KICK OFF MEETING">Onsite Kick Off Meeting</option>
                            <option value="ONSITE REVIEW SESSION">Onsite REVIEW SESSION</option>
                            <option value="ONSITE PROOF OF CONCEPT">Onsite Proof Of Concept</option>
                            <option value="ONSITE BUSINESS TRIP">Onsite Business Trip</option>
                        </select>
                        @error('onsiteCategory')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Company selection -->
                    <div class="form-group">
                        <label for="selectedCompany" class="form-label">Software Handover ID / Company Name <span class="text-red-600">*</span></label>

                        <!-- Company dropdown (only open/delay projects) -->
                        <select wire:model="selectedCompany" id="selectedCompany" class="form-select">
                            <option value="">-- Select a company --</option>

                            <!-- Open Projects Group -->
                            <optgroup label="Open Projects">
                                @foreach($filteredOpenDelayCompanies as $id => $data)
                                    @if($data['status'] === 'Open')
                                        <option value="{{ $id }}">
                                            SW_{{ $data['handover_id'] }} | {{ $data['name'] }}
                                            @if($requestSessionType === 'DATA MIGRATION SESSION' && isset($data['data_migration_count']) && $data['data_migration_count'] > 0)
                                                ({{ $data['data_migration_count'] }}/2 Data Migration)
                                            @endif
                                            @if($requestSessionType === 'SYSTEM SETTING SESSION' && isset($data['system_setting_count']) && $data['system_setting_count'] > 0)
                                                ({{ $data['system_setting_count'] }}/4 System Setting)
                                            @endif
                                        </option>
                                    @endif
                                @endforeach
                            </optgroup>

                            <!-- Delay Projects Group -->
                            <optgroup label="Delay Projects">
                                @foreach($filteredOpenDelayCompanies as $id => $data)
                                    @if($data['status'] === 'Delay')
                                        <option value="{{ $id }}">
                                            SW_{{ $data['handover_id'] }} | {{ $data['name'] }}
                                            @if($requestSessionType === 'DATA MIGRATION SESSION' && isset($data['data_migration_count']) && $data['data_migration_count'] > 0)
                                                ({{ $data['data_migration_count'] }}/2 Data Migration)
                                            @endif
                                            @if($requestSessionType === 'SYSTEM SETTING SESSION' && isset($data['system_setting_count']) && $data['system_setting_count'] > 0)
                                                ({{ $data['system_setting_count'] }}/4 System Setting)
                                            @endif
                                        </option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                        @error('selectedCompany')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Required Attendees -->
                    <div class="form-group">
                        <label for="requiredAttendees" class="form-label">Required Attendees <span class="text-red-600">*</span></label>
                        <input
                            type="text"
                            wire:model="requiredAttendees"
                            id="requiredAttendees"
                            class="form-input"
                            placeholder="email1@example.com;email2@example.com"
                        >
                        <!-- <button type="button" wire:click="loadAttendees" class="px-3 py-1 mt-1 text-xs text-white bg-blue-500 rounded hover:bg-blue-600" style="background-color: #2563eb;">
                            Load from Software Handover
                        </button> -->
                        <p class="mt-1 text-xs text-gray-500">Separate each email with a semicolon (e.g., email1;email2;email3)</p>
                        @error('requiredAttendees')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button wire:click="submitOnsiteRequest" type="button" class="btn btn-primary">
                    Submit Request
                </button>
                <button wire:click="cancelBooking" type="button" class="btn btn-secondary">
                    Cancel
                </button>
            </div>
        </div>
    </div>
@endif

<!-- Implementer Request Form Modal -->
@if($showImplementerRequestModal)
    <div class="modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="modal-container">
            <div class="modal-body">
                <h3 class="modal-title">
                    Implementer Request: {{ $bookingSession }} for {{ \Carbon\Carbon::parse($bookingDate)->format('j F Y') }}
                </h3>

                <div class="modal-form">
                    <!-- Session Type -->
                    <div class="form-group">
                        <label for="requestSessionType" class="form-label">Session Type <span class="text-red-600">*</span></label>
                        <select wire:model="requestSessionType" id="requestSessionType" class="form-select" wire:change="onRequestSessionTypeChange">
                            <option value="">-- Select Session Type --</option>
                            <option value="DATA MIGRATION SESSION">Data Migration Session</option>
                            <option value="SYSTEM SETTING SESSION">System Setting Session</option>
                            @if(!$this->weekHasFollowUpSession($selectedYear, $selectedWeek))
                                <option value="WEEKLY FOLLOW UP SESSION">Weekly Follow Up Session</option>
                            @else
                                <option value="WEEKLY FOLLOW UP SESSION" disabled>Weekly Follow Up Session (Already Scheduled)</option>
                            @endif
                        </select>
                        @error('requestSessionType')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    @if($requestSessionType === 'DATA MIGRATION SESSION' || $requestSessionType === 'SYSTEM SETTING SESSION')
                        <!-- Company selection for Data Migration or System Setting -->
                        <div class="form-group">
                            <label for="selectedCompany" class="form-label">Software Handover ID / Company Name <span class="text-red-600">*</span></label>

                            <!-- Company dropdown (only open/delay projects) -->
                            <select wire:model="selectedCompany" id="selectedCompany" class="form-select">
                                <option value="">-- Select a company --</option>

                                <!-- Open Projects Group -->
                                <optgroup label="Open Projects">
                                    @foreach($filteredOpenDelayCompanies as $id => $data)
                                        @if($data['status'] === 'Open')
                                            <option value="{{ $id }}">
                                                SW_{{ $data['handover_id'] }} | {{ $data['name'] }}
                                                @if($requestSessionType === 'DATA MIGRATION SESSION' && isset($data['data_migration_count']) && $data['data_migration_count'] > 0)
                                                    ({{ $data['data_migration_count'] }}/2 Data Migration)
                                                @endif
                                                @if($requestSessionType === 'SYSTEM SETTING SESSION' && isset($data['system_setting_count']) && $data['system_setting_count'] > 0)
                                                    ({{ $data['system_setting_count'] }}/4 System Setting)
                                                @endif
                                            </option>
                                        @endif
                                    @endforeach
                                </optgroup>

                                <!-- Delay Projects Group -->
                                <optgroup label="Delay Projects">
                                    @foreach($filteredOpenDelayCompanies as $id => $data)
                                        @if($data['status'] === 'Delay')
                                            <option value="{{ $id }}">
                                                SW_{{ $data['handover_id'] }} | {{ $data['name'] }}
                                                @if($requestSessionType === 'DATA MIGRATION SESSION' && isset($data['data_migration_count']) && $data['data_migration_count'] > 0)
                                                    ({{ $data['data_migration_count'] }}/2 Data Migration)
                                                @endif
                                                @if($requestSessionType === 'SYSTEM SETTING SESSION' && isset($data['system_setting_count']) && $data['system_setting_count'] > 0)
                                                    ({{ $data['system_setting_count'] }}/4 System Setting)
                                                @endif
                                            </option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select>
                            @error('selectedCompany')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    @elseif($requestSessionType === 'WEEKLY FOLLOW UP SESSION')
                        <!-- Year and Week selection for Weekly Follow Up -->
                        <div class="form-group">
                            <label for="selectedYear" class="form-label">Year</label>
                            <p class="form-display-text">{{ $selectedYear }}</p>
                            <input type="hidden" wire:model="selectedYear">
                        </div>

                        <div class="form-group">
                            <label for="selectedWeek" class="form-label">Week</label>
                            <p class="form-display-text">Week {{ $selectedWeek }}</p>
                            <input type="hidden" wire:model="selectedWeek">
                        </div>
                    @endif
                </div>
            </div>

            <div class="modal-footer">
                <button wire:click="submitImplementerRequest" type="button" class="btn btn-primary">
                    Submit Request
                </button>
                <button wire:click="cancelBooking" type="button" class="btn btn-secondary">
                    Cancel
                </button>
            </div>
        </div>
    </div>
@endif

<!-- Appointment Details Modal -->
@if($showAppointmentDetailsModal)
    <div class="modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="modal-container" x-data="{ showConfirmation: false }">
            <div class="modal-body">
                <h3 class="modal-title">
                    Implementation Session Details
                </h3>

                <div class="grid grid-cols-1 gap-4 modal-form md:grid-cols-2">
                    <!-- Left Column -->
                    <div>
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <p class="form-display-text">{{ $currentAppointment->company_name ?? $currentAppointment->title ?? 'N/A' }}</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Session Type</label>
                            <p class="form-display-text">{{ $currentAppointment->type ?? 'N/A' }}</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Appointment Type</label>
                            <p class="form-display-text">{{ $currentAppointment->appointment_type ?? 'N/A' }}</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <p class="form-display-text">{{ $currentAppointment->status ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <div class="form-group">
                            <label class="form-label">Date & Time</label>
                            <p class="form-display-text">
                                {{ $currentAppointment ? \Carbon\Carbon::parse($currentAppointment->date)->format('j F Y') : 'N/A' }}
                                {{ $currentAppointment ? \Carbon\Carbon::parse($currentAppointment->start_time)->format('g:i A') : '' }} -
                                {{ $currentAppointment ? \Carbon\Carbon::parse($currentAppointment->end_time)->format('g:i A') : '' }}
                            </p>
                        </div>

                        @if($currentAppointment->request_status)
                        <div class="form-group">
                            <label class="form-label">Request Status</label>
                            <p class="form-display-text">{{ $currentAppointment->request_status }}</p>
                        </div>
                        @endif

                        @if($currentAppointment->required_attendees)
                        <div class="form-group">
                            <label class="form-label">Required Attendees</label>
                            <p class="form-display-text">{{ $currentAppointment->required_attendees }}</p>
                        </div>
                        @endif

                        @if($currentAppointment->remarks)
                            <div class="form-group">
                                <label class="form-label">Remarks</label>
                                <p class="form-display-text">{{ $currentAppointment->remarks }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Cancellation Confirmation - show when showConfirmation is true -->
                    <div x-show="showConfirmation"
                        class="fixed inset-0 z-50 flex items-center justify-center"
                        style="background-color: rgba(0, 0, 0, 0.5);">
                        <div class="w-full max-w-md p-6 mx-auto bg-white rounded-lg shadow-xl"
                            @click.away="showConfirmation = false">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-red-700">Confirm Cancellation</h3>
                                <button @click="showConfirmation = false" class="text-gray-500 hover:text-gray-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="p-4 mb-4 border border-red-300 rounded-md bg-red-50">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 01-1-1V8a1 1 0 112 0v6a1 1 0 01-1 1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700">
                                            Are you sure you want to cancel this appointment?
                                        </p>
                                        <p class="mt-2 text-sm text-red-600">
                                            This action cannot be undone and will send cancellation notifications to all attendees.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 mt-5">
                                <button @click="showConfirmation = false" type="button"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Keep Appointment
                                </button>
                                <button wire:click="cancelAppointment({{ $currentAppointment->id }})" type="button"
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Yes, Cancel Appointment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                @if($currentAppointment && $currentAppointment->status !== 'Cancelled')
                <button @click="showConfirmation = true" type="button" class="btn"
                    style="background-color: #ef4444; color: white;">
                    Cancel Session
                </button>
                @endif
                <button wire:click="closeAppointmentDetails" type="button" class="btn btn-secondary">
                    Close
                </button>
            </div>
        </div>
    </div>
@endif

<!-- Implementation Session Form Modal -->
@if($showImplementationSessionModal)
    <div class="modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="modal-container">
            <div class="modal-body">
                <h3 class="modal-title">
                    Implementation Session: {{ $bookingSession }} for {{ \Carbon\Carbon::parse($bookingDate)->format('j F Y') }}
                </h3>

                <div class="modal-form">
                    <!-- Company selection -->
                    <div class="form-group">
                        <label for="selectedCompany" class="form-label">Software Handover ID / Company Name <span class="text-red-600">*</span></label>

                        <!-- Company dropdown with Software Handover ID and grouped by status -->
                        <select wire:model="selectedCompany" id="selectedCompany" class="form-select">
                            <option value="">-- Select a company --</option>

                            <!-- Open Projects Group -->
                            <optgroup label="Open Projects">
                                @foreach($filteredOpenDelayCompanies as $id => $data)
                                    @if($data['status'] === 'Open')
                                        <option value="{{ $id }}">
                                            SW_{{ $data['handover_id'] }} | {{ $data['name'] }}
                                            @if(isset($data['data_migration_count']) && $data['data_migration_count'] > 0 && $implementationDemoType === 'DATA MIGRATION SESSION')
                                                ({{ $data['data_migration_count'] }}/2 Data Migration)
                                            @endif
                                            @if(isset($data['system_setting_count']) && $data['system_setting_count'] > 0 && $implementationDemoType === 'SYSTEM SETTING SESSION')
                                                ({{ $data['system_setting_count'] }}/4 System Setting)
                                            @endif
                                        </option>
                                    @endif
                                @endforeach
                            </optgroup>

                            <!-- Delay Projects Group -->
                            <optgroup label="Delay Projects">
                                @foreach($filteredOpenDelayCompanies as $id => $data)
                                    @if($data['status'] === 'Delay')
                                        <option value="{{ $id }}">
                                            SW_{{ $data['handover_id'] }} | {{ $data['name'] }}
                                            @if(isset($data['data_migration_count']) && $data['data_migration_count'] > 0 && $implementationDemoType === 'DATA MIGRATION SESSION')
                                                ({{ $data['data_migration_count'] }}/2 Data Migration)
                                            @endif
                                            @if(isset($data['system_setting_count']) && $data['system_setting_count'] > 0 && $implementationDemoType === 'SYSTEM SETTING SESSION')
                                                ({{ $data['system_setting_count'] }}/4 System Setting)
                                            @endif
                                        </option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                        @error('selectedCompany')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Demo Type - Left side -->
                        <div class="form-group">
                            <label for="implementationDemoType" class="form-label">Demo Type <span class="text-red-600">*</span></label>
                            <select wire:model="implementationDemoType" id="implementationDemoType" class="form-select" {{ isset($hasKickOffMeeting) && $hasKickOffMeeting ? 'disabled' : '' }}>
                                @if((!$hasKickOffMeeting))
                                    <option value="KICK OFF MEETING SESSION">KICK OFF MEETING SESSION</option>
                                @else
                                    <option value="REVIEW SESSION">REVIEW SESSION</option>
                                @endif
                            </select>
                            @if(isset($hasKickOffMeeting) && $hasKickOffMeeting)
                                <p class="mt-1 text-xs text-gray-500">This company already had a Kick Off Meeting Session.</p>
                            @endif
                            @error('implementationDemoType')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Appointment Type - Right side -->
                        <div class="form-group">
                            <label for="appointmentType" class="form-label">Appointment Type <span class="text-red-600">*</span></label>
                            <select wire:model="appointmentType" id="appointmentType" class="form-select">
                                <option value="ONLINE">ONLINE</option>
                                <option value="ONSITE">ONSITE</option>
                                <option value="INHOUSE">INHOUSE</option>
                            </select>
                            @error('appointmentType')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Required Attendees -->
                    <div class="form-group">
                        <label for="requiredAttendees" class="form-label">Required Attendees <span class="text-red-600">*</span></label>
                        <input
                            type="text"
                            wire:model="requiredAttendees"
                            id="requiredAttendees"
                            class="form-input"
                            placeholder="email1@example.com;email2@example.com"
                        >
                        <!-- <button type="button" wire:click="loadAttendees" class="px-3 py-1 mt-1 text-xs text-white bg-blue-500 rounded hover:bg-blue-600" style="background-color: #2563eb;">
                            Load from Software Handover
                        </button> -->
                        <p class="mt-1 text-xs text-gray-500">Separate each email with a semicolon (e.g., email1;email2;email3)</p>
                        @error('requiredAttendees')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Skip Email and Teams Meeting Toggle -->
                    <div class="form-group">
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                wire:model="skipEmailAndTeams"
                                id="skipEmailAndTeams"
                                class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                            >
                            <label for="skipEmailAndTeams" class="block ml-2 text-sm font-medium text-gray-700">
                                Skip sending email and creating Teams meeting
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Select this option if you don't want to send an email notification or create a Teams meeting
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button wire:click="submitImplementationSession" type="button" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submitImplementationSession">Book Session</span>
                    <span wire:loading wire:target="submitImplementationSession">
                        <svg class="inline-block w-4 h-4 mr-2 -ml-1 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
                <button wire:click="cancelBooking" type="button" class="btn btn-secondary">
                    Cancel
                </button>
            </div>
        </div>
    </div>
@endif

<script>
    function tooltipHandler() {
        return {
            tooltip: '', // Holds the text to show
            showTooltip: false, // Controls tooltip visibility
            tooltipX: 0, // X position for the tooltip
            tooltipY: 0, // Y position for the tooltip

            // Called when the mouse enters an image
            show(event) {
                this.tooltip = event.target.dataset.tooltip;
                this.showTooltip = true;
                this.updatePosition(event);
            },

            // Update tooltip position on mouse move
            updatePosition(event) {
                // Position the tooltip near the cursor. Adjust offsets as needed.
                this.tooltipX = event.clientX;
                this.tooltipY = event.clientY - 10; // Slightly above the cursor
            },

            // Hide the tooltip when mouse leaves
            hide() {
                this.showTooltip = false;
            },

            // Compute the inline style for the tooltip (positioned relative to the viewport)
            get tooltipStyle() {
                return `left: ${this.tooltipX}px; top: ${this.tooltipY}px; transform: translate(-50%, -100%); background-color:black; z-index: 10000`;
            }
        };
    }

    function weeklyPicker() {
        return {
            init() {
                flatpickr(this.$refs.datepicker, {
                    enable: [date => date.getDay() === 1], // Only allow Mondays
                    dateFormat: 'd-m-Y'
                })
            }
        }
    }
</script>
</div>
