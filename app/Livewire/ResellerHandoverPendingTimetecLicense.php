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

class ResellerHandoverPendingTimetecLicense extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];

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
            ->query(ResellerHandover::query()->where('status', 'pending_timetec_license')->orderBy('completed_at', 'desc'))
            ->columns([
                TextColumn::make('fb_id')
                    ->label('FB ID')
                    ->searchable()
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandover $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('reseller_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'pending_confirmation',
                        'info' => 'pending_timetec_license',
                        'success' => 'completed',
                        'danger' => 'rejected',
                        'secondary' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
            ])
            ->actions([
                Action::make('complete_task')
                    ->label('Complete Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('official_receipt_number')
                            ->label('Official Receipt Number')
                            ->required()
                            ->maxLength(255)
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
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->helperText('Enter the official receipt number'),
                    ])
                    ->action(function (ResellerHandover $record, array $data) {
                        // Determine status based on reseller_option
                        $newStatus = $record->reseller_option === 'reseller_normal_invoice_with_payment_slip'
                            ? 'completed'
                            : 'pending_reseller_payment';

                        $record->update([
                            'official_receipt_number' => $data['official_receipt_number'],
                            'status' => $newStatus,
                            'completed_at' => now(),
                        ]);

                        // Send email notification only if completed
                        if ($newStatus === 'completed') {
                            try {
                                \Illuminate\Support\Facades\Mail::send('emails.reseller-handover-completed', [
                                    'record' => $record,
                                    'officialReceiptNumber' => $data['official_receipt_number']
                                ], function ($message) use ($record) {
                                    $message->to('zilih.ng@timeteccloud.com')
                                        ->subject('Reseller Handover Completed - FB ID: ' . $record->fb_id);
                                });
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send reseller handover completion email', [
                                    'error' => $e->getMessage(),
                                    'record_id' => $record->id
                                ]);
                            }
                        }

                        $statusMessage = $newStatus === 'completed'
                            ? 'Task completed successfully. Email notification sent to zi lih.ng@timeteccloud.com'
                            : 'Task completed successfully. Status changed to pending reseller payment';

                        Notification::make()
                            ->title('Task completed successfully')
                            ->body($statusMessage)
                            ->success()
                            ->send();

                        $this->dispatch('refresh-leadowner-tables');
                    })
                    ->modalHeading('Complete Task')
                    ->modalButton('Complete')
                    ->modalWidth('md'),
            ])
            ->defaultSort('completed_at', 'desc');
    }

    public function render()
    {
        return view('livewire.reseller-handover-pending-timetec-license');
    }
}
