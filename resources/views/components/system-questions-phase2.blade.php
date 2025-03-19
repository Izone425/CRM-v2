@php
    use Illuminate\Support\HtmlString;

    $lead = $this->record; // Accessing Filament's record
    $systemQuestionPhase2 = $lead->systemQuestionPhase2; // Avoid multiple calls

    $phase2Questions = [
        ['key' => 'support', 'label' => '1. PROSPECT QUESTION – NEED TO REFER SUPPORT TEAM.', 'value' => $systemQuestionPhase2?->support ?? '-'],
        ['key' => 'product', 'label' => '2. PROSPECT CUSTOMIZATION – NEED TO REFER PRODUCT TEAM.', 'value' => $systemQuestionPhase2?->product ?? '-'],
        ['key' => 'additional', 'label' => '3. ADDITIONAL QUESTIONS?', 'value' => $systemQuestionPhase2?->additional ?? '-'],
    ];

    // Apply the multi-line formatting to each value
    foreach ($phase2Questions as &$question) {
        $content = $question['value'];
        $question['value'] = new HtmlString(
            '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
            . nl2br(e($content))
            . '</div>'
        );
    }
    unset($question); // Prevent accidental overwrites in foreach
@endphp

<div style="--cols-default: repeat(1, minmax(0, 1fr));" class="grid grid-cols-[--cols-default] fi-fo-component-ctn gap-6">
    @foreach ($phase2Questions as $item)
        <div style="--col-span-default: span 1 / span 1;" class="col-[--col-span-default]">
            <div data-field-wrapper="" class="fi-fo-field-wrp">
                <div class="grid gap-y-2">
                    <div class="flex items-center justify-between gap-x-3">
                        <label class="inline-flex items-center fi-fo-field-wrp-label gap-x-3" for="data.{{ $item['key'] }}">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                {{ $item['label'] }}
                            </span>
                        </label>
                    </div>
                    <div class="grid auto-cols-fr gap-y-2">
                        <div class="text-sm leading-6 fi-fo-placeholder" style="padding-left: 15px; font-weight: bold;">
                            {!! $item['value'] !!}  {{-- Render HTML string safely --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
