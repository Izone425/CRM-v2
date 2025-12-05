<?php
namespace App\Filament\Pages;

use App\Models\SoftwareHandover;
use App\Models\Customer;
use App\Models\LicenseCertificate;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;

class AdminPortalHrV2 extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.admin-portal-hr-v2';

    protected static ?string $navigationLabel = 'HR V2 Database Portal';

    protected static ?string $title = 'Admin Portal HR V2';

    protected static ?string $navigationGroup = 'Admin Portal';

    protected static ?int $navigationSort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SoftwareHandover::query()
                    ->where('hr_version', 2)
                    ->whereNotNull('hr_company_id')
                    ->with(['licenseCertificateById']) // ✅ Use correct relationship
                    ->orderBy('completed_at', 'desc')
            )
            ->columns([
                TextColumn::make('hr_company_id')
                    ->label(new HtmlString('Database<br>Backend ID'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Backend ID copied!')
                    ->weight('bold'),

                TextColumn::make('completed_at')
                    ->label(new HtmlString('Date & Time<br>DB Created'))
                    ->getStateUsing(function (SoftwareHandover $record) {
                        if (!$record->completed_at) {
                            return new HtmlString('
                                <div style="color: #6b7280; font-style: italic;">
                                    Not Available
                                </div>
                            ');
                        }

                        $date = Carbon::parse($record->completed_at)->format('d M Y');
                        $time = Carbon::parse($record->completed_at)->format('h:i A');

                        return new HtmlString("
                            <div style='display: flex; flex-direction: column; gap: 2px;'>
                                <div style='font-weight: 600; color: #111827; font-size: 13px;'>{$date}</div>
                                <div style='color: #6b7280; font-size: 11px;'>{$time}</div>
                            </div>
                        ");
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('project_code')
                    ->label('SW ID')
                    ->copyable()
                    ->copyMessage('Handover ID copied!')
                    ->getStateUsing(fn (SoftwareHandover $record) => $record->project_code),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('buffer_license_period')
                    ->label(new HtmlString('Buffer License<br>Period'))
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $certificate = $record->licenseCertificateById;

                        if (!$certificate) {
                            return new HtmlString('
                                <div style="display: inline-flex; align-items: center; padding: 6px 12px; font-size: 12px; font-weight: 500; border-radius: 20px; background-color: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db;">
                                    <svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    No License
                                </div>
                            ');
                        }

                        $startDate = $certificate->buffer_license_start ?
                            Carbon::parse($certificate->buffer_license_start)->format('d M Y') :
                            'N/A';

                        $endDate = $certificate->buffer_license_end ?
                            Carbon::parse($certificate->buffer_license_end)->format('d M Y') :
                            'N/A';

                        // Check if buffer license is expired
                        $isExpired = $certificate->buffer_license_end &&
                            Carbon::parse($certificate->buffer_license_end)->isPast();

                        if ($isExpired) {
                            $badgeStyle = 'background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;';
                            $statusText = 'EXPIRED';
                            $icon = '<svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>';
                        } else {
                            $badgeStyle = 'background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;';
                            $statusText = 'ACTIVE';
                            $icon = '<svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>';
                        }

                        return new HtmlString("
                            <div style='display: flex; flex-direction: column; gap: 8px;'>
                                <div style='font-size: 13px;'>
                                    <div style='font-weight: 600; color: #111827;'>{$startDate}</div>
                                    <div style='color: #6b7280; font-size: 11px;'>to {$endDate}</div>
                                </div>
                                <div style='display: inline-flex; align-items: center; padding: 4px 8px; font-size: 11px; font-weight: 600; border-radius: 20px; {$badgeStyle}'>
                                    {$icon}
                                    {$statusText}
                                </div>
                            </div>
                        ");
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('paid_license_period')
                    ->label(new HtmlString('Paid License<br>Period'))
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $certificate = $record->licenseCertificateById;

                        if (!$certificate || !$certificate->paid_license_start) {
                            return new HtmlString('
                                <div style="display: inline-flex; align-items: center; padding: 8px 12px; font-size: 13px; font-weight: 500; border-radius: 8px; background-color: #fef3c7; color: #d97706; border: 1px solid #fbbf24;">
                                    <svg style="width: 16px; height: 16px; margin-right: 6px;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path>
                                    </svg>
                                    Not Created
                                </div>
                            ');
                        }

                        $startDate = Carbon::parse($certificate->paid_license_start)->format('d M Y');
                        $endDate = $certificate->paid_license_end ?
                            Carbon::parse($certificate->paid_license_end)->format('d M Y') :
                            'N/A';

                        $isExpired = $certificate->paid_license_end &&
                            Carbon::parse($certificate->paid_license_end)->isPast();

                        // Days remaining calculation
                        $daysRemaining = '';
                        if ($certificate->paid_license_end) {
                            $endDateCarbon = Carbon::parse($certificate->paid_license_end);
                            $now = Carbon::now();

                            if ($endDateCarbon->isFuture()) {
                                $days = $now->diffInDays($endDateCarbon);
                                $daysRemaining = "<div style='font-size: 11px; color: #2563eb; margin-top: 4px;'>• {$days} days remaining</div>";
                            }
                        }

                        if ($isExpired) {
                            $badgeStyle = 'background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;';
                            $statusText = 'EXPIRED';
                            $icon = '<svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>';
                            $daysRemaining = '';
                        } else {
                            $badgeStyle = 'background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;';
                            $statusText = 'ACTIVE';
                            $icon = '<svg style="width: 12px; height: 12px; margin-right: 4px;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>';
                        }

                        return new HtmlString("
                            <div style='display: flex; flex-direction: column; gap: 8px;'>
                                <div style='font-size: 13px;'>
                                    <div style='font-weight: 600; color: #111827;'>{$startDate}</div>
                                    <div style='color: #6b7280; font-size: 11px;'>to {$endDate}</div>
                                    {$daysRemaining}
                                </div>
                                <div style='display: inline-flex; align-items: center; padding: 4px 8px; font-size: 11px; font-weight: 600; border-radius: 20px; {$badgeStyle}'>
                                    {$icon}
                                    {$statusText}
                                </div>
                            </div>
                        ");
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('master_email')
                    ->label('Master Email')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $customer = Customer::where('sw_id', $record->id)->first();
                        return $customer?->email ?? "sw{$record->id}@timeteccloud.com";
                    })
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->searchable(),

                TextColumn::make('plain_password')
                    ->label('Master Password')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $customer = Customer::where('sw_id', $record->id)->first();
                        return $customer?->plain_password ?? 'N/A';
                    })
                    ->copyable()
                    ->copyMessage('Password copied!'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Completed' => 'Completed',
                        'Rejected' => 'Rejected',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('completed_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('completed_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // ✅ License Management Actions
                    Action::make('create_paid_license')
                        ->label('Create Paid License')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->visible(fn (SoftwareHandover $record) =>
                            !$record->licenseCertificateById?->paid_license_start
                        )
                        ->form([
                            DatePicker::make('paid_license_start')
                                ->label('Paid License Start Date')
                                ->required()
                                ->default(now()->addMonth())
                                ->live() // ✅ Add live() to trigger updates
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $years = $get('license_years') ?? 1;
                                    if ($state && $years) {
                                        $endDate = \Carbon\Carbon::parse($state)->addYears($years)->subDay();
                                        $set('paid_license_end', $endDate->format('Y-m-d'));
                                        $set('next_renewal_date', $endDate->format('Y-m-d'));
                                    }
                                }),

                            Select::make('license_years')
                                ->label('License Duration')
                                ->options([
                                    1 => '1 Year',
                                    2 => '2 Years',
                                    3 => '3 Years',
                                ])
                                ->required()
                                ->default(1)
                                ->live() // ✅ Use live() instead of reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $startDate = $get('paid_license_start');
                                    if ($startDate && $state) {
                                        $endDate = \Carbon\Carbon::parse($startDate)->addYears($state)->subDay();
                                        $set('paid_license_end', $endDate->format('Y-m-d'));
                                        $set('next_renewal_date', $endDate->format('Y-m-d'));
                                    }
                                }),

                            DatePicker::make('paid_license_end')
                                ->label('Paid License End Date')
                                ->required()
                                ->disabled()
                                ->dehydrated(true), // ✅ Ensure it's included in form data

                            DatePicker::make('next_renewal_date')
                                ->label('Next Renewal Date')
                                ->helperText('Optional: When should this license be renewed?'),
                        ])
                        ->fillForm(function (SoftwareHandover $record) {
                            $startDate = now()->addMonth();
                            $endDate = $startDate->copy()->addYear()->subDay();

                            return [
                                'paid_license_start' => $startDate->format('Y-m-d'),
                                'paid_license_end' => $endDate->format('Y-m-d'),
                                'license_years' => 1,
                                'next_renewal_date' => $endDate->format('Y-m-d'),
                            ];
                        })
                        ->action(function (SoftwareHandover $record, array $data) {
                            try {
                                // ✅ Debug: Log the incoming form data
                                \Illuminate\Support\Facades\Log::info("Form data received", [
                                    'handover_id' => $record->id,
                                    'form_data' => $data
                                ]);

                                // ✅ Ensure paid_license_end exists - calculate if missing
                                if (!isset($data['paid_license_end']) || empty($data['paid_license_end'])) {
                                    $startDate = \Carbon\Carbon::parse($data['paid_license_start']);
                                    $years = intval($data['license_years'] ?? 1);
                                    $data['paid_license_end'] = $startDate->copy()->addYears($years)->subDay()->format('Y-m-d');

                                    \Illuminate\Support\Facades\Log::info("Calculated missing paid_license_end", [
                                        'handover_id' => $record->id,
                                        'calculated_end_date' => $data['paid_license_end'],
                                        'start_date' => $data['paid_license_start'],
                                        'years' => $years
                                    ]);
                                }

                                // ✅ Validate required fields
                                $requiredFields = ['paid_license_start', 'paid_license_end', 'license_years'];
                                foreach ($requiredFields as $field) {
                                    if (!isset($data[$field]) || empty($data[$field])) {
                                        throw new \Exception("Required field '{$field}' is missing or empty");
                                    }
                                }

                                // ✅ 1. Create or update license certificate FIRST
                                $certificate = $record->licenseCertificateById ?? new LicenseCertificate();

                                $certificateData = [
                                    'software_handover_id' => $record->id,
                                    'company_name' => $record->company_name,
                                    'paid_license_start' => $data['paid_license_start'],
                                    'paid_license_end' => $data['paid_license_end'],
                                    'license_years' => $data['license_years'],
                                    'next_renewal_date' => $data['next_renewal_date'] ?? null,
                                    'updated_by' => auth()->id(),
                                ];

                                if (!$certificate->exists) {
                                    $certificateData['created_by'] = auth()->id();
                                }

                                $certificate->fill($certificateData);
                                $certificate->save();

                                \Illuminate\Support\Facades\Log::info("License certificate saved", [
                                    'handover_id' => $record->id,
                                    'certificate_id' => $certificate->id,
                                    'certificate_data' => $certificateData
                                ]);

                                // ✅ Update software handover license certificate ID
                                $record->update([
                                    'license_certification_id' => $certificate->id
                                ]);

                                // ✅ 2. Get selected modules from the handover record
                                $selectedModules = [
                                    'ta' => $record->ta,
                                    'tl' => $record->tl,
                                    'tc' => $record->tc,
                                    'tp' => $record->tp,
                                    'tapp' => $record->tapp,
                                    'thire' => $record->thire,
                                    'tacc' => $record->tacc,
                                    'tpbi' => $record->tpbi,
                                ];

                                // ✅ 3. Call CRM API to create paid licenses
                                $licenseService = app(\App\Services\LicenseSeatService::class);

                                $startDateObj = \Carbon\Carbon::parse($data['paid_license_start']);
                                $endDateObj = \Carbon\Carbon::parse($data['paid_license_end']);

                                $apiResult = $licenseService->addPaidApplicationLicenses(
                                    $record,
                                    $record->hr_account_id,
                                    $record->hr_company_id,
                                    $selectedModules,
                                    $record->formatted_handover_id ?? "SW_{$record->id}",
                                    $startDateObj,
                                    $endDateObj
                                );

                                if ($apiResult['success']) {
                                    // ✅ 4. Store the CRM license IDs in the certificate
                                    $paidLicenseIds = [];
                                    if (isset($apiResult['results'])) {
                                        foreach ($apiResult['results'] as $app => $result) {
                                            if (isset($result['data']['periodId'])) {
                                                $paidLicenseIds[] = $result['data']['periodId'];
                                            }
                                        }
                                    }

                                    if (!empty($paidLicenseIds)) {
                                        $certificate->update([
                                            'paid_license_ids' => json_encode($paidLicenseIds)
                                        ]);

                                        // Also update the main record
                                        $record->update([
                                            'crm_paid_license_ids' => json_encode($paidLicenseIds)
                                        ]);
                                    }

                                    Notification::make()
                                        ->title('Paid License Created Successfully')
                                        ->body("Paid license created for {$record->company_name}. Created {$apiResult['success_count']} licenses.")
                                        ->success()
                                        ->send();

                                    \Illuminate\Support\Facades\Log::info("Paid license created successfully", [
                                        'handover_id' => $record->id,
                                        'company_name' => $record->company_name,
                                        'success_count' => $apiResult['success_count'],
                                        'fail_count' => $apiResult['fail_count'] ?? 0,
                                        'paid_license_ids' => $paidLicenseIds
                                    ]);

                                } else {
                                    Notification::make()
                                        ->title('Paid License Creation Failed')
                                        ->body("Failed to create CRM licenses: " . ($apiResult['error'] ?? 'Unknown error'))
                                        ->danger()
                                        ->send();

                                    \Illuminate\Support\Facades\Log::error("Paid license creation failed", [
                                        'handover_id' => $record->id,
                                        'error' => $apiResult['error'] ?? 'Unknown error'
                                    ]);
                                }

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error Creating Paid License')
                                    ->body("An error occurred: " . $e->getMessage())
                                    ->danger()
                                    ->send();

                                \Illuminate\Support\Facades\Log::error("Paid license creation exception", [
                                    'handover_id' => $record->id,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'form_data' => $data ?? 'No form data available'
                                ]);
                            }
                        }),

                    Action::make('view_license')
                        ->label('View License Details')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->visible(fn (SoftwareHandover $record) => $record->licenseCertificateById)
                        ->form([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->disabled(),

                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('buffer_license_start')
                                        ->label('Buffer License Start')
                                        ->disabled(),

                                    DatePicker::make('buffer_license_end')
                                        ->label('Buffer License End')
                                        ->disabled(),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('paid_license_start')
                                        ->label('Paid License Start')
                                        ->disabled(),

                                    DatePicker::make('paid_license_end')
                                        ->label('Paid License End')
                                        ->disabled(),
                                ]),

                            TextInput::make('license_years')
                                ->label('License Duration (Years)')
                                ->disabled(),

                            DatePicker::make('next_renewal_date')
                                ->label('Next Renewal Date')
                                ->disabled(),
                        ])
                        ->fillForm(function (SoftwareHandover $record) {
                            $certificate = $record->licenseCertificateById;

                            if (!$certificate) {
                                return [];
                            }

                            return [
                                'company_name' => $certificate->company_name ?? '',
                                'buffer_license_start' => $certificate->buffer_license_start ?
                                    Carbon::parse($certificate->buffer_license_start)->format('Y-m-d') : null,
                                'buffer_license_end' => $certificate->buffer_license_end ?
                                    Carbon::parse($certificate->buffer_license_end)->format('Y-m-d') : null,
                                'paid_license_start' => $certificate->paid_license_start ?
                                    Carbon::parse($certificate->paid_license_start)->format('Y-m-d') : null,
                                'paid_license_end' => $certificate->paid_license_end ?
                                    Carbon::parse($certificate->paid_license_end)->format('Y-m-d') : null,
                                'license_years' => $certificate->license_years ?? 0,
                                'next_renewal_date' => $certificate->next_renewal_date ?
                                    Carbon::parse($certificate->next_renewal_date)->format('Y-m-d') : null,
                            ];
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),

                ])
                ->label(false)
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('primary'),
            ])
            ->defaultSort('completed_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function canAccess(): bool
    {
        // Add your authorization logic here
        return auth()->check();
    }
}
