<?php

namespace App\Livewire\ImplementerDashboard;

use App\Filament\Filters\SortFilter;
use App\Models\CompanyDetail;
use App\Models\HardwareHandover;
use App\Models\ImplementerNote;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
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

class ImplementerProjectOpen extends Component implements HasForms, HasTable
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

        $query = SoftwareHandover::query()
            ->where('status_handover', '=', 'Open')
            // Add a subquery to count the review sessions
            ->select('software_handovers.*', DB::raw('(
                SELECT COUNT(*)
                FROM implementer_appointments
                WHERE implementer_appointments.software_handover_id = software_handovers.id
                AND implementer_appointments.type = "REVIEW SESSION"
                AND implementer_appointments.status != "Cancelled"
            ) as review_session_count'))
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
                            ->modalHeading(' ')
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

                        // Get priority and set background color with inline styles
                        $priority = $record->project_priority ?? 'low'; // Default to low if not set
                        $priorityStyles = match(strtolower($priority)) {
                            'high' => 'border: 1px solid rgba(220, 38, 38, 0.3); background-color: #ffeeee;',
                            'medium' => 'border: 1px solid rgba(217, 119, 6, 0.3); background-color: #fff9f0;',
                            'low' => 'border: 1px solid rgba(16, 185, 129, 0.3); background-color: #f2fff0;',
                            default => 'border: 1px solid rgba(16, 185, 129, 0.3); background-color: #f2fff0;'
                        };

                        // Get dot color based on priority
                        $dotColor = match(strtolower($priority)) {
                            'high' => 'rgb(220, 38, 38)',
                            'medium' => 'rgb(217, 119, 6)',
                            'low' => 'rgb(16, 185, 129)',
                            default => 'rgb(16, 185, 129)'
                        };

                        if ($company) {
                            $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<div style="padding: 8px; border-radius: 4px; ' . $priorityStyles . '">
                                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: ' . $dotColor . '; margin-right: 8px; vertical-align: middle;"></span>
                                <a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $company->company_name . '
                                </a>
                            </div>');
                        }

                        $shortened = strtoupper(Str::limit($state, 20, '...'));
                        return new HtmlString('<div style="padding: 8px; border-radius: 4px; ' . $priorityStyles . '">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: ' . $dotColor . '; margin-right: 8px; vertical-align: middle;"></span>
                            <span title="' . $state . '">' . $state . '</span>
                        </div>');
                    })
                    ->html(),

                TextColumn::make('status_handover')
                    ->label('Status'),

                TextColumn::make('review_session_count')
                    ->label('Review Sessions')
                    ->formatStateUsing(function ($state) {
                        // If count is zero, display dash or zero
                        if ($state == 0) {
                            return '-';
                        }

                        // Return the count as a badge with styling
                        return $state;
                    })
                    ->alignCenter()
                    ->html()
            ])
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
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),
                    Action::make('add_note')
                        ->label('Add Note')
                        ->color('primary')
                        ->icon('heroicon-o-plus')
                        ->form([
                            RichEditor::make('notes')
                                ->label('New Note')
                                ->disableToolbarButtons([
                                    'attachFiles',
                                    'blockquote',
                                    'codeBlock',
                                    'h2',
                                    'h3',
                                    'link',
                                    'redo',
                                    'strike',
                                    'undo',
                                ])
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->placeholder('Add your note here...')
                                ->required()
                        ])
                        ->modalHeading('Add New Note')
                        ->action(function (SoftwareHandover $record, array $data) {
                            // Create a new implementer note
                            ImplementerNote::create([
                                'lead_id' => $record->lead_id,
                                'user_id' => auth()->id(),
                                'content' => $data['notes'],
                            ]);

                            Notification::make()
                                ->title('Note added successfully')
                                ->success()
                                ->send();
                        }),
                    Action::make('webinar_training')
                        ->label('Webinar Training')
                        ->icon('heroicon-o-plus')
                        ->color('info')
                        ->visible(function (SoftwareHandover $record) {
                            // Only show if webinar_training is null (not scheduled yet)
                            return is_null($record->webinar_training);
                        })
                        ->form([
                            DatePicker::make('webinar_training_date')
                                ->label('Webinar Training Date')
                                ->required()
                                ->native(false)
                                ->displayFormat('d M Y')
                                ->default(now())
                                ->helperText('Select the date for the online webinar training.')
                                ->closeOnDateSelection(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading(function (SoftwareHandover $record) {
                            // Get company name from the software handover record
                            $companyName = $record->company_name ?? 'Unknown Company';

                            // If company_name is not available in SoftwareHandover, try to get it from Lead
                            if (empty($companyName) || $companyName === 'Unknown Company') {
                                if ($record->lead_id) {
                                    $lead = \App\Models\Lead::find($record->lead_id);
                                    if ($lead && $lead->companyDetail) {
                                        $companyName = $lead->companyDetail->company_name ?? 'Unknown Company';
                                    }
                                }
                            }

                            return "Schedule Webinar Training for {$companyName}";
                        })
                        ->modalDescription('Are you sure you want to schedule webinar training for this project?')
                        ->modalSubmitActionLabel('Yes, Schedule Training')
                        ->action(function (array $data, SoftwareHandover $record) {
                            $record->webinar_training = $data['webinar_training_date'];
                            $record->save();

                            // Send notification
                            \Filament\Notifications\Notification::make()
                                ->title('Webinar Training Scheduled')
                                ->body('Training scheduled for ' . \Carbon\Carbon::parse($data['webinar_training_date'])->format('d M Y'))
                                ->success()
                                ->send();

                            // Refresh the table
                            $this->refreshTable();
                        }),
                    Action::make('mark_as_closed')
                        ->label('Mark as Closed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(function () {
                            $user = auth()->user();
                            return $user->role_id === 3 || $user->id === 26;
                        })
                        ->form([
                            DatePicker::make('go_live_date')
                                ->label('Go Live Date')
                                ->required()
                                ->native(false)
                                ->displayFormat('d M Y')
                                ->default(now())
                                ->helperText('Select the date when the project went live.')
                                ->closeOnDateSelection(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Mark Project as Closed')
                        ->modalDescription('Are you sure you want to mark this project as closed? This will update the status and set the go-live date.')
                        ->modalSubmitActionLabel('Yes, Close Project')
                        ->action(function (array $data, SoftwareHandover $record) {
                            $record->status_handover = 'Closed';
                            $record->go_live_date = $data['go_live_date'];
                            $record->save();

                            // Send notification
                            \Filament\Notifications\Notification::make()
                                ->title('Project marked as Closed')
                                ->success()
                                ->send();

                            // Refresh the table
                            $this->refreshTable();
                        }),

                    Action::make('mark_as_inactive')
                        ->label('Mark as InActive')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\Textarea::make('inactive_reason')
                                ->label('Inactive Reason')
                                ->placeholder('Please explain why this project is inactive')
                                ->rows(3)
                                ->maxLength(500)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->required()
                        ])
                        ->modalHeading(function (SoftwareHandover $record) {
                            // Get company name from the software handover record
                            $companyName = $record->company_name ?? 'Unknown Company';

                            // If company_name is not available in SoftwareHandover, try to get it from Lead
                            if (empty($companyName) || $companyName === 'Unknown Company') {
                                if ($record->lead_id) {
                                    $lead = \App\Models\Lead::find($record->lead_id);
                                    if ($lead && $lead->companyDetail) {
                                        $companyName = $lead->companyDetail->company_name ?? 'Unknown Company';
                                    }
                                }
                            }

                            return "Mark Project: {$companyName} as Inactive";
                        })
                        ->modalDescription('Are you sure you want to mark this project as inactive?')
                        ->modalSubmitActionLabel('Yes, Mark as Inactive')
                        ->action(function (SoftwareHandover $record, array $data) {
                            $record->status_handover = 'InActive';
                            $record->inactive_reason = $data['inactive_reason'];
                            $record->save();

                            // Send notification
                            \Filament\Notifications\Notification::make()
                                ->title('Project marked as InActive')
                                ->warning()
                                ->send();

                            // Refresh the table
                            $this->refreshTable();
                        }),
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.implementer-project-open')
            ->with('styles', '
                <style>
                    .priority-box-high {
                        border: 1px solid rgba(220, 38, 38, 0.3);
                        background-color: #ffeeee;
                    }
                    .priority-box-medium {
                        border: 1px solid rgba(217, 119, 6, 0.3);
                        background-color: #fff9f0;
                    }
                    .priority-box-low {
                        border: 1px solid rgba(16, 185, 129, 0.3);
                        background-color: #f2fff0;
                    }
                </style>
            ');
    }
}
