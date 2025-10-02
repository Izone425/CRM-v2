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
use Illuminate\Support\Facades\DB;

class ArFollowUpUpcomingUsd extends Component implements HasForms, HasTable
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

    public function getIncomingRenewals()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        // Get company IDs that have USD expiring licenses
        $usdCompanyIds = DB::connection('frontenddb')->table('crm_expiring_license')
            ->select('f_company_id')
            ->where('f_currency', 'USD')
            ->whereDate('f_expiry_date', '>=', today())
            ->whereDate('f_expiry_date', '<=', today()->addDays(60))
            ->distinct()
            ->pluck('f_company_id')
            ->flatMap(function($id) {
                // Return both formats: with leading zeros and without
                $withoutZeros = (string) (int) $id; // Remove leading zeros
                $withZeros = str_pad($withoutZeros, 10, '0', STR_PAD_LEFT); // Add leading zeros

                return [$withoutZeros, $withZeros];
            })
            ->toArray();

        $query = Renewal::query()
            ->whereIn('f_company_id', $usdCompanyIds)
            ->whereDate('follow_up_date', '>', today())
            ->where('follow_up_counter', true)
            ->where('mapping_status', 'completed_mapping')
            ->whereIn('renewal_progress', ['new', 'pending_confirmation'])
            ->selectRaw('*,
                DATEDIFF(NOW(), follow_up_date) as pending_days,
                (SELECT MIN(f_expiry_date) FROM frontenddb.crm_expiring_license
                WHERE f_company_id = renewals.f_company_id
                AND f_currency = "MYR"
                AND f_expiry_date >= CURDATE()
                AND f_name NOT IN (
                    "TimeTec VMS Corporate (1 Floor License)",
                    "TimeTec VMS SME (1 Location License)",
                    "TimeTec Patrol (1 Checkpoint License)",
                    "TimeTec Patrol (10 Checkpoint License)",
                    "Other",
                    "TimeTec Profile (10 User License)"
                )
                ) as earliest_expiry_date');

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getIncomingRenewals())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->defaultSort('earliest_expiry_date', 'asc')
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
            ])
            ->columns([
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

                TextColumn::make('earliest_expiry_date')
                    ->label('Expiry Date')
                    ->default('N/A')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {

                        return Carbon::parse(self::getEarliestExpiryDate($record->f_company_id))->format('d M Y') ?? 'N/A';
                    }),

                TextColumn::make('pending_days')
                    ->label('Counting Days')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $daysLeft = $this->getWeekdayCount(now(), $record->follow_up_date);

                        if ($daysLeft <= 0) {
                            return 'Today';
                        } else {
                            return $daysLeft . ' days';
                        }
                    })
                    ->color(function ($record) {
                        $daysLeft = $this->getWeekdayCount(now(), $record->follow_up_date);

                        if ($daysLeft > 3) {
                            return 'success';
                        } elseif ($daysLeft > 0) {
                            return 'warning';
                        } else {
                            return 'primary';
                        }
                    }),

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
                ->color('info') // Blue color for USD
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.admin_renewal_dashboard.ar-follow-up-upcoming-usd');
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
