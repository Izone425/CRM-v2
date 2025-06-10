<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\HardwareHandover;

class HardwareHandoverStats extends Component
{
    public function render()
    {
        $stats = [
            [
                'label' => 'All',
                'count' => HardwareHandover::count(),
                'class' => 'all',
            ],
            [
                'label' => 'New',
                'count' => HardwareHandover::where('status', 'New')->count(),
                'class' => 'new',
            ],
            [
                'label' => 'Pending Stock',
                'count' => HardwareHandover::where('status', 'Pending Stock')->count(),
                'class' => 'pending-stock',
            ],
            [
                'label' => 'Pending Migration',
                'count' => HardwareHandover::where('status', 'Pending Migration')->count(),
                'class' => 'pending-migration',
            ],
            [
                'label' => 'Completed',
                'count' => HardwareHandover::where('status', 'Completed')->count(),
                'class' => 'completed',
            ],
            [
                'label' => 'Draft / Rejected',
                'count' => HardwareHandover::whereIn('status', ['Draft', 'Rejected'])->count(),
                'class' => 'draft-rejected',
            ],
        ];

        return view('livewire.hardware-handover-stats', [
            'stats' => $stats,
        ]);
    }
}
