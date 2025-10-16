<?php

namespace App\Filament\Pages;

use App\Models\CompanyDetail;
use App\Models\FinanceHandover;
use App\Models\HardwareHandoverV2;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class FinanceHandoverList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Finance Handover List';
    protected static ?string $title = 'Finance Handover';
    protected static ?string $navigationGroup = 'Handovers';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.finance-handover-list';

    public function table(Table $table): Table
    {
        return $table
            ->query(FinanceHandover::query()->with(['lead.companyDetail', 'reseller']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, FinanceHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        // Get the year from created_at, fallback to current year if null
                        $year = $record->created_at ? $record->created_at->format('y') : now()->format('y');

                        return 'FN_' . $year . str_pad($record->id, 4, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(),

                // Fix the salesperson column
                TextColumn::make('salesperson')
                    ->label('SALESPERSON')
                    ->getStateUsing(function (FinanceHandover $record) {
                        // First try to get the salesperson user relationship
                        if ($record->lead->salespersonUser) {
                            return $record->lead->salespersonUser->name;
                        }

                        // If that doesn't work, return the salesperson field directly (if it's stored as name)
                        if ($record->lead->salesperson) {
                            return $record->lead->salesperson;
                        }

                        return 'Unknown';
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->getStateUsing(function (FinanceHandover $record) {
                        // Get company name from the lead's company detail relationship
                        if ($record->lead && $record->lead->companyDetail) {
                            return $record->lead->companyDetail->company_name;
                        }

                        // Fallback to lead name if no company detail
                        if ($record->lead && $record->lead->name) {
                            return $record->lead->name;
                        }

                        return 'Unknown Company';
                    })
                    ->formatStateUsing(function ($state, FinanceHandover $record) {
                        if (!$state || $state === 'Unknown Company') {
                            return $state;
                        }

                        // Create clickable link to lead
                        if ($record->lead && $record->lead->id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . e($state) . '
                                </a>');
                        }

                        return $state;
                    })
                    ->html(),

                TextColumn::make('hardware_handovers')
                    ->label('HW ID')
                    ->getStateUsing(function (FinanceHandover $record) {
                        if (!$record->related_hardware_handovers) {
                            return 'N/A';
                        }

                        $handoverIds = is_string($record->related_hardware_handovers)
                            ? json_decode($record->related_hardware_handovers, true)
                            : $record->related_hardware_handovers;

                        if (!is_array($handoverIds) || empty($handoverIds)) {
                            return 'N/A';
                        }

                        $formattedIds = [];
                        foreach ($handoverIds as $id) {
                            // Get the hardware handover to check its creation date
                            $hw = \App\Models\HardwareHandoverV2::find($id);
                            $hwYear = $hw && $hw->created_at ? $hw->created_at->format('y') : now()->format('y');

                            $formattedIds[] = 'HW_' . $hwYear . str_pad($id, 4, '0', STR_PAD_LEFT);
                        }

                        return implode(', ', $formattedIds);
                    })
                    ->wrap(),

                TextColumn::make('reseller.company_name')
                    ->label('RESELLER COMPANY NAME')
                    ->sortable()
                    ->searchable()
                    ->default('Unknown'),

                TextColumn::make('submitted_at')
                    ->label('DATE SUBMIT')
                    ->date('d M Y')
                    ->sortable()
                    ->default('Not submitted'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'Completed' => 'Completed',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(false)
                    ->modalWidth('4xl')
                    ->modalContent(function (FinanceHandover $record) {
                        return view('components.finance-handover-details', [
                            'record' => $record
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('60s'); // Auto refresh every 60 seconds
    }
}
