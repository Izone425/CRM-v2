<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/modals/referral-details.blade.php -->
<style>
    .referral-container {
        display: flex;
        gap: 24px;
        margin-bottom: 24px;
    }

    .referral-section {
        flex: 1;
        background-color: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 16px;
    }

    .referral-section-header {
        display: flex;
        align-items: center;
        margin-bottom: 16px;
    }

    .referral-section-title {
        font-size: 1.125rem;
        font-weight: 500;
        color: #111827;
        margin-left: 8px;
    }

    .referral-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .referral-item {
        margin-bottom: 12px;
    }

    .referral-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #4b5563;
        margin-bottom: 4px;
    }

    .referral-value {
        font-size: 0.875rem;
        color: #111827;
    }

    @media (max-width: 768px) {
        .referral-container {
            flex-direction: column;
        }

        .referral-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="referral-container">
    <!-- Referral From Section -->
    <div class="referral-section">
        <div class="referral-section-header">
            <x-heroicon-o-arrow-right-start-on-rectangle style="width: 1.25rem; height: 1.25rem; color: #3b82f6;" />
            <span class="referral-section-title">Referral From</span>
        </div>

        @php
            $referral = $record?->referralDetail;

            // Get the closed by user name if it exists
            $closedByUser = null;
            if ($referral && $referral->closed_by) {
                $closedByUser = \App\Models\User::find($referral->closed_by);
            }

            $referralFromDetails = [
                ['label' => 'COMPANY', 'value' => $referral?->company ?? '-'],
                ['label' => 'NAME', 'value' => $referral?->name ?? '-'],
                ['label' => 'CLOSED BY', 'value' => $closedByUser ? $closedByUser->name : '-'],
                ['label' => 'EMAIL ADDRESS', 'value' => $referral?->email ?? '-'],
                ['label' => 'CONTACT NO.', 'value' => $referral?->contact_no ?? '-'],
                ['label' => 'REMARK', 'value' => $referral?->remark ?? '-'],
            ];
        @endphp

        <div class="referral-grid">
            @foreach ($referralFromDetails as $item)
                <div class="referral-item">
                    <div class="referral-label">{{ $item['label'] }}</div>
                    <div class="referral-value">{{ $item['value'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Referral To Section -->
    <div class="referral-section">
        <div class="referral-section-header">
            <x-heroicon-o-arrow-right-end-on-rectangle style="width: 1.25rem; height: 1.25rem; color: #3b82f6;" />
            <span class="referral-section-title">Referral To</span>
        </div>

        @php
            $referralToDetails = [
                ['label' => 'COMPANY', 'value' => $record?->companyDetail?->company_name ?? '-'],
                ['label' => 'NAME', 'value' => $record?->name ?? '-'],
                ['label' => 'EMAIL ADDRESS', 'value' => $record?->email ?? '-'],
                ['label' => 'CONTACT NO.', 'value' => $record?->phone ?? '-'],
            ];
        @endphp

        <div class="referral-grid">
            @foreach ($referralToDetails as $item)
                <div class="referral-item">
                    <div class="referral-label">{{ $item['label'] }}</div>
                    <div class="referral-value">{{ $item['value'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
