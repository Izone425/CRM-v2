<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SoftwareHandover;

class SoftwareHandoverStats extends Component
{
    public function render()
    {
        // First define the individual queries so we can reuse them
        $newApproved = SoftwareHandover::whereIn('status', ['New', 'Approved']);

        $pendingKickOff = SoftwareHandover::query()
            ->whereIn('status', ['Completed'])
            ->whereNull('kick_off_meeting')
            ->where(function ($q) {
                $q->whereIn('id', [420, 520, 531, 539])
                    ->orWhere('id', '>=', 540);
            });

        $pendingLicense = SoftwareHandover::query()
            ->whereIn('status', ['Completed'])
            ->whereNull('license_activated')
            ->where(function ($q) {
                $q->whereIn('id', [420, 520, 531, 539])
                    ->orWhere('id', '>=', 540);
            });

        // The stats array with combined "Pending Task" instead of "All"
        $stats = [
            [
                'label' => 'Pending Task',
                'count' => $newApproved->count() +
                          $pendingKickOff->count() +
                          $pendingLicense->count(),
                'class' => 'all',
            ],
            [
                'label' => 'New / Approved',
                'count' => $newApproved->count(),
                'class' => 'new',
            ],
            [
                'label' => 'Pending Kick Off',
                'count' => $pendingKickOff->count(),
                'class' => 'pending-stock',
            ],
            [
                'label' => 'Pending License Activation',
                'count' => $pendingLicense->count(),
                'class' => 'pending-migration',
            ],
            [
                'label' => 'Completed',
                'count' => SoftwareHandover::where('status', 'Completed')->count(),
                'class' => 'completed',
            ],
            [
                'label' => 'Draft / Rejected',
                'count' => SoftwareHandover::whereIn('status', ['Draft', 'Rejected'])->count(),
                'class' => 'draft-rejected',
            ],
        ];

        return view('livewire.software-handover-stats', [
            'stats' => $stats,
        ]);
    }
}
