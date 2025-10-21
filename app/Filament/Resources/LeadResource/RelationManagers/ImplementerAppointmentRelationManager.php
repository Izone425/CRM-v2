<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
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
        return __('Implementer Appointments');
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
            ->columns([
                TextColumn::make('implementer')
                    ->label('IMPLEMENTER')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('IMPLEMENTATION TYPE')
                    ->sortable(),
                TextColumn::make('appointment_type')
                    ->label('APPOINTMENT TYPE')
                    ->sortable(),
                TextColumn::make('review_session_count')
                    ->label('REVIEW SESSIONS')
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

                            return "{$date} | {$startTime} - {$endTime}";
                        } catch (\Exception $e) {
                            return 'Invalid Date/Time Format';
                        }
                    }),
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
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
            ])->defaultSort('date', 'desc');
    }

    public function headerActions(): array
    {
        return [
            Tables\Actions\Action::make('Add Appointment')
                ->icon('heroicon-o-pencil')
                ->modalHeading('Add Implementation Appointment')
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
                ->form($this->defaultForm())
                ->action(function (array $data) {
                    // Get the lead record
                    $lead = $this->getOwnerRecord();

                    $skipEmailAndTeams = $data['skip_email_teams'] ?? false;

                    // Process required attendees from form data
                    $requiredAttendeesInput = $data['required_attendees'] ?? '';
                    $attendeeEmails = [];
                    if (!empty($requiredAttendeesInput)) {
                        $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendeesInput)));
                    }

                    // Find the SoftwareHandover record for this lead
                    $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                        ->orderBy('id', 'desc')
                        ->first();

                    if (!$softwareHandover) {
                        Notification::make()
                            ->title('Error: Software Handover record not found')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Create a new Appointment
                    $appointment = new \App\Models\ImplementerAppointment();
                    $appointment->fill([
                        'lead_id' => $lead->id,
                        'type' => $data['type'],
                        'appointment_type' => $data['appointment_type'],
                        'date' => $data['date'],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'implementer' => $data['implementer'],
                        'causer_id' => auth()->user()->id,
                        'implementer_assigned_date' => now(),
                        'remarks' => $data['remarks'] ?? null,
                        'title' => $data['type'] . ' | ' . $data['appointment_type'] . ' | TIMETEC IMPLEMENTER | ' . $lead->companyDetail->company_name ?? 'Client',
                        'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
                        'status' => 'New',
                        'session' => $data['session'] ?? null,
                        'software_handover_id' => $softwareHandover->id,
                    ]);

                    // Save the appointment
                    $appointment->save();

                    // Update SoftwareHandover if this is a kick-off meeting
                    if ($data['type'] === 'KICK OFF MEETING SESSION' && !$softwareHandover->kick_off_meeting) {
                        $softwareHandover->update([
                            'kick_off_meeting' => Carbon::parse($data['date'] . ' ' . $data['start_time'])->toDateTimeString(),
                        ]);
                    }

                    if (!$skipEmailAndTeams) {
                        // Set up email recipients for notification
                        $recipients = ['fazuliana.mohdarsad@timeteccloud.com']; // Default recipient

                        // Add required attendees if they have valid emails
                        foreach ($attendeeEmails as $email) {
                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $recipients[] = $email;
                            }
                        }

                        // Format start and end times for Teams meeting
                        $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
                        $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

                        // Get the implementer as the organizer
                        $implementerName = $data['implementer'] ?? null;
                        $implementerUser = User::where('name', $implementerName)->first();
                        $meetingLink = null;

                        if ($implementerUser && $implementerUser->email) {
                            $organizerEmail = $implementerUser->email;

                            // Initialize Microsoft Graph service
                            $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
                            $graph = new \Microsoft\Graph\Graph();
                            $graph->setAccessToken($accessToken);

                            $meetingPayload = [
                                'start' => [
                                    'dateTime' => $startTime,
                                    'timeZone' => 'Asia/Kuala_Lumpur'
                                ],
                                'end' => [
                                    'dateTime' => $endTime,
                                    'timeZone' => 'Asia/Kuala_Lumpur'
                                ],
                                'subject' => 'TIMETEC HR | ' . $data['appointment_type'] . ' | ' . $data['type'] . ' | ' . ($lead->companyDetail->company_name ?? 'Client'),
                                'isOnlineMeeting' => true,
                                'onlineMeetingProvider' => 'teamsForBusiness',
                                'allowNewTimeProposals' => false,
                                'responseRequested' => true,
                                'attendees' => []
                            ];

                            // Add required attendees to the meeting payload
                            if (!empty($attendeeEmails)) {
                                foreach ($attendeeEmails as $email) {
                                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                        $meetingPayload['attendees'][] = [
                                            'emailAddress' => [
                                                'address' => $email,
                                                'name' => $email // Using email as name since we don't have names
                                            ],
                                            'type' => 'required'
                                        ];
                                    }
                                }
                            }

                            try {
                                // Use the correct endpoint for app-only authentication
                                $onlineMeeting = $graph->createRequest("POST", "/users/$organizerEmail/events")
                                    ->attachBody($meetingPayload)
                                    ->setReturnType(\Microsoft\Graph\Model\Event::class)
                                    ->execute();

                                $meetingInfo = $onlineMeeting->getOnlineMeeting();
                                $meetingLink = $meetingInfo->getJoinUrl() ?? 'N/A';

                                Notification::make()
                                    ->title('Teams Meeting Created Successfully')
                                    ->success()
                                    ->body('The meeting has been scheduled successfully.')
                                    ->send();
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to create Teams meeting: ' . $e->getMessage(), [
                                    'request' => $meetingPayload,
                                    'user' => $organizerEmail,
                                ]);

                                Notification::make()
                                    ->title('Failed to Create Teams Meeting')
                                    ->danger()
                                    ->body('Error: ' . $e->getMessage())
                                    ->send();
                            }
                        }

                        // Prepare email content
                        $viewName = 'emails.implementer_appointment_notification';
                        $leadowner = User::where('name', $lead->lead_owner)->first();

                        $emailContent = [
                            'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                            'lead' => [
                                'lastName' => $lead->companyDetail->name ?? $lead->name ?? 'Client',
                                'company' => $lead->companyDetail->company_name ?? 'N/A',
                                'implementerName' => $data['implementer'] ?? 'N/A',
                                'implementerEmail' => $implementerUser->email ?? 'admin.timetec.hr@timeteccloud.com', // Add this line
                                'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                                'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                                'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                                'date' => Carbon::parse($data['date'])->format('Y-m-d'),
                                'dateDisplay' => Carbon::parse($data['date'])->format('d/m/Y'),
                                'startTime' => Carbon::parse($data['start_time'])->format('h:i A') ?? 'N/A',
                                'endTime' => Carbon::parse($data['end_time'])->format('h:i A') ?? 'N/A',
                                'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                'session' => $data['session'] ?? 'N/A',
                                'demo_type' => $data['type'],
                                'appointment_type' => $data['appointment_type'],
                                'remarks' => $data['remarks'] ?? 'N/A',
                                'meetingLink' => $meetingLink ?? 'Will be provided separately',
                            ],
                        ];

                        // Get authenticated user's email for sender
                        $authUser = auth()->user();
                        $senderEmail = $authUser->email;
                        $senderName = $authUser->name;

                        // Default to implementer email if available
                        if ($implementerUser && $implementerUser->email) {
                            $senderEmail = $implementerUser->email;
                            $senderName = $implementerUser->name;
                        }

                        try {
                            // Send email with template and custom subject format
                            if (count($recipients) > 0) {
                                \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead, $data) {
                                    $message->from($senderEmail, $senderName)
                                        ->to($recipients)
                                        ->bcc('admin.timetec.hr@timeteccloud.com')
                                        ->subject("TIMETEC HR | {$data['appointment_type']} | {$data['type']} | {$lead->companyDetail->company_name}");
                                });

                                Notification::make()
                                    ->title('Implementer appointment notification sent')
                                    ->success()
                                    ->body('Email notification sent to administrator and required attendees')
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            // Handle email sending failure
                            Log::error("Email sending failed for implementer appointment: Error: {$e->getMessage()}");

                            Notification::make()
                                ->title('Email Notification Failed')
                                ->danger()
                                ->body('Could not send email notification: ' . $e->getMessage())
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title('Email and Teams meeting skipped')
                            ->info()
                            ->body('The appointment was created without sending email or creating Teams meeting.')
                            ->send();
                    }

                    // Create activity log entry
                    \App\Models\ActivityLog::create([
                        'user_id' => auth()->id(),
                        'causer_id' => auth()->id(),
                        'action' => 'Created Appointment',
                        'description' => "Created {$data['type']} for {$lead->companyDetail->company_name} with {$data['implementer']}",
                        'subject_type' => get_class($appointment),
                        'subject_id' => $appointment->id,
                    ]);

                    Notification::make()
                        ->title('Implementer Appointment Added Successfully')
                        ->success()
                        ->send();

                    $this->dispatch('refresh');
                }),
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
}
