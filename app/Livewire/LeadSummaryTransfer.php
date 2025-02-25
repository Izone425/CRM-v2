<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadSummaryTransfer extends Component
{
    public $totalTransferLeads;
    public $statusData = [];

    public function mount()
    {
        // Define the expected lead_status values
        $statuses = ['RFQ-Transfer', 'Pending Demo', 'Demo Cancelled'];

        // Count total leads in the "Transfer" stage
        $this->totalTransferLeads = Lead::where('stage', 'Transfer')
            ->whereNotIn('lead_status', ['Under Review', 'New']) // Exclude these statuses
            ->count();

        // Fetch leads grouped by their "lead_status"
        $statusCounts = Lead::where('stage', 'Transfer')
            ->whereNotNull('lead_status') // Ensure lead_status is not NULL
            ->whereIn('lead_status', $statuses) // Only include expected statuses
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the result, even if 0
        foreach ($statuses as $status) {
            $this->statusData[$status] = $statusCounts[$status] ?? 0;
        }
    }

    public function render()
    {
        return view('livewire.lead-summary-transfer', [
            'statusData' => $this->statusData,
            'totalTransferLeads' => $this->totalTransferLeads
        ]);
    }
}
