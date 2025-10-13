<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use App\Models\AdminRepair;
use Carbon\Carbon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;

class RPTableRelationManager extends RelationManager
{
    protected static string $relationship = 'repairHandover'; // Define the relationship name in the Lead model
    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

    #[On('refresh-software-handovers')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'OR_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('id', $direction);
                    })
                    ->action(
                        Action::make('viewRepairDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (AdminRepair $record): View {
                                return view('components.repair-detail')
                                    ->with('record', $record);
                            })
                    ),

                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('days_elapsed')
                    ->label('Total Days')
                    ->state(function (AdminRepair $record) {
                        if (!$record->created_at) {
                            return '0 days';
                        }

                        $createdDate = Carbon::parse($record->created_at);
                        $today = Carbon::now();
                        $diffInDays = $createdDate->diffInDays($today);

                        return $diffInDays . ' ' . Str::plural('day', $diffInDays);
                    }),

                TextColumn::make('creator.name')
                    ->label('Submitted By')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        // If relationship or name is null, try to get the user manually
                        if (!$state && $record->created_by) {
                            $user = \App\Models\User::find($record->created_by);
                            return $user ? $user->name : "User #{$record->created_by}";
                        }
                        return $state ?? "Unknown";
                    }),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        // First try to get company name directly from softwareHandover relation
                        if ($state) {
                            return $state;
                        }

                        // If that's null, try to get it from softwareHandover's relationship
                        if ($record->softwareHandover) {
                            if ($record->softwareHandover->lead && $record->softwareHandover->lead->companyDetail) {
                                return $record->softwareHandover->lead->companyDetail->company_name;
                            }
                        }

                        // Fallback to the old method as a last resort
                        return $record->companyDetail->company_name ?? 'Unknown Company';
                    }),

                TextColumn::make('devices')
                    ->label('Devices')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        if ($record->devices) {
                            $devices = is_string($record->devices)
                                ? json_decode($record->devices, true)
                                : $record->devices;

                            if (is_array($devices)) {
                                return collect($devices)
                                    ->map(fn ($device) =>
                                        "{$device['device_model']} (SN: {$device['device_serial']})")
                                    ->join('<br>');
                            }
                        }

                        if ($record->device_model) {
                            return "{$record->device_model} (SN: {$record->device_serial})";
                        }

                        return '—';
                    })
                    ->html(),

                TextColumn::make('zoho_ticket')
                    ->searchable()
                    ->label('Zoho Ticket'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Inactive' => 'gray',
                        'New' => 'danger',
                        'Accepted' => 'danger',
                        'Pending Confirmation' => 'danger',
                        'Pending Onsite Repair' => 'danger',
                        'Completed' => 'success',
                        default => 'gray',
                    }),
                ]);
    }
}
