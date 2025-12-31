@php
    $ttInvoiceNumber = $extraAttributes['tt_invoice_number'] ?? 'N/A';
@endphp

<div class="p-6">
    <div class="text-center">
        <p class="font-mono text-xl font-bold tracking-wider text-blue-800 dark:text-blue-200">
            {{ $ttInvoiceNumber }}
        </p>
    </div>
</div>
