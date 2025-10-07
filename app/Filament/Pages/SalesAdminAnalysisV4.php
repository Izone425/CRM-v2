<?php
namespace App\Filament\Pages;

use App\Models\CallLog;
use App\Models\Lead;
use App\Models\PhoneExtension;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\DB;

class SalesAdminAnalysisV4 extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $title = '';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Sales & Admin Call Analysis';
    protected static ?int $navigationSort = 86;
    protected static ?string $navigationGroup = 'Communication';
    protected static string $view = 'filament.pages.sales-admin-analysis-v4';
    protected static ?string $slug = 'sales-admin-call-log';

    public $showStaffStats = false;
    public $slideOverTitle = 'Sales Admin Call Analytics';
    public $staffStats = [];
    public $type = 'all';

    public $expandedStaff = [];
    public $staffDateTimes = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.sales-admin-analysis-v4');
    }

    public function getSalesAdminCalls(): Builder
    {
        // Get all sales & admin staff extensions (is_support = false)
        $salesAdminExtensions = PhoneExtension::where('is_support_staff', false)
            ->where('is_active', true)
            ->pluck('extension')
            ->toArray();

        $query = CallLog::query()
            ->where(function ($query) use ($salesAdminExtensions) {
                // Include calls where sales/admin staff are involved
                $query->whereIn('caller_number', $salesAdminExtensions)
                    ->orWhereIn('receiver_number', $salesAdminExtensions);
            })
            // Exclude "NO ANSWER" call logs
            ->where('call_status', '!=', 'NO ANSWER')
            // Exclude calls with duration less than 5 seconds
            ->where(function($query) {
                $query->where('call_duration', '>=', 5)
                    ->orWhereNull('call_duration');
            });

        // Get extension to user mapping
        $extensionUserMapping = [];
        foreach (PhoneExtension::with('user')->where('is_active', true)->get() as $ext) {
            // If user_id exists, use User name, otherwise fallback to extension name
            $userName = ($ext->user_id && $ext->user) ? $ext->user->name : $ext->name;
            $extensionUserMapping[] = "WHEN '{$ext->extension}' THEN '{$userName}'";
        }
        $extensionUserMappingStr = implode(' ', $extensionUserMapping);

        // Map both receiver and caller numbers to specific staff names based on call type
        $query->addSelect([
            '*',
            DB::raw("CASE
                -- For incoming calls, use the receiver's extension to identify staff
                WHEN call_type = 'incoming' THEN (
                    CASE receiver_number
                        {$extensionUserMappingStr}
                        ELSE receiver_number
                    END
                )
                -- For outgoing calls, use the caller's extension to identify staff
                ELSE (
                    CASE caller_number
                        {$extensionUserMappingStr}
                        ELSE caller_number
                    END
                )
            END as staff_name")
        ]);

        return $query;
    }

    public function table(Table $table): Table
    {
        $salesAdminStaffOptions = [];
        $extensionUserMapping = [];

        $salesAdminStaff = PhoneExtension::with('user')
            ->where('is_support_staff', false)
            ->where('is_active', true)
            ->get();

        foreach ($salesAdminStaff as $staff) {
            $userName = ($staff->user_id && $staff->user) ? $staff->user->name : $staff->name;
            $salesAdminStaffOptions[$userName] = $userName;
            $extensionUserMapping[$userName] = $staff->extension;
        }

        return $table
            ->query($this->getSalesAdminCalls())
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->sortable(),

                TextColumn::make('staff_name')
                    ->label('Sales Admin')
                    ->sortable(),

                TextColumn::make('started_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('started_at_time')
                    ->label('Start Time')
                    ->state(function ($record) {
                        return $record->started_at ? date('H:i:s', strtotime($record->started_at)) : null;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('started_at', $direction);
                    }),

                TextColumn::make('end_at')
                    ->label('End Time')
                    ->formatStateUsing(fn ($state) => $state ? date('H:i:s', strtotime($state)) : '-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('call_duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $this->formatDuration($state))
                    ->sortable(),

                TextColumn::make('call_type')
                    ->label('Call Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'internal' => 'success',
                        'outgoing' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('task_status')
                    ->label('Task Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Pending' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('lead_id')
                    ->label('Lead')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            return '—';
                        }

                        // Get company name from the lead relationship
                        if ($record->lead && $record->lead->companyDetail && $record->lead->companyDetail->company_name) {
                            $companyName = $record->lead->companyDetail->company_name;

                            // Truncate company name to 30 characters if it's longer
                            if (strlen($companyName) > 30) {
                                $companyName = substr($companyName, 0, 27) . '...';
                            }

                            return "#{$state} - {$companyName}";
                        }

                        return "Lead #{$state}";
                    }),
            ])
            ->filters([
                SelectFilter::make('task_status')
                    ->label('Task Status')
                    ->options([
                        'Completed' => 'Completed',
                        'Pending' => 'Pending',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $status): Builder => $query->where('task_status', $status)
                            );
                    }),
                SelectFilter::make('staff_name')
                    ->label('Staff')
                    ->options($salesAdminStaffOptions)
                    ->query(function (Builder $query, array $data) use ($extensionUserMapping): Builder {
                        // If no data or no value selected, return unmodified query
                        if (empty($data['value'])) {
                            return $query;
                        }

                        $staffName = $data['value'];
                        $extension = $extensionUserMapping[$staffName] ?? null;

                        if ($extension) {
                            return $query->where(function ($q) use ($extension) {
                                // For incoming calls, check receiver_number
                                $q->where(function($subq) use ($extension) {
                                    $subq->where('call_type', 'incoming')
                                        ->where('receiver_number', $extension);
                                })
                                // For outgoing calls, check caller_number
                                ->orWhere(function($subq) use ($extension) {
                                    $subq->where('call_type', 'outgoing')
                                        ->where('caller_number', $extension);
                                })
                                // For internal calls
                                ->orWhere(function($subq) use ($extension) {
                                    $subq->where('call_type', 'internal')
                                        ->where(function($innerq) use ($extension) {
                                            $innerq->where('caller_number', $extension)
                                                ->orWhere('receiver_number', $extension);
                                        });
                                });
                            });
                        }

                        return $query;
                    }),

                SelectFilter::make('call_type')
                    ->options([
                        'incoming' => 'Incoming',
                        'outgoing' => 'Outgoing',
                        'internal' => 'Internal',
                    ]),

                Filter::make('started_at')
                    ->form([
                        DateTimePicker::make('from'),
                        DateTimePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('link_lead')
                        ->label('Link with Lead')
                        ->color('success')
                        ->icon('heroicon-o-link')
                        ->requiresConfirmation(false)
                        ->visible(function (CallLog $record): bool {
                            return $record->task_status === 'Pending';
                        })
                        ->form([
                            Select::make('lead_id')
                                ->label('Select Company')
                                ->options(function () {
                                    // Use get() and mapWithKeys instead of pluck to access the relationship
                                    return Lead::query()
                                        ->with('companyDetail') // Eager load the companyDetail relationship
                                        ->get()
                                        ->mapWithKeys(function ($lead) {
                                            // Only include leads with valid company names from companyDetail
                                            if ($lead->companyDetail && $lead->companyDetail->company_name) {
                                                return [$lead->id => "#{$lead->id} - {$lead->companyDetail->company_name}"];
                                            }
                                            return [];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->action(function (CallLog $record, array $data): void {
                            // Update the record with lead_id and set task status to completed
                            $record->update([
                                'lead_id' => $data['lead_id'],
                                'task_status' => 'Completed',
                            ]);

                            // Show success notification
                            Notification::make()
                                ->title('Call linked to company successfully')
                                ->success()
                                ->send();
                        }),

                    Action::make('view_details')
                        ->label('View')
                        ->color('secondary')
                        ->icon('heroicon-o-eye')
                        ->visible(function (CallLog $record): bool {
                            return $record->task_status === 'Completed';
                        })
                        ->modalHeading('Call Details')
                        ->modalContent(function (CallLog $record) {
                            // Determine caller and receiver names
                            $callerName = $this->getStaffNameFromExtension($record->caller_number);
                            $receiverName = $this->getStaffNameFromExtension($record->receiver_number);

                            // Get linked lead information if available
                            $linkedCompany = $record->lead ? $record->lead->companyDetail->company_name : '—';

                            return Infolist::make()
                                ->record($record)
                                ->schema([
                                    \Filament\Infolists\Components\Section::make('Call Information')
                                        ->schema([
                                            \Filament\Infolists\Components\TextEntry::make('call_type')
                                                ->label('Call Type')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                    'internal' => 'success',
                                                    'outgoing' => 'danger',
                                                    default => 'gray',
                                                }),

                                            \Filament\Infolists\Components\TextEntry::make('started_at')
                                                ->label('Date & Time')
                                                ->dateTime('d/m/Y H:i:s'),

                                            \Filament\Infolists\Components\TextEntry::make('call_duration')
                                                ->label('Duration')
                                                ->formatStateUsing(fn ($state) => $this->formatDuration($state)),

                                            \Filament\Infolists\Components\TextEntry::make('caller_info')
                                                ->label('From')
                                                ->state($callerName ? "$callerName ({$record->caller_number})" : $record->caller_number),

                                            \Filament\Infolists\Components\TextEntry::make('receiver_info')
                                                ->label('To')
                                                ->state($receiverName ? "$receiverName ({$record->receiver_number})" : $record->receiver_number),

                                            \Filament\Infolists\Components\TextEntry::make('linked_company')
                                                ->label('Linked Company')
                                                ->state($linkedCompany),

                                            \Filament\Infolists\Components\TextEntry::make('task_status')
                                                ->label('Status')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                    'Completed' => 'success',
                                                    'Pending' => 'danger',
                                                    default => 'gray',
                                                }),
                                        ])
                                        ->columns(2),
                                ]);
                        })
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                ])
            ])
            ->defaultSort('started_at', 'desc');
    }

    public function formatDuration($seconds)
    {
        if (!$seconds) return '-';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
        }

        return sprintf("%02d:%02d", $minutes, $secs);
    }

    protected function getStaffNameFromExtension($extension)
    {
        $phoneExt = PhoneExtension::where('extension', $extension)
            ->where('is_active', true)
            ->first();

        if (!$phoneExt) {
            return null;
        }

        return ($phoneExt->user_id && $phoneExt->user) ? $phoneExt->user->name : $phoneExt->name;
    }

    public function toggleStaff($staffName)
    {
        if (in_array($staffName, $this->expandedStaff)) {
            $this->expandedStaff = array_diff($this->expandedStaff, [$staffName]);
        } else {
            $this->expandedStaff[] = $staffName;
        }
    }

    public function openStaffStatsSlideOver($type = 'all')
    {
        $this->type = $type;

        // Reset expanded state when opening - all collapsed by default
        $this->expandedStaff = [];
        $this->staffDateTimes = [];

        if ($type === 'duration') {
            $this->slideOverTitle = 'Sales & Admin - Call Duration';

            // Get sales & admin staff with active extensions
            $salesAdminExtensions = \App\Models\PhoneExtension::with('user')
                ->where('is_support_staff', false)
                ->where('is_active', true)
                ->get();

            $staffStats = [];

            foreach ($salesAdminExtensions as $extension) {
                $staffName = ($extension->user_id && $extension->user) ? $extension->user->name : $extension->name;

                // Get staff call logs
                $logs = \App\Models\CallLog::query()
                    ->where(function ($query) use ($extension) {
                        $query->where('caller_number', $extension->extension)
                            ->orWhere('receiver_number', $extension->extension);
                    })
                    ->where('call_status', '!=', 'NO ANSWER')
                    ->where(function($query) {
                        $query->where('call_duration', '>=', 5)
                            ->orWhereNull('call_duration');
                    })
                    ->get();

                $totalDuration = $logs->sum('call_duration');
                $hours = floor($totalDuration / 3600);
                $minutes = floor(($totalDuration % 3600) / 60);

                // Only add staff with call duration
                if ($totalDuration > 0) {
                    $staffStats[] = [
                        'name' => $staffName,
                        'extension' => $extension->extension,
                        'total_duration' => $totalDuration,
                        'formatted_time' => "{$hours}h {$minutes}m",
                    ];

                    // Get date-wise call details for this staff
                    $dateGroups = $logs->groupBy(function ($log) {
                        return date('Y-m-d', strtotime($log->started_at));
                    })->map(function ($dayLogs, $date) {
                        $totalDuration = $dayLogs->sum('call_duration');
                        $hours = floor($totalDuration / 3600);
                        $minutes = floor(($totalDuration % 3600) / 60);

                        return [
                            'display_date' => date('j M Y', strtotime($date)),
                            'formatted_time' => "{$hours}h {$minutes}m",
                            'total_duration' => $totalDuration,
                        ];
                    })->sortByDesc(function ($item, $key) {
                        return $key; // Sort by date descending
                    })->values()->toArray();

                    $this->staffDateTimes[$staffName] = $dateGroups;
                }
            }

            // Sort staff by total duration descending
            usort($staffStats, function($a, $b) {
                return $b['total_duration'] <=> $a['total_duration'];
            });

            $this->staffStats = $staffStats;
        } else {
            // Keep existing code for other types
            $this->staffStats = $this->getStaffStats($type);

            switch ($type) {
                case 'completed':
                    $this->slideOverTitle = 'Sales & Admin - Completed Tasks';
                    break;
                case 'pending':
                    $this->slideOverTitle = 'Sales & Admin - Pending Tasks';
                    break;
                default:
                    $this->slideOverTitle = 'Sales & Admin - Call Analytics';
            }
        }

        $this->showStaffStats = true;
    }

    protected function getStaffStats($type = 'all')
    {
        if ($type === 'duration') {
            return $this->getHierarchicalDurationStats();
        }

        // Define staff members with their corresponding extensions
        $stats = [];

        // Get all sales & admin staff (is_support_staff = false)
        $salesAdminStaff = PhoneExtension::with('user')
            ->where('is_support_staff', false)
            ->where('is_active', true)
            ->get();

        foreach ($salesAdminStaff as $staff) {
            // Use User name if available, otherwise fallback to extension name
            $staffName = ($staff->user_id && $staff->user) ? $staff->user->name : $staff->name;

            // Base query builder to get calls for this staff member
            $baseQueryBuilder = function () use ($staff) {
                return CallLog::query()->where(function ($query) use ($staff) {
                    // For incoming calls, check receiver_number
                    $query->where(function($subq) use ($staff) {
                        $subq->where('call_type', 'incoming')
                            ->where('receiver_number', $staff->extension);
                    })
                    // For outgoing calls, check caller_number
                    ->orWhere(function($subq) use ($staff) {
                        $subq->where('call_type', 'outgoing')
                            ->where('caller_number', $staff->extension);
                    })
                    // For internal calls
                    ->orWhere(function($subq) use ($staff) {
                        $subq->where('call_type', 'internal')
                            ->where(function($innerq) use ($staff) {
                            $innerq->where('caller_number', $staff->extension)
                                ->orWhere('receiver_number', $staff->extension);
                        });
                    });
                })
                ->where('call_status', '!=', 'NO ANSWER')
                // Exclude calls with duration less than 5 seconds
                ->where(function($query) {
                    $query->where('call_duration', '>=', 5)
                        ->orWhereNull('call_duration');
                });
            };

            // Create a fresh query instance for the main filter
            $query = $baseQueryBuilder();

            // Apply type filter if needed
            if ($type === 'completed') {
                $query->where('task_status', 'Completed');
            } elseif ($type === 'pending') {
                $query->where('task_status', 'Pending');
            }

            // Count total calls (unfiltered)
            $totalCalls = $baseQueryBuilder()->count();

            // Create separate query instances for each metric
            $completedTasks = $baseQueryBuilder()->where('task_status', 'Completed')->count();
            $pendingTasks = $baseQueryBuilder()->where('task_status', 'Pending')->count();
            $totalDuration = $query->sum('call_duration');

            // Format total time
            $hours = floor($totalDuration / 3600);
            $minutes = floor(($totalDuration % 3600) / 60);
            $seconds = $totalDuration % 60;
            $totalTime = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

            // Skip if we have a type filter and there are no matching calls
            if (($type === 'completed' && $completedTasks === 0) ||
                ($type === 'pending' && $pendingTasks === 0) ||
                ($type === 'duration' && $totalDuration === 0)) {
                continue;
            }

            // Add to stats array
            $stats[] = [
                'name' => $staffName,
                'extension' => $staff->extension,
                'user_id' => $staff->user_id,
                'total_calls' => $totalCalls,
                'completed_tasks' => $completedTasks,
                'pending_tasks' => $pendingTasks,
                'total_duration' => $totalDuration,
                'total_time' => $totalTime,
            ];
        }

        // Sort by relevant metric based on type
        if ($type === 'completed') {
            usort($stats, function($a, $b) {
                return $b['completed_tasks'] <=> $a['completed_tasks'];
            });
        } elseif ($type === 'pending') {
            usort($stats, function($a, $b) {
                return $b['pending_tasks'] <=> $a['pending_tasks'];
            });
        } elseif ($type === 'duration') {
            usort($stats, function($a, $b) {
                return $b['total_duration'] <=> $a['total_duration'];
            });
        } else {
            usort($stats, function($a, $b) {
                return $b['total_calls'] <=> $a['total_calls'];
            });
        }

        return $stats;
    }

    protected function getHierarchicalDurationStats()
    {
        // Get all sales & admin staff extensions (is_support_staff = false)
        $salesAdminExtensions = PhoneExtension::where('is_support_staff', false)
            ->where('is_active', true)
            ->pluck('extension')
            ->toArray();

        // Step 1: Get all dates with calls
        $dates = CallLog::query()
            ->where(function ($query) use ($salesAdminExtensions) {
                $query->whereIn('caller_number', $salesAdminExtensions)
                    ->orWhereIn('receiver_number', $salesAdminExtensions);
            })
            ->where('call_status', '!=', 'NO ANSWER')
            ->where(function($query) {
                $query->where('call_duration', '>=', 5)
                    ->orWhereNull('call_duration');
            })
            ->selectRaw('DATE(started_at) as call_date')
            ->distinct()
            ->orderByDesc('call_date')
            ->pluck('call_date')
            ->toArray();

        // Step 2: Build hierarchical stats
        $hierarchicalStats = [];

        foreach ($dates as $date) {
            // Format date for display
            $displayDate = date('d F Y', strtotime($date));

            // Get staff data for this date
            $staffData = [];
            $totalDateDuration = 0;

            // Get all staff with calls on this date
            $staffWithCalls = CallLog::query()
                ->where(function ($query) use ($salesAdminExtensions) {
                    $query->whereIn('caller_number', $salesAdminExtensions)
                        ->orWhereIn('receiver_number', $salesAdminExtensions);
                })
                ->whereDate('started_at', $date)
                ->where('call_status', '!=', 'NO ANSWER')
                ->where(function($query) {
                    $query->where('call_duration', '>=', 5)
                        ->orWhereNull('call_duration');
                })
                ->get(['caller_number', 'receiver_number'])
                ->map(function ($call) use ($salesAdminExtensions) {
                    if (in_array($call->caller_number, $salesAdminExtensions)) {
                        return $call->caller_number;
                    }
                    if (in_array($call->receiver_number, $salesAdminExtensions)) {
                        return $call->receiver_number;
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->toArray();

            foreach ($staffWithCalls as $extension) {
                // Get staff info
                $phoneExt = PhoneExtension::where('extension', $extension)
                    ->where('is_active', true)
                    ->first();

                if (!$phoneExt) continue;

                $staffName = ($phoneExt->user_id && $phoneExt->user)
                    ? $phoneExt->user->name
                    : $phoneExt->name;

                // Calculate total duration for this staff on this date
                $duration = CallLog::query()
                    ->where(function ($query) use ($extension) {
                        $query->where('caller_number', $extension)
                            ->orWhere('receiver_number', $extension);
                    })
                    ->whereDate('started_at', $date)
                    ->where('call_status', '!=', 'NO ANSWER')
                    ->where(function($query) {
                        $query->where('call_duration', '>=', 5)
                            ->orWhereNull('call_duration');
                    })
                    ->sum('call_duration');

                // Skip if no duration
                if ($duration <= 0) continue;

                $totalDateDuration += $duration;

                // Format time
                $hours = floor($duration / 3600);
                $minutes = floor(($duration % 3600) / 60);
                $seconds = $duration % 60;
                $formattedTime = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

                // Add to staff data array
                $staffData[] = [
                    'name' => $staffName,
                    'extension' => $extension,
                    'duration' => $duration,
                    'formatted_time' => $formattedTime,
                ];
            }

            // Sort staff by duration (highest first)
            usort($staffData, function($a, $b) {
                return $b['duration'] <=> $a['duration'];
            });

            // Format total time for this date
            $totalHours = floor($totalDateDuration / 3600);
            $totalMinutes = floor(($totalDateDuration % 3600) / 60);
            $totalSeconds = $totalDateDuration % 60;
            $totalFormattedTime = sprintf("%02d:%02d:%02d", $totalHours, $totalMinutes, $totalSeconds);

            // Add to hierarchical stats
            $hierarchicalStats[] = [
                'date' => $date,
                'display_date' => $displayDate,
                'total_duration' => $totalDateDuration,
                'total_formatted_time' => $totalFormattedTime,
                'staff' => $staffData,
            ];
        }

        return $hierarchicalStats;
    }
}
