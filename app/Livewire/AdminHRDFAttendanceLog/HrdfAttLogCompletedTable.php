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

class HrdfAttLogCompletedTable extends Component implements HasTable, HasForms
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

    public function getCompletedHrdfAttendanceLogs()
    {
        return HrdfAttendanceLog::where('status', 'completed');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getCompletedHrdfAttendanceLogs())
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
            ->defaultSort('created_at', 'desc');
    }

    #[On('refresh-hrdf-att-log-tables')]
    public function refresh(): void
    {
        // This method will be called when the event is dispatched
    }

    public function render()
    {
        return view('livewire.admin-h-r-d-f-attendance-log.hrdf-att-log-completed-table');
    }
}
