<?php
namespace App\Filament\Pages;

use App\Models\ImplementerAppointment;
use App\Models\SoftwareHandover;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Support\Colors\Color;
use Illuminate\View\View;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class KickOffMeetingSession extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Kick-Off Meeting Sessions';
    protected static ?string $title = 'Kick-Off Meeting Sessions';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.kick-off-meeting-session';

    public function getStatusCount(?string $status = null): int
    {
        $query = ImplementerAppointment::query()
            ->where('type', 'KICK OFF MEETING SESSION');

        // Filter by status if provided
        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ImplementerAppointment::query()
                    ->where('type', 'KICK OFF MEETING SESSION')
                    ->orderBy('created_at', 'desc')
            )
            ->defaultPaginationPageOption(50)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('software_handover_id')
                    ->label('SW ID')
                    ->formatStateUsing(function ($state, $record) {
                        if (empty($state)) {
                            return 'N/A';
                        }

                        $yearDigits = '25'; // Default

                        // Try to get the software handover creation date
                        $softwareHandover = SoftwareHandover::where('id', $record->software_handover_id)
                            ->first();

                        if ($softwareHandover && $softwareHandover->created_at) {
                            $yearDigits = Carbon::parse($softwareHandover->created_at)->format('y');
                        }

                        if (Str::startsWith($state, 'SW_')) {
                            return $state;
                        }

                        $numericId = preg_replace('/[^0-9]/', '', $state);

                        return 'SW_' . $yearDigits . '0' . str_pad($numericId, 3, '0', STR_PAD_LEFT);
                    }),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('COMPANY NAME')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            return strtoupper(Str::limit($state, 30, '...'));
                        }

                        // Fallback for relationship issues
                        $softwareHandover = SoftwareHandover::where('lead_id', $record->lead_id)->latest()->first();
                        if ($softwareHandover && $softwareHandover->company_name) {
                            return strtoupper(Str::limit($softwareHandover->company_name, 30, '...'));
                        }

                        return 'N/A';
                    })
                    ->url(function ($record) {
                        if ($record->lead_id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead_id);
                            return url('admin/leads/' . $encryptedId);
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->color(Color::hex('#338cf0')),

                TextColumn::make('salesperson')
                    ->label('SALESPERSON')
                    ->getStateUsing(function ($record) {
                        // First try to get from lead relationship
                        if ($record->lead && $record->lead->salesperson) {
                            $salespersonUser = User::find($record->lead->salesperson);
                            return $salespersonUser ? $salespersonUser->name : 'N/A';
                        }

                        // Try to get from software handover
                        $softwareHandover = SoftwareHandover::where('lead_id', $record->lead_id)->latest()->first();
                        if ($softwareHandover) {
                            return $softwareHandover->salesperson ?? 'N/A';
                        }

                        return 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('lead.user', fn (Builder $q) =>
                            $q->where('name', 'like', "%{$search}%")
                        );
                    })
                    ->sortable(),

                TextColumn::make('implementer')
                    ->label('IMPLEMENTER')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project_status')
                    ->label('PROJECT STATUS')
                    ->getStateUsing(function ($record) {
                        $softwareHandover = SoftwareHandover::where('id', $record->software_handover_id)
                            ->latest()
                            ->first();

                        return $softwareHandover ? ($softwareHandover->status_handover ?? 'N/A') : 'N/A';
                    }),

                TextColumn::make('softwarehandover.completed_at')
                    ->label('DB CREATION')
                    ->date('d M Y')
                    ->toggleable(),

                TextColumn::make('date')
                    ->label('KICK OFF MEETING')
                    ->formatStateUsing(function ($state, $record) {
                        return $state ? Carbon::parse($state)->format('d M Y') : 'N/A';
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? 'N/A'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'New' => 'info',
                        'Done' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('submitted_by')
                    ->label('SUBMITTED BY')
                    ->getStateUsing(function ($record) {
                        $user = User::find($record->causer_id);
                        return $user ? $user->name : 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('causer', fn (Builder $q) =>
                            $q->where('name', 'like', "%{$search}%")
                        );
                    }),
            ])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->options(
                        User::where('role_id', 2) // Role 2 is for salespeople
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray()
                    )
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            // Get all software handover IDs where salesperson matches
                            $softwareHandoverIds = SoftwareHandover::where('salesperson', $data['value'])
                                ->pluck('id')
                                ->toArray();

                            // Filter appointments by these software handover IDs
                            if (!empty($softwareHandoverIds)) {
                                $query->whereIn('software_handover_id', $softwareHandoverIds);
                            } else {
                                // If no matching software handovers, return no results
                                $query->whereRaw('1 = 0');
                            }
                        }
                    }),

                SelectFilter::make('implementer')
                    ->label('Implementer')
                    ->options(
                        User::whereIn('role_id', [4, 5])
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('project_status')
                    ->label('Project Status')
                    ->options([
                        'Open' => 'Open',
                        'Delay' => 'Delay',
                        'InActive' => 'InActive',
                        'Closed' => 'Closed',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            // Check software_handovers directly by ID (matching the TextColumn approach)
                            $query->whereExists(function ($subQuery) use ($data) {
                                $subQuery->from('software_handovers')
                                    ->whereColumn('software_handovers.id', 'implementer_appointments.software_handover_id')
                                    ->where('software_handovers.status_handover', $data['value']);
                            });
                        }
                    }),

                SelectFilter::make('status')
                    ->label('Kick Off Meeting Status')
                    ->options([
                        'New' => 'New',
                        'Done' => 'Done',
                        'Cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('submitted_by')
                    ->label('Submitted By')
                    ->options(function() {
                        return User::whereIn('id', function($query) {
                            $query->select('causer_id')
                                ->from('implementer_appointments')
                                ->distinct();
                        })
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('causer_id', $data['value']);
                        }
                    }),
            ]);
    }
}
