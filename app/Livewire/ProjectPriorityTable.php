<?php
namespace App\Livewire;

use App\Models\SoftwareHandover;
use App\Models\User;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use App\Classes\Encryptor;
use Filament\Notifications\Notification;

class ProjectPriorityTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedImplementer;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
        $this->selectedImplementer = session('selectedImplementer', null);
    }

    #[On('updateProjectTable')]
    public function updateProjectTable($implementer)
    {
        $this->selectedImplementer = $implementer === "" ? null : $implementer;

        session(['selectedImplementer' => $this->selectedImplementer]);

        $this->resetTable();
    }

    public function getFilteredProjectsQuery(): Builder
    {
        $this->selectedImplementer = $this->selectedImplementer ?? session('selectedImplementer', null);

        $query = SoftwareHandover::query()
            ->where('status_handover', '!=', 'Closed')
            ->where('status_handover', '!=', 'InActive');

        if ($this->selectedImplementer) {
            $query->where('implementer', $this->selectedImplementer);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->query($this->getFilteredProjectsQuery())
            ->defaultSort('project_priority')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50, 100, 'all'])
            ->filters([
                SelectFilter::make('project_priority')
                    ->label('Project Priority')
                    ->multiple()
                    ->options([
                        'High' => 'High',
                        'Medium' => 'Medium',
                        'Low' => 'Low',
                    ]),

                SelectFilter::make('status_handover')
                    ->label('Project Status')
                    ->multiple()
                    ->options([
                        'Open' => 'Open',
                        'Delay' => 'Delay',
                    ]),

                SelectFilter::make('implementer')
                    ->label('Implementer')
                    ->multiple()
                    ->options(function () {
                        return User::whereIn('role_id', [4, 5])
                            ->pluck('name', 'name')
                            ->toArray();
                    }),
                SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->multiple()
                    ->options(function () {
                        return User::where('role_id', 2)
                            ->pluck('name', 'name')
                            ->toArray();
                    }),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        if ($record->handover_pdf) {
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $shortened = strtoupper(Str::limit($state, 25, '...'));
                        $encryptedId = $record->lead_id ? Encryptor::encrypt($record->lead_id) : null;

                        if ($encryptedId) {
                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                target="_blank"
                                title="' . e($state) . '"
                                class="inline-block"
                                style="color:#338cf0;">
                                ' . $shortened . '
                            </a>');
                        }

                        return $shortened;
                    })
                    ->html(),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('project_priority')
                    ->label('Project Priority')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'High' => 'danger',
                        'Medium' => 'warning',
                        'Low' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('status_handover')
                    ->label('Project Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Open' => 'info',
                        'Delay' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_project')
                        ->label('View Project')
                        ->icon('heroicon-o-eye')
                        ->url(fn (SoftwareHandover $record) => $record->lead_id ? url('admin/leads/' . Encryptor::encrypt($record->lead_id)) : null)
                        ->openUrlInNewTab(),

                    Action::make('update_priority')
                        ->label('Update Priority')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->form([
                            Select::make('project_priority')
                                ->label('Project Priority')
                                ->options([
                                    'High' => 'High',
                                    'Medium' => 'Medium',
                                    'Low' => 'Low',
                                ])
                                ->required(),
                        ])
                        ->action(function (SoftwareHandover $record, array $data) {
                            // Check permissions: allow if user is admin (1), manager (3), senior implementer (5)
                            // or if they are the assigned implementer
                            $currentUser = auth()->user();
                            $canUpdate = in_array($currentUser->role_id, [3, 5]) ||
                                        ($currentUser->name === $record->implementer);

                            if (!$canUpdate) {
                                Notification::make()
                                    ->title('Permission Denied')
                                    ->body('You can only update your own projects.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $record->update([
                                'project_priority' => $data['project_priority'],
                            ]);

                            Notification::make()
                                ->title('Project Updated')
                                ->body('Project priority and status have been updated successfully.')
                                ->success()
                                ->send();

                            // Dispatch event to refresh the project counts
                            $this->dispatch('refresh-project-counts');
                        })
                        // Update the visibility condition for the update button
                        ->visible(fn (SoftwareHandover $record) =>
                            in_array(auth()->user()->role_id, [3, 5]) ||
                            auth()->user()->name === $record->implementer
                        ),
                ])
                ->button()
                ->label('Actions')
                ->color('warning'),
            ])
            ->bulkActions([
                BulkAction::make('update_bulk_priority')
                    ->label('Update Priority')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Select::make('project_priority')
                            ->label('Project Priority')
                            ->options([
                                'High' => 'High',
                                'Medium' => 'Medium',
                                'Low' => 'Low',
                            ])
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data) {
                        $currentUser = auth()->user();
                        $isPrivilegedUser = in_array($currentUser->role_id, [3, 5]);
                        $updatedCount = 0;
                        $skippedCount = 0;

                        foreach ($records as $record) {
                            // Check permissions - privileged users can update all, others only their own
                            if ($isPrivilegedUser || $currentUser->name == $record->implementer) {
                                $record->update([
                                    'project_priority' => $data['project_priority'],
                                ]);
                                $updatedCount++;
                            } else {
                                $skippedCount++;
                            }
                        }

                        $message = "Updated $updatedCount projects successfully.";
                        if ($skippedCount > 0) {
                            $message .= " Skipped $skippedCount projects due to permission restrictions.";
                        }

                        Notification::make()
                            ->title('Bulk Update Complete')
                            ->body($message)
                            ->success()
                            ->send();

                        // Dispatch event to refresh the project counts
                        $this->dispatch('refresh-project-counts');
                    })
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn () => auth()->check()),
            ]);
    }

    public function render()
    {
        return view('livewire.project-priority-table');
    }
}
