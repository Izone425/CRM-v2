<?php
// filepath: /var/www/html/timeteccrm/app/Livewire/AdminHeadcountDashboard/HeadcountNewTable.php

namespace App\Livewire\AdminHeadcountDashboard;

use App\Models\HeadcountHandover;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class HeadcountNewTable extends Component implements HasForms, HasTable
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

    public function getNewHeadcountHandovers()
    {
        return HeadcountHandover::with(['lead.companyDetail', 'lead.salespersonUser'])
            ->where('status', 'New')
            ->orderBy('created_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getNewHeadcountHandovers())
            ->columns([
                TextColumn::make('id')
                    ->label('Headcount ID')
                    ->formatStateUsing(function ($state, HeadcountHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHeadcountHandoverDetails')
                            ->modalHeading('')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HeadcountHandover $record): View {
                                return view('components.headcount-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('submitted_at')
                    ->label('Date Submitted')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('salesperson_name')
                    ->label('Salesperson')
                    ->getStateUsing(function (HeadcountHandover $record) {
                        if ($record->lead && $record->lead->salesperson) {
                            $user = User::find($record->lead->salesperson);
                            return $user ? $user->name : 'N/A';
                        }
                        return 'N/A';
                    })
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: blue; font-weight: bold;">New</span>'),
                        default => new HtmlString('<span style="font-weight: bold;">' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading('')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HeadcountHandover $record): View {
                            return view('components.headcount-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalHeading(function (HeadcountHandover $record): string {
                            $formattedId = 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            return "Complete Headcount Handover {$formattedId}";
                        })
                        ->modalSubmitActionLabel('Mark as Completed')
                        ->modalWidth(MaxWidth::ThreeExtraLarge)
                        ->form([
                            Section::make('Upload Invoice')
                                ->description('Upload the invoice file for this completed headcount handover')
                                ->schema([
                                    FileUpload::make('invoice_file')
                                        ->label('Invoice File')
                                        ->required()
                                        ->disk('public')
                                        ->directory('handovers/headcount/invoices')
                                        ->visibility('public')
                                        ->maxFiles(1)
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->helperText('Upload the invoice file (PDF, JPG, PNG)')
                                        ->openable()
                                        ->downloadable()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                            // Get the current record from the action context
                                            $record = $this->mountedTableActionRecord;

                                            // Ensure we have a proper record object
                                            if (is_numeric($record)) {
                                                $record = HeadcountHandover::find($record);
                                            }

                                            if (!$record instanceof HeadcountHandover) {
                                                throw new \Exception('Unable to determine headcount handover record');
                                            }

                                            $leadId = $record->lead_id;
                                            $handoverId = $record->id;
                                            $formattedLeadId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                            $formattedHandoverId = str_pad($handoverId, 3, '0', STR_PAD_LEFT);
                                            $extension = $file->getClientOriginalExtension();
                                            $timestamp = now()->format('YmdHis');

                                            return "{$formattedLeadId}-HC{$formattedHandoverId}-INVOICE-{$timestamp}.{$extension}";
                                        }),
                                ]),
                        ])
                        ->action(function (HeadcountHandover $record, array $data): void {
                            try {
                                // Update the record
                                $record->update([
                                    'status' => 'Completed',
                                    'completed_by' => auth()->id(),
                                    'completed_at' => now(),
                                    'invoice_file' => $data['invoice_file'] ?? null,
                                ]);

                                // Get necessary data for email
                                $handoverId = 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
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
                                        Mail::send('emails.headcount-handover-completed', [
                                            'handoverId' => $handoverId,
                                            'companyName' => $companyName,
                                            'salesperson' => $salesperson,
                                            'completedBy' => $completedBy,
                                            'completedAt' => now(),
                                            'invoiceFile' => $data['invoice_file'] ?? null,
                                            'record' => $record
                                        ], function ($mail) use ($salesperson, $completedBy, $handoverId, $data) {
                                            $mail->to($salesperson->email, $salesperson->name)
                                                ->subject("HEADCOUNT HANDOVER | {$handoverId} | COMPLETED");

                                            // Attach invoice if uploaded
                                            if (!empty($data['invoice_file'])) {
                                                $invoiceFile = $data['invoice_file'];
                                                if (is_array($invoiceFile)) {
                                                    $invoiceFile = $invoiceFile[0];
                                                }
                                                $filePath = storage_path('app/public/' . $invoiceFile);
                                                if (file_exists($filePath)) {
                                                    $mail->attach($filePath);
                                                }
                                            }
                                        });

                                        \Illuminate\Support\Facades\Log::info("Headcount handover completion email sent", [
                                            'handover_id' => $handoverId,
                                            'salesperson_email' => $salesperson->email,
                                            'completed_by' => $completedBy->email
                                        ]);

                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error("Failed to send headcount handover completion email", [
                                            'error' => $e->getMessage(),
                                            'handover_id' => $handoverId
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->title('Headcount Handover Completed')
                                    ->body("Headcount handover {$handoverId} has been marked as completed and notification sent to salesperson.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to complete headcount handover: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->modalHeading(function (HeadcountHandover $record): string {
                            $formattedId = 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            return "Reject Headcount Handover {$formattedId}";
                        })
                        ->modalSubmitActionLabel('Reject Handover')
                        ->form([
                            Section::make('Rejection Reason')
                                ->description('Please provide a reason for rejecting this headcount handover')
                                ->schema([
                                    Textarea::make('reject_reason')
                                        ->label('Rejection Reason')
                                        ->required()
                                        ->placeholder('Enter the reason for rejection...')
                                        ->rows(4)
                                        ->maxLength(1000)
                                        ->helperText('This reason will be visible to the salesperson'),
                                ]),
                        ])
                        ->action(function (HeadcountHandover $record, array $data): void {
                            $record->update([
                                'status' => 'Rejected',
                                'rejected_by' => auth()->id(),
                                'rejected_at' => now(),
                                'reject_reason' => $data['reject_reason'],
                            ]);

                            $handoverId = 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                            Notification::make()
                                ->title('Headcount Handover Rejected')
                                ->body("Headcount handover {$handoverId} has been rejected.")
                                ->warning()
                                ->send();
                        }),
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->label('Actions')
                ->color('primary')
                ->button(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('300s');
    }

    public function render()
    {
        return view('livewire.admin-headcount-dashboard.headcount-new-table');
    }
}
