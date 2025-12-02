<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApolloLeadTracker extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Apollo Lead Tracker';
    protected static ?string $title = 'Apollo Lead Tracker';
    protected static string $view = 'filament.pages.apollo-lead-tracker';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('pickup_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('day_name')
                    ->label('Day')
                    ->getStateUsing(function ($record) {
                        return Carbon::parse($record->pickup_date)->format('l');
                    }),

                // ✅ Owner breakdown - using DATE() to match grouped dates
                Tables\Columns\TextColumn::make('jaja_count')
                    ->label('Jaja')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return Lead::where('lead_code', 'Apollo')
                            ->whereDate('pickup_date', $record->pickup_date)
                            ->where('lead_owner', 'Nurul Najaa Nadiah')
                            ->count();
                    })
                    ->color('secondary'),

                Tables\Columns\TextColumn::make('sheena_count')
                    ->label('Sheena')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return Lead::where('lead_code', 'Apollo')
                            ->whereDate('pickup_date', $record->pickup_date)
                            ->where('lead_owner', 'Sheena Liew')
                            ->count();
                    })
                    ->color('secondary'),

                Tables\Columns\TextColumn::make('total_daily')
                    ->label('Daily Total')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return Lead::where('lead_code', 'Apollo')
                            ->whereDate('pickup_date', $record->pickup_date)
                            ->count();
                    })
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('balance_leads')
                    ->label('Balance')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $totalApollo = Lead::where('lead_code', 'Apollo')->count();
                        $processedUpToDate = Lead::where('lead_code', 'Apollo')
                            ->whereDate('pickup_date', '<=', $record->pickup_date)
                            ->count();

                        return $totalApollo - $processedUpToDate;
                    })
                    ->color('danger'),
            ])
            ->defaultSort('pickup_date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->default(now()->subDays(30)),
                        \Filament\Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->where('pickup_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->where('pickup_date', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('lead_owner')
                    ->label('Filter by Owner')
                    ->options([
                        'Nurul Najaa Nadiah' => 'Jaja',
                        'Sheena Liew' => 'Sheena',
                    ])
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereExists(function ($subQuery) use ($data) {
                            $subQuery->select(DB::raw(1))
                                ->from('leads')
                                ->whereColumn('pickup_date', 'pickup_date')
                                ->where('leads.lead_code', 'Apollo')
                                ->whereIn('leads.lead_owner', $data['values']);
                        });
                    }),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    // ✅ Fix GROUP BY SQL mode compatibility
    protected function getTableQuery(): Builder
    {
        return Lead::fromSub(function ($query) {
            $query->selectRaw('
                    DATE(pickup_date) as pickup_date,
                    COUNT(*) as total_count
                ')
                ->from('leads')
                ->where('lead_code', 'Apollo')
                ->whereNotNull('pickup_date')
                ->groupByRaw('DATE(pickup_date)');
        }, 'grouped_leads')
        ->selectRaw('
            pickup_date,
            MD5(pickup_date) as unique_id,
            total_count,
            ROW_NUMBER() OVER (ORDER BY pickup_date DESC) as id
        ')
        ->orderBy('pickup_date', 'desc');
    }

    public function getTableRecordKey($record): string
    {
        return $record->unique_id ?? md5($record->pickup_date ?? 'default');
    }
}
