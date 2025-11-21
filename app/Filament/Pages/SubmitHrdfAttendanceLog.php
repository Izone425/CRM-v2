<?php
namespace App\Filament\Pages;

use App\Models\HrdfAttendanceLog;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Carbon\Carbon;

class SubmitHrdfAttendanceLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'HRDF Attendance Log';
    protected static ?string $title = 'HRDF Attendance Log Submission';
    protected static string $view = 'filament.pages.submit-hrdf-attendance-log';
    protected static ?string $navigationGroup = 'HRDF Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'hrdf-attendance-log';

    public function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('createLog')
                ->label('Create New Log')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->size(ActionSize::Large)
                ->form([
                    TextInput::make('company_name')
                        ->label('Company Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Enter company name')
                        ->columnSpanFull(),

                    DateRangePicker::make('training_dates')
                        ->label('Select 3 Training Dates')
                        ->required()
                        ->format('d/m/Y')
                        ->displayFormat('DD/MM/YYYY')
                        ->placeholder('Select date range covering 3 training dates')
                        ->helperText('Please select a date range that includes exactly 3 weekdays (Monday-Friday)')
                        ->columnSpanFull()
                        ->minDate(now()->subMonths(3)->format('Y-m-d'))
                        ->maxDate(now()->addMonths(1)->format('Y-m-d')),
                ])
                ->action(function (array $data) {
                    if (!isset($data['training_dates']) || empty($data['training_dates'])) {
                        Notification::make()
                            ->title('Error')
                            ->body('Please select a valid date range')
                            ->danger()
                            ->send();
                        return;
                    }

                    // ✅ Parse the date range
                    $dateRange = $data['training_dates'];
                    [$startDateStr, $endDateStr] = explode(' - ', $dateRange);

                    $startDate = Carbon::createFromFormat('d/m/Y', trim($startDateStr));
                    $endDate = Carbon::createFromFormat('d/m/Y', trim($endDateStr));

                    // ✅ Extract all weekdays from the range
                    $weekdays = [];
                    $currentDate = $startDate->copy();

                    while ($currentDate->lte($endDate)) {
                        if (!$currentDate->isWeekend()) {
                            $weekdays[] = $currentDate->format('Y-m-d');
                        }
                        $currentDate->addDay();
                    }

                    // ✅ Validate that we have exactly 3 weekdays
                    if (count($weekdays) < 3) {
                        Notification::make()
                            ->title('Error')
                            ->body('The selected date range must contain at least 3 weekdays. Found only ' . count($weekdays) . ' weekday(s).')
                            ->danger()
                            ->send();
                        return;
                    }

                    // ✅ Take the first 3 weekdays
                    $trainingDates = array_slice($weekdays, 0, 3);

                    // Create the log
                    $log = HrdfAttendanceLog::create([
                        'company_name' => $data['company_name'],
                        'training_date_1' => $trainingDates[0],
                        'training_date_2' => $trainingDates[1],
                        'training_date_3' => $trainingDates[2],
                        'submitted_by' => Auth::id(),
                        'status' => 'new',
                    ]);

                    $formattedDates = implode(', ', array_map(function($date) {
                        return Carbon::parse($date)->format('d/m/Y (D)');
                    }, $trainingDates));

                    Notification::make()
                        ->title('Log Created Successfully')
                        ->body("HRDF Attendance Log #{$log->id} has been created for {$data['company_name']}<br>Training Dates: {$formattedDates}")
                        ->success()
                        ->send();

                    // Refresh the table
                    $this->resetTable();
                })
                ->modalWidth('3xl')
                ->modalHeading('Create New HRDF Attendance Log')
                ->modalDescription('Please enter company name and select 3 training dates (must be weekdays)')
                ->modalSubmitActionLabel('Create Log')
                ->modalCancelActionLabel('Cancel'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(HrdfAttendanceLog::query()->latest())
            ->columns([
                TextColumn::make('formatted_log_id')
                    ->label('Log ID')
                    ->getStateUsing(fn (HrdfAttendanceLog $record) => $record->formatted_log_id)
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('id', $direction);
                    })
                    ->searchable(query: function ($query, $search) {
                        // Search by actual ID number
                        if (is_numeric($search)) {
                            return $query->where('id', $search);
                        }
                        // Search by formatted ID (e.g., LOG_240001)
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

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'new',
                        'info' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'new',
                        'heroicon-o-arrow-path' => 'in_progress',
                        'heroicon-o-check-circle' => 'completed',
                        'heroicon-o-x-circle' => 'cancelled',
                    ])
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
            ->actions([
                // Add actions here if needed (view, edit, delete)
            ])
            ->bulkActions([
                // Add bulk actions here if needed
            ])
            ->emptyStateHeading('No HRDF Attendance Logs Yet')
            ->emptyStateDescription('Click "Create New Log" to submit your first HRDF attendance log.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
