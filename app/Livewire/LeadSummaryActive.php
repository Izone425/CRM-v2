<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadSummaryActive extends Component
{
    public $totalActiveLeads;
    public $stagesData = [];

    public function mount()
    {
        // Define the expected stages
        $stages = ['Transfer', 'Demo', 'Follow Up'];

        // Count total leads in "Active" category
        $this->totalActiveLeads = Lead::where('categories', 'Active')->count();

        // Fetch leads grouped by their "stage", ensuring missing stages are set to 0
        $this->stagesData = Lead::where('categories', 'Active')
            ->whereIn('stage', $stages) // Only include expected stages
            ->select('stage', DB::raw('COUNT(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->toArray();

        // Ensure all stages exist in the result, even if 0
        foreach ($stages as $stage) {
            if (!isset($this->stagesData[$stage])) {
                $this->stagesData[$stage] = 0;
            }
        }
    }

    public function render()
    {
        return view('livewire.lead-summary-active', [
            'stagesData' => $this->stagesData,
            'totalActiveLeads' => $this->totalActiveLeads
        ]);
    }
}
