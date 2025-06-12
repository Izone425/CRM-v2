<?php

namespace App\Livewire;

use App\Filament\Filters\SortFilter;
use App\Models\HardwareHandover;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

class HardwareHandoverPendingStock extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getOverdueHardwareHandovers()
    {
        return HardwareHandover::query()
            ->whereIn('status', ['Pending Stock'])
            ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getOverdueHardwareHandovers())
            ->defaultSort('created_at', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id',15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('handover_pdf')
                    ->label('ID')
                    ->formatStateUsing(function ($state) {
                        // If handover_pdf is null, return a placeholder
                        if (!$state) {
                            return '-';
                        }

                        // Extract just the filename without extension
                        $filename = basename($state, '.pdf');

                        // Return just the formatted ID part
                        return $filename;
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('md')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandover $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 20, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $fullName . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            // ->filters([
            //     // Filter for Creator
            //     SelectFilter::make('created_by')
            //         ->label('Created By')
            //         ->multiple()
            //         ->options(User::pluck('name', 'id')->toArray())
            //         ->placeholder('Select User'),

            //     // Filter by Company Name
            //     SelectFilter::make('company_name')
            //         ->label('Company')
            //         ->searchable()
            //         ->options(HardwareHandover::distinct()->pluck('company_name', 'company_name')->toArray())
            //         ->placeholder('Select Company'),
            // ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(' ')
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (HardwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.hardware-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('mark_as_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading("Mark as Completed")
                        ->modalDescription('Are you sure you want to mark this handover as completed? This will complete the software handover process.')
                        ->modalSubmitActionLabel('Yes, Mark as Completed')
                        ->modalCancelActionLabel('No, Cancel')
                        ->action(function (HardwareHandover $record): void {
                            // Get the implementer info
                            $implementerId = $record->implementer;
                            $implementer = \App\Models\User::find($implementerId);
                            $implementerEmail = $implementer?->email ?? null;
                            $implementerName = $implementer?->name ?? 'Unknown';

                            // Get the salesperson info
                            $salespersonId = $record->lead->salesperson ?? null;
                            $salesperson = \App\Models\User::find($salespersonId);
                            $salespersonEmail = $salesperson?->email ?? null;
                            $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                            // Get the company name
                            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                            // Update the record
                            $record->update([
                                'completed_at' => now(),
                                'status' => 'Completed'
                            ]);

                            // Format the handover ID properly
                            $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                            // Get the handover PDF URL
                            $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

                            $invoiceFiles = [];
                            if ($record->invoice_file) {
                                $invoiceFileArray = is_string($record->invoice_file)
                                    ? json_decode($record->invoice_file, true)
                                    : $record->invoice_file;

                                if (is_array($invoiceFileArray)) {
                                    foreach ($invoiceFileArray as $file) {
                                        $invoiceFiles[] = url('storage/' . $file);
                                    }
                                }
                            }

                            $salesOrderFiles = [];
                            if ($record->sales_order_file) {
                                $salesOrderFileArray = is_string($record->sales_order_file)
                                    ? json_decode($record->sales_order_file, true)
                                    : $record->sales_order_file;

                                if (is_array($salesOrderFileArray)) {
                                    foreach ($salesOrderFileArray as $file) {
                                        $salesOrderFiles[] = url('storage/' . $file);
                                    }
                                }
                            }

                            // Send email notification
                            try {
                                $viewName = 'emails.hardware_completed_notification';

                                // Create email content structure
                                $emailContent = [
                                    'implementer' => [
                                        'name' => $record->implementer,
                                    ],
                                    'company' => [
                                        'name' => $companyName,
                                    ],
                                    'salesperson' => [
                                        'name' => $salespersonName,
                                    ],
                                    'handover_id' => $handoverId,
                                    'activatedAt' => now()->format('d M Y'),
                                    'handoverFormUrl' => $handoverFormUrl,
                                    'invoiceFiles' => $invoiceFiles,
                                    'salesOrderFiles' => $salesOrderFiles,
                                    'devices' => [
                                        'tc10' => [
                                            'quantity' => $record->tc10_quantity,
                                            'status' => $record->tc10_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'tc20' => [
                                            'quantity' => $record->tc20_quantity,
                                            'status' => $record->tc20_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'face_id5' => [
                                            'quantity' => $record->face_id5_quantity,
                                            'status' => $record->face_id5_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'face_id6' => [
                                            'quantity' => $record->face_id6_quantity,
                                            'status' => $record->face_id6_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'time_beacon' => [
                                            'quantity' => $record->time_beacon_quantity,
                                            'status' => $record->time_beacon_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'nfc_tag' => [
                                            'quantity' => $record->nfc_tag_quantity,
                                            'status' => $record->nfc_tag_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ]
                                    ]
                                ];

                                // Initialize recipients array
                                $recipients = [];

                                // Add implementer email if valid
                                if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $implementerEmail;
                                }

                                // Add salesperson email if valid
                                if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $salespersonEmail;
                                }

                                // Always include admin
                                // $recipients[] = 'admin.timetec.hr@timeteccloud.com';

                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $authUser->email;
                                $senderName = $authUser->name;

                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $handoverId, $companyName) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("HARDWARE HANDOVER ID {$handoverId} | {$companyName}");
                                    });

                                    \Illuminate\Support\Facades\Log::info("License activation email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                }
                            } catch (\Exception $e) {
                                // Log error but don't stop the process
                                \Illuminate\Support\Facades\Log::error("Email sending failed for software handover #{$record->id}: {$e->getMessage()}");
                            }

                            Notification::make()
                                ->title('License has been activated successfully')
                                ->success()
                                ->body('Software handover has been marked as completed.')
                                ->send();
                        })
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.hardware-handover-pending-stock');
    }
}
