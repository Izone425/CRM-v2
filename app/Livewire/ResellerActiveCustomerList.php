<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ResellerActiveCustomerList extends Component
{
    public $search = '';
    public $sortDirection = 'desc';
    public $activeTab = 'active'; // 'active' or 'inactive'

    public function updatedSearch()
    {
        // Search updated
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function sortByDate()
    {
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
    }

    public function getCustomersProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->join('crm_customer', 'crm_reseller_link.f_backend_companyid', '=', 'crm_customer.f_backend_companyid')
            ->select(
                'crm_customer.f_company_name',
                'crm_customer.f_reg_date',
                'crm_customer.f_status as status',
                'crm_reseller_link.f_id',
                'crm_reseller_link.reseller_name',
                'crm_reseller_link.f_rate'
            )
            ->where('crm_reseller_link.reseller_id', $reseller->reseller_id);

        if ($this->activeTab === 'active') {
            $query->where('crm_customer.f_status', 'A');
        } else {
            $query->whereIn('crm_customer.f_status', ['D', 'I', 'T']);
        }

        if ($this->search) {
            $query->where('crm_customer.f_company_name', 'like', '%' . $this->search . '%');
        }

        return $query
            ->orderBy('crm_customer.f_reg_date', $this->sortDirection)
            ->get();
    }

    public function render()
    {
        return view('livewire.reseller-active-customer-list', [
            'customers' => $this->customers
        ]);
    }
}
