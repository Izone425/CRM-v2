<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandover;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ResellerHandoverPendingReseller extends Component
{
    use WithFileUploads;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showCompleteModal = false;
    public $selectedHandoverId = null;
    public $selectedHandover = null;
    public $resellerNormalInvoice;
    public $paymentSlip;
    public $showFilesModal = false;
    public $handoverFiles = [];

    protected $listeners = ['handover-updated' => '$refresh'];

    public function updatedSearch()
    {
        // Search updated
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getHandoversProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = ResellerHandover::query()
            ->where('status', 'pending_reseller_invoice')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FB_', SUBSTRING(YEAR(created_at), 3, 2), LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function openCompleteModal($handoverId)
    {
        $this->selectedHandoverId = $handoverId;
        $this->selectedHandover = ResellerHandover::find($handoverId);
        $this->showCompleteModal = true;
        $this->resellerNormalInvoice = null;
        $this->paymentSlip = null;
    }

    public function closeCompleteModal()
    {
        $this->showCompleteModal = false;
        $this->selectedHandoverId = null;
        $this->selectedHandover = null;
        $this->resellerNormalInvoice = null;
        $this->paymentSlip = null;
    }

    public function openFilesModal($handoverId)
    {
        $handover = ResellerHandover::find($handoverId);

        if ($handover) {
            $this->selectedHandover = $handover;
            $this->handoverFiles = $handover->getCategorizedFilesForModal();

            $this->showFilesModal = true;
        }
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
        $this->handoverFiles = [];
    }

    public function removeInvoiceFile($index)
    {
        if (is_array($this->resellerNormalInvoice)) {
            unset($this->resellerNormalInvoice[$index]);
            $this->resellerNormalInvoice = array_values($this->resellerNormalInvoice);
        }
    }

    public function removePaymentSlipFile($index)
    {
        if (is_array($this->paymentSlip)) {
            unset($this->paymentSlip[$index]);
            $this->paymentSlip = array_values($this->paymentSlip);
        }
    }

    public function completeTask()
    {
        if (!$this->selectedHandover) {
            session()->flash('error', 'Handover not found.');
            return;
        }

        $this->validate([
            'resellerNormalInvoice.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'paymentSlip.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'resellerNormalInvoice.*.required' => 'Reseller normal invoice is required.',
            'paymentSlip.*.required' => 'Payment slip is required.',
        ]);

        // Store multiple files
        $resellerInvoicePaths = [];
        if (is_array($this->resellerNormalInvoice)) {
            foreach ($this->resellerNormalInvoice as $file) {
                $resellerInvoicePaths[] = $file->store('reseller-handover/reseller-invoices', 'public');
            }
        }

        $paymentSlipPaths = [];
        if (is_array($this->paymentSlip)) {
            foreach ($this->paymentSlip as $file) {
                $paymentSlipPaths[] = $file->store('reseller-handover/payment-slips', 'public');
            }
        }

        $this->selectedHandover->update([
            'reseller_normal_invoice' => json_encode($resellerInvoicePaths),
            'reseller_payment_slip' => json_encode($paymentSlipPaths),
            'status' => 'pending_timetec_license',
            'completed_at' => now(),
        ]);

        session()->flash('message', 'Task completed successfully!');
        $this->closeCompleteModal();

        // Emit event to refresh all handover components and counts after delay
        $this->dispatch('handover-completed-notification');
    }

    public function render()
    {
        return view('livewire.reseller-handover-pending-reseller', [
            'handovers' => $this->handovers
        ]);
    }
}
