<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandover;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class ResellerHandoverNew extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-leadowner-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function openFilesModal($recordId)
    {
        $handover = ResellerHandover::find($recordId);

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

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerHandover::query()->where('status', 'new')->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('fb_id')
                    ->label('FB ID')
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandover $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'rejected',
                        'secondary' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->actions([
                Action::make('complete')
                    ->label('Complete the Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalwidth('xl')
                    ->form([
                        TextInput::make('timetec_proforma_invoice')
                            ->label('TimeTec Proforma Invoice Number')
                            ->required()
                            ->minLength(12)
                            ->maxLength(12)
                            ->alphanum()
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                            // ->rule(function ($record) {
                            //     return function (string $attribute, $value, \Closure $fail) use ($record) {
                            //         if (!$value) {
                            //             return;
                            //         }

                            //         // Check if invoice exists in crm_invoice_details
                            //         $invoice = \DB::connection('frontenddb')
                            //             ->table('crm_invoice_details')
                            //             ->where('f_invoice_no', strtoupper($value))
                            //             ->first();

                            //         if (!$invoice) {
                            //             $fail('The invoice number does not exist in the system.');
                            //             return;
                            //         }

                            //         // Validate f_payer_id matches reseller_id
                            //         if ($invoice->f_payer_id != $record->reseller_id) {
                            //             $fail('The invoice payer ID does not match the reseller ID.');
                            //             return;
                            //         }

                            //         // Validate f_company_id matches subscriber_id
                            //         if ($invoice->f_company_id != $record->subscriber_id) {
                            //             $fail('The invoice company ID does not match the subscriber ID.');
                            //             return;
                            //         }
                            //     };
                            // }),
                        Textarea::make('admin_reseller_remark')
                            ->label('Admin Reseller Remark')
                            ->rows(2)
                            ->maxLength(1000)
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                    ])
                    ->action(function (ResellerHandover $record, array $data) {
                        // // Verify invoice one more time before saving
                        // $invoice = \DB::connection('frontenddb')
                        //     ->table('crm_invoice_details')
                        //     ->where('f_invoice_no', strtoupper($data['timetec_proforma_invoice']))
                        //     ->where('f_payer_id', $record->reseller_id)
                        //     ->where('f_company_id', $record->subscriber_id)
                        //     ->first();

                        // if (!$invoice) {
                        //     Notification::make()
                        //         ->title('Invalid Invoice')
                        //         ->danger()
                        //         ->body('The invoice number is invalid or does not match the reseller and subscriber.')
                        //         ->send();
                        //     return;
                        // }

                        $record->update([
                            'status' => 'pending_confirmation',
                            'timetec_proforma_invoice' => $data['timetec_proforma_invoice'] ?? null,
                            'ttpi_submitted_at' => now(),
                            'admin_reseller_remark' => $data['admin_reseller_remark'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Task completed successfully')
                            ->success()
                            ->body('Status changed to pending confirmation')
                            ->send();

                        $this->resetTable();
                    })
                    ->modalHeading(false)
                    ->modalSubmitActionLabel('Complete'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.reseller-handover-new');
    }
}
