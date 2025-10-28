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

                // TextColumn::make('email')
                //     ->label('Email Address')
                //     ->searchable()
                //     ->sortable(),

                // TextColumn::make('password_display')
                //     ->label('Password')
                //     ->getStateUsing(function ($record) {
                //         return '••••••••••••'; // Hidden for security
                //     })
                //     ->tooltip('Password is hidden for security')
                //     ->searchable(false)
                //     ->sortable(false)
                //     ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Date Time - Submission')
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
                    ->label('Kick-off Meeting')
                    ->getStateUsing(function ($record) {
                        // Get SW_ID directly from the customer record
                        $swId = $record->sw_id;

                        if ($swId) {
                            // Get the latest KICK OFF MEETING SESSION appointment directly by software_handover_id
                            $latestKickoffAppointment = \App\Models\ImplementerAppointment::where('software_handover_id', $swId)
                                ->where('type', 'KICK OFF MEETING SESSION')
                                ->orderBy('date', 'desc')
                                ->orderBy('start_time', 'desc')
                                ->first();

                            if ($latestKickoffAppointment) {
                                $date = $latestKickoffAppointment->date;
                                $time = $latestKickoffAppointment->start_time;

                                // Handle different date/time formats properly
                                if ($date && $time) {
                                    try {
                                        // Extract just the date part if it's a datetime
                                        $dateOnly = \Carbon\Carbon::parse($date)->format('Y-m-d');

                                        // Combine date and time properly
                                        $combined = $dateOnly . ' ' . $time;
                                        $parsedDateTime = \Carbon\Carbon::parse($combined);

                                        return new \Illuminate\Support\HtmlString(
                                            $parsedDateTime->format('d M Y') . '<br>' .
                                            $parsedDateTime->format('H:i:s')
                                        );
                                    } catch (\Exception $e) {
                                        // Fallback if parsing fails
                                        return \Carbon\Carbon::parse($date)->format('d M Y');
                                    }
                                } elseif ($date) {
                                    return \Carbon\Carbon::parse($date)->format('d M Y');
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

                // TextColumn::make('completed_at')
                //     ->label('Date Time - Completed')
                //     ->getStateUsing(function ($record) {
                //         // Get the SW_ID (project_code) for this customer
                //         if ($record->lead_id) {
                //             $handover = SoftwareHandover::where('lead_id', $record->lead_id)
                //                 ->orderBy('id', 'desc')
                //                 ->first();

                //             if ($handover) {
                //                 $projectCode = $handover->id;

                //                 // Get the latest completed appointment based on SW_ID
                //                 $completedAppointment = \App\Models\ImplementerAppointment::where('software_handover_id', $projectCode)
                //                     ->where('type', 'KICK OFF MEETING SESSION')
                //                     ->where('status', 'Done')
                //                     ->orderBy('updated_at', 'desc')
                //                     ->first();

                //                 return $completedAppointment
                //                     ? $completedAppointment->updated_at->format('d M Y H:i:s')
                //                     : 'Not completed';
                //             }
                //         }

                //         return 'Not completed';
                //     })
                //     ->searchable(false)
                //     ->sortable(false)
                //     ->default('Not completed'),
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

                        return $query->whereHas('lead', function ($leadQuery) use ($data) {
                            $leadQuery->whereHas('softwareHandover', function ($handoverQuery) use ($data) {
                                if ($data['value'] === 'COMPLETED') {
                                    $handoverQuery->whereHas('implementerAppointments', function ($appointmentQuery) {
                                        $appointmentQuery->where('type', 'KICK OFF MEETING SESSION')
                                            ->where('status', 'Done');
                                    });
                                } else {
                                    $handoverQuery->whereDoesntHave('implementerAppointments', function ($appointmentQuery) {
                                        $appointmentQuery->where('type', 'KICK OFF MEETING SESSION')
                                            ->where('status', 'Done');
                                    });
                                }
                            });
                        });
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
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('60s'); // Auto refresh every 60 seconds
    }

    protected function getTableQuery(): Builder
    {
        return Customer::query()
            ->whereNotNull('lead_id')
            ->with(['lead.salespersonUser', 'lead.softwareHandover', 'lead.implementerAppointment']);
    }
}
