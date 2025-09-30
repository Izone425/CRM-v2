<?php
namespace App\Livewire\AdminRenewalDashboard;

use App\Filament\Actions\AdminRenewalActions;
use App\Filament\Filters\SortFilter;
use App\Filament\Pages\RenewalDataMyr;
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
use Illuminate\Support\Facades\Log;

class ArFollowUpAllMyr extends Component implements HasForms, HasTable
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

        // Get company IDs that have MYR expiring licenses
        $myrCompanyIds = DB::connection('frontenddb')->table('crm_expiring_license')
            ->select('f_company_id')
            ->where('f_currency', 'MYR')
            ->whereDate('f_expiry_date', '>=', today())
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
            ->whereIn('f_company_id', $myrCompanyIds)
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
                ) as earliest_expiry_date')
            ->orderBy('earliest_expiry_date', 'ASC');

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getOverdueRenewals())
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
                    ->formatStateUsing(function ($state, $record) {

                        return Carbon::parse(self::getEarliestExpiryDate($record->f_company_id))->format('d M Y') ?? 'N/A';
                    }),

                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->formatStateUsing(fn ($record) => $this->getWeekdayCount($record->follow_up_date, now()) . ' days')
                    ->color(fn ($record) => $this->getWeekdayCount($record->follow_up_date, now()) == 0 ? 'draft' : 'danger'),

                TextColumn::make('follow_up_date')
                    ->label('Follow Up Date')
                    ->date('d M Y'),

                // TextColumn::make('f_company_id')
                //     ->label('Currency')
                //     ->formatStateUsing(function ($state) {
                //         $hasMyr = DB::connection('frontenddb')->table('crm_expiring_license')
                //             ->where('f_company_id', $state)
                //             ->where('f_currency', 'MYR')
                //             ->exists();

                //         return $hasMyr ? 'MYR' : 'N/A';
                //     })
                //     ->badge()
                //     ->color('warning'),
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
                    Action::make('view_last_follow_up')
                        ->label('View Last Follow Up')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading('Last Follow Up Information')
                        ->modalContent(function (Renewal $record) {
                            $data = AdminRenewalLogs::where('subject_id', $record->id)
                                ->latest()
                                ->first();

                            if (! $data) {
                                return new HtmlString(
                                    "<div class='p-6 text-center'>
                                        <p class='text-gray-500'>No follow-up records found for this renewal.</p>
                                    </div>"
                                );
                            }

                            $followUpDate = $data->created_at ? Carbon::parse($data->created_at)->format('d M Y, h:i A') : 'N/A';
                            $followUpBy = $data->causer ? $data->causer->name : 'System';
                            $nextFollowUpDate = $data->follow_up_date ? Carbon::parse($data->follow_up_date)->format('d M Y') : 'N/A';
                            $followUpCount = $data->manual_follow_up_count ? "Follow-up #{$data->manual_follow_up_count}" : '';

                            return new HtmlString(
                                "<div class='space-y-6'>
                                    <div class='p-4 rounded-lg bg-gray-50'>
                                        <h3 class='mb-3 text-lg font-semibold text-gray-900'>Follow Up Details</h3>
                                        <div class='grid grid-cols-2 gap-4 text-sm'>
                                            <div>
                                                <span class='font-medium text-gray-700'>Follow Up Date:</span>
                                                <span class='ml-2 text-gray-900'>{$followUpDate}</span>
                                            </div>
                                            <div>
                                                <span class='font-medium text-gray-700'>Follow Up By:</span>
                                                <span class='ml-2 text-gray-900'>{$followUpBy}</span>
                                            </div>
                                            <div>
                                                <span class='font-medium text-gray-700'>Next Follow Up:</span>
                                                <span class='ml-2 text-gray-900'>{$nextFollowUpDate}</span>
                                            </div>
                                            ".($followUpCount ? "<div><span class='font-medium text-gray-700'>Count:</span><span class='ml-2 text-gray-900'>{$followUpCount}</span></div>" : '')."
                                        </div>
                                    </div>

                                    <div class='p-4 rounded-lg bg-blue-50'>
                                        <h3 class='mb-3 text-lg font-semibold text-gray-900'>Description</h3>
                                        <div class='text-sm text-gray-800'>
                                            {$data->description}
                                        </div>
                                    </div>

                                    <div class='p-4 rounded-lg bg-yellow-50'>
                                        <h3 class='mb-3 text-lg font-semibold text-gray-900'>Remarks</h3>
                                        <div class='prose-sm prose max-w-none'>
                                            <div class='p-3 text-sm bg-white border border-yellow-200 rounded'>
                                                {$data->remark}
                                            </div>
                                        </div>
                                    </div>
                                </div>"
                            );
                        })
                        ->modalWidth('2xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                    Action::make('view process data')
                        ->label('View Process Data')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->url(function (Renewal $record) {
                            $padded = str_pad($record->f_company_id, 10, '0', STR_PAD_LEFT);

                            $data = RenewalDataMyr::where('f_company_id', $padded)
                                ->first();
                            if ($data->f_currency == 'MYR') {
                                //    $encryptedId = \App\Classes\Encryptor::encrypt($data->id);

                                return url('/admin/admin-renewal-process-data-myr');
                            } else {
                                return url('/admin/admin-renewal-process-data-usd');
                            }

                            return '#';

                        })
                        ->openUrlInNewTab(),
                    AdminRenewalActions::addAdminRenewalFollowUp()
                        ->action(function (Renewal $record, array $data) {
                            AdminRenewalActions::processFollowUpWithEmail($record, $data);
                            $this->dispatch('refresh-admin-renewal-tables');
                        }),
                    // AdminRenewalActions::stopAdminRenewalFollowUp()
                    //     ->action(function (Renewal $record, array $data) {
                    //         AdminRenewalActions::processStopFollowUp($record, $data);
                    //         $this->dispatch('refresh-admin-renewal-tables');
                    //     }),
                ])
                ->button()
                ->color('warning') // Orange color for MYR
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.admin_renewal_dashboard.ar-follow-up-all-myr');
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

    protected static function getEarliestExpiryDate($companyId)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            $earliestExpiry = DB::connection('frontenddb')
                ->table('crm_expiring_license')
                ->where('f_company_id', $companyId)
                ->where('f_expiry_date', '>=', $today)
                ->where('f_currency', 'MYR')
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
}
