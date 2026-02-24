@php
    $record = $getRecord();
    $enabled = (bool) $record->is_enabled;
@endphp

<div
    x-data="{ enabled: {{ $enabled ? 'true' : 'false' }}, loading: false }"
    style="display: flex; justify-content: center;"
>
    <button
        x-on:click="
            loading = true;
            enabled = !enabled;
            $wire.toggleAutoRenewal({{ $record->id }}).then(() => { loading = false; });
        "
        x-bind:disabled="loading"
        x-bind:style="'position: relative; display: inline-block; width: 40px; height: 22px; border-radius: 11px; border: none; cursor: pointer; transition: background-color 0.2s; flex-shrink: 0; background-color: ' + (enabled ? '#3b82f6' : '#d1d5db') + ';'"
    >
        <span
            x-bind:style="'position: absolute; top: 2px; width: 18px; height: 18px; background: white; border-radius: 50%; transition: left 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); left: ' + (enabled ? '20px' : '2px') + ';'"
        ></span>
    </button>
</div>
