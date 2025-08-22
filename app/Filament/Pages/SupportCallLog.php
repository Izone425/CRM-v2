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
            ->where('caller_name', 'Reception');

        // Map receiver numbers to specific staff names
        $query->addSelect([
            '*',
            DB::raw("CASE
                WHEN receiver_number = '323' THEN 'Ummu Najwa Fajrina'
                WHEN receiver_number = '324' THEN 'Siti Nadia'
                WHEN receiver_number = '333' THEN 'Noor Syazana'
                WHEN receiver_number = '343' THEN 'Rahmah'
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
                        'OutComing' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('task_status')
                    ->label('Task Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Pending' => 'warning',
                        'Missed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('tier1_category_id')
                    ->label('Module')
                    ->formatStateUsing(function ($record) {
                        return $record->tier1Category ? $record->tier1Category->name : '—';
                    })
                    ->sortable(),

                TextColumn::make('tier2_category_id')
                    ->label('Main Category')
                    ->formatStateUsing(function ($record) {
                        return $record->tier2Category ? $record->tier2Category->name : '—';
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tier3_category_id')
                    ->label('Sub Category')
                    ->formatStateUsing(function ($record) {
                        return $record->tier3Category ? $record->tier3Category->name : '—';
                    })
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Support')
                    ->options(User::pluck('name', 'id')),

                SelectFilter::make('call_type')
                    ->options([
                        'InComing' => 'InComing',
                        'OutComing' => 'OutComing',
                    ]),

                SelectFilter::make('call_status')
                    ->options([
                        'Completed' => 'Completed',
                        'Pending' => 'Pending',
                        'Missed' => 'Missed',
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
                    ViewAction::make()
                        ->infolist([
                            \Filament\Infolists\Components\TextEntry::make('question')
                                ->label('Call Question')
                                ->formatStateUsing(fn ($state) => nl2br($state)) // Convert newlines to <br> tags
                                ->html() // Allow HTML rendering
                                ->columnSpanFull(),
                        ])
                        ->modalWidth('3xl'),
                    EditAction::make()
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

                            Select::make('tier2_category_id')
                                ->label('Main Category (Tier 2)')
                                ->options(function (callable $get) {
                                    $tier1Id = $get('tier1_category_id');
                                    if (!$tier1Id) {
                                        return [];
                                    }

                                    return \App\Models\CallCategory::where('tier', '2')
                                        ->where('parent_id', $tier1Id)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(fn (callable $set) => $set('tier3_category_id', null))
                                ->visible(fn (callable $get) => (bool) $get('tier1_category_id')),

                            Select::make('tier3_category_id')
                                ->label('Sub Category (Tier 3)')
                                ->options(function (callable $get) {
                                    $tier2Id = $get('tier2_category_id');
                                    if (!$tier2Id) {
                                        return [];
                                    }

                                    return \App\Models\CallCategory::where('tier', '3')
                                        ->where('parent_id', $tier2Id)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->visible(fn (callable $get) => (bool) $get('tier2_category_id')),

                            Select::make('task_status')
                                ->label('Task Status')
                                ->options([
                                    'Pending' => 'Pending',
                                    'Completed' => 'Completed',
                                ])
                                ->searchable()
                                ->default('Pending'),

                            Textarea::make('question')
                                ->label('Call Question')
                                ->nullable()
                                ->columnSpanFull(),
                        ])
                ])
            ])
            ->recordUrl(null)
            ->defaultSort('id', 'desc');
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
