<?php
// filepath: /var/www/html/timeteccrm/app/Filament/Pages/SalesAdminClosedDeal.php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\User;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class SalesAdminClosedDeal extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Closed Deals Analytics';
    protected static ?string $title = 'Sales Admin - Closed Deals';
    protected static string $view = 'filament.pages.sales-admin-closed-deal';

    public function getTableQuery(): Builder
    {
        return Lead::query()
            ->whereIn('lead_status', ['Closed'])
            ->whereNotNull('closing_date')
            ->with(['quotations.quotationDetails', 'companyDetail'])
            ->orderBy('closing_date', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('sales_admin_name')
                    ->label('Sales Admin')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if ($record->salesperson) {
                            $user = User::find($record->salesperson);
                            if ($user && $user->id) {
                                $admin = User::find($user->id);
                                return $admin ? $admin->name : 'N/A';
                            }
                        }
                        return 'N/A';
                    }),

                TextColumn::make('sales_person_name')
                    ->label('Sales Person')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if ($record->salesperson) {
                            $user = User::find($record->salesperson);
                            return $user ? $user->name : 'N/A';
                        }
                        return 'N/A';
                    }),

                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->default('N/A'),

                TextColumn::make('company_size')
                    ->label('Company Size')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Small' => 'success',
                        'Medium' => 'warning',
                        'Large' => 'danger',
                        default => 'gray',
                    })
                    ->default('N/A'),

                TextColumn::make('created_at')
                    ->label('Leads Created Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('closing_date')
                    ->label('Leads Closed Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('total_days')
                    ->label('Total Days')
                    ->getStateUsing(function ($record) {
                        if ($record->closing_date && $record->created_at) {
                            return Carbon::parse($record->created_at)
                                ->diffInDays(Carbon::parse($record->closing_date)) . ' days';
                        }
                        return 'N/A';
                    })
                    ->sortable(),

                TextColumn::make('pi_amount')
                    ->label('Proforma Invoice Amount')
                    ->getStateUsing(function ($record) {
                        // Get total from quotation_details table
                        $totalAmount = 0;

                        foreach ($record->quotations as $quotation) {
                            if (in_array($quotation->status, ['Approved', 'Sent', 'Accepted'])) {
                                $totalAmount += $quotation->quotationDetails()
                                    ->sum('total_before_tax');
                            }
                        }

                        return 'RM ' . number_format($totalAmount, 2);
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sales_admin')
                    ->label('Sales Admin')
                    ->options(function () {
                        // Get all users who are admins (have leads assigned to their subordinates)
                        $adminIds = User::whereIn('id', function ($query) {
                            $query->select('id')
                                  ->from('users')
                                  ->whereNotNull('id');
                        })->pluck('name', 'id');

                        return $adminIds;
                    })
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            $query->whereIn('salesperson', function ($subQuery) use ($data) {
                                $subQuery->select('id')
                                        ->from('users')
                                        ->where('id', $data['value']);
                            });
                        }
                    }),

                SelectFilter::make('sales_person')
                    ->label('Sales Person')
                    ->options(function () {
                        // Get all users who are assigned to leads
                        return User::whereIn('id', function ($query) {
                            $query->select('salesperson')
                                  ->from('leads')
                                  ->whereNotNull('salesperson');
                        })->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            $query->where('salesperson', $data['value']);
                        }
                    }),

                SelectFilter::make('company_size')
                    ->label('Company Size')
                    ->options([
                        'Small' => 'Small',
                        'Medium' => 'Medium',
                        'Large' => 'Large',
                    ]),

                Filter::make('created_at')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('created_from')
                                    ->label('Created From'),
                                DatePicker::make('created_until')
                                    ->label('Created Until'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Filter::make('closed_at')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('closed_from')
                                    ->label('Closed From'),
                                DatePicker::make('closed_until')
                                    ->label('Closed Until'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['closed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('closing_date', '>=', $date),
                            )
                            ->when(
                                $data['closed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('closing_date', '<=', $date),
                            );
                    }),
            ])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100])
            ->defaultSort('closing_date', 'desc');
    }

    public function getSalespersonStats()
    {
        $salespeople = ['Muim', 'Yasmin', 'Farhanah', 'Joshua', 'Aziz', 'Bari', 'Vince'];
        $stats = [];

        foreach ($salespeople as $salesperson) {
            // Get user ID by name
            $userId = User::where('name', 'like', '%' . $salesperson . '%')->first()?->id;

            if ($userId) {
                $totalLeads = Lead::where('salesperson', $userId)->count();

                $closedLeads = Lead::where('salesperson', $userId)
                    ->whereIn('lead_status', ['Closed'])
                    ->whereNotNull('closing_date')
                    ->count();

                $stats[] = [
                    'name' => $salesperson,
                    'total_leads' => $totalLeads,
                    'closed_leads' => $closedLeads,
                    'conversion_rate' => $totalLeads > 0 ? round(($closedLeads / $totalLeads) * 100, 1) : 0,
                ];
            } else {
                $stats[] = [
                    'name' => $salesperson,
                    'total_leads' => 0,
                    'closed_leads' => 0,
                    'conversion_rate' => 0,
                ];
            }
        }

        return $stats;
    }

    public function getOverallStats()
    {
        // Calculate total amount from quotation_details for closed leads
        $totalAmount = QuotationDetail::whereHas('quotation', function ($query) {
            $query->whereHas('lead', function ($leadQuery) {
                $leadQuery->whereIn('lead_status', ['Closed']);
            })
            ->whereIn('status', ['Approved', 'Sent', 'Accepted']);
        })->sum('total_before_tax');

        return [
            'total_leads' => Lead::count(),
            'total_closed' => Lead::whereIn('lead_status', ['Closed'])->count(),
            'total_amount' => $totalAmount,
        ];
    }
}
