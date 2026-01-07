<div class="subsidiary-export-container" style="margin-top: 1.5rem; padding: 1rem; border-top: 1px solid #e5e7eb; display: flex; gap: 1rem; flex-wrap: wrap;">
    <a href="{{ route('software-handover.export-customer', ['lead' => \App\Classes\Encryptor::encrypt($record->lead_id)]) }}"
        target="_blank"
        style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background-color: #10b981; color: white; border-radius: 0.375rem; text-decoration: none; font-weight: 500; transition: all 0.2s; font-size: 0.875rem;"
        onmouseover="this.style.backgroundColor='#059669'"
        onmouseout="this.style.backgroundColor='#10b981'"
        class="sw-export-btn">
        <!-- Download Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        Export AutoCount Debtor
    </a>

    <a href="{{ route('einvoice.export', [
            'lead' => \App\Classes\Encryptor::encrypt($record->lead_id),
            'subsidiaryId' => $record->id
        ]) }}"
       target="_blank"
       style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background-color: #3b82f6; color: white; border-radius: 0.375rem; text-decoration: none; font-weight: 500; transition: all 0.2s; font-size: 0.875rem;"
       onmouseover="this.style.backgroundColor='#2563eb'"
       onmouseout="this.style.backgroundColor='#3b82f6'"
       class="einvoice-export-btn">
        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        Export AutoCount E-Invoice
    </a>
</div>
