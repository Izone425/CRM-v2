@php
    use Illuminate\Support\HtmlString;
    use Carbon\Carbon;

    $lead = $this->record; // Access Filament's record
    $phase3 = $lead->systemQuestionPhase3;

    $phase3Questions = [
        ['key' => 'percentage', 'label' => '1. BASED ON MY PRESENTATION, HOW MANY PERCENT OUR SYSTEM CAN MEET YOUR REQUIREMENT?', 'value' => $phase3?->percentage ?? '-'],
        ['key' => 'vendor', 'label' => '2. CURRENTLY HOW MANY VENDORS YOU EVALUATE? VENDOR NAME?', 'value' => $phase3?->vendor ?? '-'],
        ['key' => 'plan', 'label' => '3. WHEN DO YOU PLAN TO IMPLEMENT THE SYSTEM?', 'value' => $phase3?->plan ?? '-'],
        ['key' => 'finalise', 'label' => '4. WHEN DO YOU PLAN TO FINALISE WITH THE MANAGEMENT?', 'value' => $phase3?->finalise ?? '-'],
        ['key' => 'additional', 'label' => '5. ADDITIONAL QUESTIONS?', 'value' => $phase3?->additional ?? '-'],
    ];

    foreach ($phase3Questions as &$question) {
        $content = $question['value'];
        $question['value'] = new HtmlString(
            '<div style="white-space: pre-wrap; word-wrap: break-word; word-break: break-word; line-height: 1;">'
            . nl2br(e($content))
            . '</div>'
        );
    }
    unset($question);
@endphp

<div style="--cols-default: repeat(1, minmax(0, 1fr));" class="grid grid-cols-[--cols-default] fi-fo-component-ctn gap-6">
    @foreach ($phase3Questions as $item)
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
                            {!! $item['value'] !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
