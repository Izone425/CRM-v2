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
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;

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

    public function removeInvoiceFile()
    {
        $this->resellerNormalInvoice = null;
    }

    public function removePaymentSlipFile()
    {
        $this->paymentSlip = null;
    }

    public function completeTask()
    {
        if (!$this->selectedHandover) {
            session()->flash('error', 'Handover not found.');
            return;
        }

        // Build validation rules based on reseller option
        $rules = [
            'resellerNormalInvoice' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];

        $messages = [
            'resellerNormalInvoice.required' => 'Reseller normal invoice is required.',
            'resellerNormalInvoice.file' => 'The upload must be a valid file.',
            'resellerNormalInvoice.mimes' => 'The file must be a PDF, JPG, JPEG, or PNG.',
            'resellerNormalInvoice.max' => 'The file size must not exceed 10MB.',
        ];

        // Only require payment slip if the option includes it
        if ($this->selectedHandover->reseller_option === 'reseller_normal_invoice_with_payment_slip') {
            $rules['paymentSlip'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:10240';
            $messages['paymentSlip.required'] = 'Payment slip is required.';
            $messages['paymentSlip.file'] = 'The upload must be a valid file.';
            $messages['paymentSlip.mimes'] = 'The file must be a PDF, JPG, JPEG, or PNG.';
            $messages['paymentSlip.max'] = 'The file size must not exceed 10MB.';
        }

        $this->validate($rules, $messages);

        // Store single file
        $resellerInvoicePath = $this->resellerNormalInvoice->store('reseller-handover/reseller-invoices', 'public');

        $paymentSlipPath = null;
        if ($this->paymentSlip) {
            $paymentSlipPath = $this->paymentSlip->store('reseller-handover/payment-slips', 'public');
        }

        // Determine next status based on reseller option
        $nextStatus = $this->selectedHandover->reseller_option === 'reseller_normal_invoice_with_payment_slip'
            ? 'completed'
            : 'pending_reseller_payment';

        $updateData = [
            'reseller_normal_invoice' => $resellerInvoicePath,
            'rni_submitted_at' => now(),
            'status' => 'pending_timetec_license',
            'completed_at' => now(),
        ];

        // Only add payment slip if it exists
        if ($paymentSlipPath) {
            $updateData['reseller_payment_slip'] = $paymentSlipPath;
        }

        $this->selectedHandover->update($updateData);

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
