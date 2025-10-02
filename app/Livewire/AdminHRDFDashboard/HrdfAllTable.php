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
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

class HrdfAllTable extends Component implements HasForms, HasTable
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
                    ->weight('bold'),

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
                        'Completed' => new HtmlString('<span style="color: green;">Completed</span>'), // Changed from 'Approved'
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
                        ->modalHeading('HRDF Handover Details')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HRDFHandover $record): View {
                            return view('components.hrdf-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('complete')  // Changed from 'approve'
                        ->label('Mark as Completed')  // Changed label
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(): bool => auth()->user()->role_id === 3) // Only managers can complete
                        ->requiresConfirmation()
                        ->action(function (HRDFHandover $record): void {
                            $record->update([
                                'status' => 'Completed',  // Changed from 'Approved'
                                'completed_by' => auth()->id(),  // Changed from 'approved_by'
                                'completed_at' => now(),  // Changed from 'approved_at'
                            ]);

                            Notification::make()
                                ->title('HRDF handover marked as completed successfully')  // Updated message
                                ->success()
                                ->send();
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
                                // Removed the uppercase transformations since the model handles it now
                        ])
                        ->action(function (HRDFHandover $record, array $data): void {
                            $record->update([
                                'status' => 'Rejected',
                                'reject_reason' => $data['reject_reason'],
                                'rejected_by' => auth()->id(),
                                'rejected_at' => now(),
                            ]);

                            Notification::make()
                                ->title('HRDF handover rejected')
                                ->success()
                                ->send();
                        }),
                ])->button()
                ->label('Actions')
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.admin-hrdf-dashboard.hrdf-all-table');
    }
}
