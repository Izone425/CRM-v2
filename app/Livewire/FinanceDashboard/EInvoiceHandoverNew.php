<?php

namespace App\Livewire\FinanceDashboard;

use App\Models\CompanyDetail;
use App\Models\EInvoiceHandover;
use App\Models\Subsidiary;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;

class EInvoiceHandoverNew extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;
    public $lastRefreshTime;

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

    #[On('refresh-softwarehandover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function render()
    {
        return view('livewire.finance-dashboard.e-invoice-handover-new');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EInvoiceHandover::query()->where('status', 'New')->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('project_code')
                    ->label('ID')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewEInvoiceDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (EInvoiceHandover $record) {
                                return view('components.einvoice-handover-details', [
                                    'record' => $record
                                ]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $displayName = $state;
                        $company = null;

                        // Check if there's a subsidiary_id and get subsidiary company name
                        if (!empty($record->subsidiary_id)) {
                            $subsidiary = Subsidiary::find($record->subsidiary_id);
                            if ($subsidiary) {
                                $displayName = $subsidiary->company_name;
                                $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                            }
                        } else {
                            // Fall back to regular company lookup
                            $company = CompanyDetail::where('company_name', $state)->first();

                            if (!empty($record->lead_id)) {
                                $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                            }
                        }

                        if ($company) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($displayName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $displayName . '
                                </a>');
                        }

                        return "<span title='{$displayName}'>{$displayName}</span>";
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="background-color: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                        default => new HtmlString('<span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                    }),
            ])
            ->actions([
                Action::make('markCompleted')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Completed')
                    ->modalDescription('Are you sure you want to mark this E-Invoice handover as completed?')
                    ->modalSubmitActionLabel('Mark Completed')
                    ->action(function (EInvoiceHandover $record) {
                        $record->update([
                            'status' => 'Completed',
                            'completed_by' => auth()->id(),
                            'completed_at' => now()
                        ]);

                        // Update lead einvoice_status to "Complete Registration" only if it hasn't been completed before
                        if ($record->lead && $record->lead->einvoice_status !== 'Complete Registration') {
                            $record->lead->update(['einvoice_status' => 'Complete Registration']);
                        }

                        // Get salesperson email
                        $salespersonEmail = null;
                        $salespersonName = $record->salesperson;

                        // Try to find salesperson by name first
                        $salesperson = User::where('name', $salespersonName)->first();

                        // If not found by name, try to get from lead relationship
                        if (!$salesperson && $record->lead && $record->lead->salesperson) {
                            $salesperson = User::find($record->lead->salesperson);
                            $salespersonName = $salesperson ? $salesperson->name : $record->salesperson;
                        }

                        if ($salesperson) {
                            $salespersonEmail = $salesperson->email;
                        }

                        // Send email notification
                        if ($salespersonEmail) {
                            try {
                                $projectCode = $record->project_code;
                                $companyName = $record->company_name;
                                $status = 'COMPLETED';

                                $subject = "{$projectCode} / {$salespersonName} / {$companyName} / {$status}";

                                // Generate lead URL
                                $leadEncrypted = \App\Classes\Encryptor::encrypt($record->lead_id);
                                $leadUrl = url('admin/leads/' . $leadEncrypted);

                                $emailData = [
                                    'salesperson_name' => strtoupper($salespersonName),
                                    'company_name' => $companyName,
                                    'project_code' => $projectCode,
                                    'lead_url' => $leadUrl
                                ];

                                Mail::send('emails.einvoice_completion_notification', $emailData, function ($message) use ($salespersonEmail, $subject) {
                                    $message->to($salespersonEmail)
                                        ->cc(['auni@timeteccloud.com', 'faiz@timeteccloud.com'])
                                        ->subject($subject);
                                });

                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to send E-Invoice completion email", [
                                    'error' => $e->getMessage(),
                                    'einvoice_handover_id' => $record->id,
                                    'salesperson_email' => $salespersonEmail
                                ]);
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning("No salesperson email found for E-Invoice completion notification", [
                                'einvoice_handover_id' => $record->id,
                                'salesperson_name' => $salespersonName
                            ]);
                        }

                        Notification::make()
                            ->title('E-Invoice handover marked as completed')
                            ->success()
                            ->send();

                        $this->resetTable();
                    })
            ])
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->poll('300s');
    }
}
