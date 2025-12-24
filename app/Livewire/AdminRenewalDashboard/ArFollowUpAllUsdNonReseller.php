<?php

namespace App\Livewire\AdminRenewalDashboard;

use App\Filament\Actions\AdminRenewalActions;
use App\Filament\Filters\SortFilter;
use App\Filament\Pages\RenewalDataMyr;
use App\Models\AdminRenewalLogs;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\Renewal;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class ArFollowUpAllUsdNonReseller extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

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

        // Get company IDs that have USD expiring licenses
        $usdCompanyIds = DB::connection('frontenddb')->table('crm_expiring_license')
            ->select('f_company_id')
            ->where('f_currency', 'USD')
            ->whereDate('f_expiry_date', '>=', today())
            ->whereDate('f_expiry_date', '<=', today()->addDays(90))
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
            ->where('follow_up_counter', true)
            ->where('mapping_status', 'completed_mapping')
            ->whereIn('renewal_progress', ['pending_confirmation'])
            // Only show records that do NOT have a reseller
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('frontenddb.crm_reseller_link')
                    ->whereRaw('crm_reseller_link.f_company_id = renewals.f_company_id');
            })
            ->selectRaw('*,
                DATEDIFF(NOW(), follow_up_date) as pending_days,
                (SELECT MIN(f_expiry_date) FROM frontenddb.crm_expiring_license
                WHERE f_company_id = renewals.f_company_id
                AND f_currency = "USD"
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

    protected static function getEarliestExpiryDate($companyId)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            $earliestExpiry = DB::connection('frontenddb')
                ->table('crm_expiring_license')
                ->where('f_company_id', $companyId)
                ->where('f_expiry_date', '>=', $today)
                ->where('f_currency', 'USD')
                ->whereNotIn('f_name', [
                    'TimeTec VMS Corporate (1 Floor License)',
                    'TimeTec VMS SME (1 Location License)',
                    'TimeTec Patrol (1 Checkpoint License)',
                    'TimeTec Patrol (10 Checkpoint License)',
                    'Other',
                    'TimeTec Profile (10 User License)',
                ])
                ->min('f_expiry_date');

            return $earliestExpiry;
        } catch (\Exception $e) {
            Log::error("Error fetching earliest expiry date for company {$companyId}: ".$e->getMessage());

            return null;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getOverdueRenewals())
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

                SelectFilter::make('renewal_progress')
                    ->label('Filter by Status')
                    ->options([
                        'new' => 'New',
                        'pending_confirmation' => 'Pending Confirmation',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
            ])
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->lead_id) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();

                            if ($company) {
                                $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                                return new HtmlString('<a href="'.url('admin/leads/'.$encryptedId).'"
                                        target="_blank"
                                        title="'.e($state).'"
                                        class="inline-block"
                                        style="color:#338cf0;">
                                        '.$company->company_name.'
                                    </a>');
                            }
                        }

                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('renewal_progress')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        $statusMap = [
                            'new' => 'New',
                            'pending_confirmation' => 'Pending Confirmation',
                            'pending_payment' => 'Pending Payment',
                            'completed_renewal' => 'Completed Payment',
                            'terminated' => 'Terminated',
                        ];

                        return $statusMap[$state] ?? ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('earliest_expiry_date')
                    ->label('Expiry Date')
                    ->default('N/A')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {

                        return Carbon::parse(self::getEarliestExpiryDate($record->f_company_id))->format('d M Y') ?? 'N/A';
                    }),

                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $this->getWeekdayCount($record->follow_up_date, now()).' days')
                    ->color(fn ($record) => $this->getWeekdayCount($record->follow_up_date, now()) == 0 ? 'draft' : 'danger'),

                TextColumn::make('follow_up_date')
                    ->label('Follow Up Date')
                    ->date('d M Y'),
            ])
            ->actions([
                ActionGroup::make([
                    AdminRenewalActions::viewAction(),
                    AdminRenewalActions::viewLastFollowUpAction(),
                    AdminRenewalActions::viewProcessDataAction(),

                    AdminRenewalActions::addAdminRenewalFollowUp()
                        ->action(function (Renewal $record, array $data) {
                            AdminRenewalActions::processFollowUpWithEmail($record, $data);
                            $this->dispatch('refresh-admin-renewal-tables');
                        }),
                ])
                ->button()
                ->color('info') // Blue color for USD
                ->label('Actions'),
            ]);
    }

    public function render()
    {
        return view('livewire.admin_renewal_dashboard.ar-follow-up-all-usd-non-reseller');
    }

    private function getWeekdayCount($startDate, $endDate)
    {
        $weekdayCount = 0;
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        while ($currentDate->lte($endDate)) {
            if (! $currentDate->isWeekend()) {
                $weekdayCount++;
            }
            $currentDate->addDay();
        }

        return $weekdayCount;
    }
}