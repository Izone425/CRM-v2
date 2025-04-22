<?php

namespace App\View\Components\weeklyCalendar;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class tableModalV2 extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public array $modalArray) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.weekly-calendar.table-modal-v2');
    }
}
