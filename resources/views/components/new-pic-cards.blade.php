{{-- filepath: /var/www/html/timeteccrm/resources/views/components/new-pic-cards.blade.php --}}
<style>
    /* Container Styles */
    .pics-container {
        margin-bottom: 1rem;
    }

    .empty-state {
        padding: 1rem;
        text-align: center;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }

    .empty-message {
        color: #6b7280;
    }

    /* Grid Layout */
    .pics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }

    /* Card Styles */
    .pic-card {
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 1px solid #e5e7eb;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .pic-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .pic-card-inner {
        padding: 1rem;
    }

    .pic-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #424242;
    }

    .pic-name {
        font-size: 1.125rem;
        font-weight: 500;
        color: #111827;
    }

    /* Badge Styles */
    .badge {
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        border-radius: 9999px;
        font-weight: 600;
    }

    .available-badge {
        background-color: #d1fae5;
        color: #065f46;
    }

    .resign-badge {
        background-color: #fee2e2;
        color: #b91c1c;
    }

    /* Info Rows */
    .pic-info-row {
        display: flex;
        padding: 0.375rem 0;
    }

    .pic-info-label {
        width: 80px;
        font-weight: 600;
        color: #4b5563;
    }

    .pic-info-value {
        flex: 1;
        color: #111827;
    }

    /* Responsive Adjustments */
    @media (max-width: 1200px) {
        .pics-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 900px) {
        .pics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .pics-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    // Get the record from Filament context
    $record = $this->record;

    $pics = [];
    if ($record && $record->companyDetail && !empty($record->companyDetail->additional_pic)) {
        try {
            if (is_string($record->companyDetail->additional_pic)) {
                $pics = json_decode($record->companyDetail->additional_pic, true) ?? [];
            } else {
                $pics = $record->companyDetail->additional_pic ?? [];
            }

            // Add default status if not present
            foreach ($pics as $key => $pic) {
                if (!isset($pic['status'])) {
                    $pics[$key]['status'] = 'Available';
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to parse additional_pic: ' . $e->getMessage());
            $pics = [];
        }
    }
@endphp

<div class="pics-container">
    @if (empty($pics))
        <div class="empty-state">
            <p class="empty-message">
                @if (isset($type) && $type === 'renewal')
                    No additional PICs added yet.
                @else
                    No additional PICs added by implementer yet.
                @endif
            </p>
        </div>
    @else
        <div class="pics-grid">
            @foreach ($pics as $pic)
                <div class="pic-card"
                    style="{{ isset($pic['status']) && $pic['status'] == 'Resign' ? 'background-color: #fee2e2;' : 'background-color: #d1fae5;' }}">
                    <div class="pic-card-inner">
                        <div class="pic-card-header">
                            <h3 class="pic-name">
                                {{ $pic['name'] ?? 'N/A' }}
                            </h3>
                            <div>
                                @if (isset($pic['status']) && $pic['status'] == 'Resign')
                                    <span class="badge resign-badge">
                                        Resign
                                    </span>
                                @else
                                    <span class="badge available-badge">
                                        Available
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="pic-info">
                            <div class="pic-info-row">
                                <div class="pic-info-label">Position:</div>
                                <div class="pic-info-value">{{ $pic['position'] ?? 'N/A' }}</div>
                            </div>
                            <div class="pic-info-row">
                                <div class="pic-info-label">Phone:</div>
                                <div class="pic-info-value">{{ $pic['hp_number'] ?? 'N/A' }}</div>
                            </div>
                            <div class="pic-info-row">
                                <div class="pic-info-label">Email:</div>
                                <div class="pic-info-value">{{ $pic['email'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
