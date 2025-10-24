<?php
namespace App\Livewire\AdminHRDFDashboard;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use App\Models\HRDFHandover;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class HrdfNewTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

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

    #[On('refresh-hrdf-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getNewHrdfHandovers()
    {
        return HRDFHandover::query()
            ->where('status', 'New')
            ->orderBy('submitted_at', 'desc')
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewHrdfHandovers())
            ->defaultSort('submitted_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple()
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $salespersonNames = $data['value'];
                            $salespersonIds = User::whereIn('name', $salespersonNames)
                                ->where('role_id', '2')
                                ->pluck('id')
                                ->toArray();

                            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                                $leadQuery->whereIn('salesperson', $salespersonIds);
                            });
                        }
                    }),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HRDFHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'HRDF_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HRDFHandover $record): View {
                                return view('components.hrdf-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('submitted_at')
                    ->label('Date Submitted')
                    ->dateTime('d M Y, g:ia')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, HRDFHandover $record) {
                        // Determine display name
                        $displayName = $state ?? 'N/A';

                        // If subsidiary_id exists and is not null/empty, get subsidiary company name for display
                        if (!empty($record->subsidiary_id)) {
                            $subsidiary = \App\Models\Subsidiary::find($record->subsidiary_id);
                            if ($subsidiary && !empty($subsidiary->company_name)) {
                                $displayName = $subsidiary->company_name . ' (Subsidiary)';
                            }
                        }

                        // Shorten the display name
                        $shortened = strtoupper(Str::limit($displayName, 25, '...'));

                        // Always encrypt the main lead ID for the link
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($displayName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('hrdf_grant_id')
                    ->label('HRDF Grant ID')
                    ->searchable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('lead.salesperson')
                    ->label('Salesperson')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'No Salesperson';

                        $user = User::find($state);
                        return $user ? $user->name : 'Unknown';
                    })
                    ->searchable(),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Completed' => new HtmlString('<span style="color: green;">Completed</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HRDFHandover $record): View {
                            return view('components.hrdf-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('complete')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(): bool => auth()->user()->role_id === 3) // Only managers can complete
                        ->form([
                            \Filament\Forms\Components\TextInput::make('hrdf_claim_id')
                                ->label('HRDF Claim ID')
                                ->required()
                                ->placeholder('Enter HRDF Claim ID')
                                ->maxLength(100)
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
                                ->validationMessages([
                                    'required' => 'HRDF Claim ID is required.',
                                    'max' => 'HRDF Claim ID cannot exceed 100 characters.',
                                ])
                                ->helperText('This will be saved as the HRDF Claim ID for this handover'),
                        ])
                        ->modalHeading('Complete HRDF Handover')
                        ->modalSubmitActionLabel('Complete Handover')
                        ->modalWidth('md')
                        ->action(function (HRDFHandover $record, array $data): void {
                            try {
                                // Use database transaction to ensure both updates succeed or fail together
                                DB::transaction(function () use ($record, $data) {
                                    // 1. Update the HRDF Handover record
                                    $record->update([
                                        'status' => 'Completed',
                                        'completed_by' => auth()->id(),
                                        'completed_at' => now(),
                                        'hrdf_claim_id' => $data['hrdf_claim_id'], // Save the HRDF Claim ID
                                    ]);

                                    // 2. Update the related HRDF Claim status to SUBMITTED using relationship
                                    if ($record->hrdfClaim) {
                                        $record->hrdfClaim->update([
                                            'claim_status' => 'SUBMITTED',
                                            'hrdf_claim_id' => $data['hrdf_claim_id'], // Also update the claim ID in the claim record
                                            'invoice_number' => $record->autocount_invoice_number, // Add invoice number from handover
                                        ]);

                                        \Illuminate\Support\Facades\Log::info("HRDF Claim status updated to SUBMITTED", [
                                            'claim_id' => $record->hrdfClaim->id,
                                            'hrdf_grant_id' => $record->hrdf_grant_id,
                                            'hrdf_claim_id' => $data['hrdf_claim_id'],
                                            'invoice_number' => $record->autocount_invoice_number, // Log the invoice number
                                            'updated_by' => auth()->id()
                                        ]);
                                    } else {
                                        \Illuminate\Support\Facades\Log::warning("No HRDF Claim relationship found", [
                                            'hrdf_grant_id' => $record->hrdf_grant_id,
                                            'handover_id' => $record->id
                                        ]);
                                    }
                                });

                                // Get necessary data for email
                                $handoverId = 'HRDF_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                                $companyDetail = $record->lead->companyDetail;
                                $companyName = $companyDetail ? $companyDetail->company_name : 'Unknown Company';

                                // Get salesperson from lead->salesperson (user ID)
                                $salesperson = null;
                                if ($record->lead && $record->lead->salesperson) {
                                    $salesperson = User::find($record->lead->salesperson);
                                }

                                $completedBy = auth()->user();

                                // Send email notification to salesperson
                                if ($salesperson && $salesperson->email) {
                                    try {
                                        Mail::send('emails.hrdf-handover-completed', [
                                            'handoverId' => $handoverId,
                                            'companyName' => $companyName,
                                            'salesperson' => $salesperson,
                                            'completedBy' => $completedBy,
                                            'completedAt' => now(),
                                            'hrdfClaimId' => $data['hrdf_claim_id'], // Include HRDF Claim ID in email
                                            'record' => $record
                                        ], function ($mail) use ($salesperson, $handoverId) {
                                            $mail->to($salesperson->email, $salesperson->name)
                                                ->subject("HRDF HANDOVER | {$handoverId} | COMPLETED");
                                        });

                                        \Illuminate\Support\Facades\Log::info("HRDF handover completion email sent", [
                                            'handover_id' => $handoverId,
                                            'hrdf_claim_id' => $data['hrdf_claim_id'],
                                            'salesperson_email' => $salesperson->email,
                                            'completed_by' => $completedBy->email
                                        ]);

                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error("Failed to send HRDF handover completion email", [
                                            'error' => $e->getMessage(),
                                            'handover_id' => $handoverId
                                        ]);
                                    }
                                }

                                // Success notification with updated information
                                $message = "HRDF handover {$handoverId} has been completed with Claim ID: {$data['hrdf_claim_id']}";
                                if ($record->hrdfClaim) {
                                    $message .= ". Related HRDF Claim status updated to SUBMITTED.";
                                }

                                Notification::make()
                                    ->title('HRDF handover completed successfully')
                                    ->body($message)
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to complete HRDF handover", [
                                    'error' => $e->getMessage(),
                                    'handover_id' => $record->id,
                                    'trace' => $e->getTraceAsString()
                                ]);

                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to complete HRDF handover: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn(): bool => auth()->user()->role_id === 3) // Only managers can reject
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reject_reason')
                                ->label('Reason for Rejection')
                                ->required()
                                ->placeholder('Please provide a reason for rejecting this HRDF handover')
                                ->maxLength(500)
                        ])
                        ->action(function (HRDFHandover $record, array $data): void {
                            try {
                                // Update the record
                                $record->update([
                                    'status' => 'Rejected',
                                    'reject_reason' => $data['reject_reason'],
                                    'rejected_by' => auth()->id(),
                                    'rejected_at' => now(),
                                ]);

                                // Get necessary data for email
                                $handoverId = 'HRDF_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                                $companyDetail = $record->lead->companyDetail;
                                $companyName = $companyDetail ? $companyDetail->company_name : 'Unknown Company';

                                // Get salesperson from lead->salesperson (user ID)
                                $salesperson = null;
                                if ($record->lead && $record->lead->salesperson) {
                                    $salesperson = User::find($record->lead->salesperson);
                                }

                                $rejectedBy = auth()->user();

                                // Send email notification to salesperson
                                if ($salesperson && $salesperson->email) {
                                    try {
                                        Mail::send('emails.hrdf-handover-rejected', [
                                            'handoverId' => $handoverId,
                                            'companyName' => $companyName,
                                            'salesperson' => $salesperson,
                                            'rejectedBy' => $rejectedBy,
                                            'rejectedAt' => now(),
                                            'rejectReason' => $data['reject_reason'],
                                            'record' => $record
                                        ], function ($mail) use ($salesperson, $handoverId) {
                                            $mail->to($salesperson->email, $salesperson->name)
                                                ->subject("{$handoverId} | Rejected");
                                        });

                                        \Illuminate\Support\Facades\Log::info("HRDF handover rejection email sent", [
                                            'handover_id' => $handoverId,
                                            'salesperson_email' => $salesperson->email,
                                            'rejected_by' => $rejectedBy->email
                                        ]);

                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error("Failed to send HRDF handover rejection email", [
                                            'error' => $e->getMessage(),
                                            'handover_id' => $handoverId
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->title('HRDF handover rejected')
                                    ->body("HRDF handover {$handoverId} has been rejected and notification sent to salesperson.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to reject HRDF handover: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])->button()
                ->label('Actions')
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.admin-hrdf-dashboard.hrdf-new-table');
    }
}
