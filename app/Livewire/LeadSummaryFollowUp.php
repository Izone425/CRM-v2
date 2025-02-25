<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadSummaryFollowUp extends Component
{
    public $totalFollowUpLeads;
    public $statusData = [];

    public function mount()
    {
        // Define the expected lead_status values
        $statuses = ['RFQ-Follow Up', 'Hot', 'Warm', 'Cold'];

        // Count total leads in the "Follow Up" stage
        $this->totalFollowUpLeads = Lead::where('stage', 'Follow Up')->count();

        // Fetch leads grouped by their "lead_status"
        $statusCounts = Lead::where('stage', 'Follow Up')
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
        return view('livewire.lead-summary-follow-up', [
            'statusData' => $this->statusData,
            'totalFollowUpLeads' => $this->totalFollowUpLeads
        ]);
    }
}
