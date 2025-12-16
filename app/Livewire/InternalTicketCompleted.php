<?php

namespace App\Livewire;

use App\Models\InternalTicket;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;

class InternalTicketCompleted extends Component implements HasTable, HasForms
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

    #[On('refresh-hardwarehandover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function render()
    {
        return view('livewire.internal-ticket-completed');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(InternalTicket::query()->where('status', 'completed'))
            ->columns([
                TextColumn::make('formatted_ticket_id')
                    ->label('Ticket ID')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->action(
                        Action::make('viewTicketDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->modalContent(function (InternalTicket $record): View {
                                return view('filament.pages.ticket-details-modal')
                                    ->with('ticket', $record);
                            })
                    ),
                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created Date/Time')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('attentionTo.name')
                    ->label('Attention To')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'in_progress',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('completedBy.name')
                    ->label('Completed By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Completed Date/Time')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '—';
                        $hours = intval($state / 60);
                        $minutes = $state % 60;
                        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                    })
                    ->sortable(),
            ])
            ->defaultSort('completed_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No Completed Tickets Found')
            ->emptyStateDescription('There are no completed tickets at the moment.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
