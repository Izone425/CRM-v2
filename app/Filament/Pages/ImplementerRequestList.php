<?php

namespace App\Filament\Pages;

use App\Models\ImplementerAppointment;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class ImplementerRequestList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationLabel = 'Implementer Request List';
    protected static ?int $navigationSort = 17;
    protected static string $view = 'filament.pages.implementer-request-list';

    public function getTableQuery(): Builder
    {
        $query = ImplementerAppointment::query()
            ->whereIn('type', ['DATA MIGRATION SESSION', 'SYSTEM SETTING SESSION', 'WEEKLY FOLLOW UP SESSION'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc');

        // Check user permissions
        $currentUser = auth()->user();
        $hasAdminAccess = $currentUser->id === 26 || $currentUser->role_id === 3;

        // If not admin, restrict to viewing only their own data
        if (!$hasAdminAccess) {
            $query->where('implementer', $currentUser->name);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        // Check user permissions
        $currentUser = auth()->user();
        $hasAdminAccess = $currentUser->id === 26 || $currentUser->role_id === 3;

        $implementerOptions = [];

        // Only admins can filter by implementer
        if ($hasAdminAccess) {
            $implementerOptions = User::whereIn('role_id', [4, 5])
                ->orderBy('name')
                ->pluck('name', 'name')
                ->toArray();
        } else {
            // Non-admins can only see themselves in the filter
            $implementerOptions = [$currentUser->name => $currentUser->name];
        }

        return $table
            ->query($this->getTableQuery())
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 'all'])
            ->columns([
                TextColumn::make('id')
                    ->label('NO')
                    ->formatStateUsing(function ($state) {
                        return 'IMP_' . str_pad($state, 6, '0', STR_PAD_LEFT);
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->formatStateUsing(function ($state, ImplementerAppointment $record) {
                        $date = Carbon::parse($state);
                        $dayName = $date->format('l');
                        return $date->format('j F Y') . ' / ' . $dayName;
                    })
                    ->sortable(),

                TextColumn::make('session')
                    ->label('Session')
                    ->formatStateUsing(function ($state, ImplementerAppointment $record) {
                        $slotCode = $record->session;

                        // Format the time in 12-hour format with AM/PM
                        $formattedStartTime = Carbon::parse($record->start_time)->format('h:i A');
                        $formattedEndTime = Carbon::parse($record->end_time)->format('h:i A');

                        // Combine slot code with formatted time
                        return "{$slotCode} ({$formattedStartTime} - {$formattedEndTime})";
                    }),

                TextColumn::make('type')
                    ->label('Session Type')
                    ->searchable(),

                TextColumn::make('session')
                    ->label('Session')
                    ->formatStateUsing(function ($state, ImplementerAppointment $record) {
                        $slotCode = $record->session;

                        // Format the time in 12-hour format with AM/PM
                        $formattedStartTime = Carbon::parse($record->start_time)->format('h:i A');
                        $formattedEndTime = Carbon::parse($record->end_time)->format('h:i A');

                        // Combine slot code with formatted time
                        return "{$slotCode} ({$formattedStartTime} - {$formattedEndTime})";
                    }),

                TextColumn::make('type')
                    ->label('Session Type')
                    ->searchable(),

                TextColumn::make('session_count')
                    ->label('Count')
                    ->getStateUsing(function ($record) {
                        // Skip cancelled sessions
                        if ($record->status == 'Cancelled') {
                            return '-';
                        }

                        // For weekly follow-up sessions, return week number if available
                        if ($record->type === 'WEEKLY FOLLOW UP SESSION' && $record->selected_week) {
                            return "W{$record->selected_week}";
                        }

                        // If no lead_id, we can't determine the count
                        if (!$record->lead_id) {
                            return '-';
                        }

                        // Get all appointments of this specific type for this lead that aren't cancelled
                        $sessions = ImplementerAppointment::where('lead_id', $record->lead_id)
                            ->where('type', $record->type)
                            ->where('status', '!=', 'Cancelled')
                            ->orderBy('date', 'asc')
                            ->orderBy('start_time', 'asc')
                            ->orderBy('id', 'asc')
                            ->get();

                        // Find position of current record in the sorted list
                        $position = 0;
                        foreach ($sessions as $index => $session) {
                            if ($session->id === $record->id) {
                                $position = $index + 1; // +1 because we want to start counting from 1, not 0
                                break;
                            }
                        }

                        // Get total count
                        $totalCount = $sessions->count();

                        // Return position and max counts for specific session types
                        if ($record->type === 'DATA MIGRATION SESSION') {
                            return "{$position}/2";
                        } elseif ($record->type === 'SYSTEM SETTING SESSION') {
                            return "{$position}/4";
                        } else {
                            return $position > 0 ? "{$position}/{$totalCount}" : '-';
                        }
                    })
                    ->alignCenter()
                    ->badge()
                    ->color(function ($record, $state) {
                        if ($state === '-') return 'gray';

                        return match ($record->type) {
                            'DATA MIGRATION SESSION' => 'warning',
                            'SYSTEM SETTING SESSION' => 'info',
                            'WEEKLY FOLLOW UP SESSION' => 'purple',
                            default => 'primary',
                        };
                    }),

                TextColumn::make('request_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'CANCELLED' => 'gray',
                        default => 'primary',
                    })
                    ->searchable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                // Week filter
                Filter::make('date')
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
                            $query->whereBetween('date', [$startDate, $endDate]);
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
                // Session Type filter
                SelectFilter::make('type')
                    ->label('Session Type')
                    ->options([
                        'DATA MIGRATION SESSION' => 'Data Migration',
                        'SYSTEM SETTING SESSION' => 'System Setting',
                        'WEEKLY FOLLOW UP SESSION' => 'Weekly Follow Up',
                    ]),

                // Status filter
                SelectFilter::make('request_status')
                    ->label('Status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Cancelled' => 'Cancelled',
                    ]),

                // Implementer filter
                SelectFilter::make('implementer')
                    ->label('Implementer')
                    ->options(function() {
                        return User::whereIn('role_id', [4, 5])
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('implementer', $data['value']);
                        }
                    })
            ])
            ->actions([
                // ActionGroup::make([
                //     // View details action
                //     Action::make('view')
                //         ->icon('heroicon-o-eye')
                //         ->color('primary')
                //         // ->url(fn (ImplementerAppointment $record): string =>
                //         //     route('implementer-appointment.view', $record->id))
                //         ->openUrlInNewTab(),

                //     // Approve action (for pending requests)
                //     Action::make('approve')
                //         ->icon('heroicon-o-check')
                //         ->color('success')
                //         ->visible(fn (ImplementerAppointment $record): bool =>
                //             $record->request_status === 'Pending Approval')
                //         ->requiresConfirmation()
                //         ->modalHeading('Approve Request')
                //         ->modalDescription('Are you sure you want to approve this implementer request?')
                //         ->modalSubmitActionLabel('Yes, approve')
                //         ->action(function (ImplementerAppointment $record) {
                //             $record->request_status = 'Approved';
                //             $record->save();

                //             // Here you can add notification logic
                //         }),

                //     // Reject action (for pending requests)
                //     Action::make('reject')
                //         ->icon('heroicon-o-x-mark')
                //         ->color('danger')
                //         ->visible(fn (ImplementerAppointment $record): bool =>
                //             $record->request_status === 'Pending Approval')
                //         ->requiresConfirmation()
                //         ->modalHeading('Reject Request')
                //         ->modalDescription('Are you sure you want to reject this implementer request?')
                //         ->modalSubmitActionLabel('Yes, reject')
                //         ->action(function (ImplementerAppointment $record) {
                //             $record->request_status = 'Rejected';
                //             $record->save();

                //             // Here you can add notification logic
                //         }),

                //     // Cancel action (for approved requests)
                //     Action::make('cancel')
                //         ->icon('heroicon-o-trash')
                //         ->color('gray')
                //         ->visible(fn (ImplementerAppointment $record): bool =>
                //             $record->request_status === 'Approved')
                //         ->requiresConfirmation()
                //         ->modalHeading('Cancel Request')
                //         ->modalDescription('Are you sure you want to cancel this implementer request?')
                //         ->modalSubmitActionLabel('Yes, cancel')
                //         ->action(function (ImplementerAppointment $record) {
                //             $record->request_status = 'Cancelled';
                //             $record->save();

                //             // Here you can add notification logic
                //         }),
                // ])
            ]);
    }
}
