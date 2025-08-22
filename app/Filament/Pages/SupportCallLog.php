<?php
namespace App\Filament\Pages;

use App\Models\CallLog;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\DB;

class SupportCallLog extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $title = '';

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Call Logs';

    protected static ?string $slug = 'call-logs';

    protected static ?int $navigationSort = 85;

    protected static ?string $navigationGroup = 'Communication';

    protected static string $view = 'filament.pages.support-call-log';

    public function getReceptionCalls(): Builder
    {
        $query = CallLog::query()
            ->where(function ($query) {
                // Condition 1: Caller is Reception or Najwa
                $query->whereIn('caller_number', ['100', '323', '324', '333', '343']);
            });

        // Map both receiver and caller numbers to specific staff names
        $query->addSelect([
            '*',
            DB::raw("CASE
                -- Map receiver numbers to staff names
                WHEN caller_number = '323' THEN 'Ummu Najwa Fajrina'
                WHEN caller_number = '324' THEN 'Siti Nadia'
                WHEN caller_number = '333' THEN 'Noor Syazana'
                WHEN caller_number = '343' THEN 'Rahmah'

                -- When caller is 100 (reception), use receiver's name instead
                WHEN caller_number = '100' AND receiver_number = '323' THEN 'Ummu Najwa Fajrina'
                WHEN caller_number = '100' AND receiver_number = '324' THEN 'Siti Nadia'
                WHEN caller_number = '100' AND receiver_number = '333' THEN 'Noor Syazana'
                WHEN caller_number = '100' AND receiver_number = '343' THEN 'Rahmah'

                ELSE receiver_number
            END as staff_name")
        ]);

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getReceptionCalls())
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->sortable(),

                TextColumn::make('staff_name')
                    ->label('Support')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('started_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('started_at_time')
                    ->label('Start Time')
                    ->state(function ($record) {
                        return $record->started_at ? date('H:i:s', strtotime($record->started_at)) : null;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('started_at', $direction);
                    }),

                TextColumn::make('end_at')
                    ->label('End Time')
                    ->formatStateUsing(fn ($state) => $state ? date('H:i:s', strtotime($state)) : '-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('call_duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $this->formatDuration($state))
                    ->sortable(),

                TextColumn::make('call_type')
                    ->label('Call Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'internal' => 'success',
                        'outgoing' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('task_status')
                    ->label('Task Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Pending' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('tier1_category_id')
                    ->label('Module')
                    ->formatStateUsing(function ($record) {
                        return $record->tier1Category ? $record->tier1Category->name : '—';
                    })
                    ->sortable(),

                // TextColumn::make('tier2_category_id')
                //     ->label('Main Category')
                //     ->formatStateUsing(function ($record) {
                //         return $record->tier2Category ? $record->tier2Category->name : '—';
                //     })
                //     ->sortable()
                //     ->toggleable(),

                // TextColumn::make('tier3_category_id')
                //     ->label('Sub Category')
                //     ->formatStateUsing(function ($record) {
                //         return $record->tier3Category ? $record->tier3Category->name : '—';
                //     })
                //     ->sortable()
                //     ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Support')
                    ->options(User::pluck('name', 'id')),

                SelectFilter::make('call_type')
                    ->options([
                        'internal' => 'internal',
                        'outgoing' => 'OutGoing',
                    ]),

                SelectFilter::make('call_status')
                    ->options([
                        'Completed' => 'Completed',
                        'Pending' => 'Pending',
                    ]),

                Filter::make('started_at')
                    ->form([
                        DateTimePicker::make('from'),
                        DateTimePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->color('secondary')
                        ->icon('heroicon-o-eye')
                        ->visible(fn() => auth()->user()->role_id == 3) // Only visible to admins
                        ->modalHeading('')
                        ->modalContent(function (CallLog $record) {
                            return Infolist::make()
                                ->record($record)
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('question')
                                        ->label('Question')
                                        ->formatStateUsing(fn ($state) => nl2br($state))
                                        ->html()
                                        ->columnSpanFull(),
                                    // Add other fields you want to display
                                ]);
                        })
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false),
                    Action::make('submit')
                        ->label('Submit ')
                        ->color('success')
                        ->icon('heroicon-o-paper-airplane')
                        ->visible(function (CallLog $record) {
                            if ($record->task_status !== 'Pending') {
                                return false;
                            }

                            if (auth()->user()->role_id == 3) {
                                return true;
                            }

                            return $record->staff_name === auth()->user()->name;
                        })
                        ->form([
                            Select::make('tier1_category_id')
                                ->label('Module (Tier 1)')
                                ->options(function () {
                                    return \App\Models\CallCategory::where('tier', '1')
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(fn (callable $set) => $set('tier2_category_id', null))
                                ->afterStateUpdated(fn (callable $set) => $set('tier3_category_id', null)),

                            // Select::make('tier2_category_id')
                            //     ->label('Main Category (Tier 2)')
                            //     ->options(function (callable $get) {
                            //         $tier1Id = $get('tier1_category_id');
                            //         if (!$tier1Id) {
                            //             return [];
                            //         }

                            //         return \App\Models\CallCategory::where('tier', '2')
                            //             ->where('parent_id', $tier1Id)
                            //             ->where('is_active', true)
                            //             ->pluck('name', 'id');
                            //     })
                            //     ->searchable()
                            //     ->reactive()
                            //     ->afterStateUpdated(fn (callable $set) => $set('tier3_category_id', null))
                            //     ->visible(fn (callable $get) => (bool) $get('tier1_category_id')),

                            // Select::make('tier3_category_id')
                            //     ->label('Sub Category (Tier 3)')
                            //     ->options(function (callable $get) {
                            //         $tier2Id = $get('tier2_category_id');
                            //         if (!$tier2Id) {
                            //             return [];
                            //         }

                            //         return \App\Models\CallCategory::where('tier', '3')
                            //             ->where('parent_id', $tier2Id)
                            //             ->where('is_active', true)
                            //             ->pluck('name', 'id');
                            //     })
                            //     ->searchable()
                            //     ->visible(fn (callable $get) => (bool) $get('tier2_category_id')),

                            // Select::make('task_status')
                            //     ->label('Task Status')
                            //     ->options([
                            //         'Pending' => 'Pending',
                            //         'Completed' => 'Completed',
                            //     ])
                            //     ->searchable()
                            //     ->default('Pending'),

                            Textarea::make('question')
                                ->label('Question')
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
                                ->columnSpanFull(),
                        ])
                        ->action(function (CallLog $record, array $data): void {
                            $data['task_status'] = 'Completed';

                            $record->update($data);

                            Notification::make()
                                ->title('Call log updated successfully')
                                ->success()
                                ->send();
                        })
                ])
            ])
            ->defaultSort('started_at', 'desc');
    }

    public function formatDuration($seconds)
    {
        if (!$seconds) return '-';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
        }

        return sprintf("%02d:%02d", $minutes, $secs);
    }
}
