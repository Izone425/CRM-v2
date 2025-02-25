<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadSummaryInactive extends Component
{
    public $totalInactiveLeads;
    public $statusData = [];

    public function mount()
    {
        // Define the fixed order of statuses
        $statuses = ['Closed', 'Lost', 'On Hold', 'No Response'];

        // Count total inactive leads
        $this->totalInactiveLeads = Lead::where('categories', 'Inactive')->count();

        // Fetch leads grouped by their "lead_status"
        $statusCounts = Lead::where('categories', 'Inactive')
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
        return view('livewire.lead-summary-inactive', [
            'statusData' => $this->statusData,
            'totalInactiveLeads' => $this->totalInactiveLeads
        ]);
    }
}
