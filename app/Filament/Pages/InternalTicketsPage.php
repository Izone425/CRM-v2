<?php
// filepath: /var/www/html/timeteccrm/app/Filament/Pages/InternalTicketsPage.php

namespace App\Filament\Pages;

use App\Models\InternalTicket;
use App\Models\User;
use Filament\Pages\Page;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InternalTicketsPage extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $title = 'Internal Ticketing System';
    protected static ?string $navigationLabel = 'Internal Tickets';
    protected static ?string $navigationGroup = 'Administration';

    protected static string $view = 'filament.pages.internal-tickets-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(InternalTicket::query())
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
                            ->maxFiles(10),
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
                // Action::make('view_details')
                //     ->label('View Details')
                //     ->icon('heroicon-o-eye')
                //     ->color('info')
                //     ->modalHeading(false)
                //     ->modalWidth('4xl')
                //     ->modalSubmitAction(false)
                //     ->modalCancelActionLabel('Close')
                //     ->modalContent(function (InternalTicket $record): View {
                //         return view('filament.pages.ticket-details-modal')
                //             ->with('ticket', $record);
                //     }),

                Action::make('complete_ticket')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => ($record->status === 'pending' || $record->status === 'in_progress') && $record->attention_to === Auth::id()) // ✅ Compare with user ID
                    ->form([
                        Textarea::make('admin_remark')
                            ->label('Admin Remark')
                            ->rows(3)
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
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
                        $record->update([
                            'status' => 'completed',
                            'completed_by' => Auth::id(),
                            'completed_at' => now(),
                            'duration_minutes' => $record->created_at->diffInMinutes(now()),
                            'admin_remark' => $data['admin_remark'] ?? null,
                            'admin_attachments' => $data['admin_attachments'] ?? [],
                        ]);

                        Notification::make()
                            ->title('Ticket completed successfully')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Complete Ticket')
                    ->modalWidth(MaxWidth::Large),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }
}
