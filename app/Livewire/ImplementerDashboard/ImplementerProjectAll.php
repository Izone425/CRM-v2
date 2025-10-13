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

class ImplementerProjectAll extends Component implements HasForms, HasTable
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

    public function getAllSoftwareHandover()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        $query =  SoftwareHandover::query()
            ->where('status', '=', 'Completed')
            ->orderBy('id', 'desc');

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
            ->query($this->getAllSoftwareHandover())
            ->defaultSort('created_at', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status_handover')
                    ->label('Filter by Status')
                    ->options([
                        'Open' => 'Open',
                        'Delay' => 'Delay',
                        'InActive' => 'InActive',
                        'Closed' => 'Closed',
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

                SortFilter::make("sort_by"),
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

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('md')
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
                TextColumn::make('payroll_code')
                    ->label('Payroll Code'),
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
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),
                    // Action::make('changeLead')
                    //     ->label('Change Lead ID')
                    //     ->icon('heroicon-o-link')
                    //     ->color('primary')
                    //     ->form([
                    //         \Filament\Forms\Components\Select::make('lead_id')
                    //             ->label('New Lead')
                    //             ->required()
                    //             ->searchable()
                    //             ->options(function () {
                    //                 // Get all leads with closed lead_status and their company names
                    //                 $options = DB::table('leads')
                    //                     ->join('company_details', 'leads.id', '=', 'company_details.lead_id')
                    //                     ->where('leads.lead_status', 'Closed')
                    //                     ->whereNotNull('company_details.company_name') // Ensure company name is not null
                    //                     ->orderBy('company_details.company_name')
                    //                     ->select([
                    //                         'leads.id',
                    //                         'company_details.company_name'
                    //                     ])
                    //                     ->get()
                    //                     ->mapWithKeys(function ($item) {
                    //                         // Create a safe label that's never null
                    //                         $label = $item->company_name ?
                    //                             $item->company_name . ' (ID: ' . $item->id . ')' :
                    //                             'Unknown Company (ID: ' . $item->id . ')';

                    //                         return [$item->id => $label];
                    //                     })
                    //                     ->toArray();

                    //                 // Filter out any potentially problematic entries
                    //                 return collect($options)->filter()->toArray();
                    //             })
                    //             ->placeholder('Search and select a closed lead')
                    //             ->helperText('Only closed leads are available for selection')
                    //     ])
                    //     ->modalHeading('Change Lead Association')
                    //     ->modalDescription('Select a new lead to associate with this software handover.')
                    //     ->requiresConfirmation()
                    //     ->action(function (array $data, SoftwareHandover $record): void {
                    //         try {
                    //             // Get the lead details (just for notification)
                    //             $company = CompanyDetail::where('lead_id', $data['lead_id'])->first();

                    //             if (!$company) {
                    //                 Notification::make()
                    //                     ->title('Error')
                    //                     ->body('The selected lead could not be found.')
                    //                     ->danger()
                    //                     ->send();
                    //                 return;
                    //             }

                    //             // Get the old lead ID for logging
                    //             $oldLeadId = $record->lead_id;

                    //             // Update ONLY the lead_id in the software handover
                    //             // Removed company_name update as requested
                    //             $record->update([
                    //                 'lead_id' => $data['lead_id'],
                    //                 // No company_name update here
                    //             ]);

                    //             // Log the change
                    //             \Illuminate\Support\Facades\Log::info('Software handover lead ID changed', [
                    //                 'handover_id' => $record->id,
                    //                 'old_lead_id' => $oldLeadId,
                    //                 'new_lead_id' => $data['lead_id'],
                    //                 'changed_by' => auth()->user()->name,
                    //             ]);

                    //             // Send notification
                    //             Notification::make()
                    //                 ->title('Lead ID Updated')
                    //                 ->body("Software handover lead ID changed from #{$oldLeadId} to #{$data['lead_id']}")
                    //                 ->success()
                    //                 ->send();

                    //             // Refresh the table
                    //             $this->refreshData();

                    //         } catch (\Exception $e) {
                    //             Notification::make()
                    //                 ->title('Error')
                    //                 ->body('Failed to update lead ID: ' . $e->getMessage())
                    //                 ->danger()
                    //                 ->send();
                    //         }
                    //     })
                    //     ->visible(fn () => auth()->user()->id === 26 || auth()->user()->role_id === 3),
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.implementer-project-all');
    }
}
