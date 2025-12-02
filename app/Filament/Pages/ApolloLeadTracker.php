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

                Tables\Columns\TextColumn::make('jaja_count')
                    ->label('Jaja')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return Lead::where('lead_code', 'Apollo')
                            ->where('pickup_date', $record->pickup_date)
                            ->where('lead_owner', 'Jaja')
                            ->count();
                    }),

                Tables\Columns\TextColumn::make('sheena_count')
                    ->label('Sheena')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return Lead::where('lead_code', 'Apollo')
                            ->where('pickup_date', $record->pickup_date)
                            ->where('lead_owner', 'Sheena')
                            ->count();
                    }),

                Tables\Columns\TextColumn::make('balance_leads')
                    ->label('Balance Leads')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        // Get total Apollo leads for this date
                        $totalForDate = Lead::where('lead_code', 'Apollo')
                            ->where('pickup_date', $record->pickup_date)
                            ->count();

                        // Get running total up to this date
                        $runningTotal = Lead::where('lead_code', 'Apollo')
                            ->where('pickup_date', '<=', $record->pickup_date)
                            ->count();

                        // Calculate balance (this is a simplified calculation)
                        // You might want to adjust this logic based on your business needs
                        $totalApollo = Lead::where('lead_code', 'Apollo')->count();
                        $processedUpToDate = Lead::where('lead_code', 'Apollo')
                            ->where('pickup_date', '<', $record->pickup_date)
                            ->count();

                        return $totalApollo - $processedUpToDate;
                    }),

                Tables\Columns\TextColumn::make('total_daily')
                    ->label('Daily Total')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return Lead::where('lead_code', 'Apollo')
                            ->where('pickup_date', $record->pickup_date)
                            ->count();
                    })
                    ->color('success')
                    ->weight('bold'),
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
                    })
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    protected function getTableQuery(): Builder
    {
        // Get distinct pickup dates for Apollo leads
        return Lead::select('pickup_date')
            ->where('lead_code', 'Apollo')
            ->whereNotNull('pickup_date')
            ->distinct()
            ->orderBy('pickup_date', 'desc');
    }
}
