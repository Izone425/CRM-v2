<?php
namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\SoftwareHandover;
use App\Models\Lead;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions;
use Filament\Tables\Filters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CustomerPortalRawData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Customer Portal Data';
    protected static ?string $title = 'Customer Portal Raw Data';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?int $navigationSort = 15;
    protected static string $view = 'filament.pages.customer-portal-raw-data';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('sw_id')
                    ->label('ID')
                    ->getStateUsing(function ($record) {
                        if ($record->lead_id) {
                            $handover = SoftwareHandover::where('lead_id', $record->lead_id)
                                ->orderBy('id', 'desc')
                                ->first();

                            if ($handover) {
                                // Use the model's getProjectCodeAttribute method
                                return $handover->project_code;
                            }
                        }
                        return 'N/A';
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->searchable(false)
                    ->sortable(false),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            return 'Unknown Company';
                        }

                        // Create clickable link to lead if available
                        if ($record->lead_id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead_id);

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
                    ->html()
                    ->wrap(),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function ($record) {
                        if ($record->lead_id) {
                            $lead = Lead::with('salespersonUser')->find($record->lead_id);

                            // First try to get the salesperson user relationship
                            if ($lead && $lead->salespersonUser) {
                                return $lead->salespersonUser->name;
                            }

                            // If that doesn't work, try to find by ID
                            if ($lead && $lead->salesperson) {
                                $salesperson = User::find($lead->salesperson);
                                return $salesperson ? $salesperson->name : 'Unknown';
                            }
                        }
                        return 'Unknown';
                    })
                    ->searchable(false)
                    ->sortable(false),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->getStateUsing(function ($record) {
                        if ($record->lead_id) {
                            $handover = SoftwareHandover::where('lead_id', $record->lead_id)
                                ->orderBy('id', 'desc')
                                ->first();
                            return $handover ? ($handover->implementer ?? 'Not Assigned') : 'Not Assigned';
                        }
                        return 'Not Assigned';
                    })
                    ->searchable(false)
                    ->sortable(false),

                TextColumn::make('db_creation')
                    ->label('DB Creation')
                    ->getStateUsing(function ($record) {
                        if ($record->lead_id) {
                            $handover = SoftwareHandover::where('lead_id', $record->lead_id)
                                ->orderBy('id', 'desc')
                                ->first();

                            if ($handover && $handover->completed_at) {
                                return \Carbon\Carbon::parse($handover->completed_at)->format('d M Y');
                            }
                        }
                        return '-';
                    })
                    ->searchable(false)
                    ->sortable(false),

                TextColumn::make('total_days')
                    ->label('Total Days')
                    ->getStateUsing(function ($record) {
                        if ($record->lead_id) {
                            $handover = SoftwareHandover::where('lead_id', $record->lead_id)
                                ->orderBy('id', 'desc')
                                ->first();

                            if ($handover && $handover->completed_at) {
                                try {
                                    $completedDate = \Carbon\Carbon::parse($handover->completed_at);

                                    // Check if go_live_date exists, if yes use it, otherwise use today
                                    if ($handover->go_live_date) {
                                        $endDate = \Carbon\Carbon::parse($handover->go_live_date);
                                    } else {
                                        $endDate = \Carbon\Carbon::now();
                                    }

                                    // Calculate weekdays only (excluding weekends)
                                    $weekdayCount = 0;
                                    $currentDate = $completedDate->copy();

                                    while ($currentDate->lte($endDate)) {
                                        // Check if it's a weekday (Monday = 1, Sunday = 7)
                                        if ($currentDate->isWeekday()) {
                                            $weekdayCount++;
                                        }
                                        $currentDate->addDay();
                                    }

                                    return $weekdayCount . ' ' . \Illuminate\Support\Str::plural('day', $weekdayCount);
                                } catch (\Exception $e) {
                                    return 'Error';
                                }
                            }
                        }
                        return '-';
                    })
                    ->searchable(false)
                    ->sortable(false),

                TextColumn::make('created_at')
                    ->label('Submission')
                    ->getStateUsing(function ($record) {
                        // Get SW_ID directly from the customer record
                        $swId = $record->sw_id;

                        if ($swId) {
                            // Get the first appointment submission date directly by software_handover_id
                            $firstAppointment = \App\Models\ImplementerAppointment::where('software_handover_id', $swId)
                                ->orderBy('created_at', 'desc')
                                ->first();

                            return $firstAppointment
                                ? new \Illuminate\Support\HtmlString(
                                    $firstAppointment->created_at->format('d M Y') . '<br>' .
                                    $firstAppointment->created_at->format('H:i:s')
                                )
                                : new \Illuminate\Support\HtmlString(
                                    '<span style="color: red; font-weight: bold;">Not submitted</span>'
                                );;
                        }

                        return new \Illuminate\Support\HtmlString(
                            '<span style="color: red; font-weight: bold;">Not submitted</span>'
                        );
                    })
                    ->html() // Enable HTML rendering
                    ->sortable(false)
                    ->searchable(false)
                    ->default('Not submitted'),

                TextColumn::make('latest_kickoff_date')
                    ->label('Kick-Off')
                    ->getStateUsing(function ($record) {
                        // Get SW_ID directly from the customer record
                        $swId = $record->sw_id;

                        if ($swId) {
                            // Get all KICK OFF MEETING SESSION appointments
                            $kickoffAppointments = \App\Models\ImplementerAppointment::where('software_handover_id', $swId)
                                ->where('type', 'KICK OFF MEETING SESSION')
                                ->orderBy('date', 'desc')
                                ->orderBy('start_time', 'desc')
                                ->get();

                            if ($kickoffAppointments->isNotEmpty()) {
                                // Check if any appointment is cancelled
                                $hasCancelled = $kickoffAppointments->contains('status', 'Cancelled');

                                if ($hasCancelled) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<span style="color: #dc2626; font-weight: bold;">Cancelled</span>'
                                    );
                                }

                                // Get the first non-cancelled appointment
                                $activeAppointment = $kickoffAppointments->where('status', '!=', 'Cancelled')->first();

                                if ($activeAppointment) {
                                    $date = $activeAppointment->date;
                                    $time = $activeAppointment->start_time;

                                    if ($date && $time) {
                                        try {
                                            $dateOnly = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                            $combined = $dateOnly . ' ' . $time;
                                            $parsedDateTime = \Carbon\Carbon::parse($combined);

                                            return new \Illuminate\Support\HtmlString(
                                                $parsedDateTime->format('d M Y') . '<br>' .
                                                $parsedDateTime->format('H:i:s')
                                            );
                                        } catch (\Exception $e) {
                                            return \Carbon\Carbon::parse($date)->format('d M Y');
                                        }
                                    } elseif ($date) {
                                        return \Carbon\Carbon::parse($date)->format('d M Y');
                                    }
                                }
                            }
                        }
                        return new \Illuminate\Support\HtmlString(
                            '<span style="color: red; font-weight: bold;">Not available</span>'
                        );
                    })
                    ->html() // Enable HTML rendering
                    ->sortable(false)
                    ->searchable(false)
                    ->default('No kick-off meeting')
                    ->color(fn ($state) => $state === 'No kick-off meeting' ? 'gray' : 'primary'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        // Get the SW_ID (project_code) for this customer
                        if ($record->lead_id) {
                            $handover = SoftwareHandover::where('lead_id', $record->lead_id)
                                ->orderBy('id', 'desc')
                                ->first();

                            if ($handover) {
                                $projectCode = $handover->id;

                                // Check if customer has any completed appointments based on SW_ID
                                $hasCompletedAppointment = \App\Models\ImplementerAppointment::where('software_handover_id', $projectCode)
                                    ->where('type', 'KICK OFF MEETING SESSION')
                                    ->where('status', 'Done')
                                    ->exists();

                                return $hasCompletedAppointment ? 'COMPLETED' : 'PENDING';
                            }
                        }

                        return 'PENDING';
                    })
                    ->colors([
                        'success' => 'COMPLETED',
                        'warning' => 'PENDING',
                    ])
                    ->searchable(false)
                    ->sortable(false),

                TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('plain_password')
                    ->label('Password')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('implementer')
                    ->options(function () {
                        return SoftwareHandover::whereNotNull('implementer')
                            ->distinct()
                            ->pluck('implementer', 'implementer')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('lead.softwareHandover', function ($q) use ($data) {
                            $q->where('implementer', $data['value']);
                        });
                    }),

                Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'COMPLETED' => 'Completed',
                        'PENDING' => 'Pending',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'COMPLETED') {
                            // Filter customers where their sw_id has completed kick-off appointments
                            return $query->whereExists(function ($subQuery) {
                                $subQuery->select(DB::raw(1))
                                    ->from('implementer_appointments')
                                    ->whereColumn('implementer_appointments.software_handover_id', 'customers.sw_id')
                                    ->where('type', 'KICK OFF MEETING SESSION')
                                    ->where('status', 'Done');
                            });
                        } else {
                            // Filter customers where their sw_id doesn't have completed kick-off appointments
                            return $query->whereNotExists(function ($subQuery) {
                                $subQuery->select(DB::raw(1))
                                    ->from('implementer_appointments')
                                    ->whereColumn('implementer_appointments.software_handover_id', 'customers.sw_id')
                                    ->where('type', 'KICK OFF MEETING SESSION')
                                    ->where('status', 'Done');
                            });
                        }
                    }),

                Filters\SelectFilter::make('salesperson')
                    ->label('Sales Person')
                    ->options(function () {
                        return User::whereIn('id', function ($query) {
                            $query->select('salesperson')
                                ->from('leads')
                                ->whereNotNull('salesperson')
                                ->distinct();
                        })
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('lead', function ($leadQuery) use ($data) {
                            $leadQuery->where('salesperson', $data['value']);
                        });
                    })
                    ->searchable()
                    ->preload(),

                Filters\SelectFilter::make('date_submission')
                    ->label('Date Submission')
                    ->options([
                        'submitted' => 'Has Submission',
                        'not_submitted' => 'No Submission',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'submitted') {
                            // Filter customers who have appointments based on sw_id
                            return $query->whereExists(function ($subQuery) {
                                $subQuery->select(DB::raw(1))
                                    ->from('implementer_appointments')
                                    ->whereColumn('implementer_appointments.software_handover_id', 'customers.sw_id');
                            });
                        } else {
                            // Filter customers who don't have appointments based on sw_id
                            return $query->whereNotExists(function ($subQuery) {
                                $subQuery->select(DB::raw(1))
                                    ->from('implementer_appointments')
                                    ->whereColumn('implementer_appointments.software_handover_id', 'customers.sw_id');
                            });
                        }
                    }),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->orderByDesc(
                    SoftwareHandover::select('id')
                        ->whereColumn('software_handovers.lead_id', 'customers.lead_id')
                        ->orderBy('id', 'desc')
                        ->limit(1)
                );
            })
            ->paginated([50, 100])
            ->poll('120s'); // Auto refresh every 2 minutes instead of 60s
    }

    protected function getTableQuery(): Builder
    {
        return Customer::query()
            ->whereNotNull('lead_id')
            ->with([
                'lead' => function($query) {
                    $query->with(['salespersonUser', 'softwareHandover' => function($subQuery) {
                        $subQuery->with('implementerAppointments');
                    }]);
                }
            ])
            ->select('customers.*'); // Only select necessary columns
    }
}
