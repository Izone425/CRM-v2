<?php

namespace App\Filament\Pages;

use App\Models\HardwareHandover;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Illuminate\View\View;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class HardwareDashboardAll extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Hardware Dashboard';
    protected static ?string $title = 'Dashboard - All';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.hardware-dashboard-all';

    public function getTableQuery(): Builder
    {
        $query = HardwareHandover::query()
            ->whereIn('status', ['Completed', 'Pending Stock', 'Pending Migration', 'Completed: Installation', 'Completed: Courier', 'Completed Migration'])
            ->orderBy('created_at', 'desc');

        // if (auth()->user()->role_id === 2) {
        //     $userId = auth()->id();
        //     $query->whereHas('lead', function ($leadQuery) use ($userId) {
        //         $leadQuery->where('salesperson', $userId);
        //     });
        // }

        return $query;
    }

    public function getDeviceCount(string $columnName): int
    {
        $query = HardwareHandover::query()
            ->whereIn('status', ['Completed', 'Pending Stock', 'Pending Migration', 'Completed: Installation', 'Completed: Courier', 'Completed Migration']);

        // Apply salesperson filter for sales users
        // if (auth()->user()->role_id === 2) {
        //     $userId = auth()->id();
        //     $query->whereHas('lead', function ($leadQuery) use ($userId) {
        //         $leadQuery->where('salesperson', $userId);
        //     });
        // }

        // Sum the quantities
        return $query->sum($columnName) ?? 0;
    }

    public function getHandoverCountByStatus(string $status): int
    {
        $query = HardwareHandover::query()
            ->where('status', $status);

        // Apply salesperson filter for sales users
        // if (auth()->user()->role_id === 2) {
        //     $userId = auth()->id();
        //     $query->whereHas('lead', function ($leadQuery) use ($userId) {
        //         $leadQuery->where('salesperson', $userId);
        //     });
        // }

        return $query->count();
    }

    /**
     * Get the total count of all handovers
     *
     * @return int
     */
    public function getTotalHandoverCount(): int
    {
        $query = HardwareHandover::query();

        // Apply salesperson filter for sales users
        // if (auth()->user()->role_id === 2) {
        //     $userId = auth()->id();
        //     $query->whereHas('lead', function ($leadQuery) use ($userId) {
        //         $leadQuery->where('salesperson', $userId);
        //     });
        // }

        return $query->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HardwareHandover::query()
                    ->whereIn('status', ['Completed', 'Pending Stock', 'Pending Migration', 'Completed: Installation', 'Completed: Courier', 'Completed Migration'])
                    // ->when(auth()->user()->role_id === 2, function ($query) {
                    //     $userId = auth()->id();
                    //     $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    //         $leadQuery->where('salesperson', $userId);
                    //     });
                    // })
                    ->orderBy('created_at', 'desc')
            )
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50,])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting logic that uses the raw ID value
                        return $query->orderBy('id', $direction);
                    })
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandover $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.companyDetail.company_name')
                    ->searchable()
                    ->label('Company Name')
                    ->url(function ($state, $record) {
                        if ($record->lead && $record->lead->id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                            return url('admin/leads/' . $encryptedId);
                        }

                        return null;
                    })
                    ->openUrlInNewTab()
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            return strtoupper(Str::limit($state, 30, '...'));
                        }

                        if ($record->lead && $record->lead->companyDetail) {
                            return strtoupper(Str::limit($record->lead->companyDetail->company_name, 30, '...'));
                        }

                        return $record->company_name ? strtoupper(Str::limit($record->company_name, 30, '...')) : '-';
                    })
                    ->color(function ($record) {
                        if ($record->lead && $record->lead->companyDetail) {
                            return Color::hex('#338cf0');
                        }

                        return Color::hex("#000000");
                    }),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    }),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->toggleable(),

                TextColumn::make('tc10_quantity')
                    ->label('TC10')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tc20_quantity')
                    ->label('TC20')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('face_id5_quantity')
                    ->label('FACE ID 5')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('face_id6_quantity')
                    ->label('FACE ID 6')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('time_beacon_quantity')
                    ->label('TIME BEACON')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nfc_tag_quantity')
                    ->label('NFC TAG')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Date Submit')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('pending_stock_at')
                    ->label(new HtmlString('Date<br>Pending Stock'))
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('pending_migration_at')
                    ->label(new HtmlString('Date<br>Pending Migration'))
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label(new HtmlString('Date<br>Completed'))
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('created_at')
                ->form([
                    DateRangePicker::make('date_range')
                        ->label('')
                        ->placeholder('Select date range'),
                ])
                ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                    if (!empty($data['date_range'])) {
                        // Parse the date range from the "start - end" format
                        [$start, $end] = explode(' - ', $data['date_range']);

                        // Ensure valid dates
                        $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                        $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                        // Apply the filter
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                })
                ->indicateUsing(function (array $data) {
                    if (!empty($data['date_range'])) {
                        // Parse the date range for display
                        [$start, $end] = explode(' - ', $data['date_range']);

                        return 'From: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                            ' To: ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                    }
                    return null;
                }),
            ]);
    }
}
