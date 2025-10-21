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

                            if ($handover && $handover->id) {
                                try {
                                    // Get the year from created_at, fallback to current year if null
                                    $year = $handover->created_at ? $handover->created_at->format('y') : now()->format('y');
                                    return 'SW_' . $year . str_pad($handover->id, 4, '0', STR_PAD_LEFT);
                                } catch (\Exception $e) {
                                    return 'SW_' . str_pad($handover->id, 6, '0', STR_PAD_LEFT);
                                }
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

                TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable(),

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
                        // Get the first appointment submission date
                        $firstAppointment = \App\Models\ImplementerAppointment::where('lead_id', $record->lead_id)
                            ->orderBy('created_at', 'desc')
                            ->first();

                        return $firstAppointment
                            ? $firstAppointment->created_at->format('d M Y H:i:s')
                            : 'Not submitted';
                    })
                    ->sortable(false)
                    ->searchable(false)
                    ->default('Not submitted'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        // Check if customer has any confirmed appointments
                        $hasCompletedAppointment = \App\Models\ImplementerAppointment::where('lead_id', $record->lead_id)
                            ->where('status', 'Done')
                            ->exists();

                        return $hasCompletedAppointment ? 'COMPLETED' : 'PENDING';
                    })
                    ->colors([
                        'success' => 'COMPLETED',
                        'warning' => 'PENDING',
                    ])
                    ->searchable(false)
                    ->sortable(false),

                TextColumn::make('completed_at')
                    ->label('Date Time - Completed')
                    ->getStateUsing(function ($record) {
                        // Get the latest confirmed appointment date
                        $completedAppointment = \App\Models\ImplementerAppointment::where('lead_id', $record->lead_id)
                            ->where('status', 'Confirmed')
                            ->orderBy('updated_at', 'desc')
                            ->first();

                        return $completedAppointment
                            ? $completedAppointment->updated_at->format('d M Y H:i:s')
                            : 'Not completed';
                    })
                    ->searchable(false)
                    ->sortable(false)
                    ->default('Not completed'),
            ])
            ->filters([
                Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PENDING' => 'Pending',
                        'COMPLETED' => 'Completed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'COMPLETED') {
                            return $query->whereHas('lead.implementerAppointment', function ($q) {
                                $q->where('status', 'Confirmed');
                            });
                        }

                        if ($data['value'] === 'PENDING') {
                            return $query->whereDoesntHave('lead.implementerAppointment', function ($q) {
                                $q->where('status', 'Confirmed');
                            });
                        }

                        return $query;
                    }),

                Filters\Filter::make('date_range')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

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
