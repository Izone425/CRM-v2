{{-- filepath: /var/www/html/timeteccrm/resources/views/components/primary-pic-card.blade.php --}}
@php
    $record = $this->record;
    $primaryPIC = [
        'Name' => $record->companyDetail->name ?? $record->name ?? '-',
        'Position' => $record->companyDetail->position ?? '-',
        'Email' => $record->companyDetail->email ?? $record->email ?? '-',
        'Contact No' => $record->companyDetail->contact_no ?? $record->phone ?? '-',
    ];
@endphp

<style>
    .primary-pic-card {
        background-color: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 0.5rem;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .primary-pic-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #bae6fd;
    }

    .primary-pic-title {
        font-weight: 600;
        color: #0369a1;
        font-size: 1rem;
    }

    .primary-badge {
        background-color: #0ea5e9;
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
    }

    .primary-pic-info {
        display: grid;
        grid-template-columns: 100px 1fr;
        gap: 0.5rem;
    }

    .primary-pic-label {
        font-weight: 500;
        color: #64748b;
    }

    .primary-pic-value {
        color: #0f172a;
    }
</style>

<div class="primary-pic-card">
    <div class="primary-pic-header">
        <div class="primary-pic-title">Primary Contact Person</div>
        <div class="primary-badge">Main</div>
    </div>

    <div class="primary-pic-info">
        @foreach($primaryPIC as $label => $value)
            <div class="primary-pic-label">{{ $label }}:</div>
            <div class="primary-pic-value">{{ $value }}</div>
        @endforeach
    </div>
</div>
