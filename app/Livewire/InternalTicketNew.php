<?php
namespace App\Livewire;

use App\Models\InternalTicket;
use App\Models\User;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail; // ✅ Add this import
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;

class InternalTicketNew extends Component implements HasTable, HasForms
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
        return view('livewire.internal-ticket-new');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(InternalTicket::query()->where('status', 'new'))
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
                        'info' => 'new',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_details')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(function (InternalTicket $record): View {
                            return view('filament.pages.ticket-details-modal')
                                ->with('ticket', $record);
                        }),
                    Action::make('complete_ticket')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'new')
                        ->form([
                            Textarea::make('admin_remark')
                                ->label('Admin Remark')
                                ->rows(3)
                                ->required()
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
                            FileUpload::make('admin_attachments')
                                ->label('Admin Attachments')
                                ->multiple()
                                ->directory('internal-tickets/admin')
                                ->maxFiles(10),
                        ])
                        ->action(function ($record, array $data): void {
                            // Update the ticket
                            $record->update([
                                'status' => 'completed',
                                'completed_by' => Auth::id(),
                                'completed_at' => now(),
                                'duration_minutes' => $record->created_at->diffInMinutes(now()),
                                'admin_remark' => $data['admin_remark'] ?? null,
                                'admin_attachments' => $data['admin_attachments'] ?? [],
                            ]);

                            // ✅ Send email directly using Mail::send like your example
                            try {
                                // Refresh the model to get updated relationships
                                $record->refresh();

                                // Calculate duration for email
                                $hours = intval($record->duration_minutes / 60);
                                $minutes = $record->duration_minutes % 60;
                                $durationFormatted = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";

                                // Send email using Mail::send
                                Mail::send('emails.ticket-completed', [
                                    'ticket' => $record,
                                    'ticketId' => $record->formatted_ticket_id,
                                    'createdByName' => $record->createdBy->name,
                                    'createdDate' => $record->created_at->format('d/m/Y H:i'),
                                    'attentionToName' => $record->attentionTo->name,
                                    'completedByName' => $record->completedBy->name,
                                    'completedDate' => $record->completed_at->format('d/m/Y H:i'),
                                    'duration' => $durationFormatted,
                                    'remark' => $record->remark,
                                    'adminRemark' => $record->admin_remark,
                                    'attachments' => $record->attachments ?? [],
                                    'adminAttachments' => $record->admin_attachments ?? [],
                                ], function ($message) use ($record) {
                                    $message->from(config('mail.from.address'), config('mail.from.name'))
                                            ->to($record->createdBy->email) // Send to ticket creator
                                            ->cc($record->attentionTo->email) // CC the attention_to person
                                            ->subject("INTERNAL TICKET | {$record->formatted_ticket_id} | COMPLETED");
                                });

                                Notification::make()
                                    ->title('Ticket completed successfully and email sent')
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Ticket completed successfully')
                                    ->body('However, there was an issue sending the email notification: ' . $e->getMessage())
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->modalHeading('Complete Ticket')
                        ->modalWidth(MaxWidth::Large),
                ])
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No New Tickets Found')
            ->emptyStateDescription('There are no pending tickets at the moment.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
