{{-- filepath: /var/www/html/timeteccrm/resources/views/components/renewal-handover.blade.php --}}
@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        echo 'No handover record found.';
        return;
    }

    // Get renewal-specific data
    $renewalDetails = $record->renewalDetail ?? null;
    $quotations = $record->quotations ?? collect();
    $implementationPics = $record->implementationPics ?? collect();
    $primaryContact = $implementationPics->first();
@endphp

<style>
    .renewal-card {
        @apply bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4;
    }
    .renewal-header {
        @apply text-lg font-semibold text-blue-800 mb-3;
    }
    .info-row {
        @apply flex justify-between py-2 border-b border-gray-100;
    }
    .info-label {
        @apply text-sm font-medium text-gray-600;
    }
    .info-value {
        @apply text-sm text-gray-900 font-medium;
    }
    .status-badge {
        @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
    }
    .status-completed {
        @apply bg-green-100 text-green-800;
    }
    .status-pending {
        @apply bg-yellow-100 text-yellow-800;
    }
</style>

<div class="space-y-6">
    <!-- Header -->
    <div class="renewal-card">
        <div class="renewal-header">
            🔄 Renewal Handover Details
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="info-row">
                <span class="info-label">Handover ID:</span>
                <span class="info-value">{{ $record->formatted_handover_id ?? 'RW_' . str_pad($record->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Company Name:</span>
                <span class="info-value">{{ $record->company_name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Renewal Date:</span>
                <span class="info-value">{{ $record->created_at?->format('d M Y') ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="status-badge {{ $record->completion_status ? 'status-completed' : 'status-pending' }}">
                    {{ $record->completion_status ? 'Completed' : 'Pending' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Renewal Information -->
    @if($renewalDetails)
    <div class="renewal-card">
        <div class="renewal-header">
            📋 Renewal Information
        </div>
        <div class="space-y-2">
            <div class="info-row">
                <span class="info-label">Previous License Period:</span>
                <span class="info-value">{{ $renewalDetails->previous_license_start ?? 'N/A' }} - {{ $renewalDetails->previous_license_end ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">New License Period:</span>
                <span class="info-value">{{ $renewalDetails->new_license_start ?? 'N/A' }} - {{ $renewalDetails->new_license_end ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">License Type:</span>
                <span class="info-value">{{ $renewalDetails->license_type ?? 'Standard Renewal' }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Quotations -->
    @if($quotations && $quotations->count() > 0)
    <div class="renewal-card">
        <div class="renewal-header">
            💰 Renewal Quotations
        </div>
        <div class="space-y-3">
            @foreach($quotations as $quotation)
            <div class="p-3 bg-white border rounded-lg">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-medium text-blue-600">{{ $quotation->quotation_no }}</div>
                        <div class="text-sm text-gray-500">Created: {{ $quotation->created_at?->format('d M Y') }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold">RM {{ number_format($quotation->total_after_tax ?? 0, 2) }}</div>
                        <div class="text-sm text-gray-500">
                            {{ $quotation->quotationDetails?->count() ?? 0 }} items
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Implementation Contact -->
    @if($primaryContact)
    <div class="renewal-card">
        <div class="renewal-header">
            👤 Implementation Contact
        </div>
        <div class="space-y-2">
            <div class="info-row">
                <span class="info-label">Contact Person:</span>
                <span class="info-value">{{ $primaryContact->name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value">{{ $primaryContact->phone ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $primaryContact->email ?? 'N/A' }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Renewal Notes -->
    @if($record->notes || $record->special_instructions)
    <div class="renewal-card">
        <div class="renewal-header">
            📝 Renewal Notes
        </div>
        <div class="space-y-2">
            @if($record->notes)
            <div>
                <span class="info-label">General Notes:</span>
                <p class="mt-1 text-sm text-gray-700">{{ $record->notes }}</p>
            </div>
            @endif
            @if($record->special_instructions)
            <div>
                <span class="info-label">Special Instructions:</span>
                <p class="mt-1 text-sm text-gray-700">{{ $record->special_instructions }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
