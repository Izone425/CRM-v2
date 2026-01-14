<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ResellerHandover;

class ResellerRenewalRequest extends Component
{
    public $showModal = false;
    public $search = '';
    public $selectedSubscriber = null;
    public $subscriberStatus = 'active';
    public $attendance = 0;
    public $leave = 0;
    public $claim = 0;
    public $payroll = 0;
    public $resellerRemark = '';

    public function openModal()
    {
        $this->showModal = true;
        $this->resetFields();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFields();
    }

    public function resetFields()
    {
        $this->search = '';
        $this->selectedSubscriber = null;
        $this->subscriberStatus = 'active';
        $this->attendance = 0;
        $this->leave = 0;
        $this->claim = 0;
        $this->payroll = 0;
        $this->resellerRemark = '';
    }

    public function selectSubscriber($fId, $companyName)
    {
        $this->selectedSubscriber = [
            'f_id' => $fId,
            'company_name' => $companyName
        ];
        $this->search = $companyName;
    }

    public function getSubscribersProperty()
    {
        if (strlen($this->search) < 2) {
            return collect([]);
        }

        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->join('crm_customer', 'crm_reseller_link.f_backend_companyid', '=', 'crm_customer.f_backend_companyid')
            ->select(
                'crm_reseller_link.f_id',
                'crm_reseller_link.f_company_name',
                'crm_customer.f_status'
            )
            ->where('crm_reseller_link.reseller_id', $reseller->reseller_id)
            ->where('crm_reseller_link.f_company_name', 'like', '%' . $this->search . '%');

        if ($this->subscriberStatus === 'active') {
            $query->where('crm_customer.f_status', 'A');
        } else {
            $query->whereIn('crm_customer.f_status', ['D', 'I', 'T']);
        }

        return $query->limit(10)->get();
    }

    public function submitRequest()
    {
        $this->validate([
            'selectedSubscriber' => 'required',
            'attendance' => 'required|integer|min:0',
            'leave' => 'required|integer|min:0',
            'claim' => 'required|integer|min:0',
            'payroll' => 'required|integer|min:0',
            'resellerRemark' => 'nullable|string|max:1000',
        ]);

        $reseller = Auth::guard('reseller')->user();

        // Mark existing handover requests for this subscriber as inactive if not completed
        ResellerHandover::where('subscriber_id', $this->selectedSubscriber['f_id'])
            ->where('status', '!=', 'completed')
            ->update(['status' => 'inactive']);

        // Store the renewal request in the database
        ResellerHandover::create([
            'reseller_id' => $reseller->reseller_id,
            'reseller_name' => $reseller->name,
            'reseller_company_name' => $reseller->company_name ?? '',
            'subscriber_id' => $this->selectedSubscriber['f_id'],
            'subscriber_name' => $this->selectedSubscriber['company_name'],
            'subscriber_status' => $this->subscriberStatus === 'active' ? 'A' : 'I',
            'attendance_qty' => $this->attendance,
            'leave_qty' => $this->leave,
            'claim_qty' => $this->claim,
            'payroll_qty' => $this->payroll,
            'reseller_remark' => $this->resellerRemark,
            'status' => 'new',
        ]);

        // Dispatch event to update dashboard counts
        $this->dispatch('handover-updated');

        $this->dispatch('notify', message: 'Renewal request submitted successfully!', type: 'success');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.reseller-renewal-request', [
            'subscribers' => $this->subscribers
        ]);
    }
}
