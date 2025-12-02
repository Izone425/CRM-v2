<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Filament\Actions\ImplementerActions;
use App\Mail\CancelRepairAppointmentNotification;
use App\Mail\RepairAppointmentNotification;
use App\Models\ActivityLog;
use App\Models\AdminRepair;
use App\Models\Appointment;
use App\Models\ImplementerAppointment;
use App\Models\RepairAppointment;
use App\Models\SoftwareHandover;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\TemplateSelector;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
use Spatie\Activitylog\Traits\LogsActivity;
use Livewire\Attributes\On;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Filters\SelectFilter;

class ImplementerAppointmentRelationManager extends RelationManager
{
    protected static string $relationship = 'implementerAppointment';

    #[On('refresh-repair-appointments')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    protected function getTableHeading(): string
    {
        return __('Implementer Sessions');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function defaultForm()
    {
        return [
            Grid::make(3)
            ->schema([
                DatePicker::make('date')
                    ->required()
                    ->label('DATE (MONDAY-THURSDAY/FRIDAY)')
                    ->default(function ($record = null) {
                        return $record ? $record->date : Carbon::today()->toDateString();
                    })
                    ->reactive()
                    ->columnSpan(1),

                Select::make('session')
                    ->label('SESSION')
                    ->options(function (callable $get) {
                        $date = $get('date');
                        $selectedImplementer = $get('implementer');

                        if (!$date) return [];

                        $selectedDate = Carbon::parse($date);
                        $dayOfWeek = $selectedDate->dayOfWeek;

                        // Define base sessions based on day
                        if ($dayOfWeek === 5) { // Friday
                            $baseSessions = [
                                'SESSION 1' => 'SESSION 1 (0930 - 1030)',
                                'SESSION 2' => 'SESSION 2 (1100 - 1200)',
                                'SESSION 3' => 'SESSION 3 (1500 - 1600)',
                                'SESSION 4' => 'SESSION 4 (1630 - 1730)',
                            ];
                        } else if ($dayOfWeek >= 1 && $dayOfWeek <= 4) { // Monday to Thursday
                            $baseSessions = [
                                'SESSION 1' => 'SESSION 1 (0930 - 1030)',
                                'SESSION 2' => 'SESSION 2 (1100 - 1200)',
                                'SESSION 3' => 'SESSION 3 (1400 - 1500)',
                                'SESSION 4' => 'SESSION 4 (1530 - 1630)',
                                'SESSION 5' => 'SESSION 5 (1700 - 1800)',
                            ];
                        } else {
                            // Weekend
                            return ['NO_SESSIONS' => 'No sessions available on weekends'];
                        }

                        // Filter out unavailable sessions for the selected implementer
                        $availableSessions = [];
                        foreach ($baseSessions as $sessionKey => $sessionLabel) {
                            // Check if this slot is already booked by the SAME implementer
                            $existingAppointment = \App\Models\ImplementerAppointment::where('date', $date)
                                ->where('session', $sessionKey)
                                ->where('implementer', $selectedImplementer) // Check same implementer
                                ->where('status', '!=', 'Cancelled')
                                ->first();

                            // Only include session if it's available (no existing appointment for this implementer)
                            if (!$existingAppointment) {
                                $availableSessions[$sessionKey] = $sessionLabel;
                            }
                        }

                        // If no sessions are available, show a message
                        if (empty($availableSessions)) {
                            return ['NO_AVAILABLE' => 'No sessions available for this implementer on this date'];
                        }

                        return $availableSessions;
                    })
                    ->default(function (callable $get, ?Model $record = null) {
                        // If editing existing record, use its session value
                        if ($record && $record->session) {
                            return $record->session;
                        }

                        // For new records, find the first available session for the selected implementer
                        $date = $get('date');
                        $selectedImplementer = $get('implementer');

                        if (!$date || !$selectedImplementer) return null;

                        $selectedDate = Carbon::parse($date);
                        $dayOfWeek = $selectedDate->dayOfWeek;

                        // Get session list based on day
                        $sessions = [];
                        if ($dayOfWeek === 5) {
                            $sessions = ['SESSION 1', 'SESSION 2', 'SESSION 3', 'SESSION 4'];
                        } else if ($dayOfWeek >= 1 && $dayOfWeek <= 4) {
                            $sessions = ['SESSION 1', 'SESSION 2', 'SESSION 3', 'SESSION 4', 'SESSION 5'];
                        }

                        // Find first available session for this implementer
                        foreach ($sessions as $session) {
                            $existingAppointment = \App\Models\ImplementerAppointment::where('date', $date)
                                ->where('session', $session)
                                ->where('implementer', $selectedImplementer) // Check same implementer
                                ->where('status', '!=', 'Cancelled')
                                ->first();

                            if (!$existingAppointment) {
                                return $session; // Return first available session
                            }
                        }

                        return null; // No available sessions
                    })
                    ->columnSpan(2)
                    ->required()
                    ->reactive()
                    ->rules([
                        function (callable $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $date = $get('date');
                                $selectedImplementer = $get('implementer');

                                if (!$date || !$value || !$selectedImplementer) return;

                                // Prevent selection of unavailable indicator
                                if ($value === 'NO_AVAILABLE' || $value === 'NO_SESSIONS') {
                                    $fail('Please select a valid session or choose a different date.');
                                    return;
                                }

                                // Double-check availability for the same implementer (in case of race conditions)
                                $existingAppointment = \App\Models\ImplementerAppointment::where('date', $date)
                                    ->where('session', $value)
                                    ->where('implementer', $selectedImplementer) // Check same implementer
                                    ->where('status', '!=', 'Cancelled')
                                    ->first();

                                if ($existingAppointment) {
                                    $fail('This session slot has already been booked by this implementer. Please select a different session.');
                                }
                            };
                        },
                    ])
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Set the start_time and end_time based on selected session
                        $times = [
                            'SESSION 1' => ['09:30', '10:30'],
                            'SESSION 2' => ['11:00', '12:00'],
                            'SESSION 3' => ['14:00', '15:00'],
                            'SESSION 4' => ['15:30', '16:30'],
                            'SESSION 5' => ['17:00', '18:00'],
                        ];

                        // Friday has different times for sessions 3 and 4
                        $date = $get('date');
                        if ($date) {
                            $carbonDate = Carbon::parse($date);
                            if ($carbonDate->dayOfWeek === 5) { // Friday
                                $times['SESSION 3'] = ['15:00', '16:00'];
                                $times['SESSION 4'] = ['16:30', '17:30'];
                            }
                        }

                        if (isset($times[$state])) {
                            $set('start_time', $times[$state][0]);
                            $set('end_time', $times[$state][1]);
                            $set('start_time_display', $times[$state][0]);
                            $set('end_time_display', $times[$state][1]);
                        }
                    }),

                // Display-only time fields (non-editable)
                Hidden::make('start_time_display')
                    ->label('START TIME')
                    ->disabled()
                    ->default(function (callable $get) {
                        $session = $get('session');
                        $date = $get('date');

                        if (!$session || !$date) {
                            return '09:30';  // Default to SESSION 1 start time
                        }

                        $times = [
                            'SESSION 1' => '09:30',
                            'SESSION 2' => '11:00',
                            'SESSION 3' => '14:00',
                            'SESSION 4' => '15:30',
                            'SESSION 5' => '17:00',
                        ];

                        // Adjust for Friday
                        $selectedDate = Carbon::parse($date);
                        if ($selectedDate->dayOfWeek === 5) { // Friday
                            $times['SESSION 4'] = '15:00';
                            $times['SESSION 5'] = '16:30';
                        }

                        return $times[$session] ?? '09:30';
                    }),

                Hidden::make('end_time_display')
                    ->label('END TIME')
                    ->disabled()
                    ->default(function (callable $get) {
                        $session = $get('session');
                        $date = $get('date');

                        if (!$session || !$date) {
                            return '10:30';  // Default to SESSION 1 end time
                        }

                        $times = [
                            'SESSION 1' => '10:30',
                            'SESSION 2' => '12:00',
                            'SESSION 3' => '15:00',
                            'SESSION 4' => '16:30',
                            'SESSION 5' => '18:00',
                        ];

                        // Adjust for Friday
                        $selectedDate = Carbon::parse($date);
                        if ($selectedDate->dayOfWeek === 5) { // Friday
                            $times['SESSION 4'] = '16:00';
                            $times['SESSION 5'] = '17:30';
                        }

                        return $times[$session] ?? '10:30';
                    }),

                // These are hidden fields that will store the actual time values
                Hidden::make('start_time')
                    ->default('09:30'),

                Hidden::make('end_time')
                    ->default('10:30'),
            ]),
            Grid::make(3)
            ->schema([
                Select::make('type')
                    ->options(function () {
                        // Get the lead record
                        $lead = $this->getOwnerRecord();

                        // Find the latest software handover for this lead
                        $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                            ->latest()
                            ->first();

                        // Check if there are any existing kick-off meetings that are completed or scheduled
                        $hasKickoffAppointment = \App\Models\ImplementerAppointment::where('lead_id', $lead->id)
                            ->where('software_handover_id', $softwareHandover->id ?? 0)
                            ->where('type', 'KICK OFF MEETING SESSION')
                            ->whereIn('status', ['Done', 'New']) // Check for completed or scheduled kick-offs
                            ->exists();

                        // Also check if kick_off_meeting exists in the software handover record as a backup
                        $hasKickoffMeeting = $softwareHandover && !empty($softwareHandover->kick_off_meeting);

                        // If either condition is true, allow REVIEW SESSIONs
                        if ($hasKickoffAppointment || $hasKickoffMeeting) {
                            return [
                                'REVIEW SESSION' => 'REVIEW SESSION',
                            ];
                        } else {
                            return [
                                'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                            ];
                        }
                    })
                    ->default(function () {
                        // Get the lead record
                        $lead = $this->getOwnerRecord();

                        // Find the latest software handover
                        $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                            ->latest()
                            ->first();

                        // Check if there are any existing kick-off meetings that are completed or scheduled
                        $hasKickoffAppointment = \App\Models\ImplementerAppointment::where('lead_id', $lead->id)
                            ->where('software_handover_id', $softwareHandover->id ?? 0)
                            ->where('type', 'KICK OFF MEETING SESSION')
                            ->whereIn('status', ['Completed', 'New'])
                            ->exists();

                        // Also check if kick_off_meeting exists in the software handover record as a backup
                        $hasKickoffMeeting = $softwareHandover && !empty($softwareHandover->kick_off_meeting);

                        // Set default based on whether any kick-off meeting exists
                        return ($hasKickoffAppointment || $hasKickoffMeeting)
                            ? 'REVIEW SESSION'
                            : 'KICK OFF MEETING SESSION';
                    })
                    ->required()
                    ->label('SESSION TYPE')
                    ->reactive()
                    ->dehydrated(true),

                Select::make('appointment_type')
                    ->options([
                        'ONLINE' => 'ONLINE',
                        'ONSITE' => 'ONSITE',
                        'INHOUSE' => 'INHOUSE',
                    ])
                    ->required()
                    ->default('ONLINE')
                    ->label('APPOINTMENT TYPE'),

                Select::make('implementer')
                    ->label('IMPLEMENTER')
                    ->options(function () {
                        // Get the lead record
                        $lead = $this->getOwnerRecord();

                        // Find the latest software handover for this lead
                        $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                            ->latest()
                            ->first();

                        // If we found a software handover with an implementer, only show that implementer
                        if ($softwareHandover && $softwareHandover->implementer) {
                            return [$softwareHandover->implementer => $softwareHandover->implementer];
                        }

                        // Fallback: if no software handover or no implementer assigned,
                        // show all implementers (role_id 4 or 5)
                        return \App\Models\User::whereIn('role_id', [4, 5])
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($tech) {
                                return [$tech->name => $tech->name];
                            })
                            ->toArray();
                    })
                    ->default(function ($record = null) {
                        // First try to get from existing record if editing
                        if ($record && $record->implementer) {
                            return $record->implementer;
                        }

                        // If creating new record or record has no implementer,
                        // try to get from lead's latest software handover
                        $lead = $this->getOwnerRecord();
                        if ($lead) {
                            $softwareHandover = $lead->softwareHandover()->latest()->first();
                            if ($softwareHandover && $softwareHandover->implementer) {
                                return $softwareHandover->implementer;
                            }
                        }

                        // Default to null if nothing found
                        return null;
                    })
                    ->searchable()
                    ->required()
                    ->disabled(function () {
                        // Disable the field if there's a software handover with an implementer
                        $lead = $this->getOwnerRecord();
                        if (!$lead) return false;

                        $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                            ->latest()
                            ->first();

                        return $softwareHandover && $softwareHandover->implementer;
                    })
                    ->dehydrated(true)
                    ->placeholder('Select a implementer'),
                ]),

