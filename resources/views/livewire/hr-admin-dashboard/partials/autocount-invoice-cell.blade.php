@php
    $record = $getRecord();
    $isEditable = in_array($record->payment_method, ['PayPal', 'Razer']);
    $currentValue = $record->autocount_invoice_no ?? '';
@endphp

@if ($isEditable)
    <div x-data="{ value: '{{ $currentValue }}', saving: false, saved: false }" style="display: flex; align-items: center; gap: 4px; flex-wrap: nowrap;">
        <input
            type="text"
            x-model="value"
            maxlength="20"
            style="width: 100px; padding: 2px 6px; font-size: 0.7rem; border: 1px solid #d1d5db; border-radius: 4px; flex-shrink: 1; min-width: 0;"
            placeholder="Enter Invoice No."
        />
        <button
            x-on:click="
                saving = true;
                saved = false;
                $wire.updateAutocountInvoice({{ $record->id }}, value).then(() => {
                    saving = false;
                    saved = true;
                    setTimeout(() => { saved = false; }, 2000);
                });
            "
            x-bind:disabled="saving"
            style="padding: 2px 8px; font-size: 0.65rem; font-weight: 500; color: white; background-color: #7abee5; border: none; border-radius: 4px; cursor: pointer; white-space: nowrap; flex-shrink: 0;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#7abee5'"
        >
            <template x-if="!saving && !saved">
                <span>Update</span>
            </template>
            <template x-if="saving">
                <span>...</span>
            </template>
            <template x-if="saved">
                <span>Saved</span>
            </template>
        </button>
    </div>
@else
    <span style="font-size: 0.75rem;">{{ $currentValue ?: '-' }}</span>
@endif
