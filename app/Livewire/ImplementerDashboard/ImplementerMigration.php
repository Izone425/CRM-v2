<?php

namespace App\Livewire\ImplementerDashboard;

use App\Filament\Filters\SortFilter;
use App\Models\CompanyDetail;
use App\Models\HardwareHandover;
use App\Models\SoftwareHandover;
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
use Livewire\Attributes\On;

class ImplementerMigration extends Component implements HasForms, HasTable
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

    #[On('refresh-implementer-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]); // Store selected user
        } else {
            // Reset to "Your Own Dashboard" (value = 7)
            $this->selectedUser = 7;
            session(['selectedUser' => 7]);
        }

        $this->resetTable(); // Refresh the table
    }

    public function getOverdueHardwareHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        $query =  SoftwareHandover::query()
            ->whereIn('status', ['Completed'])
            // ->whereIn('status_handover', ['Open','Delay'])
            ->where('data_migrated', false)
            ->where('id', '>=', 561)
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);

        if ($this->selectedUser === 'all-implementer') {

        }
        elseif (is_numeric($this->selectedUser)) {
            $user = User::find($this->selectedUser);

            if ($user && ($user->role_id === 4 || $user->role_id === 5)) {
                $query->where('implementer', $user->name);
            }
        }
        else {
            $currentUser = auth()->user();

            if ($currentUser->role_id === 4) {
                $query->where('implementer', $currentUser->name);
            }
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
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

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', [4,5])
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),

                SelectFilter::make('invoice_type')
                    ->label('Filter by Invoice Type')
                    ->options([
                        'single' => 'Single Invoice',
                        'combined' => 'Combined Invoice',
                    ])
                    ->placeholder('All Invoice Types')
                    ->multiple()
                    ->query(function ($query, array $data) {
                        if (!empty($data['values'])) {
                            $isSingleSelected = in_array('single', $data['values']);
                            $isCombinedSelected = in_array('combined', $data['values']);

                            // Get IDs from hardware handovers that match the selected invoice types
                            $matchingIds = \App\Models\HardwareHandoverV2::whereIn('invoice_type', $data['values'])
                                ->get()
                                ->filter(function ($hw) {
                                    if (!$hw->related_software_handovers) {
                                        return false;
                                    }

                                    $relatedIds = is_string($hw->related_software_handovers)
                                        ? json_decode($hw->related_software_handovers, true)
                                        : $hw->related_software_handovers;

                                    return is_array($relatedIds) && !empty($relatedIds);
                                })
                                ->flatMap(function ($hw) {
                                    $relatedIds = is_string($hw->related_software_handovers)
                                        ? json_decode($hw->related_software_handovers, true)
                                        : $hw->related_software_handovers;

                                    return is_array($relatedIds) ? $relatedIds : [];
                                })
                                ->unique()
                                ->filter()
                                ->values()
                                ->toArray();

                            // Get all software handover IDs that are NOT in any hardware handover's related_software_handovers
                            $allRelatedIds = \App\Models\HardwareHandoverV2::whereNotNull('related_software_handovers')
                                ->get()
                                ->flatMap(function ($hw) {
                                    $relatedIds = is_string($hw->related_software_handovers)
                                        ? json_decode($hw->related_software_handovers, true)
                                        : $hw->related_software_handovers;

                                    return is_array($relatedIds) ? $relatedIds : [];
                                })
                                ->unique()
                                ->toArray();

                            // If 'single' is selected, include records not in any hardware handover
                            if ($isSingleSelected && !empty($matchingIds)) {
                                $query->where(function ($q) use ($matchingIds, $allRelatedIds) {
                                    $q->whereIn('software_handovers.id', $matchingIds)
                                    ->orWhereNotIn('software_handovers.id', $allRelatedIds);
                                });
                            } elseif ($isSingleSelected) {
                                // Only 'single' selected but no matching IDs, show records not in any hardware handover
                                $query->whereNotIn('software_handovers.id', $allRelatedIds);
                            } elseif (!empty($matchingIds)) {
                                // Only 'combined' selected or both selected with matches
                                $query->whereIn('software_handovers.id', $matchingIds);
                            } else {
                                // No matches found, return empty result
                                $query->whereRaw('1 = 0');
                            }
                        }
                    }),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }


                        return $record->formatted_handover_id;
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->visible(fn(): bool => auth()->user()->role_id !== 4),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $company->company_name . '
                                </a>');
                        }

                        $shortened = strtoupper(Str::limit($state, 20, '...'));
                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('invoice_type')
                    ->label('Invoice Type')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        // Get all hardware handovers and check if this software handover ID is in related_software_handovers
                        $hardwareHandover = \App\Models\HardwareHandoverV2::get()
                            ->first(function ($hw) use ($record) {
                                if (!$hw->related_software_handovers) {
                                    return false;
                                }

                                $relatedIds = is_string($hw->related_software_handovers)
                                    ? json_decode($hw->related_software_handovers, true)
                                    : $hw->related_software_handovers;

                                return is_array($relatedIds) && in_array((string)$record->id, $relatedIds);
                            });

                        // If no hardware handover found or no invoice type, default to 'single'
                        return $hardwareHandover?->invoice_type ?? 'single';
                    })
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'single' => 'Single',
                        'combined' => 'Combined',
                        default => 'Single'
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status_handover')
                    ->label('Status'),
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
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('mark_as_migrated')
                        ->label('Complete Migration')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading("Mark as Migration Completed")
                        ->modalDescription('Are you sure you want to mark this handover as migration completed? This will complete the software handover process and update the associated hardware handover.')
                        ->modalSubmitActionLabel('Yes, Mark as Migration Completed')
                        ->modalCancelActionLabel('No, Cancel')
                        ->action(function (SoftwareHandover $record): void {
                            // Get the implementer info
                            $implementer = \App\Models\User::where('name', $record->implementer)->first();
                            $implementerEmail = $implementer?->email ?? null;

                            // Get the salesperson info
                            $salespersonId = $record->lead->salesperson ?? null;
                            $salesperson = \App\Models\User::find($salespersonId);
                            $salespersonEmail = $salesperson?->email ?? null;
                            $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                            // Get the company name
                            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                            // Update the software handover record
                            $record->update([
                                // 'completed_at' => now(),
                                'data_migrated' => true
                            ]);

                            // Update the associated hardware handover
                            if ($record->lead_id) {
                                // Find the latest hardware handover for the same lead
                                $hardwareHandover = HardwareHandover::where('lead_id', $record->lead_id)
                                    ->where('status', 'Pending Migration')
                                    ->orderBy('created_at', 'desc')
                                    ->first();

                                // If found, update its status to 'Completed'
                                if ($hardwareHandover) {
                                    $hardwareHandover->update([
                                        'status' => 'Completed Migration',
                                        // 'completed_at' => now(),
                                        'updated_at' => now(),
                                    ]);

                                    // Log the hardware handover update
                                    \Illuminate\Support\Facades\Log::info("Hardware handover #{$hardwareHandover->id} marked as Completed from software handover migration completion", [
                                        'software_handover_id' => $record->id,
                                        'lead_id' => $record->lead_id,
                                        'hardware_handover_id' => $hardwareHandover->id,
                                        'updated_by' => auth()->user()->name,
                                    ]);

                                    // Show additional success notification for hardware handover
                                    Notification::make()
                                        ->title("Hardware handover #{$hardwareHandover->id} updated")
                                        ->success()
                                        ->body("The associated hardware handover has been marked as completed.")
                                        ->send();

                                    // Emit event to refresh hardware handover tables
                                    $this->dispatch('refresh-hardwarehandover-tables');
                                }
                            }

                            // Format the handover ID properly
                            $handoverId = $record->formatted_handover_id;

                            // Get the handover PDF URL
                            $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

                            // Send email notification
                            try {
                                $viewName = 'emails.implementer_migrated';

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

                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $authUser->email;
                                $senderName = $authUser->name;

                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $handoverId, $companyName) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("COMPLETED USER DATA MIGRATION | TIMETEC HR | {$companyName}");
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
        return view('livewire.implementer_dashboard.implementer-migration');
    }
}
