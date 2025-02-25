<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadSummary extends Component
{
    public $totalLeads;
    public $activePercentage;
    public $inactivePercentage;
    public $companySizeData;

    public function mount()
    {
        $this->totalLeads = Lead::count();
        $activeLeads = Lead::where('categories', 'Active')->count();
        $inactiveLeads = Lead::where('categories', 'Inactive')->count();

        $this->activePercentage = $this->totalLeads > 0 ? round(($activeLeads / $this->totalLeads) * 100, 2) : 0;
        $this->inactivePercentage = $this->totalLeads > 0 ? round(($inactiveLeads / $this->totalLeads) * 100, 2) : 0;

        // Fetch company size data
        $this->companySizeData = Lead::all() // Get all leads
            ->groupBy(fn($lead) => $lead->company_size_label) // Use model accessor
            ->map(fn($group) => $group->count()) // Count each group
            ->toArray();
    }

    public function render()
    {
        return view('livewire.lead-summary');
    }
}
