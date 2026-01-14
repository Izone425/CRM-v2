<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandover;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ResellerHandoverPendingPayment extends Component
{
    use WithFileUploads;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showCompleteModal = false;
    public $selectedHandoverId = null;
    public $paymentSlip;

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
            ->where('status', 'pending_reseller_payment')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FB_', SUBSTRING(YEAR(created_at), 3, 2), LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function openFilesModal($handoverId)
    {
        $this->selectedHandover = ResellerHandover::find($handoverId);

        if ($this->selectedHandover) {
            $this->handoverFiles = $this->selectedHandover->getCategorizedFilesForModal();

            $this->showFilesModal = true;
        }
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
        $this->handoverFiles = [];
    }

    public function openCompleteModal($handoverId)
    {
        $this->selectedHandoverId = $handoverId;
        $this->selectedHandover = ResellerHandover::find($handoverId);
        $this->showCompleteModal = true;
        $this->paymentSlip = null;
    }

    public function closeCompleteModal()
    {
        $this->showCompleteModal = false;
        $this->selectedHandoverId = null;
        $this->selectedHandover = null;
        $this->paymentSlip = null;
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
            'paymentSlip.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'paymentSlip.*.required' => 'Payment slip is required.',
        ]);

        // Store multiple payment slip files
        $paymentSlipPaths = [];
        if (is_array($this->paymentSlip)) {
            foreach ($this->paymentSlip as $file) {
                $paymentSlipPaths[] = $file->store('reseller-handover/payment-slips', 'public');
            }
        }

        $this->selectedHandover->update([
            'reseller_payment_slip' => json_encode($paymentSlipPaths),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        session()->flash('message', 'Payment slip uploaded successfully!');
        $this->closeCompleteModal();

        // Emit event to refresh
        $this->dispatch('handover-completed-notification');
    }

    public function render()
    {
        return view('livewire.reseller-handover-pending-payment', [
            'handovers' => $this->handovers
        ]);
    }
}
