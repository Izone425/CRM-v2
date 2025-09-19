<?php

namespace App\Livewire\AdminRenewalDashboard;

use App\Filament\Actions\AdminRenewalActions;
use App\Filament\Filters\SortFilter;
use App\Models\CompanyDetail;
use App\Models\AdminRenewalLogs;
use App\Models\Renewal;
use App\Models\Lead;
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
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;

class ArFollowUpOverdue extends Component implements HasForms, HasTable
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

    #[On('refresh-admin-renewal-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]);
        } else {
            $this->selectedUser = auth()->id();
            session(['selectedUser' => auth()->id()]);
        }

        $this->resetTable();
    }

    public function getOverdueRenewals()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        $query = Renewal::query()
            ->whereDate('follow_up_date', '<', today())
            ->where('follow_up_counter', true)
            ->orderBy('created_at', 'asc')
            ->selectRaw('*, DATEDIFF(NOW(), follow_up_date) as pending_days');

        // if ($this->selectedUser === 'all-admin-renewal') {
        //     // Show all admin renewals
        // } elseif (is_numeric($this->selectedUser)) {
        //     $user = User::find($this->selectedUser);

        //     if ($user && $user->role_id === 3) {
        //         $query->where('admin_renewal', $user->name);
        //     }
        // } else {
        //     $currentUser = auth()->user();

        //     if ($currentUser->role_id === 3) {
        //         $query->where('admin_renewal', $currentUser->name);
        //     }
        // }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getOverdueRenewals())
            ->defaultSort('created_at', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('admin_renewal')
                    ->label('Filter by Admin Renewal')
                    ->options(function () {
                        return User::where('role_id', 3)
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Admin Renewals')
                    ->multiple(),

                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', 2)
                            ->whereNot('id', 15)
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
                    ->formatStateUsing(function ($state, Renewal $record) {
                        return 'AR_' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('lead.salesperson_name')
                    ->label('Salesperson')
                    ->formatStateUsing(function ($state, Renewal $record) {
                        if ($record->lead && $record->lead->salesperson) {
                            $salesperson = User::find($record->lead->salesperson);
                            return $salesperson ? $salesperson->name : 'Unknown';
                        }
                        return 'N/A';
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('admin_renewal')
                    ->label('Admin Renewal')
                    ->visible(fn(): bool => auth()->user()->role_id !== 3),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->lead_id) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();

                            if ($company) {
                                $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                                return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                        target="_blank"
                                        title="' . e($state) . '"
                                        class="inline-block"
                                        style="color:#338cf0;">
                                        ' . $company->company_name . '
                                    </a>');
                            }
                        }

                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->formatStateUsing(fn ($record) => $this->getWeekdayCount($record->follow_up_date, now()) . ' days')
                    ->color(fn ($record) => $this->getWeekdayCount($record->follow_up_date, now()) == 0 ? 'draft' : 'danger'),

                TextColumn::make('follow_up_date')
                    ->label('Follow Up Date')
                    ->date('d M Y'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->url(function (Renewal $record) {
                            if ($record->lead_id) {
                                $encryptedId = \App\Classes\Encryptor::encrypt($record->lead_id);
                                return url('admin/leads/' . $encryptedId);
                            }
                            return '#';
                        })
                        ->openUrlInNewTab(),

                    AdminRenewalActions::addAdminRenewalFollowUp()
                        ->action(function (Renewal $record, array $data) {
                            AdminRenewalActions::processFollowUpWithEmail($record, $data);
                            $this->dispatch('refresh-admin-renewal-tables');
                        }),
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.admin_renewal_dashboard.ar-follow-up-overdue');
    }

    private function getWeekdayCount($startDate, $endDate)
    {
        $weekdayCount = 0;
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        while ($currentDate->lte($endDate)) {
            if (!$currentDate->isWeekend()) {
                $weekdayCount++;
            }
            $currentDate->addDay();
        }

        return $weekdayCount;
    }
}
