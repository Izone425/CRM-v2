<?php

namespace App\Livewire\AdminHRDFAttendanceLog;

use App\Models\HrdfAttendanceLog;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HrdfAttLogNewTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

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

    public function getNewHrdfAttendanceLogs()
    {
        return HrdfAttendanceLog::where('status', 'new')
            ->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getNewHrdfAttendanceLogs())
            ->emptyState(fn() => view('components.empty-state-question'))
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
                    ->weight('bold'),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->weight('medium'),

                TextColumn::make('training_dates')
                    ->label('Training Dates')
                    ->getStateUsing(fn (HrdfAttendanceLog $record) => $record->training_dates)
                    ->wrap(),

                TextColumn::make('submittedByUser.name')
                    ->label('Submitted By')
                    ->sortable()
                    ->searchable()
                    ->default('N/A'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

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
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ActionGroup::make([
                    Action::make('accept')
                        ->label('Accept')
                        ->modalHeading(false)
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalWidth('xl')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('grant_id')
                                ->label('Grant ID')
                                ->required()
                                ->maxLength(50)
                                ->extraAlpineAttributes([
                                    'x-on:input' => '
                                        const start = $el.selectionStart;
                                        const end = $el.selectionEnd;
                                        $el.value = $el.value.toUpperCase();
                                        $el.setSelectionRange(start, end);
                                    '
                                ])
                                ->unique(HrdfAttendanceLog::class, 'grant_id', ignoreRecord: true)
                                ->validationMessages([
                                    'unique' => 'This Grant ID already exists in the system. Please use a different Grant ID.',
                                ])
                                ->helperText('Grant ID must be unique')
                                ->columnSpanFull(),

                            \Filament\Forms\Components\Select::make('salesperson_id')
                                ->label('SalesPerson Name')
                                ->required()
                                ->options(function () {
                                    // Get all users with role_id = 2 (salespersons) + user ID 5
                                    return \App\Models\User::where('role_id', 2)
                                        ->orWhere('id', 5)
                                        ->orderBy('name', 'asc')
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->columnSpanFull(),

                            \Filament\Forms\Components\FileUpload::make('documents')
                                ->label('JD14 Form + 3Days Attendance Log')
                                ->required()
                                ->multiple()
                                ->minFiles(4)
                                ->maxFiles(4)
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(10240) // 10MB per file
                                ->disk('public')
                                ->directory('hrdf-attendance-logs')
                                ->visibility('private')
                                ->downloadable()
                                ->openable()
                                ->reorderable()
                                ->columnSpanFull()
                        ])
                        ->action(function (HrdfAttendanceLog $record, array $data) {
                            if (!isset($data['documents']) || !is_array($data['documents']) || count($data['documents']) !== 4) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Please upload exactly 4 PDF files (1 JD14 Form + 3 Attendance Logs)')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // ✅ Get salesperson details
                            $salesperson = \App\Models\User::find($data['salesperson_id']);

                            if (!$salesperson) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Selected salesperson not found')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // ✅ Update the record - STORE ALL 4 FILES IN ONE COLUMN AS JSON
                            $record->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                                'salesperson_id' => $data['salesperson_id'],
                                'grant_id' => strtoupper($data['grant_id']),
                                'document_paths' => json_encode($data['documents']), // ✅ All 4 files in one column
                                'completed_by' => auth()->id(),
                            ]);

                            // ✅ Send email notification to salesperson
                            try {
                                $emailData = [
                                    'log' => $record,
                                    'salesperson' => $salesperson,
                                ];

                                Mail::send('emails.hrd_attendance_log_notification', $emailData, function($message) use ($salesperson, $record) {
                                    $message->to($salesperson->email)
                                            ->subject("HRDF ATTENDANCE LOG - {$record->company_name}");
                                });

                                $emailSent = true;
                            } catch (\Exception $e) {
                                Log::error('Failed to send HRDF Attendance Log email: ' . $e->getMessage());
                                $emailSent = false;
                            }

                            // ✅ Success notification
                            $message = "Log {$record->formatted_log_id} has been completed\n";
                            $message .= "Grant ID: {$data['grant_id']}\n";
                            $message .= "Salesperson: {$salesperson->name}";

                            if ($emailSent) {
                                $message .= "\n✅ Email sent to {$salesperson->email}";
                            } else {
                                $message .= "\n⚠️ Email notification failed";
                            }

                            Notification::make()
                                ->title('Log Completed Successfully')
                                ->body($message)
                                ->success()
                                ->send();

                            $this->dispatch('refresh-hrdf-att-log-tables');
                        }),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
            ])
            ->emptyStateHeading('No New HRDF Attendance Logs')
            ->emptyStateDescription('All new logs have been processed.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    #[On('refresh-hrdf-att-log-tables')]
    public function refresh(): void
    {
        // This method will be called when the event is dispatched
    }

    public function render()
    {
        return view('livewire.admin-h-r-d-f-attendance-log.hrdf-att-log-new-table');
    }
}
