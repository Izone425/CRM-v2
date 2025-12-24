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
use Illuminate\Support\Facades\Cache;

class ApolloLeadTracker extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Apollo Lead Tracker';
    protected static ?string $title = 'Apollo Lead Tracker';
    protected static string $view = 'filament.pages.apollo-lead-tracker';

    // Cache for total Apollo leads count
    protected $totalApolloLeads = null;

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

                Tables\Columns\TextColumn::make('jaja_count')
                    ->label('Jaja')
                    ->alignCenter()
                    ->color('secondary'),

                Tables\Columns\TextColumn::make('sheena_count')
                    ->label('Sheena')
                    ->alignCenter()
                    ->color('secondary'),

                Tables\Columns\TextColumn::make('total_daily')
                    ->label('Daily Total')
                    ->alignCenter()
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('target')
                    ->label('Target')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return $record->target;
                    })
                    ->color(function ($record) {
                        return $record->target < 0 ? 'danger' : 'gray';
                    })
                    ->weight(function ($record) {
                        return $record->target < 0 ? 'bold' : 'normal';
                    }),

                Tables\Columns\TextColumn::make('balance_leads')
                    ->label('Balance')
                    ->alignCenter()
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

                        return $query->whereIn('has_owner_data', $data['values']);
                    }),
            ])
            ->striped()
            ->paginated([50]);
    }

    // ✅ Optimized query with pre-calculated aggregations
    protected function getTableQuery(): Builder
    {
        // Get total Apollo leads count (cached for 5 minutes)
        $this->totalApolloLeads = Cache::remember('total_apollo_leads', 300, function () {
            return Lead::where('lead_code', 'Apollo')->count();
        });

        return Lead::fromSub(function ($query) {
            $query->selectRaw('
                    DATE(pickup_date) as pickup_date,
                    SUM(CASE WHEN lead_owner = "Nurul Najaa Nadiah" THEN 1 ELSE 0 END) as jaja_count,
                    SUM(CASE WHEN lead_owner = "Sheena Liew" THEN 1 ELSE 0 END) as sheena_count,
                    COUNT(*) as total_daily,
                    (SUM(CASE WHEN lead_owner = "Nurul Najaa Nadiah" THEN 1 ELSE 0 END) + 
                     SUM(CASE WHEN lead_owner = "Sheena Liew" THEN 1 ELSE 0 END) - 100) as target,
                    GROUP_CONCAT(DISTINCT lead_owner) as has_owner_data
                ')
                ->from('leads')
                ->where('lead_code', 'Apollo')
                ->whereNotNull('pickup_date')
                ->groupByRaw('DATE(pickup_date)');
        }, 'grouped_leads')
        ->selectRaw('
            pickup_date,
            jaja_count,
            sheena_count, 
            total_daily,
            target,
            has_owner_data,
            ? - (
                SELECT COUNT(*) 
                FROM leads l2 
                WHERE l2.lead_code = "Apollo" 
                AND DATE(l2.pickup_date) <= grouped_leads.pickup_date
            ) as balance_leads,
            MD5(pickup_date) as unique_id,
            ROW_NUMBER() OVER (ORDER BY pickup_date DESC) as id
        ', [$this->totalApolloLeads])
        ->orderBy('pickup_date', 'desc');
    }

    public function getTableRecordKey($record): string
    {
        return $record->unique_id ?? md5($record->pickup_date ?? 'default');
    }
}
