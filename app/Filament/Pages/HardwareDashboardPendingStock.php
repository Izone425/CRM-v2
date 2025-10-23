<?php

namespace App\Filament\Pages;

use App\Models\HardwareHandoverV2;
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
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class HardwareDashboardPendingStock extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Hardware Dashboard';
    protected static ?string $title = 'Dashboard - Pending Stocks';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.hardware-dashboard-all';

    public function getTableQuery(): Builder
    {
        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Pending Stock'])
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
        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Pending Stock']);

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
        $query = HardwareHandoverV2::query()
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
        $query = HardwareHandoverV2::query();

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
                HardwareHandoverV2::query()
                    ->whereIn('status', ['Pending Stock'])
                    // ->when(auth()->user()->role_id === 2, function ($query) {
                    //     $userId = auth()->id();
                    //     $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    //         $leadQuery->where('salesperson', $userId);
                    //     });
                    // })
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandoverV2 $record) {
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
                            ->modalHeading(false)
                            ->modalWidth('6xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandoverV2 $record): View {
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
                    ->getStateUsing(function (HardwareHandoverV2 $record) {
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
                    ->toggleable(),

                TextColumn::make('tc20_quantity')
                    ->label('TC20')
                    ->numeric(0)
                    ->toggleable(),

                TextColumn::make('face_id5_quantity')
                    ->label('FACE ID 5')
                    ->numeric(0)
                    ->toggleable(),

                TextColumn::make('face_id6_quantity')
                    ->label('FACE ID 6')
                    ->numeric(0)
                    ->toggleable(),

                TextColumn::make('time_beacon_quantity')
                    ->label('TIME BEACON')
                    ->numeric(0)
                    ->toggleable(),

                TextColumn::make('nfc_tag_quantity')
                    ->label('NFC TAG')
                    ->numeric(0)
                    ->toggleable(),
            ])
            ->filters([
                // Existing Date Range Filter
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

                // F1 - BY DEVICE MODEL
                Filter::make('device_model')
                    ->label('Device Model')
                    ->form([
                        \Filament\Forms\Components\CheckboxList::make('device_models')
                            ->label('')
                            ->options([
                                'tc10' => 'TC10',
                                'tc20' => 'TC20',
                                'face_id5' => 'FACE ID 5',
                                'face_id6' => 'FACE ID 6',
                                'time_beacon' => 'TIME BEACON',
                                'nfc_tag' => 'NFC TAG',
                            ])
                            ->columns(2)
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['device_models'])) {
                            $query->where(function (Builder $query) use ($data) {
                                foreach ($data['device_models'] as $device) {
                                    switch ($device) {
                                        case 'tc10':
                                            $query->orWhere('tc10_quantity', '>', 0);
                                            break;
                                        case 'tc20':
                                            $query->orWhere('tc20_quantity', '>', 0);
                                            break;
                                        case 'face_id5':
                                            $query->orWhere('face_id5_quantity', '>', 0);
                                            break;
                                        case 'face_id6':
                                            $query->orWhere('face_id6_quantity', '>', 0);
                                            break;
                                        case 'time_beacon':
                                            $query->orWhere('time_beacon_quantity', '>', 0);
                                            break;
                                        case 'nfc_tag':
                                            $query->orWhere('nfc_tag_quantity', '>', 0);
                                            break;
                                    }
                                }
                            });
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (!empty($data['device_models'])) {
                            $deviceNames = [
                                'tc10' => 'TC10',
                                'tc20' => 'TC20',
                                'face_id5' => 'FACE ID 5',
                                'face_id6' => 'FACE ID 6',
                                'time_beacon' => 'TIME BEACON',
                                'nfc_tag' => 'NFC TAG',
                            ];

                            $selectedDevices = array_map(function ($device) use ($deviceNames) {
                                return $deviceNames[$device] ?? $device;
                            }, $data['device_models']);

                            return 'Device Model: ' . implode(', ', $selectedDevices);
                        }
                        return null;
                    }),

                // F2 - BY STATUS
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'New' => 'New',
                        'Rejected' => 'Rejected',
                        'Pending Stock' => 'Pending Stock',
                        'Pending Migration' => 'Pending Migration',
                        'Pending Payment' => 'Pending Payment',
                        'Pending: Courier' => 'Pending: Courier',
                        'Completed: Courier' => 'Completed: Courier',
                        'Pending Admin: Self Pick-Up' => 'Pending Admin: Self Pick-Up',
                        'Pending Customer: Self Pick-Up' => 'Pending Customer: Self Pick-Up',
                        'Completed: Self Pick-Up' => 'Completed: Self Pick-Up',
                        'Pending: External Installation' => 'Pending: External Installation',
                        'Completed: External Installation' => 'Completed: External Installation',
                        'Pending: Internal Installation' => 'Pending: Internal Installation',
                        'Completed: Internal Installation' => 'Completed: Internal Installation',
                    ])
                    ->searchable()
                    ->preload(),

                // F3 - BY SALESPERSON
                SelectFilter::make('salesperson')
                    ->label('SalesPerson')
                    ->options(function () {
                        // Get unique salesperson IDs from hardware handovers through leads
                        $salespersonIds = DB::table('hardware_handovers')
                            ->join('leads', 'hardware_handovers.lead_id', '=', 'leads.id')
                            ->whereNotNull('leads.salesperson')
                            ->distinct()
                            ->pluck('leads.salesperson')
                            ->filter()
                            ->toArray();

                        // Get user names for these IDs
                        return User::whereIn('id', $salespersonIds)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('lead', function (Builder $query) use ($data) {
                                $query->where('salesperson', $data['value']);
                            });
                        }
                    }),

                // F4 - BY IMPLEMENTER
                SelectFilter::make('implementer')
                    ->label('Implementer')
                    ->options(function () {
                        return HardwareHandoverV2::whereNotNull('implementer')
                            ->where('implementer', '!=', '')
                            ->distinct()
                            ->pluck('implementer', 'implementer')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),
            ]);
    }
}
