@php
    use Illuminate\Support\HtmlString;
    use Carbon\Carbon;

    $lead = $this->record; // Accessing Filament's record
    $systemQuestion = $lead->systemQuestion; // Avoid multiple calls

    $systemQuestions = [
        ['key' => 'modules', 'label' => '1. WHICH MODULE THAT YOU ARE LOOKING FOR?', 'value' => $systemQuestion?->modules ?? '-'],
        ['key' => 'existing_system', 'label' => '2. WHAT IS YOUR EXISTING SYSTEM FOR EACH MODULE?', 'value' => $systemQuestion?->existing_system ?? '-'],
        ['key' => 'usage_duration', 'label' => '3. HOW LONG HAVE YOU BEEN USING THE SYSTEM?', 'value' => $systemQuestion?->usage_duration ?? '-'],
        ['key' => 'expired_date', 'label' => '4. WHEN IS THE EXPIRED DATE?', 'value' => $systemQuestion?->expired_date
            ? Carbon::createFromFormat('Y-m-d', $systemQuestion->expired_date)->format('d/m/Y')
            : '-'],
        ['key' => 'reason_for_change', 'label' => '5. WHAT MAKES YOU LOOK FOR A NEW SYSTEM?', 'value' => $systemQuestion?->reason_for_change ?? '-'],
        ['key' => 'staff_count', 'label' => '6. HOW MANY STAFF DO YOU HAVE?', 'value' => $systemQuestion?->staff_count ?? '-'],
        ['key' => 'hrdf_contribution', 'label' => '7. DO YOU CONTRIBUTE TO HRDF FUND?', 'value' => $systemQuestion?->hrdf_contribution ?? '-'],
        ['key' => 'additional', 'label' => '8. ADDITIONAL QUESTIONS?', 'value' => $systemQuestion?->additional ?? '-'],
    ];

    // Apply the multi-line formatting to each value
    foreach ($systemQuestions as &$question) {
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
    @foreach ($systemQuestions as $item)
        <div style="--col-span-default: span 1 / span 1;" class="col-[--col-span-default]">
            <div data-field-wrapper="" class="fi-fo-field-wrp">
                <div class="grid gap-y-2">
                    <div class="flex items-center justify-between gap-x-3">
                        <div class="inline-flex items-center fi-fo-field-wrp-label gap-x-3">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                {{ $item['label'] }}
                            </span>
                        </div>
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
