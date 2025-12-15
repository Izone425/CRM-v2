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
                            ->modalWidth('4xl')
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
                    ->dateTime('d/m/Y H:i')
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
                TextColumn::make('completedBy.name')
                    ->label('Completed By')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('completed_at')
                    ->label('Completed Date/Time')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '—';
                        $hours = intval($state / 60);
                        $minutes = $state % 60;
                        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                    })
                    ->placeholder('—'),
            ])
            ->headerActions([
                Action::make('create_ticket')
                    ->label('Create New Ticket')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('attention_to')
                            ->label('Attention To')
                            ->options(function () {
                                // ✅ Get users dynamically and return their IDs as values
                                return User::whereIn('name', [
                                    'Nur Irdina',
                                    'Fatimah Nurnabilah',
                                    'Norhaiyati'
                                ])->pluck('name', 'id')->toArray();
                            })
                            ->default(function () {
                                // ✅ Set default to Nur Irdina's user ID
                                $nurIrdina = User::where('name', 'Nur Irdina')->first();
                                return $nurIrdina ? $nurIrdina->id : null;
                            })
                            ->required()
                            ->searchable(),
                        Textarea::make('remark')
                            ->label('Remark')
                            ->required()
                            ->rows(4)
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
                        FileUpload::make('attachments')
                            ->label('Attachments')
                            ->multiple()
                            ->directory('internal-tickets')
                            ->maxFiles(10)
                            ->helperText('You can upload multiple files. No limit on file types.'),
                    ])
                    ->action(function (array $data): void {
                        InternalTicket::create([
                            'created_by' => Auth::id(),
                            'attention_to' => $data['attention_to'], // ✅ Now saves as user ID
                            'remark' => $data['remark'],
                            'attachments' => $data['attachments'] ?? [],
                        ]);

                        Notification::make()
                            ->title('Ticket created successfully')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Attention to Admin')
                    ->modalWidth(MaxWidth::Large),
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
                                ->maxFiles(10)
                                ->helperText('You can upload multiple files. No limit on file types.'),
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
