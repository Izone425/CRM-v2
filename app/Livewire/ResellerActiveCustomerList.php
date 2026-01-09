<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ResellerActiveCustomerList extends Component
{
    public $customers = [];
    public $search = '';
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->loadCustomers();
    }

    public function updatedSearch()
    {
        $this->loadCustomers();
    }

    public function sortByDate()
    {
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->loadCustomers();
    }

    public function loadCustomers()
    {
        $reseller = Auth::guard('reseller')->user();

        if ($reseller && $reseller->reseller_id) {
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
                ->where('crm_reseller_link.reseller_id', $reseller->reseller_id)
                ->where('crm_customer.f_status', 'A');

            if ($this->search) {
                $query->where('crm_customer.f_company_name', 'like', '%' . $this->search . '%');
            }

            $this->customers = $query
                ->orderBy('crm_customer.f_reg_date', $this->sortDirection)
                ->get();
        }
    }

    public function render()
    {
        return view('livewire.reseller-active-customer-list');
    }
}
