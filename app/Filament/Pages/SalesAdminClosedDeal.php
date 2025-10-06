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
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class SalesAdminClosedDeal extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Closed Deals Analytics';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.sales-admin-closed-deal';

    public function getTableQuery(): Builder
    {
        // Define the 7 salespersons by getting their user IDs
        $salespersonNames = ['Muim', 'Yasmin', 'Farhanah', 'Joshua', 'Aziz', 'Bari', 'Vince'];

        $salespersonIds = User::where(function ($query) use ($salespersonNames) {
            foreach ($salespersonNames as $name) {
                $query->orWhere('name', 'like', '%' . $name . '%');
            }
        })->pluck('id')->toArray();

        return Lead::query()
            ->whereIn('lead_status', ['Closed'])
            ->whereIn('salesperson', $salespersonIds) // Filter by the 7 salespersons
            ->with(['companyDetail'])
            ->orderBy('closing_date', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('lead_owner')
                    ->label('Sales Admin')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if ($record->lead_owner) {
                            return $record->lead_owner ? $record->lead_owner: 'N/A';
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

                TextColumn::make('company_size_label')
                    ->label('COMPANY SIZE')
                    ->getStateUsing(function ($record) {
                        return $record->company_size_label ?? 'Unknown';
                    })
                    ->sortable(),

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
                                ->diffInWeekdays(Carbon::parse($record->closing_date)) . ' business days';
                        }
                        return 'N/A';
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sales_admin')
                    ->label('Sales Admin')
                    ->options(function () {
                        // Get only users with role_id = 2 (Sales Admin role)
                        return User::where('role_id', 1)
                            ->pluck('name', 'name'); // Use name as both key and value since lead_owner stores names
                    })
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            $query->where('lead_owner', $data['value']);
                        }
                    }),

                SelectFilter::make('sales_person')
                    ->label('Sales Person')
                    ->options(function () {
                        // Get only the 7 specific salespersons that are being displayed
                        $salespersonNames = ['Muim', 'Yasmin', 'Farhanah', 'Joshua', 'Aziz', 'Bari', 'Vince'];

                        return User::where(function ($query) use ($salespersonNames) {
                            foreach ($salespersonNames as $name) {
                                $query->orWhere('name', 'like', '%' . $name . '%');
                            }
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
                        '1-24' => 'Small',
                        '25-99' => 'Medium',
                        '100-500' => 'Large',
                        '501 and Above' => 'Enterprise',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            $query->where('company_size', $data['value']);
                        }
                    }),

                Filter::make('created_at')
                    ->form([
                        DateRangePicker::make('created_date_range')
                            ->label('Created Date Range')
                            ->placeholder('Select created date range'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (! empty($data['created_date_range'])) {
                            // Parse the date range from the "start - end" format
                            [$start, $end] = explode(' - ', $data['created_date_range']);

                            // Ensure valid dates
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                            // Apply the filter
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (! empty($data['created_date_range'])) {
                            // Parse the date range for display
                            [$start, $end] = explode(' - ', $data['created_date_range']);

                            return 'Created: '.Carbon::createFromFormat('d/m/Y', $start)->format('j M Y').
                                ' To: '.Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }

                        return null;
                    }),

                Filter::make('closed_at')
                    ->form([
                        DateRangePicker::make('closed_date_range')
                            ->label('Closed Date Range')
                            ->placeholder('Select closed date range'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (! empty($data['closed_date_range'])) {
                            // Parse the date range from the "start - end" format
                            [$start, $end] = explode(' - ', $data['closed_date_range']);

                            // Ensure valid dates
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                            // Apply the filter
                            $query->whereBetween('closing_date', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (! empty($data['closed_date_range'])) {
                            // Parse the date range for display
                            [$start, $end] = explode(' - ', $data['closed_date_range']);

                            return 'Closed: '.Carbon::createFromFormat('d/m/Y', $start)->format('j M Y').
                                ' To: '.Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }

                        return null;
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