            TextInput::make('required_attendees')
                ->label('REQUIRED ATTENDEES')
                ->default(function () {
                    // Get the lead record
                    $lead = $this->getOwnerRecord();
                    if (!$lead) return null;

                    // Get the most recent software handover for this lead
                    $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                        ->latest()
                        ->first();

                    if (!$softwareHandover) return null;

                    // Handle the implementation_pics field properly
                    $implementation_pics = $softwareHandover->implementation_pics;
                    $emails = [];

                    // Handle JSON string format (stored as string)
                    if (is_string($implementation_pics)) {
                        try {
                            $pics = json_decode($implementation_pics, true);
                            if (is_array($pics)) {
                                foreach ($pics as $pic) {
                                    if (!empty($pic['pic_email_impl'])) {
                                        $emails[] = $pic['pic_email_impl'];
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error parsing JSON: ' . $e->getMessage());
                        }
                    }
                    // Handle array format (if using model casting)
                    else if (is_array($implementation_pics)) {
                        foreach ($implementation_pics as $pic) {
                            if (!empty($pic['pic_email_impl'])) {
                                $emails[] = $pic['pic_email_impl'];
                            }
                        }
                    }

                    return !empty($emails) ? implode(';', $emails) : null;
                })
                ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).')
                ->hidden(fn (callable $get): bool => (bool) $get('skip_email_teams'))
                ->reactive(),

            Checkbox::make('skip_email_teams')
                ->label('Skip Email & Teams Meeting')
                ->helperText('Check this to create appointment without sending emails or creating a Teams meeting')
                ->default(false)
                ->reactive(),

            Textarea::make('remarks')
                ->label('REMARKS')
                ->rows(3)
                ->autosize()
                ->default(function ($record = null) {
                    return $record ? $record->remarks : '';
                })
                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
            ->defaultPaginationPageOption('all')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Filter')
                    ->options([
                        'new_done' => 'New + Done',
                        'cancelled' => 'Cancelled Only',
                    ])
                    ->default('new_done') // ✅ Default to "New + Done"
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? 'new_done';

                        if ($value === 'new_done') {
                            // Show only New and Done statuses
                            return $query->whereIn('status', ['New', 'Done']);
                        } elseif ($value === 'cancelled') {
                            // Show only Cancelled status
                            return $query->where('status', 'Cancelled');
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $value = $data['value'] ?? null;

                        if (!$value || $value === 'new_done') {
                            return 'Showing: New + Done';
                        }

                        if ($value === 'all') {
                            return 'Showing: All Status';
                        }

                        if ($value === 'cancelled') {
                            return 'Showing: Cancelled Only';
                        }

                        return null;
                    }),
            ])
            ->columns([
                TextColumn::make('implementer')
                    ->label('IMPLEMENTER')
                    ->sortable(),
                TextColumn::make('type')
                    ->label(new HtmlString('IMPLEMENTATION<br>TYPE'))
                    ->sortable()
                    ->formatStateUsing(function (string $state): string {
                        // Count the number of words
                        $words = str_word_count($state);

                        // Only add <br> before SESSION if there are more than 3 words
                        if ($words > 3) {
                            return str_replace(' SESSION', '<br>SESSION', $state);
                        }

                        // Return as-is if 3 words or less
                        return $state;
                    })
                    ->html()
                    ->searchable(),
                TextColumn::make('appointment_type')
                    ->label(new HtmlString('APPOINTMENT<br>TYPE'))
                    ->sortable(),
                TextColumn::make('review_session_count')
                    ->label(new HtmlString('REVIEW<br>SESSIONS'))
                    ->getStateUsing(function ($record) {
                        // Skip cancelled sessions
                        if ($record->status == 'Cancelled') {
                            return '-';
                        }

                        // For weekly follow-up sessions, return week number if available
                        if ($record->type === 'WEEKLY FOLLOW UP SESSION' && $record->selected_week) {
                            return $record->selected_week;
                        }

                        // Get all appointments of this specific type for this lead that aren't cancelled
                        $sessions = \App\Models\ImplementerAppointment::where('lead_id', $record->lead_id)
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

                        // Return the position for display
                        return $position > 0 ? $position : '-';
                    })
                    ->alignCenter()
                    ->color(function ($state) {
                        // Only color as success if it's a number greater than 0
                        if (is_numeric($state) && $state > 0) {
                            return 'success';
                        }
                        return 'gray';
                    })
                    ->weight('bold'),
                TextColumn::make('date')
                    ->label('DATE & TIME')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        if (!$record->date || !$record->start_time || !$record->end_time) {
                            return 'No Data Available';
                        }

                        try {
                            // Format the date
                            $date = \Carbon\Carbon::parse($record->date)->format('d M Y');

                            // Try different time formats
                            $startTime = '';
                            $endTime = '';

                            // Try H:i:s format first, then H:i
                            try {
                                $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $record->start_time)->format('h:i A');
                            } catch (\Exception $e) {
                                $startTime = \Carbon\Carbon::createFromFormat('H:i', $record->start_time)->format('h:i A');
                            }

                            try {
                                $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $record->end_time)->format('h:i A');
                            } catch (\Exception $e) {
                                $endTime = \Carbon\Carbon::createFromFormat('H:i', $record->end_time)->format('h:i A');
                            }

                            // ✅ Return HTML with line break between date and time
                            return new \Illuminate\Support\HtmlString("{$date}<br>{$startTime} - {$endTime}");
                        } catch (\Exception $e) {
                            return 'Invalid Date/Time Format';
                        }
                    })
                    ->html(),
                TextColumn::make('session_recording_link')
                    ->label('RECORDING LINK')
                    ->sortable()
                    ->limit(30)
                    ->copyable(),
                IconColumn::make('view_remark')
                    ->label('View Remark')
                    ->alignCenter()
                    ->getStateUsing(fn() => true)
                    ->icon(fn () => 'heroicon-o-magnifying-glass-plus')
                    ->color(fn () => 'blue')
                    ->tooltip('View Remark')
                    ->extraAttributes(['class' => 'cursor-pointer'])
                    ->action(
                        Action::make('view_remarks')
                            ->label('View Remark')
                            ->modalHeading('Appointment Remarks')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalDescription('Here are the remarks for this specific implementation appointment.')
                            ->modalContent(function (ImplementerAppointment $record) {
                                // Check if the appointment has direct remarks
                                if (!empty($record->remarks)) {
                                    // Format the direct remarks
                                    $timestamp = $record->updated_at->format('Y-m-d H:i:s');
                                    $formattedRemark = nl2br(e($record->remarks));

                                    // Build the HTML for the remarks
                                    $remarksHtml = '<div class="p-4 rounded-lg bg-gray-50">';
                                    // $remarksHtml .= "<p class='mb-1 text-sm text-gray-500'>Last updated: <strong>{$timestamp}</strong></p>";
                                    $remarksHtml .= "<div class='text-gray-800 whitespace-pre-line'>{$formattedRemark}</div>";
                                    $remarksHtml .= '</div>';

                                    return new HtmlString($remarksHtml);
                                }

                                // If no direct remarks, show a message
                                return new HtmlString('<p class="p-4 text-center text-gray-500">No remarks available for this appointment.</p>');
                            }),
                        ),
                TextColumn::make('status')
                    ->label('STATUS')
                    ->sortable()
                    ->color(fn ($state) => match ($state) {
                        'Done' => 'success',
                        'Cancelled' => 'danger',
                        'New' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'Done' => 'heroicon-o-check-circle',
                        'Cancelled' => 'heroicon-o-x-circle',
                        'New' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    }),

            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('View Implementer Remark')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('info')
                        ->modalHeading('Implementer Remark')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->visible(fn (ImplementerAppointment $record) =>
                            !empty($record->implementer_remark) && $record->status === 'Cancelled'
                        )
                        ->modalContent(function (ImplementerAppointment $record) {
                            if ($record->status === 'Cancelled' && !empty($record->implementer_remark)) {
                                // Show cancellation remark with context
                                $cancelledBy = $record->cancelled_by ? User::find($record->cancelled_by)?->name : 'Unknown';
                                $cancelledAt = $record->cancelled_at ? Carbon::parse($record->cancelled_at)->format('d M Y, g:i A') : 'Unknown';

                                $remarksHtml = '<div class="space-y-4">';
                                // Cancellation details section
                                $remarksHtml .= '<div class="p-4 border border-red-200 rounded-lg bg-red-50">';
                                $remarksHtml .= '<h4 class="mb-2 text-sm font-semibold text-red-800">Cancellation Details</h4>';
                                $remarksHtml .= '<div class="mb-3 text-sm text-red-700">';
                                $remarksHtml .= '<div><strong>Cancelled by:</strong> ' . $cancelledBy . '</div>';
                                $remarksHtml .= '<div><strong>Cancelled at:</strong> ' . $cancelledAt . '</div>';
                                $remarksHtml .= '</div>';
                                $remarksHtml .= '<div class="mt-3">';
                                $remarksHtml .= '<strong class="text-red-800">Reason:</strong>';
                                $remarksHtml .= '<div class="p-3 mt-1 text-red-800 bg-white border rounded">' . nl2br(e($record->implementer_remark)) . '</div>';
                                $remarksHtml .= '</div>';
                                $remarksHtml .= '</div>';

                                $remarksHtml .= '</div>';

                                return new HtmlString($remarksHtml);
                            }else {
                                // No implementer remark available
                                return new HtmlString('<p class="p-4 text-center text-gray-500">No implementer remark available for this appointment.</p>');
                            }
                        }),
                    Tables\Actions\Action::make('View Appointment')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->modalHeading('Implementation Appointment Details')
                        ->modalSubmitAction(false)
                        ->form(function ($record) {
                            if (!$record) {
                                return [
                                    TextInput::make('error')->default('Appointment not found')->disabled(),
                                ];
                            }

                            return [
                                DatePicker::make('date')
                                    ->label('Date')
                                    ->default($record->date)
                                    ->disabled(),


                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('session')
                                            ->label('SESSION')
                                            ->default(strtoupper($record->session))
                                            ->disabled(),

                                        TimePicker::make('start_time')
                                            ->label('START TIME')
                                            ->default($record->start_time)
                                            ->disabled(),

                                        TimePicker::make('end_time')
                                            ->label('END TIME')
                                            ->default($record->end_time)
                                            ->disabled(),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('type')
                                            ->label('DEMO TYPE')
                                            ->default(strtoupper($record->type))
                                            ->disabled(),

                                        TextInput::make('appointment_type')
                                            ->label('APPOINTMENT TYPE')
                                            ->default($record->appointment_type)
                                            ->disabled(),

                                        TextInput::make('implementer')
                                            ->label('IMPLEMENTER')
                                            ->default($record->implementer)
                                            ->disabled(),
                                    ]),

                                TextInput::make('required_attendees')
                                    ->label('REQUIERED ATTENDEES')
                                    ->default($record->required_attendees)
                                    ->disabled(),

                                Textarea::make('remarks')
                                    ->label('REMARKS')
                                    ->default($record->remarks)
                                    ->autosize()
                                    ->disabled()
                                    ->reactive()
                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                            ];
                        }),

                    // self::rescheduleAppointmentAction(),

                    Tables\Actions\Action::make('appointment_cancel')
                        ->visible(fn (ImplementerAppointment $appointment) =>
                            now()->lte(Carbon::parse($appointment->date)->addDays(7)) && $appointment->status !== 'Cancelled'
                        )
                        ->label(__('Cancel Appointment'))
                        ->modalHeading('Cancel Implementation Appointment')
                        ->modalDescription('Please provide a reason for cancelling this appointment.')
                        ->form([
                            Textarea::make('implementer_remark')
                                ->label('CANCELLATION REASON')
                                ->placeholder('Please explain why you are cancelling this appointment...')
                                ->required()
                                ->rows(4)
                                ->maxLength(1000)
                                ->helperText('This reason will be recorded and visible to administrators.')
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                        ])
                        ->requiresConfirmation()
                        ->modalSubmitActionLabel('Cancel Appointment')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->hidden(function() {
                            // Get the current user
                            $user = auth()->user();

                            // Get the lead record
                            $lead = $this->getOwnerRecord();
                            if (!$lead) return true; // Hide if no lead record found

                            // Admins (role_id = 3) can always add appointments
                            if ($user->role_id == 3) {
                                return false; // Don't hide for admins
                            }

                            // Find the latest software handover for this lead
                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                                ->latest()
                                ->first();

                            // If there's a software handover and the current user is the assigned implementer, allow access
                            if ($softwareHandover && $softwareHandover->implementer === $user->name) {
                                return false; // Don't hide for the assigned implementer
                            }

                            // For all other cases, hide the button
                            return true;
                        })
                        ->action(function (array $data, ImplementerAppointment $record) {
                            // Update the Appointment status
                            try {
                                // Update status to Cancelled with implementer remark
                                $record->status = 'Cancelled';
                                $record->request_status = 'CANCELLED';
                                $record->implementer_remark = $data['implementer_remark'] ?? null;
                                $record->cancelled_by = auth()->id();
                                $record->cancelled_at = now();

                                // Cancel Teams meeting if exists
                                if ($record->event_id) {
                                    $eventId = $record->event_id;

                                    // Get implementer's email instead of using organizer_email
                                    $implementer = User::where('name', $record->implementer)->first();

                                    if ($implementer && $implementer->email) {
                                        $implementerEmail = $implementer->email;

                                        try {
                                            $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
                                            $graph = new Graph();
                                            $graph->setAccessToken($accessToken);

                                            // Cancel the Teams meeting using implementer's email
                                            $graph->createRequest("DELETE", "/users/$implementerEmail/events/$eventId")->execute();

                                            Notification::make()
                                                ->title('Teams Meeting Cancelled Successfully')
                                                ->warning()
                                                ->body('The meeting has been cancelled in Microsoft Teams.')
                                                ->send();

                                        } catch (\Exception $e) {
                                            Log::error('Failed to cancel Teams meeting: ' . $e->getMessage(), [
                                                'event_id' => $eventId,
                                                'implementer' => $implementerEmail,
                                                'trace' => $e->getTraceAsString()
                                            ]);

                                            Notification::make()
                                                ->title('Failed to Cancel Teams Meeting')
                                                ->warning()
                                                ->body('The appointment was cancelled, but there was an error cancelling the Teams meeting: ' . $e->getMessage())
                                                ->send();
                                        }
                                    } else {
                                        Log::error('Failed to cancel Teams meeting: Implementer email not found', [
                                            'event_id' => $eventId,
                                            'implementer_name' => $record->implementer
                                        ]);

                                        Notification::make()
                                            ->title('Failed to Cancel Teams Meeting')
                                            ->warning()
                                            ->body('The appointment was cancelled, but the implementer email was not found.')
                                            ->send();
                                    }
                                }

                                if ($record->lead_id) {
                                    try {
                                        // Find the customer associated with this lead
                                        $customer = \App\Models\Customer::where('lead_id', $record->lead_id)->first();

                                        if ($customer) {
                                            // Update customer's able_set_meeting to true
                                            $customer->update(['able_set_meeting' => true]);

                                            Log::info('Customer able_set_meeting enabled after admin cancellation', [
                                                'customer_id' => $customer->id,
                                                'customer_email' => $customer->email,
                                                'appointment_id' => $record->id,
                                                'company_name' => $customer->company_name,
                                                'cancelled_by' => auth()->user()->name,
                                                'cancellation_type' => 'admin_cancellation'
                                            ]);
                                        } else {
                                            Log::warning('Customer not found for lead_id during cancellation', [
                                                'lead_id' => $record->lead_id,
                                                'appointment_id' => $record->id
                                            ]);
                                        }
                                    } catch (\Exception $e) {
                                        Log::error('Failed to update customer able_set_meeting during admin cancellation: ' . $e->getMessage(), [
                                            'appointment_id' => $record->id,
                                            'lead_id' => $record->lead_id,
                                            'trace' => $e->getTraceAsString()
                                        ]);
                                    }
                                }

                                $record->save();

                                Notification::make()
                                    ->title('Appointment cancelled successfully')
                                    ->success()
                                    ->send();

                                // Refresh tables
                                $this->dispatch('refresh-implementer-tables');
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error cancelling appointment')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }

                            try {
                                $companyDetail = null;
                                if ($record->lead_id) {
                                    $companyDetail = \App\Models\CompanyDetail::where('lead_id', $record->lead_id)->first();
                                }

                                $companyName = $companyDetail ? $companyDetail->company_name :
                                    ($record->title ?: 'Unknown Company');

                                $recipients = [];

                                // Add attendees from the appointment
                                if ($record->required_attendees) {
                                    // Handle JSON format
                                    if (is_string($record->required_attendees)) {
                                        try {
                                            $attendees = json_decode($record->required_attendees, true);
                                            if (is_array($attendees)) {
                                                $attendeeEmails = $attendees;
                                            } else {
                                                // Fallback to semicolon-separated string
                                                $attendeeEmails = array_map('trim', explode(';', $record->required_attendees));
                                            }
                                        } catch (\Exception $e) {
                                            // Fallback to semicolon-separated string
                                            $attendeeEmails = array_map('trim', explode(';', $record->required_attendees));
                                        }
                                    } else {
                                        $attendeeEmails = is_array($record->required_attendees) ? $record->required_attendees : [];
                                    }

                                    foreach ($attendeeEmails as $email) {
                                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                            $recipients[] = $email;
                                        }
                                    }
                                }

                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $authUser->email;
                                $senderName = $authUser->name;

                                $emailData = [
                                    'appointmentType' => $record->type,
                                    'companyName' => $companyName,
                                    'date' => Carbon::parse($record->date)->format('d F Y'),
                                    'time' => Carbon::parse($record->start_time)->format('g:i A') . ' - ' .
                                            Carbon::parse($record->end_time)->format('g:i A'),
                                    'implementer' => $record->implementer,
                                ];

                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send(
                                        'emails.implementer_appointment_cancelled',
                                        ['content' => $emailData],
                                        function ($message) use ($recipients, $senderEmail, $senderName, $companyName, $record) {
                                            $message->from($senderEmail, $senderName)
                                                ->to($recipients)
                                                ->cc($senderEmail)
                                                ->subject("CANCELLED: TIMETEC IMPLEMENTER APPOINTMENT | {$record->type} | {$companyName}");
                                        }
                                    );
                                }
                            } catch (\Exception $e) {
                                Log::error("Email sending failed for cancelled implementer appointment: Error: {$e->getMessage()}");
                            }

                            Notification::make()
                                ->title('You have cancelled an implementation appointment')
                                ->danger()
                                ->send();
                        }),

                    Tables\Actions\Action::make('add_session_follow_up')
                        ->label('Send Session Summary')
                        ->color('success')
                        ->icon('heroicon-o-envelope')
                        ->modalWidth('6xl')
                        ->modalHeading(function () {
                            $lead = $this->getOwnerRecord();
                            $companyName = 'Unknown Company';

                            if ($lead->companyDetail && $lead->companyDetail->company_name) {
                                $companyName = $lead->companyDetail->company_name;
                            } else {
                                $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                                    ->orderBy('created_at', 'desc')
                                    ->first();

                                if ($softwareHandover && $softwareHandover->company_name) {
                                    $companyName = $softwareHandover->company_name;
                                }
                            }

                            return "Add Session Summary for {$companyName}";
                        })
                        ->visible(function (ImplementerAppointment $record) {
                            return $record->sent_summary_email != 1 && $record->session_recording_link != null;
                        })
                        ->hidden(function() {
                            $user = auth()->user();
                            $lead = $this->getOwnerRecord();
                            if (!$lead) return true;

                            if ($user->role_id == 3) {
                                return false;
                            }

                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                                ->latest()
                                ->first();

                            if ($softwareHandover && $softwareHandover->implementer === $user->name) {
                                return false;
                            }

                            return true;
                        })
                        ->form([
                            Hidden::make('send_email')
                                ->default(true),

                            Hidden::make('scheduler_type')
                                ->default('instant'),

                            // ✅ Add Session Recording Link field
                            Grid::make(2)
                                ->schema([
                                    Select::make('project_plan_files')
                                        ->label('Project Plan Files')
                                        ->options(function () {
                                            $lead = $this->getOwnerRecord();
                                            if (!$lead) {
                                                return [];
                                            }

                                            $companyName = $lead->companyDetail?->company_name ?? 'Unknown';
                                            $companySlug = \Illuminate\Support\Str::slug($companyName);

                                            $files = \Illuminate\Support\Facades\Storage::disk('public')
                                                ->files('project-plans');

                                            $matchingFiles = [];
                                            foreach ($files as $file) {
                                                if (str_contains($file, $companySlug)) {
                                                    $fullPath = storage_path('app/public/' . $file);
                                                    $matchingFiles[] = [
                                                        'path' => $file,
                                                        'name' => basename($file),
                                                        'modified' => file_exists($fullPath) ? filemtime($fullPath) : 0
                                                    ];
                                                }
                                            }

                                            usort($matchingFiles, function($a, $b) {
                                                return $b['modified'] - $a['modified'];
                                            });

                                            $options = [];
                                            foreach ($matchingFiles as $file) {
                                                $label = $file['name'];
                                                if (isset($matchingFiles[0]) && $file['path'] === $matchingFiles[0]['path']) {
                                                    $label .= ' (Latest)';
                                                }
                                                $options[$file['path']] = $label;
                                            }

                                            return $options;
                                        })
                                        ->default(function () {
                                            $lead = $this->getOwnerRecord();
                                            if (!$lead) {
                                                return null;
                                            }

                                            $companyName = $lead->companyDetail?->company_name ?? 'Unknown';
                                            $companySlug = \Illuminate\Support\Str::slug($companyName);

                                            $files = \Illuminate\Support\Facades\Storage::disk('public')
                                                ->files('project-plans');

                                            $matchingFiles = [];
                                            foreach ($files as $file) {
                                                if (str_contains($file, $companySlug)) {
                                                    $fullPath = storage_path('app/public/' . $file);
                                                    $matchingFiles[] = [
                                                        'path' => $file,
                                                        'modified' => file_exists($fullPath) ? filemtime($fullPath) : 0
                                                    ];
                                                }
                                            }

                                            usort($matchingFiles, function($a, $b) {
                                                return $b['modified'] - $a['modified'];
                                            });

                                            return !empty($matchingFiles) ? [$matchingFiles[0]['path']] : null;
                                        })
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(1),

                                    TextInput::make('session_recording_link')
                                        ->label('Session Recording Link')
                                        ->placeholder('Recording Link Not Ready Yet')
                                        ->default(function (ImplementerAppointment $record) {
                                            return $record->session_recording_link ?: null;
                                        })
                                        ->disabled()
                                        ->dehydrated(true)
                                        ->helperText(fn (callable $get) =>
                                            empty($get('session_recording_link'))
                                                ? '⏳ Recording will be available after the meeting ends and is processed by Microsoft Teams (usually within 1-4 hours).'
                                                : '✅ Recording is ready'
                                        )
                                        ->suffixIcon(fn (callable $get) =>
                                            empty($get('session_recording_link'))
                                                ? 'heroicon-o-clock'
                                                : 'heroicon-o-check-circle'
                                        )
                                        ->suffixIconColor(fn (callable $get) =>
                                            empty($get('session_recording_link'))
                                                ? 'warning'
                                                : 'success'
                                        )
                                        ->columnSpan(1),
                                ]),

                            Fieldset::make('Email Details')
                                ->schema([
                                    TextInput::make('required_attendees')
                                        ->label('Required Attendees')
                                        ->default(function () {
                                            $lead = $this->getOwnerRecord();
                                            $emails = [];

                                            if ($lead) {
                                                $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                                                if ($softwareHandover && !empty($softwareHandover->implementation_pics) && is_string($softwareHandover->implementation_pics)) {
                                                    try {
                                                        $contacts = json_decode($softwareHandover->implementation_pics, true);

                                                        if (is_array($contacts)) {
                                                            foreach ($contacts as $contact) {
                                                                if (!empty($contact['pic_email_impl'])) {
                                                                    $emails[] = $contact['pic_email_impl'];
                                                                }
                                                            }
                                                        }
                                                    } catch (\Exception $e) {
                                                        Log::error('Error parsing implementation_pics JSON: ' . $e->getMessage());
                                                    }
                                                }

                                                if ($lead->companyDetail && !empty($lead->companyDetail->additional_pic)) {
                                                    try {
                                                        $additionalPics = json_decode($lead->companyDetail->additional_pic, true);

                                                        if (is_array($additionalPics)) {
                                                            foreach ($additionalPics as $pic) {
                                                                if (
                                                                    !empty($pic['email']) &&
                                                                    isset($pic['status']) &&
                                                                    $pic['status'] === 'Available'
                                                                ) {
                                                                    $emails[] = $pic['email'];
                                                                }
                                                            }
                                                        }
                                                    } catch (\Exception $e) {
                                                        Log::error('Error parsing additional_pic JSON: ' . $e->getMessage());
                                                    }
                                                }
                                            }

                                            $uniqueEmails = array_unique($emails);
                                            return !empty($uniqueEmails) ? implode(';', $uniqueEmails) : null;
                                        })
                                        ->required()
                                        ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

                                    Select::make('email_template')
                                        ->label('Email Template')
                                        ->options(function () {
                                            return \App\Models\EmailTemplate::whereIn('type', ['implementer'])
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $template = \App\Models\EmailTemplate::find($state);
                                                if ($template) {
                                                    $set('email_subject', $template->subject);
                                                    $set('email_content', $template->content);
                                                }
                                            }
                                        })
                                        ->required(),

                                    TextInput::make('email_subject')
                                        ->label('Email Subject')
                                        ->required(),

                                    RichEditor::make('email_content')
                                        ->label('Email Content')
                                        ->disableToolbarButtons([
                                            'attachFiles',
                                        ])
                                        ->required(),
                                ]),

                            Hidden::make('implementer_name')
                                ->default(auth()->user()->name ?? ''),

                            Hidden::make('implementer_designation')
                                ->default('Implementer'),

                            Hidden::make('implementer_company')
                                ->default('TimeTec Cloud Sdn Bhd'),

                            Hidden::make('implementer_phone')
                                ->default('03-80709933'),

                            Hidden::make('implementer_email')
                                ->default(auth()->user()->email ?? ''),

                            RichEditor::make('notes')
                                ->label('Remarks')
                                ->disableToolbarButtons([
                                    'attachFiles',
                                    'blockquote',
                                    'codeBlock',
                                    'h2',
                                    'h3',
                                    'link',
                                    'redo',
                                    'strike',
                                    'undo',
                                ])
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->placeholder('Add your follow-up details here...')
                                ->required()
                        ])
                        ->action(function (array $data, ImplementerAppointment $record) {
                            $lead = $this->getOwnerRecord();

                            $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                            if (!$softwareHandover) {
                                Notification::make()
                                    ->title('Error: Software Handover record not found')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // ✅ Add project plan files as attachments
                            if (!empty($data['project_plan_files'])) {
                                $attachments = [];
                                foreach ($data['project_plan_files'] as $filePath) {
                                    $fullPath = storage_path('app/public/' . $filePath);
                                    if (file_exists($fullPath)) {
                                        $attachments[] = $fullPath;
                                    }
                                }

                                $data['project_plan_attachments'] = $attachments;

                                Log::info('Project plan files prepared for email', [
                                    'files' => $data['project_plan_files'],
                                    'attachments' => $attachments,
                                    'count' => count($attachments)
                                ]);
                            }

                            $data['send_email'] = true;

                            ImplementerActions::processFollowUpWithEmail($softwareHandover, $data, false);

                            $record->update([
                                'status' => 'Done',
                                'sent_summary_email' => 1,
                                'summary_email_sent_at' => now(),
                                'summary_email_sent_by' => auth()->id(),
                                'session_recording_link' => $data['session_recording_link'] ?? null,
                            ]);

                            Log::info('Session summary email sent and marked', [
                                'appointment_id' => $record->id,
                                'lead_id' => $lead->id,
                                'sent_by' => auth()->user()->name,
                                'sent_at' => now(),
                                'has_attachments' => !empty($data['project_plan_files']),
                                'has_recording_link' => !empty($data['session_recording_link']),
                                'recording_link' => $data['session_recording_link'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Session Summary Sent Successfully')
                                ->success()
                                ->body('The session summary email has been sent and the appointment has been marked.')
                                ->send();

                            $this->dispatch('refresh');
                        }),
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
            ])->defaultSort('date', 'desc');
    }

    public function headerActions(): array
    {
        return [
            Tables\Actions\Action::make('Activate Review Sessions')
                ->label('Activate Sessions')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->modalHeading(false)
                ->modalWidth('6xl')
                ->hidden(function() {
                    $user = auth()->user();

                    $lead = $this->getOwnerRecord();
                    if (!$lead) return true;

                    $hasNewImplementerAppointments = \App\Models\ImplementerAppointment::where('lead_id', $lead->id)
                        ->where('status', 'New')
                        ->exists();

                    if ($hasNewImplementerAppointments) {
                        return true;
                    }

                    if ($user->role_id == 3) {
                        return false;
                    }

                    $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                        ->latest()
                        ->first();

                    if ($softwareHandover && $softwareHandover->implementer === $user->name) {
                        return false;
                    }

                    return true;
                })
                ->form([
                    Grid::make(3)
                        ->schema([
                            Hidden::make('enable_customer_booking')
                                ->default(true)
                                ->dehydrated(true),

                            Toggle::make('create_customer_account')
                                ->label('Create New Customer Account')
                                ->helperText('Check this to create a new customer account if one does not exist')
                                ->onIcon('heroicon-o-user-plus')
                                ->offIcon('heroicon-o-user-plus')
                                ->onColor('success')
                                ->offColor('gray')
                                ->default(true)
                                ->reactive()
                                ->visible(function () {
                                    // Only show this toggle if customer doesn't exist or doesn't have credentials
                                    $lead = $this->getOwnerRecord();
                                    $customer = \App\Models\Customer::where('lead_id', $lead->id)->first();

                                    return !$customer || empty($customer->email) || empty($customer->plain_password);
                                }),

                            Hidden::make('scheduler_type')
                                ->default('instant')
                                ->dehydrated(true),
                        ]),

                    Fieldset::make('Email Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    // Left column - Email form fields
                                    Grid::make(1)
                                        ->schema([
                                            TextInput::make('required_attendees')
                                                ->label('Required Attendees')
                                                ->default(function () {
                                                    $lead = $this->getOwnerRecord();
                                                    $emails = [];

                                                    if ($lead) {
                                                        // 1. Get emails from SoftwareHandover implementation_pics
                                                        $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                                                        if ($softwareHandover && !empty($softwareHandover->implementation_pics) && is_string($softwareHandover->implementation_pics)) {
                                                            try {
                                                                $contacts = json_decode($softwareHandover->implementation_pics, true);

                                                                if (is_array($contacts)) {
                                                                    foreach ($contacts as $contact) {
                                                                        if (!empty($contact['pic_email_impl'])) {
                                                                            $emails[] = $contact['pic_email_impl'];
                                                                        }
                                                                    }
                                                                }
                                                            } catch (\Exception $e) {
                                                                Log::error('Error parsing implementation_pics JSON: ' . $e->getMessage());
                                                            }
                                                        }

                                                        // 2. Get emails from company_detail->additional_pic
                                                        if ($lead->companyDetail && !empty($lead->companyDetail->additional_pic)) {
                                                            try {
                                                                $additionalPics = json_decode($lead->companyDetail->additional_pic, true);

                                                                if (is_array($additionalPics)) {
                                                                    foreach ($additionalPics as $pic) {
                                                                        // Only include contacts with "Available" status
                                                                        if (
                                                                            !empty($pic['email']) &&
                                                                            isset($pic['status']) &&
                                                                            $pic['status'] === 'Available'
                                                                        ) {
                                                                            $emails[] = $pic['email'];
                                                                        }
                                                                    }
                                                                }
                                                            } catch (\Exception $e) {
                                                                Log::error('Error parsing additional_pic JSON: ' . $e->getMessage());
                                                            }
                                                        }
                                                    }

                                                    // Remove duplicates and return as semicolon-separated string
                                                    $uniqueEmails = array_unique($emails);
                                                    return !empty($uniqueEmails) ? implode(';', $uniqueEmails) : null;
                                                })
                                                ->required(),

                                            Select::make('email_template')
                                                ->label('Email Template')
                                                ->options(function () {
                                                    return \App\Models\EmailTemplate::whereIn('type', ['implementer', 'client'])
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    if ($state) {
                                                        $template = \App\Models\EmailTemplate::find($state);
                                                        if ($template) {
                                                            $set('email_subject', $template->subject);
                                                            $set('email_content', $template->content);

                                                            // NEW: Trigger preview update
                                                            $set('template_selected', true);
                                                        }
                                                    }
                                                })
                                                ->required(),
                                            TextInput::make('email_subject')
                                                ->label('Email Subject')
                                                ->required()
                                                ->reactive(),

                                            RichEditor::make('email_content')
                                                ->label('Email Content')
                                                ->disableToolbarButtons([
                                                    'attachFiles',
                                                ])
                                                ->required()
                                                ->reactive(),

                                            Forms\Components\FileUpload::make('email_attachments')
                                                ->label('Email Attachments')
                                                ->multiple()
                                                ->maxFiles(5)
                                                ->acceptedFileTypes(['application/pdf'])
                                                ->maxSize(10240) // 10MB per file
                                                ->directory('temp_email_attachments')
                                                ->preserveFilenames()
                                                ->storeFileNamesIn('attachment_names')
                                                ->helperText('Upload up to 5 PDF files')
                                                ->columnSpanFull()
                                                ->reactive(),
                                        ])->columnSpan(1),

                                    // Right column - Email preview (remove the nested Grid and Fieldset)
                                    Forms\Components\Placeholder::make('email_preview')
                                        ->label('Email Preview')
                                        ->content(function (callable $get) {
                                            $subject = $get('email_subject') ?? '';
                                            $content = $get('email_content') ?? '';
                                            $createNewAccount = $get('create_customer_account') ?? false;

                                            if (empty($subject) && empty($content)) {
                                                return new \Illuminate\Support\HtmlString('<p class="italic text-gray-500">Select a template to see preview...</p>');
                                            }

                                            // Get customer data for replacement
                                            $lead = $this->getOwnerRecord();
                                            $customer = \App\Models\Customer::where('lead_id', $lead->id)->first();
                                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                                            // Get form values for preview
                                            $previewImplementerName = $get('implementer_name') ?? auth()->user()->name ?? '';
                                            $previewDesignation = $get('implementer_designation') ?? 'Implementer';

                                            // Determine customer credentials
                                            $customerEmail = '';
                                            $customerPassword = '';
                                            $isAutoGenerated = false;

                                            if ($customer && !empty($customer->email) && !empty($customer->plain_password)) {
                                                // Existing customer with credentials and not creating new account
                                                $customerEmail = $customer->email;
                                                $customerPassword = $customer->plain_password;
                                            } elseif ($createNewAccount) {
                                                // Auto-generate credentials preview using project code format
                                                $projectCode = $softwareHandover ? $softwareHandover->project_code : 'SW_250000';
                                                $codeWithoutPrefix = str_replace('SW_', '', $projectCode);

                                                // Show what the generated credentials would look like
                                                $customerEmail = "sw_{$codeWithoutPrefix}@timeteccloud.com";
                                                $customerPassword = 'Auto Generated Strong Password'; // Don't show actual password in preview
                                                $isAutoGenerated = true;
                                            } else {
                                                // No customer or credentials
                                                $customerEmail = 'Not Available';
                                                $customerPassword = 'Not Available';
                                            }

                                            // Replace placeholders
                                            $placeholders = [
                                                '{customer_name}' => $lead->contact_name ?? '',
                                                '{company_name}' => $softwareHandover->company_name ?? ($lead->companyDetail?->company_name ?? 'Unknown Company'),
                                                '{implementer_name}' => $previewImplementerName,
                                                '{implementer_designation}' => $previewDesignation,
                                                '{lead_owner}' => $lead->lead_owner ?? '',
                                                '{customer_email}' => $customerEmail,
                                                '{customer_password}' => $customerPassword,
                                                '{customer_portal_url}' => str_replace('http://', 'https://', config('app.url')) . '/customer/login',
                                                '{project_plan_link}' => $softwareHandover->project_plan_link ?? 'Not Generated Yet',
                                            ];

                                            $previewSubject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
                                            $previewContent = str_replace(array_keys($placeholders), array_values($placeholders), $content);

                                            // If auto-generated, make email and password red and bold in the preview
                                            if ($isAutoGenerated) {
                                                $previewContent = str_replace(
                                                    $customerEmail,
                                                    '<span style="color: red; font-weight: bold;">' . $customerEmail . ' (Will be generated)</span>',
                                                    $previewContent
                                                );
                                                $previewContent = str_replace(
                                                    $customerPassword,
                                                    '<span style="color: red; font-weight: bold;">' . $customerPassword . ' (Will be generated)</span>',
                                                    $previewContent
                                                );
                                            }

                                            // Add signature automatically at the end of the content regardless of template
                                            $signature = "<br>Regards,<br>{$previewImplementerName}<br>{$previewDesignation}<br>TimeTec Cloud Sdn Bhd<br>Phone: 03-80709933";
                                            $previewContent .= $signature;

                                            $html = '<div class="p-4 border rounded-lg bg-gray-50">';
                                            $html .= '<div class="mb-3"><strong>Subject:</strong> <span class="text-blue-600">' . e($previewSubject) . '</span></div>';
                                            $html .= '<div><strong>Content:</strong></div>';
                                            $html .= '<div class="p-3 mt-2 bg-white border rounded">' . $previewContent . '</div>';
                                            $html .= '</div>';

                                            return new \Illuminate\Support\HtmlString($html);
                                        })
                                        ->columnSpan(1)
                                        ->dehydrated(false)
                                        ->visible(fn (callable $get) => !empty($get('email_subject')) || !empty($get('email_content')))
                                ]),
                            ]),

                    // Add a hidden field to track template selection
                    Hidden::make('template_selected')
                        ->default(false)
                        ->dehydrated(false),

                    Hidden::make('implementer_name')
                        ->default(auth()->user()->name ?? ''),

                    Hidden::make('implementer_designation')
                        ->default('Implementer'),

                    Hidden::make('implementer_company')
                        ->default('TimeTec Cloud Sdn Bhd'),

                    Hidden::make('implementer_phone')
                        ->default('03-80709933'),

                    Hidden::make('implementer_email')
                        ->default(auth()->user()->email ?? ''),
                ])
                ->action(function (array $data) {
                    $lead = $this->getOwnerRecord();

                    try {
                        $updatedAppointments = \App\Models\ImplementerAppointment::where('lead_id', $lead->id)
                            ->where('status', 'New')
                            ->whereIn('type', ['KICK OFF MEETING SESSION', 'REVIEW SESSION'])
                            ->update([
                                'status' => 'Done',
                                'updated_at' => now(),
                            ]);

                        if ($updatedAppointments > 0) {
                            Log::info("Auto-updated {$updatedAppointments} appointment(s) to Done status", [
                                'lead_id' => $lead->id,
                                'company' => $lead->companyDetail?->company_name,
                                'updated_by' => auth()->user()->name,
                                'action' => 'activate_sessions'
                            ]);

                            Notification::make()
                                ->title('Appointments Updated')
                                ->success()
                                ->body("{$updatedAppointments} appointment(s) automatically marked as Done")
                                ->send();
                        }

                        if (isset($data['create_customer_account']) && $data['create_customer_account']) {
                            $customer = \App\Models\Customer::where('lead_id', $lead->id)->first();

                            // Only create if customer doesn't exist or doesn't have credentials
                            if (!$customer || empty($customer->email) || empty($customer->plain_password)) {
                                // Get the software handover to generate proper project code (same as CustomerActivationController)
                                $handover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)->orderBy('id', 'desc')->first();
                                $projectCode = $handover ? $handover->project_code : 'SW_250000';

                                // Get company and contact details from lead
                                $companyName = $lead->companyDetail ? $lead->companyDetail->company_name : ($lead->company_name ?? 'Unknown Company');
                                $customerName = $lead->companyDetail ? $lead->companyDetail->name : ($lead->contact_name ?? 'Customer');
                                $customerPhone = $lead->companyDetail ? $lead->companyDetail->contact_no : ($lead->phone ?? null);
                                $originalEmail = $lead->email ?? null; // Store original email as reference

                                // Generate credentials using the same method as CustomerActivationController
                                $generatedEmail = $this->generateRandomEmail($companyName, $projectCode);
                                $generatedPassword = $this->generateRandomPassword();

                                if ($customer) {
                                    // Check if the random email already exists (excluding current customer)
                                    while (\App\Models\Customer::where('email', $generatedEmail)->where('id', '!=', $customer->id)->exists()) {
                                        $generatedEmail = $this->generateRandomEmail($companyName, $projectCode);
                                    }

                                    // Update existing customer with new credentials
                                    $customer->update([
                                        'name' => $customerName,
                                        'email' => $generatedEmail,
                                        'original_email' => $originalEmail,
                                        'password' => bcrypt($generatedPassword),
                                        'plain_password' => $generatedPassword,
                                        'company_name' => $companyName,
                                        'phone' => $customerPhone,
                                        'sw_id' => $handover ? $handover->id : null,
                                        'status' => 'active',
                                        'able_set_meeting' => $data['enable_customer_booking'] ?? true,
                                        'email_verified_at' => now(),
                                    ]);

                                    Log::info('Customer account updated with new credentials', [
                                        'customer_id' => $customer->id,
                                        'email' => $generatedEmail,
                                        'original_email' => $originalEmail,
                                        'company_name' => $companyName,
                                        'project_code' => $projectCode,
                                        'lead_id' => $lead->id
                                    ]);
                                } else {
                                    // Check if the random email already exists
                                    while (\App\Models\Customer::where('email', $generatedEmail)->exists()) {
                                        $generatedEmail = $this->generateRandomEmail($companyName, $projectCode);
                                    }

                                    // Create new customer account
                                    $customer = \App\Models\Customer::create([
                                        'name' => $customerName,
                                        'email' => $generatedEmail,
                                        'original_email' => $originalEmail,
                                        'password' => bcrypt($generatedPassword),
                                        'plain_password' => $generatedPassword,
                                        'company_name' => $companyName,
                                        'phone' => $customerPhone,
                                        'lead_id' => $lead->id,
                                        'sw_id' => $handover ? $handover->id : null,
                                        'status' => 'active',
                                        'able_set_meeting' => $data['enable_customer_booking'] ?? true,
                                        'email_verified_at' => now(),
                                    ]);

                                    Log::info('New customer account created', [
                                        'customer_id' => $customer->id,
                                        'email' => $generatedEmail,
                                        'original_email' => $originalEmail,
                                        'company_name' => $companyName,
                                        'project_code' => $projectCode,
                                        'lead_id' => $lead->id
                                    ]);
                                }

                                Notification::make()
                                    ->title('Customer Account Created/Updated')
                                    ->success()
                                    ->body("Customer account created with email: {$generatedEmail} (Project: {$projectCode})")
                                    ->send();
                            }
                        }

                        // Update customer's able_set_meeting if enabled
                        if (isset($data['enable_customer_booking']) && $data['enable_customer_booking']) {
                            $customer = \App\Models\Customer::where('lead_id', $lead->id)->first();

                            if ($customer) {
                                $customer->update(['able_set_meeting' => true]);

                                Log::info('Customer able_set_meeting enabled via implementer email action', [
                                    'customer_id' => $customer->id,
                                    'customer_email' => $customer->email,
                                    'lead_id' => $lead->id,
                                    'company_name' => $customer->company_name,
                                    'enabled_by' => auth()->user()->name,
                                    'action_type' => 'implementer_email_action'
                                ]);
                            }
                        }

                        // Get recipient emails
                        $recipientStr = $data['required_attendees'] ?? '';

                        if (!empty($recipientStr)) {
                            // Get email template content
                            $subject = $data['email_subject'];
                            $content = $data['email_content'];

                            // Add signature to email content
                            if (isset($data['implementer_name']) && !empty($data['implementer_name'])) {
                                $signature = "<br><br>Regards,<br>";
                                $signature .= "{$data['implementer_name']}<br>";
                                $signature .= "{$data['implementer_designation']}<br>";
                                $signature .= "{$data['implementer_company']}<br>";
                                $signature .= "Phone: {$data['implementer_phone']}<br>";

                                if (!empty($data['implementer_email'])) {
                                    $signature .= "Email: {$data['implementer_email']}<br>";
                                }

                                $content .= $signature;
                            }

                            // Replace placeholders with actual data
                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                            $customer = \App\Models\Customer::where('lead_id', $lead->id)->first();

                            $placeholders = [
                                '{customer_name}' => $lead->contact_name ?? '',
                                '{company_name}' => $softwareHandover->company_name ?? ($lead->companyDetail?->company_name ?? 'Unknown Company'),
                                '{implementer_name}' => $data['implementer_name'] ?? auth()->user()->name ?? '',
                                '{lead_owner}' => $lead->lead_owner ?? '',
                                '{customer_email}' => $customer ? $customer->email : 'Not Available',
                                '{customer_password}' => $customer ? $customer->plain_password : 'Not Available',
                                '{customer_portal_url}' => config('app.url') . '/customer/login',
                            ];

                            $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
                            $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);

                            // Collect valid email addresses
                            $validRecipients = [];
                            foreach (explode(';', $recipientStr) as $recipient) {
                                $recipient = trim($recipient);
                                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                                    $validRecipients[] = $recipient;
                                }
                            }

                            if (!empty($validRecipients)) {
                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $data['implementer_email'] ?? $authUser->email;
                                $senderName = $data['implementer_name'] ?? $authUser->name;

                                $schedulerType = $data['scheduler_type'] ?? 'instant';

                                $template = \App\Models\EmailTemplate::find($data['email_template']);
                                $templateName = $template ? $template->name : 'Custom Email';

                                // Store email data
                                $emailData = [
                                    'content' => $content,
                                    'subject' => $subject,
                                    'recipients' => $validRecipients,
                                    'sender_email' => $senderEmail,
                                    'sender_name' => $senderName,
                                    'lead_id' => $lead->id,
                                    'template_name' => $templateName,
                                    'scheduler_type' => $schedulerType,
                                    'booking_enabled' => $data['enable_customer_booking'] ?? false,
                                    'email_attachments' => $data['email_attachments'] ?? [], // Add this line
                                ];

                                // Handle different scheduler types
                                if ($schedulerType === 'instant' || $schedulerType === 'both') {
                                    // Send email immediately
                                    $this->sendClientEmail($emailData, $lead);

                                    Notification::make()
                                        ->title('Email sent successfully')
                                        ->success()
                                        ->body('Email sent to ' . count($validRecipients) . ' recipient(s)')
                                        ->send();
                                }

                                if ($schedulerType === 'scheduled' || $schedulerType === 'both') {
                                    // For scheduled emails, you can implement your scheduling logic here
                                    // This is just a placeholder for the scheduled functionality
                                    Notification::make()
                                        ->title('Email scheduling feature')
                                        ->info()
                                        ->body('Scheduled email functionality can be implemented here')
                                        ->send();
                                }

                                if (isset($data['enable_customer_booking']) && $data['enable_customer_booking']) {
                                    Notification::make()
                                        ->title('Customer booking enabled')
                                        ->success()
                                        ->body('The customer can now schedule new meetings through their portal')
                                        ->send();
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Error sending client email: ' . $e->getMessage());
                        Notification::make()
                            ->title('Error sending email')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            // Tables\Actions\Action::make('Add Appointment')
            //     ->label('Session')
            //     ->icon('heroicon-o-plus')
            //     ->modalHeading('Implementation Session')
            //     ->hidden(function() {
            //         // Get the current user
            //         $user = auth()->user();

            //         // Get the lead record
            //         $lead = $this->getOwnerRecord();
            //         if (!$lead) return true; // Hide if no lead record found

            //         // Admins (role_id = 3) can always add appointments
            //         if ($user->role_id == 3) {
            //             return false; // Don't hide for admins
            //         }

            //         // Find the latest software handover for this lead
            //         $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
            //             ->latest()
            //             ->first();

            //         // If there's a software handover and the current user is the assigned implementer, allow access
            //         if ($softwareHandover && $softwareHandover->implementer === $user->name) {
            //             return false; // Don't hide for the assigned implementer
            //         }

            //         // For all other cases, hide the button
            //         return true;
            //     })
            //     ->form($this->defaultForm())
            //     ->action(function (array $data) {
            //         // Get the lead record
            //         $lead = $this->getOwnerRecord();

            //         $skipEmailAndTeams = $data['skip_email_teams'] ?? false;

            //         // Process required attendees from form data
            //         $requiredAttendeesInput = $data['required_attendees'] ?? '';
            //         $attendeeEmails = [];
            //         if (!empty($requiredAttendeesInput)) {
            //             $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendeesInput)));
            //         }

            //         // Find the SoftwareHandover record for this lead
            //         $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
            //             ->orderBy('id', 'desc')
            //             ->first();

            //         if (!$softwareHandover) {
            //             Notification::make()
            //                 ->title('Error: Software Handover record not found')
            //                 ->danger()
            //                 ->send();
            //             return;
            //         }

            //         // Create a new Appointment
            //         $appointment = new \App\Models\ImplementerAppointment();
            //         $appointment->fill([
            //             'lead_id' => $lead->id,
            //             'type' => $data['type'],
            //             'appointment_type' => $data['appointment_type'],
            //             'date' => $data['date'],
            //             'start_time' => $data['start_time'],
            //             'end_time' => $data['end_time'],
            //             'implementer' => $data['implementer'],
            //             'causer_id' => auth()->user()->id,
            //             'implementer_assigned_date' => now(),
            //             'remarks' => $data['remarks'] ?? null,
            //             'title' => $data['type'] . ' | ' . $data['appointment_type'] . ' | TIMETEC IMPLEMENTER | ' . $lead->companyDetail->company_name ?? 'Client',
            //             'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
            //             'status' => 'New',
            //             'session' => $data['session'] ?? null,
            //             'software_handover_id' => $softwareHandover->id,
            //         ]);

            //         // Save the appointment
            //         $appointment->save();

            //         // Update SoftwareHandover if this is a kick-off meeting
            //         if ($data['type'] === 'KICK OFF MEETING SESSION' && !$softwareHandover->kick_off_meeting) {
            //             $softwareHandover->update([
            //                 'kick_off_meeting' => Carbon::parse($data['date'] . ' ' . $data['start_time'])->toDateTimeString(),
            //             ]);
            //         }

            //         if (!$skipEmailAndTeams) {
            //             // Set up email recipients for notification
            //             $recipients = ['fazuliana.mohdarsad@timeteccloud.com']; // Default recipient

            //             // Add required attendees if they have valid emails
            //             foreach ($attendeeEmails as $email) {
            //                 if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            //                     $recipients[] = $email;
            //                 }
            //             }

            //             // Format start and end times for Teams meeting
            //             $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
            //             $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

            //             // Get the implementer as the organizer
            //             $implementerName = $data['implementer'] ?? null;
            //             $implementerUser = User::where('name', $implementerName)->first();
            //             $meetingLink = null;
            //             $onlineMeetingId = null;

            //             if ($implementerUser && ($implementerUser->azure_user_id || $implementerUser->email)) {
            //                 // Initialize Microsoft Graph service
            //                 $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
            //                 $graph = new \Microsoft\Graph\Graph();
            //                 $graph->setAccessToken($accessToken);

            //                 $meetingPayload = [
            //                     'start' => [
            //                         'dateTime' => $startTime,
            //                         'timeZone' => 'Asia/Kuala_Lumpur'
            //                     ],
            //                     'end' => [
            //                         'dateTime' => $endTime,
            //                         'timeZone' => 'Asia/Kuala_Lumpur'
            //                     ],
            //                     'subject' => 'TIMETEC HR | ' . $data['appointment_type'] . ' | ' . $data['type'] . ' | ' . ($lead->companyDetail->company_name ?? 'Client'),
            //                     'isOnlineMeeting' => true,
            //                     'onlineMeetingProvider' => 'teamsForBusiness',
            //                     'allowNewTimeProposals' => false,
            //                     'responseRequested' => true,
            //                     'attendees' => []
            //                 ];

            //                 // Add required attendees to the meeting payload
            //                 if (!empty($attendeeEmails)) {
            //                     foreach ($attendeeEmails as $email) {
            //                         if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            //                             $meetingPayload['attendees'][] = [
            //                                 'emailAddress' => [
            //                                     'address' => $email,
            //                                     'name' => $email
            //                                 ],
            //                                 'type' => 'required'
            //                             ];
            //                         }
            //                     }
            //                 }

            //                 try {
            //                     // ✅ STEP 1: Create the event using EMAIL (not azure_user_id)
            //                     $organizerEmail = $implementerUser->email;

            //                     $onlineMeeting = $graph->createRequest("POST", "/users/$organizerEmail/events")
            //                         ->attachBody($meetingPayload)
            //                         ->setReturnType(\Microsoft\Graph\Model\Event::class)
            //                         ->execute();

            //                     $meetingInfo = $onlineMeeting->getOnlineMeeting();
            //                     $meetingLink = $meetingInfo->getJoinUrl() ?? 'N/A';
            //                     $eventId = $onlineMeeting->getId();

            //                     Log::info('✅ Step 1: Event created successfully', [
            //                         'event_id' => $eventId,
            //                         'join_url' => $meetingLink,
            //                         'organizer_email' => $organizerEmail,
            //                         'has_azure_id' => !empty($implementerUser->azure_user_id)
            //                     ]);

            //                     // ✅ STEP 2: Query onlineMeetings using AZURE_USER_ID (if available) or EMAIL
            //                     if ($meetingLink && $meetingLink !== 'N/A') {
            //                         try {
            //                             // ✅ Use azure_user_id for querying online meetings, fallback to email
            //                             $queryIdentifier = $implementerUser->azure_user_id ?? $organizerEmail;
            //                             $filterQuery = "joinWebUrl eq '$meetingLink'";

            //                             // Query to get the online meeting ID
            //                             $onlineMeetingResponse = $graph->createRequest("GET", "/users/$queryIdentifier/onlineMeetings?\$filter=$filterQuery")
            //                                 ->execute();

            //                             $responseBody = $onlineMeetingResponse->getBody();

            //                             Log::info('✅ Step 2: Online meeting query response', [
            //                                 'response' => $responseBody,
            //                                 'join_url' => $meetingLink,
            //                                 'query_identifier' => $queryIdentifier,
            //                                 'used_azure_id' => !empty($implementerUser->azure_user_id),
            //                                 'azure_user_id' => $implementerUser->azure_user_id ?? null,
            //                                 'email' => $organizerEmail
            //                             ]);

            //                             // Extract the online meeting ID from response
            //                             if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
            //                                 $onlineMeetingId = $responseBody['value'][0]['id'] ?? null;

            //                                 Log::info('✅ Step 2: Online meeting ID retrieved', [
            //                                     'online_meeting_id' => $onlineMeetingId,
            //                                     'event_id' => $eventId,
            //                                     'join_url' => $meetingLink,
            //                                     'query_identifier' => $queryIdentifier,
            //                                     'query_method' => $implementerUser->azure_user_id ? 'azure_user_id' : 'email'
            //                                 ]);

            //                                 // ✅ STEP 3: Enable automatic recording using PATCH with online_meeting_id
            //                                 if ($onlineMeetingId) {
            //                                     try {
            //                                         $recordingPayload = [
            //                                             'recordAutomatically' => true
            //                                         ];

            //                                         $recordingResponse = $graph->createRequest("PATCH", "/users/$queryIdentifier/onlineMeetings/$onlineMeetingId")
            //                                             ->attachBody($recordingPayload)
            //                                             ->execute();

            //                                         Log::info('✅ Step 3: Automatic recording enabled', [
            //                                             'online_meeting_id' => $onlineMeetingId,
            //                                             'query_identifier' => $queryIdentifier,
            //                                             'response' => $recordingResponse->getBody()
            //                                         ]);

            //                                         Notification::make()
            //                                             ->title('Automatic Recording Enabled')
            //                                             ->success()
            //                                             ->body('The meeting will automatically start recording when it begins.')
            //                                             ->send();

            //                                     } catch (\Exception $e) {
            //                                         Log::error('❌ Step 3: Failed to enable automatic recording', [
            //                                             'error' => $e->getMessage(),
            //                                             'online_meeting_id' => $onlineMeetingId,
            //                                             'query_identifier' => $queryIdentifier,
            //                                             'trace' => $e->getTraceAsString()
            //                                         ]);

            //                                         Notification::make()
            //                                             ->title('Recording Setup Warning')
            //                                             ->warning()
            //                                             ->body('Meeting created but automatic recording could not be enabled: ' . $e->getMessage())
            //                                             ->send();
            //                                     }
            //                                 }
            //                             } else {
            //                                 Log::warning('⚠️ Step 2: No online meeting found with joinWebUrl', [
            //                                     'join_url' => $meetingLink,
            //                                     'response' => $responseBody,
            //                                     'query_identifier' => $queryIdentifier
            //                                 ]);
            //                             }
            //                         } catch (\Exception $e) {
            //                             Log::error('❌ Step 2: Failed to retrieve online meeting ID', [
            //                                 'error' => $e->getMessage(),
            //                                 'join_url' => $meetingLink,
            //                                 'query_identifier' => $queryIdentifier ?? null,
            //                                 'trace' => $e->getTraceAsString()
            //                             ]);
            //                         }
            //                     }

            //                     // ✅ STEP 4: Update appointment with both event_id and online_meeting_id
            //                     $appointment->update([
            //                         'event_id' => $eventId,
            //                         'meeting_link' => $meetingLink,
            //                         'online_meeting_id' => $onlineMeetingId,
            //                     ]);

            //                     Log::info('✅ Step 4: Appointment updated with meeting details', [
            //                         'appointment_id' => $appointment->id,
            //                         'event_id' => $eventId,
            //                         'online_meeting_id' => $onlineMeetingId,
            //                         'meeting_link' => $meetingLink,
            //                         'recording_enabled' => !empty($onlineMeetingId),
            //                         'created_with' => 'email',
            //                         'queried_with' => $implementerUser->azure_user_id ? 'azure_user_id' : 'email',
            //                         'organizer_email' => $organizerEmail,
            //                         'azure_user_id' => $implementerUser->azure_user_id ?? null
            //                     ]);

            //                     Notification::make()
            //                         ->title('Teams Meeting Created Successfully')
            //                         ->success()
            //                         ->body('The meeting has been scheduled with automatic recording enabled.')
            //                         ->send();

            //                 } catch (\Exception $e) {
            //                     \Illuminate\Support\Facades\Log::error('Failed to create Teams meeting: ' . $e->getMessage(), [
            //                         'request' => $meetingPayload,
            //                         'organizer_email' => $organizerEmail ?? null,
            //                         'azure_user_id' => $implementerUser->azure_user_id ?? null,
            //                         'trace' => $e->getTraceAsString()
            //                     ]);

            //                     Notification::make()
            //                         ->title('Failed to Create Teams Meeting')
            //                         ->danger()
            //                         ->body('Error: ' . $e->getMessage())
            //                         ->send();
            //                 }
            //             }

            //             // Prepare email content
            //             $viewName = 'emails.implementer_appointment_notification';
            //             $leadowner = User::where('name', $lead->lead_owner)->first();

            //             $emailContent = [
            //                 'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
            //                 'lead' => [
            //                     'lastName' => $lead->companyDetail->name ?? $lead->name ?? 'Client',
            //                     'company' => $lead->companyDetail->company_name ?? 'N/A',
            //                     'implementerName' => $data['implementer'] ?? 'N/A',
            //                     'implementerEmail' => $implementerUser->email ?? 'admin.timetec.hr@timeteccloud.com', // Add this line
            //                     'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
            //                     'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
            //                     'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
            //                     'date' => Carbon::parse($data['date'])->format('Y-m-d'),
            //                     'dateDisplay' => Carbon::parse($data['date'])->format('d/m/Y'),
            //                     'startTime' => Carbon::parse($data['start_time'])->format('h:i A') ?? 'N/A',
            //                     'endTime' => Carbon::parse($data['end_time'])->format('h:i A') ?? 'N/A',
            //                     'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
            //                     'session' => $data['session'] ?? 'N/A',
            //                     'demo_type' => $data['type'],
            //                     'appointment_type' => $data['appointment_type'],
            //                     'remarks' => $data['remarks'] ?? 'N/A',
            //                     'meetingLink' => $meetingLink ?? 'Will be provided separately',
            //                 ],
            //             ];

            //             // Get authenticated user's email for sender
            //             $authUser = auth()->user();
            //             $senderEmail = $authUser->email;
            //             $senderName = $authUser->name;

            //             // Default to implementer email if available
            //             if ($implementerUser && $implementerUser->email) {
            //                 $senderEmail = $implementerUser->email;
            //                 $senderName = $implementerUser->name;
            //             }

            //             try {
            //                 // Send email with template and custom subject format
            //                 if (count($recipients) > 0) {
            //                     \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead, $data) {
            //                         $message->from($senderEmail, $senderName)
            //                             ->to($recipients)
            //                             ->bcc('admin.timetec.hr@timeteccloud.com')
            //                             ->subject("TIMETEC HR | {$data['appointment_type']} | {$data['type']} | {$lead->companyDetail->company_name}");
            //                     });

            //                     Notification::make()
            //                         ->title('Implementer appointment notification sent')
            //                         ->success()
            //                         ->body('Email notification sent to administrator and required attendees')
            //                         ->send();
            //                 }
            //             } catch (\Exception $e) {
            //                 // Handle email sending failure
            //                 Log::error("Email sending failed for implementer appointment: Error: {$e->getMessage()}");

            //                 Notification::make()
            //                     ->title('Email Notification Failed')
            //                     ->danger()
            //                     ->body('Could not send email notification: ' . $e->getMessage())
            //                     ->send();
            //             }
            //         } else {
            //             Notification::make()
            //                 ->title('Email and Teams meeting skipped')
            //                 ->info()
            //                 ->body('The appointment was created without sending email or creating Teams meeting.')
            //                 ->send();
            //         }

            //         // Create activity log entry
            //         \App\Models\ActivityLog::create([
            //             'user_id' => auth()->id(),
            //             'causer_id' => auth()->id(),
            //             'action' => 'Created Appointment',
            //             'description' => "Created {$data['type']} for {$lead->companyDetail->company_name} with {$data['implementer']}",
            //             'subject_type' => get_class($appointment),
            //             'subject_id' => $appointment->id,
            //         ]);

            //         Notification::make()
            //             ->title('Implementer Appointment Added Successfully')
            //             ->success()
            //             ->send();

            //         $this->dispatch('refresh');
            //     }),
        ];
    }

    public static function rescheduleAppointmentAction()
    {
        return Action::make('reschedule_appointment')
            ->label('Reschedule')
            ->icon('heroicon-o-clock')
            ->color('warning')
            ->modalHeading('Reschedule Implementation Appointment')
            ->form(function (?ImplementerAppointment $record = null) {
                if (!$record) {
                    return [
                        TextInput::make('error')
                            ->label('Error')
                            ->default('No appointment record found.')
                            ->disabled(),
                    ];
                }

                return [
                    Grid::make(3)
                        ->schema([
                            DatePicker::make('date')
                                ->required()
                                ->label('DATE (MONDAY-THURSDAY/FRIDAY)')
                                ->default(function ($record = null) {
                                    return $record ? $record->date : Carbon::today()->toDateString();
                                })
                                ->reactive()
                                ->columnSpan(1),

                            Select::make('session')
                                ->label('SESSION')
                                ->options(function (callable $get) {
                                    $date = $get('date');
                                    if (!$date) return [];

                                    $selectedDate = Carbon::parse($date);
                                    $dayOfWeek = $selectedDate->dayOfWeek;

                                    // Friday sessions (dayOfWeek = 5)
                                    if ($dayOfWeek === 5) {
                                        return [
                                            'SESSION 1' => 'SESSION 1 (0930 - 1030)',
                                            'SESSION 2' => 'SESSION 2 (1100 - 1200)',
                                            'SESSION 3' => 'SESSION 3 (1500 - 1600)',
                                            'SESSION 4' => 'SESSION 4 (1630 - 1730)',
                                        ];
                                    }
                                    // Monday to Thursday sessions (dayOfWeek = 1-4)
                                    else if ($dayOfWeek >= 1 && $dayOfWeek <= 4) {
                                        return [
                                            'SESSION 1' => 'SESSION 1 (0930 - 1030)',
                                            'SESSION 2' => 'SESSION 2 (1100 - 1200)',
                                            'SESSION 3' => 'SESSION 3 (1400 - 1500)',
                                            'SESSION 4' => 'SESSION 4 (1530 - 1630)',
                                            'SESSION 5' => 'SESSION 5 (1700 - 1800)',
                                        ];
                                    }

                                    // Weekend or invalid date
                                    return ['NO_SESSIONS' => 'No sessions available on weekends'];
                                })
                                ->default(function (callable $get, $record = null) {
                                    // If editing existing record, use its session value
                                    if ($record && $record->session) {
                                        return $record->session;
                                    }

                                    // For new records, select a default based on the day
                                    $date = $get('date');
                                    if (!$date) return null;

                                    return 'SESSION 1';
                                })
                                ->columnSpan(2)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Set the start_time and end_time based on selected session
                                    $times = [
                                        'SESSION 1' => ['09:30', '10:30'],
                                        'SESSION 2' => ['11:00', '12:00'],
                                        'SESSION 3' => ['14:00', '15:00'],
                                        'SESSION 4' => ['15:30', '16:30'], // Friday has different time
                                        'SESSION 5' => ['17:00', '18:00'], // Friday has different time
                                    ];

                                    // Friday has different times for sessions 4 and 5
                                    $date = $get('date');
                                    if ($date) {
                                        $carbonDate = Carbon::parse($date);
                                        if ($carbonDate->dayOfWeek === 5) { // Friday
                                            $times['SESSION 4'] = ['15:00', '16:00'];
                                            $times['SESSION 5'] = ['16:30', '17:30'];
                                        }
                                    }

                                    if (isset($times[$state])) {
                                        $set('start_time', $times[$state][0]);
                                        $set('end_time', $times[$state][1]);
                                        $set('start_time_display', $times[$state][0]);
                                        $set('end_time_display', $times[$state][1]);
                                    }
                                }),

                            // Hidden time fields
                            Hidden::make('start_time_display')->default('09:30'),
                            Hidden::make('end_time_display')->default('10:30'),
                            Hidden::make('start_time')->default('09:30'),
                            Hidden::make('end_time')->default('10:30'),
                        ]),

                    Grid::make(3)
                        ->schema([
                            Select::make('type')
                                ->options(function ($record = null) {
                                    if (!$record) return ['KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION'];

                                    // Retrieve software handover information first
                                    $softwareHandover = null;
                                    if ($record->software_handover_id) {
                                        $softwareHandover = SoftwareHandover::find($record->software_handover_id);
                                    }

                                    // Check if there are any existing kick-off meetings that are completed or scheduled
                                    $hasKickoffAppointment = ImplementerAppointment::where('lead_id', $record->lead_id)
                                        ->where('software_handover_id', $record->software_handover_id ?? 0)
                                        ->where('type', 'KICK OFF MEETING SESSION')
                                        ->whereIn('status', ['Done', 'New']) // Check for completed or scheduled kick-offs
                                        ->exists();

                                    // Also check if kick_off_meeting exists in the software handover record as a backup
                                    $hasKickoffMeeting = $softwareHandover && !empty($softwareHandover->kick_off_meeting);

                                    // If either condition is true, allow REVIEW SESSIONs
                                    if ($hasKickoffAppointment || $hasKickoffMeeting) {
                                        return [
                                            'REVIEW SESSION' => 'REVIEW SESSION',
                                        ];
                                    } else {
                                        return [
                                            'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                                        ];
                                    }
                                })
                                ->default(function ($record = null) {
                                    if (!$record) return 'KICK OFF MEETING SESSION';
                                    return $record->type;
                                })
                                ->required()
                                ->label('SESSION TYPE')
                                ->disabled() // Disable the field
                                ->dehydrated(true),

                            Select::make('appointment_type')
                                ->options([
                                    'ONLINE' => 'ONLINE',
                                    'ONSITE' => 'ONSITE',
                                    'INHOUSE' => 'INHOUSE',
                                ])
                                ->disabled()
                                ->required()
                                ->dehydrated(true)
                                ->default(function ($record = null) {
                                    if (!$record) return 'ONLINE';
                                    return $record->appointment_type;
                                })
                                ->label('APPOINTMENT TYPE'),

                            Select::make('implementer')
                                ->label('IMPLEMENTER')
                                ->options(function ($record = null) {
                                    if (!$record) {
                                        return User::whereIn('role_id', [4, 5])
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($tech) {
                                                return [$tech->name => $tech->name];
                                            })
                                            ->toArray();
                                    }

                                    // If we found a record with an implementer, only show that implementer
                                    if ($record->implementer) {
                                        return [$record->implementer => $record->implementer];
                                    }

                                    // Fallback: show all implementers (role_id 4 or 5)
                                    return User::whereIn('role_id', [4, 5])
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(function ($tech) {
                                            return [$tech->name => $tech->name];
                                        })
                                        ->toArray();
                                })
                                ->default(function ($record = null) {
                                    if (!$record) return null;
                                    return $record->implementer;
                                })
                                ->searchable()
                                ->required()
                                ->disabled() // Always disable for reschedule
                                ->dehydrated(true)
                                ->placeholder('Select an implementer'),
                        ]),

                    TextInput::make('required_attendees')
                        ->label('REQUIRED ATTENDEES')
                        ->default(function() use ($record) {
                            if (!$record) return '';

                            // Try to decode JSON if it exists
                            if (!empty($record->required_attendees)) {
                                try {
                                    $attendees = json_decode($record->required_attendees, true);
                                    if (is_array($attendees)) {
                                        return implode(';', $attendees);
                                    }
                                    return $record->required_attendees;
                                } catch (\Exception $e) {
                                    return $record->required_attendees;
                                }
                            }
                            return '';
                        })
                        ->disabled()
                        ->dehydrated(true)
                        ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

                    Textarea::make('remarks')
                        ->label('REMARKS')
                        ->rows(3)
                        ->default($record->remarks ?? '')
                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                    Hidden::make('type')
                        ->default($record->type ?? 'KICK OFF MEETING SESSION'),
                ];
            })
            ->visible(fn (ImplementerAppointment $record) =>
                $record->status !== 'Cancelled' && $record->status !== 'Completed'
            )
            ->action(function (array $data, ImplementerAppointment $record) {
                // Store the previous appointment details for the notification
                $oldDate = Carbon::parse($record->date)->format('d/m/Y');
                $oldStartTime = Carbon::parse($record->start_time)->format('h:i A');
                $oldEndTime = Carbon::parse($record->end_time)->format('h:i A');

                // Process required attendees from form data
                $requiredAttendeesInput = $data['required_attendees'] ?? '';
                $attendeeEmails = [];
                if (!empty($requiredAttendeesInput)) {
                    $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendeesInput)));
                }

                // Update the appointment with new schedule
                $record->update([
                    'date' => $data['date'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'remarks' => $data['remarks'],
                    'type' => $data['type'] ?? $record->type,
                    'appointment_type' => $data['appointment_type'] ?? $record->appointment_type,
                    'implementer' => $data['implementer'] ?? $record->implementer,
                    'session' => $data['session'] ?? $record->session,
                    'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
                    'updated_at' => now(),
                ]);

                // Get company name with fallback
                $companyName = 'N/A';
                if ($record->lead && $record->lead->companyDetail) {
                    $companyName = $record->lead->companyDetail->company_name;
                } elseif ($record->softwareHandover) {
                    $companyName = $record->softwareHandover->company_name ?? 'N/A';
                }

                $recipients = ['fazuliana.mohdarsad@timeteccloud.com']; // Always include admin

                // Add required attendees from the form input
                if (!empty($attendeeEmails)) {
                    foreach ($attendeeEmails as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients)) {
                            $recipients[] = $email;
                        }
                    }
                }

                // Ensure recipients are unique
                $viewName = 'emails.implementer_appointment_reschedule';

                $recipients = array_unique($recipients);
                $authUser = auth()->user();
                $senderEmail = $authUser->email;
                $senderName = $authUser->name;

                // Prepare email content with reschedule reason
                $emailContent = [
                    'lead' => [
                        'company' => $companyName,
                        'implementerName' => $record->implementer ?? 'N/A',
                        'date' => Carbon::parse($data['date'])->format('d/m/Y'),
                        'startTime' => Carbon::parse($data['start_time'])->format('h:i A'),
                        'endTime' => Carbon::parse($data['end_time'])->format('h:i A'),
                        'oldDate' => $oldDate,
                        'oldStartTime' => $oldStartTime,
                        'oldEndTime' => $oldEndTime,
                        'rescheduleReason' => $data['reschedule_reason'] ?? 'No reason provided',
                    ],
                ];

                // Update Teams meeting
                self::updateTeamsMeeting($record, $data, $companyName);

                try {
                    // Send email with template and custom subject format
                    if (count($recipients) > 0) {
                        Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $data, $companyName) {
                            $message->from($senderEmail, $senderName)
                                ->to($recipients)
                                ->cc($senderEmail)
                                ->subject("TIMETEC IMPLEMENTATION APPOINTMENT | {$data['type']} | {$companyName} | " . Carbon::parse($data['date'])->format('d/m/Y'));
                        });

                        Notification::make()
                            ->title('Implementation appointment notification sent')
                            ->success()
                            ->body('Email notification sent to administrator and required attendees')
                            ->send();
                    }
                } catch (\Exception $e) {
                    // Handle email sending failure
                    Log::error("Email sending failed for implementation appointment: Error: {$e->getMessage()}");

                    Notification::make()
                        ->title('Email Notification Failed')
                        ->danger()
                        ->body('Could not send email notification: ' . $e->getMessage())
                        ->send();
                }

                Notification::make()
                    ->title('Implementation Appointment Rescheduled Successfully')
                    ->success()
                    ->send();

                // Dispatch the refresh event
                \Filament\Facades\Filament::dispatchToLivewireComponents('refresh-repair-appointments');
            });
    }

    private function isJson($string) {
        if (!is_string($string)) return false;
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Update Teams meeting for an implementer appointment
     *
     * @param ImplementerAppointment $record
     * @param array $data
     * @param string $companyName
     * @return void
     */
    private static function updateTeamsMeeting(ImplementerAppointment $record, array $data, string $companyName)
    {
        try {
            $accessToken = MicrosoftGraphService::getAccessToken();
            $graph = new Graph();
            $graph->setAccessToken($accessToken);

            $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
            $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

            $implementer = User::where('name', $record->implementer)->first();
            $organizerEmail = $implementer->email ?? null;

            if (!$organizerEmail) {
                Notification::make()
                    ->title('Missing Organizer Email')
                    ->danger()
                    ->body('Implementer email is not available.')
                    ->send();
                return;
            }

            if ($record->event_id) {
                $meetingUpdatePayload = [
                    'start' => ['dateTime' => $startTime, 'timeZone' => 'Asia/Kuala_Lumpur'],
                    'end' => ['dateTime' => $endTime, 'timeZone' => 'Asia/Kuala_Lumpur'],
                    'subject' => 'TIMETEC | ' . $companyName,
                ];

                $response = $graph->createRequest("PATCH", "/users/$organizerEmail/events/{$record->event_id}")
                    ->attachBody($meetingUpdatePayload)
                    ->execute();

                $eventData = $response->getBody(); // associative array

                // Extract the meeting details
                $joinUrl = $eventData['onlineMeeting']['joinUrl'] ?? null;
                $eventId = $eventData['id'] ?? $record->event_id;

                // Update the record with the meeting details
                $record->update([
                    'event_id' => $eventId,
                    'meeting_link' => $joinUrl,
                ]);

                Notification::make()
                    ->title('Meeting Updated')
                    ->success()
                    ->body('The implementation appointment and Teams meeting have been updated.')
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Teams Meeting Reschedule Failed: ' . $e->getMessage());
            Notification::make()
                ->title('Rescheduling Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Send email to client with CC to implementer and salesperson
     *
     * @param array $emailData
     * @param \App\Models\Lead $lead
     * @return void
     */
    private function sendClientEmail(array $emailData, $lead): void
    {
        try {
            // Find the software handover record
            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

            if (!$softwareHandover) {
                Log::error("Software handover not found for lead_id: {$lead->id}");
                return;
            }

            // Get customer email and plain password
            $customer = \App\Models\Customer::where('lead_id', $lead->id)->first();
            $customerEmail = $customer ? $customer->email : null;
            $customerPlainPassword = $customer ? $customer->plain_password : null;

            // Initialize CC recipients array
            $ccRecipients = [];

            // Add implementer to CC if available and different from sender
            if ($softwareHandover->implementer) {
                $implementer = \App\Models\User::where('name', $softwareHandover->implementer)->first();
                if ($implementer && $implementer->email && $implementer->email !== $emailData['sender_email']) {
                    $ccRecipients[] = $implementer->email;
                    Log::info("Added implementer to CC: {$implementer->name} <{$implementer->email}>");
                }
            }

            // Add salesperson to CC if available and different from sender and implementer
            if ($softwareHandover->salesperson) {
                $salesperson = \App\Models\User::where('name', $softwareHandover->salesperson)->first();
                if ($salesperson && $salesperson->email &&
                    $salesperson->email !== $emailData['sender_email'] &&
                    !in_array($salesperson->email, $ccRecipients)) {
                    $ccRecipients[] = $salesperson->email;
                    Log::info("Added salesperson to CC: {$salesperson->name} <{$salesperson->email}>");
                }
            }

            // Replace placeholders in email content with customer credentials
            $customerPlaceholders = [
                '{customer_email}' => $customerEmail ?? 'Not Available',
                '{customer_password}' => $customerPlainPassword ?? 'Not Available',
                '{customer_portal_url}' => config('app.url') . '/customer/login',
            ];

            // Apply customer placeholders to email content
            $emailContent = $emailData['content'];
            $emailContent = str_replace(array_keys($customerPlaceholders), array_values($customerPlaceholders), $emailContent);

            // Apply customer placeholders to email subject as well
            $emailSubject = $emailData['subject'];
            $emailSubject = str_replace(array_keys($customerPlaceholders), array_values($customerPlaceholders), $emailSubject);

            Log::info('Preparing to send email with attachments', [
                'email_data_keys' => array_keys($emailData),
                'has_attachments' => !empty($emailData['email_attachments']),
                'attachment_count' => is_array($emailData['email_attachments'] ?? null) ? count($emailData['email_attachments']) : 0,
                'attachments' => $emailData['email_attachments'] ?? 'none'
            ]);

            // Prepare attachments data
            $attachmentsData = $this->prepareAttachmentsData($emailData);

            // Send the email with CC recipients, updated content, and attachments
            Mail::html($emailContent, function (Message $message) use ($emailData, $ccRecipients, $emailSubject, $attachmentsData) {
                $message->to($emailData['recipients'])
                    ->subject($emailSubject)
                    ->from($emailData['sender_email'], $emailData['sender_name']);

                // Add CC recipients if we have any
                if (!empty($ccRecipients)) {
                    $message->cc($ccRecipients);
                }

                // BCC the sender as well
                $message->bcc($emailData['sender_email']);

                // Add file attachments
                foreach ($attachmentsData as $attachment) {
                    if (file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'],
                            'mime' => $attachment['mime']
                        ]);
                    }
                }
            });

            // Log email sent successfully with attachments info
            Log::info('Client activation email sent successfully', [
                'to' => $emailData['recipients'],
                'cc' => $ccRecipients,
                'subject' => $emailSubject,
                'lead_id' => $emailData['lead_id'],
                'template' => $emailData['template_name'],
                'booking_enabled' => $emailData['booking_enabled'] ?? false,
                'customer_email' => $customerEmail,
                'has_customer_password' => !empty($customerPlainPassword),
                'attachments_count' => count($attachmentsData),
            ]);

            // Clean up temporary attachment files
            $this->cleanupTempAttachments($attachmentsData);

        } catch (\Exception $e) {
            Log::error('Error in sendClientEmail method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $emailData
            ]);
            throw $e;
        }
    }

    private function generateRandomEmail($companyName = null, $projectCode = null)
    {
        // If project code is provided, use it to generate email
        if ($projectCode) {
            // Extract the year and ID from project code (e.g., SW_250800 -> 250800)
            $codeWithoutPrefix = str_replace('SW_', '', $projectCode);
            return strtolower("sw_{$codeWithoutPrefix}@timeteccloud.com");
        }

        // Fallback to original method if no project code
        $cleanCompanyName = '';
        if ($companyName) {
            $cleanCompanyName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $companyName));
            $cleanCompanyName = substr($cleanCompanyName, 0, 8); // Limit to 8 characters
        }

        // Generate random string
        $randomString = strtolower(\Illuminate\Support\Str::random(6));

        // Create email with company name prefix or just random
        if ($cleanCompanyName) {
            $username = $cleanCompanyName . $randomString;
        } else {
            $username = 'customer' . $randomString . rand(100, 999);
        }

        return $username . '@timeteccloud.com';
    }

    /**
     * Generate random password (same as CustomerActivationController)
     */
    private function generateRandomPassword($length = 12)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $password = '';

        // Ensure password has at least one uppercase, one lowercase, one digit, and one special character
        $password .= $characters[rand(26, 51)]; // Uppercase
        $password .= $characters[rand(0, 25)];  // Lowercase
        $password .= $characters[rand(52, 61)]; // Digit
        $password .= $characters[rand(62, strlen($characters) - 1)]; // Special character

        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return str_shuffle($password);
    }

    private function prepareAttachmentsData(array $emailData): array
    {
        $attachmentsData = [];

        // ✅ Handle project plan attachments (from ImplementerActions)
        if (!empty($emailData['project_plan_attachments'])) {
            Log::info("Processing project plan attachments", [
                'count' => count($emailData['project_plan_attachments']),
                'files' => $emailData['project_plan_attachments']
            ]);

            foreach ($emailData['project_plan_attachments'] as $filePath) {
                try {
                    if (file_exists($filePath)) {
                        $mimeType = mime_content_type($filePath);
                        $displayName = basename($filePath);

                        $attachmentsData[] = [
                            'path' => $filePath,
                            'name' => $displayName,
                            'mime' => $mimeType,
                            'type' => 'project_plan'
                        ];

                        Log::info("Project plan attachment added", [
                            'name' => $displayName,
                            'path' => $filePath,
                            'mime' => $mimeType
                        ]);
                    } else {
                        Log::error("Project plan file not found", [
                            'path' => $filePath
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error preparing project plan attachment", [
                        'filePath' => $filePath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Handle user uploaded files
        if (!empty($emailData['email_attachments'])) {
            Log::info("Processing email attachments", [
                'attachments_raw' => $emailData['email_attachments'],
                'attachments_type' => gettype($emailData['email_attachments'])
            ]);

            foreach ($emailData['email_attachments'] as $fileName) {
                try {
                    $filePath = null;
                    $displayName = null;

                    if (is_string($fileName)) {
                        if (str_contains($fileName, 'temp_email_attachments/')) {
                            $filePath = storage_path('app/public/' . $fileName);
                            $displayName = basename($fileName);
                        } else {
                            $filePath = storage_path('app/public/temp_email_attachments/' . $fileName);
                            $displayName = $fileName;
                        }
                    } elseif (is_object($fileName) && method_exists($fileName, 'getRealPath')) {
                        $filePath = $fileName->getRealPath();
                        $displayName = $fileName->getClientOriginalName();
                    } elseif (is_array($fileName) && isset($fileName['tmp_name'])) {
                        $filePath = $fileName['tmp_name'];
                        $displayName = $fileName['name'] ?? 'unknown_file';
                    }

                    if ($filePath && file_exists($filePath)) {
                        $mimeType = mime_content_type($filePath);

                        // ✅ Changed allowed types to PDF only
                        $allowedTypes = [
                            'application/pdf',
                        ];

                        if (in_array($mimeType, $allowedTypes)) {
                            $attachmentsData[] = [
                                'path' => $filePath,
                                'name' => $displayName,
                                'mime' => $mimeType,
                                'type' => 'user_upload'
                            ];

                            Log::info("User attachment added successfully", [
                                'name' => $displayName,
                                'path' => $filePath,
                                'mime' => $mimeType
                            ]);
                        } else {
                            Log::warning("File type not allowed: {$displayName}", [
                                'mime_type' => $mimeType,
                                'allowed_types' => $allowedTypes
                            ]);
                        }
                    } else {
                        Log::error("Attachment file not found: {$displayName}", [
                            'attempted_path' => $filePath,
                            'original_data' => $fileName
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error preparing user uploaded attachment", [
                        'fileName' => $fileName,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        } else {
            Log::info("No email attachments found in email data");
        }

        Log::info("Total attachments prepared", [
            'count' => count($attachmentsData),
            'attachments' => array_map(fn($a) => ['name' => $a['name'], 'type' => $a['type']], $attachmentsData)
        ]);

        return $attachmentsData;
    }

    private function cleanupTempAttachments(array $attachmentsData): void
    {
        foreach ($attachmentsData as $attachment) {
            if ($attachment['type'] === 'user_upload' && file_exists($attachment['path'])) {
                try {
                    unlink($attachment['path']);
                } catch (\Exception $e) {
                    Log::error("Failed to cleanup attachment: {$attachment['name']}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}
