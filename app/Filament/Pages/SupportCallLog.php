<?php
namespace App\Filament\Pages;

use App\Models\CallLog;
use App\Models\Lead;
use App\Models\PhoneExtension;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\DB;

class SupportCallLog extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $title = '';
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationLabel = 'Call Logs';
    protected static ?string $slug = 'call-logs';
    protected static ?int $navigationSort = 85;
    protected static ?string $navigationGroup = 'Communication';
    protected static string $view = 'filament.pages.support-call-log';

    public $showStaffStats = false;
    public $slideOverTitle = 'Support Staff Call Analytics';
    public $staffStats = [];
    public $type = 'all'; // Add this line to track the current type

    public $expandedStaff = [];
    public $staffDateTimes = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.call-logs');
    }

    public function getReceptionCalls(): Builder
    {
        // Get all support staff extensions
        $supportExtensions = PhoneExtension::where('is_support_staff', true)
            ->where('is_active', true)
            ->pluck('extension')
            ->toArray();

        // Add reception extension
        $receptionExtension = PhoneExtension::where('extension', '100')->value('extension') ?? '100';

        $query = CallLog::query()
            ->where(function ($query) use ($supportExtensions, $receptionExtension) {
                // Include calls where reception or support staff are involved
                $query->whereIn('caller_number', array_merge([$receptionExtension], $supportExtensions))
                    ->orWhereIn('receiver_number', $supportExtensions);
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
        $supportStaffOptions = [];
        $extensionUserMapping = [];

        $supportStaff = PhoneExtension::with('user')
            ->where('is_support_staff', true)
            ->where('is_active', true)
            ->get();

        foreach ($supportStaff as $staff) {
            $userName = ($staff->user_id && $staff->user) ? $staff->user->name : $staff->name;
            $supportStaffOptions[$userName] = $userName;
            $extensionUserMapping[$userName] = $staff->extension;
        }

        return $table
            ->query($this->getReceptionCalls())
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->sortable(),

                TextColumn::make('staff_name')
                    ->label('Support')
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

                TextColumn::make('tier1_category_id')
                    ->label('Module')
                    ->formatStateUsing(function ($record) {
                        return $record->tier1Category ? $record->tier1Category->name : '—';
                    })
                    ->sortable(),

                // TextColumn::make('tier2_category_id')
                //     ->label('Main Category')
                //     ->formatStateUsing(function ($record) {
                //         return $record->tier2Category ? $record->tier2Category->name : '—';
                //     })
                //     ->sortable()
                //     ->toggleable(),

                // TextColumn::make('tier3_category_id')
                //     ->label('Sub Category')
                //     ->formatStateUsing(function ($record) {
                //         return $record->tier3Category ? $record->tier3Category->name : '—';
                //     })
                //     ->sortable()
                //     ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('staff_name')
                    ->label('Support')
                    ->options($supportStaffOptions)
                    ->query(function (Builder $query, array $data) use ($extensionUserMapping): Builder {
                        // If no data or no value selected, return unmodified query
                        if (empty($data['value'])) {
                            return $query;
                        }

                        $staffName = $data['value'];

                        // Find the extension for this staff name
                        $extension = null;
                        foreach ($extensionUserMapping as $name => $ext) {
                            if ($name === $staffName) {
                                $extension = $ext;
                                break;
                            }
                        }

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
                                // For internal calls, check both caller and receiver
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
                    ]),

                SelectFilter::make('task_status')
                    ->options([
                        'Completed' => 'Completed',
                        'Pending' => 'Pending',
                    ]),

                Filter::make('started_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
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
                    Action::make('view')
                        ->label('View')
                        ->color('secondary')
                        ->icon('heroicon-o-eye')
                        ->visible(function (CallLog $record) {
                            return $record->task_status === 'Completed';
                        })
                        ->modalHeading('')
                        ->modalContent(function (CallLog $record) {
                            return Infolist::make()
                                ->record($record)
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('question')
                                        ->label('Question')
                                        ->formatStateUsing(fn ($state) => nl2br($state))
                                        ->html()
                                        ->columnSpanFull(),
                                    // Add other fields you want to display
                                ]);
                        })
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false),
                    Action::make('submit')
                        ->label('Submit ')
                        ->color('success')
                        ->icon('heroicon-o-paper-airplane')
                        ->visible(function (CallLog $record) {
                            if ($record->task_status !== 'Pending') {
                                return false;
                            }

                            if (auth()->user()->role_id == 3) {
                                return true;
                            }

                            return $record->staff_name === auth()->user()->name;
                        })
                        ->form([
                            Select::make('tier1_category_id')
                                ->label('Module (Tier 1)')
                                ->options(function () {
                                    return \App\Models\CallCategory::where('tier', '1')
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(fn (callable $set) => $set('tier2_category_id', null))
                                ->afterStateUpdated(fn (callable $set) => $set('tier3_category_id', null)),

                            // Select::make('tier2_category_id')
                            //     ->label('Main Category (Tier 2)')
                            //     ->options(function (callable $get) {
                            //         $tier1Id = $get('tier1_category_id');
                            //         if (!$tier1Id) {
                            //             return [];
                            //         }

                            //         return \App\Models\CallCategory::where('tier', '2')
                            //             ->where('parent_id', $tier1Id)
                            //             ->where('is_active', true)
                            //             ->pluck('name', 'id');
                            //     })
                            //     ->searchable()
                            //     ->reactive()
                            //     ->afterStateUpdated(fn (callable $set) => $set('tier3_category_id', null))
                            //     ->visible(fn (callable $get) => (bool) $get('tier1_category_id')),

                            // Select::make('tier3_category_id')
                            //     ->label('Sub Category (Tier 3)')
                            //     ->options(function (callable $get) {
                            //         $tier2Id = $get('tier2_category_id');
                            //         if (!$tier2Id) {
                            //             return [];
                            //         }

                            //         return \App\Models\CallCategory::where('tier', '3')
                            //             ->where('parent_id', $tier2Id)
                            //             ->where('is_active', true)
                            //             ->pluck('name', 'id');
                            //     })
                            //     ->searchable()
                            //     ->visible(fn (callable $get) => (bool) $get('tier2_category_id')),

                            // Select::make('task_status')
                            //     ->label('Task Status')
                            //     ->options([
                            //         'Pending' => 'Pending',
                            //         'Completed' => 'Completed',
                            //     ])
                            //     ->searchable()
                            //     ->default('Pending'),

                            Textarea::make('question')
                                ->label('Question')
                                ->required()
                                ->extraAlpineAttributes([
                                    'x-on:input' => '
                                        const start = $el.selectionStart;
                                        const end = $el.selectionEnd;
                                        const value = $el.value;
                                        $el.value = value.toUpperCase();
                                        $el.setSelectionRange(start, end);
                                    '
                                ])
                                ->columnSpanFull(),
                        ])
                        ->action(function (CallLog $record, array $data): void {
                            $data['task_status'] = 'Completed';

                            $record->update($data);

                            Notification::make()
                                ->title('Call log updated successfully')
                                ->success()
                                ->send();
                        })
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
            $this->slideOverTitle = 'Support Staff - Call Duration';

            // Get support staff with active extensions
            $supportExtensions = \App\Models\PhoneExtension::with('user')
                ->where('is_support_staff', true)
                ->where('is_active', true)
                ->get();

            $staffStats = [];

            foreach ($supportExtensions as $extension) {
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

                    // Staff sections start collapsed by default - removed the auto-expansion code
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
                    $this->slideOverTitle = 'Support Staff - Completed Calls';
                    break;
                case 'pending':
                    $this->slideOverTitle = 'Support Staff - Pending Calls';
                    break;
                default:
                    $this->slideOverTitle = 'Support Staff - Call Analytics';
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

        // Get all support staff
        $supportStaff = PhoneExtension::with('user')
            ->where('is_support_staff', true)
            ->where('is_active', true)
            ->get();

        foreach ($supportStaff as $staff) {
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

            // Create separate query instances for each metric to avoid filter confusion
            $completedCalls = $baseQueryBuilder()->where('task_status', 'Completed')->count();
            $pendingCalls = $baseQueryBuilder()->where('task_status', 'Pending')->count();

            // Calculate total call duration (use the filtered query if type is specified)
            $durationQuery = $type === 'all' ? $baseQueryBuilder() : $query;
            $totalDuration = $durationQuery->sum('call_duration');

            // Format total time
            $hours = floor($totalDuration / 3600);
            $minutes = floor(($totalDuration % 3600) / 60);
            $seconds = $totalDuration % 60;
            $totalTime = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

            // Calculate average call duration
            $countForAvg = $type === 'all' ? $totalCalls : $query->count();
            $avgDuration = $countForAvg > 0 ? ($totalDuration / $countForAvg) : 0;
            $avgMinutes = floor($avgDuration / 60);
            $avgSeconds = floor($avgDuration % 60);
            $avgTime = sprintf("%02d:%02d", $avgMinutes, $avgSeconds);

            // Calculate completion rate
            $completionRate = $totalCalls > 0 ? round(($completedCalls / $totalCalls) * 100) : 0;

            // Skip if we have a type filter and there are no matching calls
            if (($type === 'completed' && $completedCalls === 0) ||
                ($type === 'pending' && $pendingCalls === 0) ||
                ($type === 'duration' && $totalDuration === 0)) {
                continue;
            }

            // Add to stats array - THIS IS THE KEY CHANGE - using $staffName instead of $staff->name
            $stats[] = [
                'name' => $staffName, // FIXED: Use the correctly determined $staffName variable
                'extension' => $staff->extension,
                'user_id' => $staff->user_id,
                'total_calls' => $totalCalls,
                'completed_calls' => $completedCalls,
                'pending_calls' => $pendingCalls,
                'total_duration' => $totalDuration,
                'total_time' => $totalTime,
                'avg_time' => $avgTime,
                'completion_rate' => $completionRate,
            ];
        }

        // Sort by relevant metric based on type
        if ($type === 'completed') {
            usort($stats, function($a, $b) {
                return $b['completed_calls'] <=> $a['completed_calls'];
            });
        } elseif ($type === 'pending') {
            usort($stats, function($a, $b) {
                return $b['pending_calls'] <=> $a['pending_calls'];
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
        // Get all support staff extensions
        $supportExtensions = PhoneExtension::where('is_support_staff', true)
            ->where('is_active', true)
            ->pluck('extension')
            ->toArray();

        // Add reception extension
        $receptionExtension = PhoneExtension::where('extension', '100')->value('extension') ?? '100';

        // Step 1: Get all dates with calls
        $dates = CallLog::query()
            ->where(function ($query) use ($supportExtensions, $receptionExtension) {
                $query->whereIn('caller_number', array_merge([$receptionExtension], $supportExtensions))
                    ->orWhereIn('receiver_number', $supportExtensions);
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
                ->where(function ($query) use ($supportExtensions, $receptionExtension) {
                    $query->whereIn('caller_number', array_merge([$receptionExtension], $supportExtensions))
                        ->orWhereIn('receiver_number', $supportExtensions);
                })
                ->whereDate('started_at', $date)
                ->where('call_status', '!=', 'NO ANSWER')
                ->where(function($query) {
                    $query->where('call_duration', '>=', 5)
                        ->orWhereNull('call_duration');
                })
                ->get(['caller_number', 'receiver_number'])
                ->map(function ($call) use ($supportExtensions) {
                    if (in_array($call->caller_number, $supportExtensions)) {
                        return $call->caller_number;
                    }
                    if (in_array($call->receiver_number, $supportExtensions)) {
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
