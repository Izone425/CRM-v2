<?php

namespace App\Livewire;

use App\Filament\Filters\SortFilter;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\User;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Builder as DatabaseQueryBuilder;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;

class SoftwareHandoverCompleted extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getNewSoftwareHandovers()
    {
        $query = SoftwareHandover::query();
        $query->whereIn('status', ['Completed']);

        // Apply normal salesperson filtering for other roles
        if ($this->selectedUser === 'all-salespersons') {
            // Keep as is - show all salespersons' handovers
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            // Validate that the selected user exists and is a salesperson
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

            if ($userExists) {
                $selectedUser = $this->selectedUser; // Create a local variable
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                // Invalid user ID or not a salesperson, fall back to default
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id());
                });
            }
        } else {
            if (auth()->user()->role_id === 2) {
                // Salespersons (role_id 2) can see Draft, New, Approved, and Completed
                $query->whereIn('status', ['Completed']);

                // But only THEIR OWN records
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            } else {
                // Other users (admin, managers) can only see New, Approved, and Completed
                $query->whereIn('status', ['Completed']);
                // But they can see ALL records
            }
        }

        $query->orderByRaw("CASE
            WHEN status = 'New' THEN 1
            WHEN status = 'Approved' THEN 2
            WHEN status = 'Completed' THEN 3
            ELSE 4
        END")
            ->orderBy('updated_at', 'desc');

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getNewSoftwareHandovers())
            // ->defaultSort('updated_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
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
                            ->whereNot('id', 15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
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
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                // TextColumn::make('lead.salesperson')
                //     ->label('SALESPERSON')
                //     ->getStateUsing(function (SoftwareHandover $record) {
                //         $lead = $record->lead;
                //         if (!$lead) {
                //             return '-';
                //         }

                //         $salespersonId = $lead->salesperson;
                //         return User::find($salespersonId)?->name ?? '-';
                //     })
                //     ->visible(fn(): bool => auth()->user()->role_id !== 2),

                // TextColumn::make('lead.companyDetail.company_name')
                //     ->label('Company Name')
                //     ->formatStateUsing(function ($state, $record) {
                //         $fullName = $state ?? 'N/A';
                //         $shortened = strtoupper(Str::limit($fullName, 20, '...'));
                //         $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                //         return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                //                     target="_blank"
                //                     title="' . e($fullName) . '"
                //                     class="inline-block"
                //                     style="color:#338cf0;">
                //                     ' . $shortened . '
                //                 </a>';
                //     })
                //     ->html(),

                TextColumn::make('salesperson')
                    ->label('SALESPERSON')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

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

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),

                // TextColumn::make('submitted_at')
                //     ->label('Date Submit')
                //     ->date('d M Y'),

                // TextColumn::make('kik_off_meeting_date')
                //     ->label('Kick Off Meeting Date')
                //     ->formatStateUsing(function ($state) {
                //         return $state ? Carbon::parse($state)->format('d M Y') : 'N/A';
                //     })
                //     ->date('d M Y'),

                // TextColumn::make('training_date')
                //     ->label('Training Date')
                //     ->formatStateUsing(function ($state) {
                //         return $state ? Carbon::parse($state)->format('d M Y') : 'N/A';
                //     })
                //     ->date('d M Y'),

                // TextColumn::make('training_date')
                //     ->label('Implementer')
                //     ->formatStateUsing(function ($state) {
                //         return $state ? Carbon::parse($state)->format('d M Y') : 'N/A';
                //     })
                //     ->date('d M Y'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(' ')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->visible(fn(SoftwareHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Approved']))
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),
                    Action::make('mark_approved')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (SoftwareHandover $record): void {
                            $record->update(['status' => 'Approved']);

                            Notification::make()
                                ->title('Software Handover marked as approved')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->hidden(
                            fn(SoftwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        ),
                    Action::make('mark_rejected')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->hidden(
                            fn(SoftwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        )
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reject_reason')
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->label('Reason for Rejection')
                                ->required()
                                ->placeholder('Please provide a reason for rejecting this handover')
                                ->maxLength(500)
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Update both status and add the rejection remarks
                            $record->update([
                                'status' => 'Rejected',
                                'reject_reason' => $data['reject_reason']
                            ]);

                            Notification::make()
                                ->title('Hardware Handover marked as rejected')
                                ->body('Rejection reason: ' . $data['reject_reason'])
                                ->danger()
                                ->send();
                        })
                        ->requiresConfirmation(false),
                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-badge') // Using check badge icon to distinguish from regular approval
                        ->color('success') // Using success color for completion
                        ->action(function (SoftwareHandover $record): void {
                            $record->update(['status' => 'Completed']);

                            Notification::make()
                                ->title('Software Handover marked as completed')
                                ->body('This handover has been marked as completed.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->hidden(
                            fn(SoftwareHandover $record): bool =>
                            $record->status !== 'Approved' || auth()->user()->role_id === 2
                        ),
                ])->button()
                    ->color('warning')
            ]);
    }

    public function render()
    {
        return view('livewire.software-handover-completed');
    }
}
