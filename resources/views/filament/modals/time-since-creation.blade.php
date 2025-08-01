<div class="p-4">
    <style>
        .time-card {
            background-color: #f3f4f6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .time-card:hover {
            background-color: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .time-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .time-value {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .time-unit {
            display: block;
            font-size: 2.25rem;
            font-weight: 700;
            text-align: center;
            color: #111827;
        }

        .unit-label {
            font-size: 0.875rem;
            color: #6b7280;
            text-align: center;
        }

        .time-units-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .age-indicator {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-align: center;
        }

        .age-indicator.old {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .age-indicator.recent {
            background-color: #dcfce7;
            color: #15803d;
        }
    </style>

    <div class="time-card">
        <p class="time-label">Created At</p>
        <p class="time-value">{{ $created_at }}</p>
    </div>

    <div class="time-units-container">
        @php
            $diffInMonths = $record->created_at->diffInMonths(now());
            $remainingDays = $record->created_at->copy()->addMonths($diffInMonths)->diffInDays(now());
            $isOlderThan6Months = $diffInMonths >= 6;
        @endphp

        <div class="time-card" style="margin-bottom: 0;">
            <span class="time-unit">{{ $diffInMonths }}</span>
            <p class="unit-label">Months</p>
        </div>

        <div class="time-card" style="margin-bottom: 0;">
            <span class="time-unit">{{ $remainingDays }}</span>
            <p class="unit-label">Days</p>
        </div>
    </div>

    <div class="age-indicator {{ $isOlderThan6Months ? 'old' : 'recent' }}">
        <p class="time-label">Is this lead more than 6 months?</p>
        {{ $isOlderThan6Months ? 'Yes, more than 6 months' : 'No, less than 6 months' }}
    </div>
</div>
