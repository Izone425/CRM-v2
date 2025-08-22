<x-filament::page>
    <style>
        /* Summary cards styling */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        .summary-card {
            padding: 1.25rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease-in-out;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }

        .card-total { background: linear-gradient(to bottom right, #ebf5ff, #dbeafe); border: 1px solid #bfdbfe; }
        .card-completed { background: linear-gradient(to bottom right, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0; }
        .card-pending { background: linear-gradient(to bottom right, #fffbeb, #fef3c7); border: 1px solid #fde68a; }
        .card-missed { background: linear-gradient(to bottom right, #fee2e2, #fecaca); border: 1px solid #fca5a5; }
        .card-time { background: linear-gradient(to bottom right, #eef2ff, #e0e7ff); border: 1px solid #c7d2fe; }

        .card-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .card-total .card-value { color: #2563eb; }
        .card-completed .card-value { color: #059669; }
        .card-pending .card-value { color: #d97706; }
        .card-missed .card-value { color: #dc2626; }
        .card-time .card-value { color: #4f46e5; }

        .card-label {
            font-size: 0.875rem;
            color: #4b5563;
            font-weight: 500;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        @media (max-width: 1024px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="mb-6">
        <h2 class="section-title">Call Log Summary</h2>

        <div class="summary-grid">
            <div class="summary-card card-total">
                <div class="card-value">{{ \App\Models\CallLog::count() }}</div>
                <div class="card-label">Total Calls</div>
            </div>

            <div class="summary-card card-completed">
                <div class="card-value">{{ \App\Models\CallLog::where('call_status', 'Completed')->count() }}</div>
                <div class="card-label">Completed Calls</div>
            </div>

            <div class="summary-card card-pending">
                <div class="card-value">{{ \App\Models\CallLog::where('call_status', 'Pending')->count() }}</div>
                <div class="card-label">Pending Calls</div>
            </div>

            <div class="summary-card card-missed">
                <div class="card-value">{{ \App\Models\CallLog::where('call_status', 'Missed')->count() }}</div>
                <div class="card-label">Missed Calls</div>
            </div>

            <div class="summary-card card-time">
                <div class="card-value">
                    @php
                        $totalDuration = \App\Models\CallLog::sum('call_duration');
                        $hours = floor($totalDuration / 3600);
                        $minutes = floor(($totalDuration % 3600) / 60);
                    @endphp
                    {{ $hours }}h {{ $minutes }}m
                </div>
                <div class="card-label">Total Call Time</div>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament::page>
