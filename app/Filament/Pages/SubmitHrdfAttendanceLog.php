<?php
namespace App\Filament\Pages;

use App\Models\HrdfAttendanceLog;
use App\Models\PublicHoliday;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;

class SubmitHrdfAttendanceLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'HRDF Attendance Log';
    protected static ?string $title = 'HRDF Attendance Log';
    protected static string $view = 'filament.pages.submit-hrdf-attendance-log';
    protected static ?string $navigationGroup = 'HRDF Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'hrdf-attendance-log';

    public function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('createLog')
                ->label('Create')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->size(ActionSize::Large)
                // ->visible(fn () => in_array(Auth::id(), [1, 14, 34]))
                ->form([
                    // ✅ Training Dates Section (applies to all companies)
                    Section::make('Training Schedule')
                        ->description('Set the training dates that will apply to all companies below')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    DatePicker::make('training_date_1')
                                        ->label('Training Date 1')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->minDate(now()->subweek(2))
                                        ->maxDate(now()->addMonths(2))
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (!$state) {
                                                $set('training_date_2', null);
                                                $set('training_date_3', null);
                                                return;
                                            }

                                            // Auto-calculate next working days
                                            $date1 = Carbon::parse($state);
                                            $date2 = $this->getNextWorkingDay($date1);
                                            $date3 = $this->getNextWorkingDay($date2);

                                            $set('training_date_2', $date2->format('Y-m-d'));
                                            $set('training_date_3', $date3->format('Y-m-d'));
                                        }),
                                        // ->disabledDates(function () {
                                        //     return $this->getDisabledDates();
                                        // }),

                                    DatePicker::make('training_date_2')
                                        ->label('Training Date 2')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->minDate(now()->subweek(2))
                                        ->maxDate(now()->addMonths(2))
                                        ->closeOnDateSelection()
                                        ->disabled(fn (Get $get) => !$get('training_date_1'))
                                        ->disabledDates(function () {
                                            return $this->getDisabledDates();
                                        }),

                                    DatePicker::make('training_date_3')
                                        ->label('Training Date 3')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->minDate(now()->subweek(2))
                                        ->maxDate(now()->addMonths(2))
                                        ->closeOnDateSelection()
                                        ->disabled(fn (Get $get) => !$get('training_date_1'))
                                        ->disabledDates(function () {
                                            return $this->getDisabledDates();
                                        }),
                                ])
                        ])
                        ->collapsible()
                        ->collapsed(false),

                    // ✅ Companies Section (multiple companies with same dates)
                    Section::make('Companies')
                        ->description('Add all companies that will attend the training on the dates above')
                        ->schema([
                            Repeater::make('companies')
                                ->label('')
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Company Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Enter company name')
                                        ->extraAlpineAttributes([
                                            'x-on:input' => '
                                                const start = $el.selectionStart;
                                                const end = $el.selectionEnd;
                                                const value = $el.value;
                                                $el.value = value.toUpperCase();
                                                $el.setSelectionRange(start, end);
                                            '
                                        ])
                                        ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                                ])
                                ->defaultItems(1)
                                ->minItems(1)
                                ->maxItems(20)
                                ->addActionLabel('Add Another Company')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['company_name'] ?? 'New Company')
                                ->columns(1)
                        ])
                        ->collapsible()
                        ->collapsed(false),
                ])
                ->action(function (array $data) {
                    // ✅ Validate training dates first
                    $dates = [
                        Carbon::parse($data['training_date_1']),
                        Carbon::parse($data['training_date_2']),
                        Carbon::parse($data['training_date_3']),
                    ];

                    $publicHolidays = PublicHoliday::whereIn('date', [
                        $data['training_date_1'],
                        $data['training_date_2'],
                        $data['training_date_3'],
                    ])->pluck('name', 'date')->toArray();

                    foreach ($dates as $index => $date) {
                        $dateString = $date->format('Y-m-d');

                        if ($date->isWeekend()) {
                            Notification::make()
                                ->title('Error')
                                ->body('Training Date ' . ($index + 1) . ' must be a weekday (Monday-Friday)')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (isset($publicHolidays[$dateString])) {
                            Notification::make()
                                ->title('Error')
                                ->body('Training Date ' . ($index + 1) . ' is a public holiday: ' . $publicHolidays[$dateString])
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    // ✅ Validate that company names are unique
                    $companyNames = collect($data['companies'])->pluck('company_name')->filter();
                    $duplicates = $companyNames->duplicates();

                    if ($duplicates->isNotEmpty()) {
                        Notification::make()
                            ->title('Error')
                            ->body('Duplicate company names found: ' . $duplicates->implode(', '))
                            ->danger()
                            ->send();
                        return;
                    }

                    // ✅ Create logs for all companies
                    $createdLogs = [];
                    $totalCompanies = count($data['companies']);

                    foreach ($data['companies'] as $company) {
                        if (empty($company['company_name'])) {
                            continue;
                        }

                        $log = HrdfAttendanceLog::create([
                            'company_name' => $company['company_name'],
                            'training_date_1' => $data['training_date_1'],
                            'training_date_2' => $data['training_date_2'],
                            'training_date_3' => $data['training_date_3'],
                            'submitted_by' => Auth::id(),
                            'status' => 'new',
                        ]);

                        $createdLogs[] = $log;
                    }

                    // ✅ Success notification with details
                    $formattedDates = implode(', ', array_map(function($date) {
                        return Carbon::parse($date)->format('d/m/Y (D)');
                    }, [$data['training_date_1'], $data['training_date_2'], $data['training_date_3']]));

                    $logIds = collect($createdLogs)->map(fn($log) => $log->formatted_log_id)->implode(', ');
                    $companyList = collect($createdLogs)->map(fn($log) => $log->company_name)->implode(', ');

                    Notification::make()
                        ->title('HRDF Logs Created Successfully')
                        ->body("✅ Created {$totalCompanies} HRDF Attendance Logs<br>📋 Log IDs: {$logIds}<br>🏢 Companies: {$companyList}<br>📅 Training Dates: {$formattedDates}")
                        ->success()
                        ->duration(8000)
                        ->send();

                    $this->resetTable();
                })
                ->modalWidth('4xl')
                ->modalHeading('Create HRDF Attendance Logs')
                ->modalDescription('Set the training dates and add all companies that will attend these training sessions')
                ->modalSubmitActionLabel('Create Logs')
                ->modalCancelActionLabel('Cancel'),
        ];
    }

    // ✅ Helper method to get disabled dates (extracted for reuse)
    protected function getDisabledDates(): array
    {
        $disabledDates = [];
        $start = now()->subDay();
        $end = now()->addMonths(2);

        // Add weekends
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekend()) {
                $disabledDates[] = $date->format('Y-m-d');
            }
        }

        // Add public holidays
        $publicHolidays = PublicHoliday::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        return array_unique(array_merge($disabledDates, $publicHolidays));
    }

    // ✅ Helper method to get next working day (skip weekends AND public holidays)
    protected function getNextWorkingDay(Carbon $date): Carbon
    {
        $nextDate = $date->copy()->addDay();

        // Get all public holidays
        $publicHolidays = PublicHoliday::pluck('date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        // Keep looking for next working day
        while ($nextDate->isWeekend() || in_array($nextDate->format('Y-m-d'), $publicHolidays)) {
            $nextDate->addDay();
        }

        return $nextDate;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(HrdfAttendanceLog::query())
            ->columns([
                TextColumn::make('formatted_log_id')
                    ->label('Log ID')
                    ->getStateUsing(fn (HrdfAttendanceLog $record) => $record->formatted_log_id)
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('id', $direction);
                    })
                    ->searchable(query: function ($query, $search) {
                        if (is_numeric($search)) {
                            return $query->where('id', $search);
                        }
                        if (preg_match('/LOG[_\s]*(\d+)/', strtoupper($search), $matches)) {
                            return $query->where('id', $matches[1]);
                        }
                        return $query;
                    })
                    ->copyable()
                    ->copyMessage('Log ID copied!')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('submittedByUser.name')
                    ->label('Submitted By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A'),

                TextColumn::make('training_dates')
                    ->label('Training Dates')
                    ->getStateUsing(fn (HrdfAttendanceLog $record) => $record->training_dates)
                    ->wrap()
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('training_date_1', 'like', "%{$search}%")
                                ->orWhere('training_date_2', 'like', "%{$search}%")
                                ->orWhere('training_date_3', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->weight('medium'),

                TextColumn::make('created_at')
                    ->label('Created Time')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'new',
                        'info' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Completed Time')
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(function (HrdfAttendanceLog $record) {
                        return $record->completed_at?->format('d/m/Y H:i:s') ?? '-';
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100])
            ->filters([
                \Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter::make('training_dates')
                    ->label('Filter by Training Dates')
                    ->placeholder('Select date range')
                    ->modifyQueryUsing(function ($query, $data) {
                        if (!empty($data['startDate']) && !empty($data['endDate'])) {
                            $query->where(function ($q) use ($data) {
                                $q->whereBetween('training_date_1', [$data['startDate'], $data['endDate']])
                                    ->orWhereBetween('training_date_2', [$data['startDate'], $data['endDate']])
                                    ->orWhereBetween('training_date_3', [$data['startDate'], $data['endDate']]);
                            });
                        }
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
