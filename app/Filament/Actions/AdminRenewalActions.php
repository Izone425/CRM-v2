<?php

namespace App\Filament\Actions;

use App\Models\AdminRenewalLogs;
use App\Models\Renewal;
use App\Models\EmailTemplate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminRenewalActions
{
    public static function addAdminRenewalFollowUp(): Action
    {
        return Action::make('add_follow_up')
            ->label('Add Follow-up')
            ->color('primary')
            ->icon('heroicon-o-plus')
            ->modalWidth('6xl')
            ->form([
                Grid::make(4)
                    ->schema([
                        DatePicker::make('follow_up_date')
                            ->label('Next Follow-up Date')
                            ->default(function() {
                                $today = now();
                                $daysUntilNextTuesday = (9 - $today->dayOfWeek) % 7;
                                if ($daysUntilNextTuesday === 0) {
                                    $daysUntilNextTuesday = 7;
                                }
                                return $today->addDays($daysUntilNextTuesday);
                            })
                            ->minDate(now()->subDay())
                            ->required(),

                        Select::make('manual_follow_up_count')
                            ->label('Follow Up Count')
                            ->required()
                            ->options([
                                0 => '0',
                                1 => '1',
                                2 => '2',
                                3 => '3',
                                4 => '4',
                            ])
                            ->default(1),

                        Toggle::make('send_email')
                            ->label('Send Email?')
                            ->onIcon('heroicon-o-bell-alert')
                            ->offIcon('heroicon-o-bell-slash')
                            ->onColor('primary')
                            ->inline(false)
                            ->offColor('gray')
                            ->default(false)
                            ->live(onBlur: true),

                        Select::make('scheduler_type')
                            ->label('Scheduler Type')
                            ->options([
                                'instant' => 'Instant',
                                'scheduled' => 'Next Follow Up Date at 8am',
                                'both' => 'Both'
                            ])
                            ->visible(fn ($get) => $get('send_email'))
                            ->required(),
                    ]),

                Fieldset::make('Email Details')
                    ->schema([
                        TextInput::make('required_attendees')
                            ->label('Required Attendees')
                            ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

                        Select::make('email_template')
                            ->label('Email Template')
                            ->options(function () {
                                return EmailTemplate::where('type', 'admin_renewal')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $template = EmailTemplate::find($state);
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
                            ->disableToolbarButtons(['attachFiles'])
                            ->required(),
                    ])
                    ->visible(fn ($get) => $get('send_email')),

                Hidden::make('admin_name')
                    ->default(auth()->user()->name ?? '')
                    ->required(),

                Hidden::make('admin_designation')
                    ->default('Admin Renewal')
                    ->required(),

                Hidden::make('admin_company')
                    ->default('TimeTec Cloud Sdn Bhd')
                    ->required(),

                Hidden::make('admin_phone')
                    ->default('03-80709933')
                    ->required(),

                Hidden::make('admin_email')
                    ->default(auth()->user()->email ?? '')
                    ->required(),

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
                    ->afterStateHydrated(fn($state) => Str::upper($state))
                    ->afterStateUpdated(fn($state) => Str::upper($state))
                    ->placeholder('Add your follow-up details here...')
                    ->required()
            ])
            ->modalHeading('Add New Follow-up');
    }

    public static function stopAdminRenewalFollowUp(): Action
    {
        return Action::make('stop_follow_up')
            ->label('Stop Follow-up')
            ->color('danger')
            ->icon('heroicon-o-stop')
            ->requiresConfirmation()
            ->modalHeading('Stop Follow-up')
            ->modalDescription('Are you sure you want to stop the follow-up for this renewal?')
            ->modalSubmitActionLabel('Yes, Stop Follow-up');
    }

    public static function processFollowUpWithEmail(Renewal $record, array $data): void
    {
        if (!$record) {
            Notification::make()
                ->title('Error: Renewal record not found')
                ->danger()
                ->send();
            return;
        }

        // Update the Renewal record with follow-up information
        $record->update([
            'follow_up_date' => $data['follow_up_date'],
            'follow_up_counter' => true,
            'manual_follow_up_count' => $data['manual_follow_up_count'],
        ]);

        // Create description for the follow-up
        $followUpDescription = 'Admin Renewal Follow Up By ' . auth()->user()->name;

        // Create a new admin_renewal_logs entry
        $adminRenewalLog = AdminRenewalLogs::create([
            'lead_id' => $record->lead_id,
            'description' => $followUpDescription,
            'causer_id' => auth()->id(),
            'remark' => $data['notes'],
            'subject_id' => $record->id,
            'follow_up_date' => $data['follow_up_date'],
            'follow_up_counter' => true,
            'manual_follow_up_count' => $data['manual_follow_up_count'],
        ]);

        // Handle email sending if enabled
        if (isset($data['send_email']) && $data['send_email']) {
            // Email sending logic similar to ARFollowUpTabs
            // ... (implement email logic)
        }

        Notification::make()
            ->title('Follow-up added successfully')
            ->success()
            ->send();
    }

    public static function processStopFollowUp(Renewal $record): ?AdminRenewalLogs
    {
        if (!$record) {
            Notification::make()
                ->title('Error: Renewal record not found')
                ->danger()
                ->send();
            return null;
        }

        try {
            // Create description for the final follow-up
            $followUpDescription = 'Admin Renewal Stop Follow Up By ' . auth()->user()->name;

            // Create a new admin_renewal_logs entry with reference to Renewal
            $adminRenewalLog = AdminRenewalLogs::create([
                'lead_id' => $record->lead_id,
                'description' => $followUpDescription,
                'causer_id' => auth()->id(),
                'remark' => 'Admin Renewal Stop the Follow Up Features',
                'subject_id' => $record->id,
                'follow_up_date' => now()->format('Y-m-d'), // Today
            ]);

            // Cancel all scheduled emails related to this renewal
            $cancelledEmailsCount = self::cancelScheduledEmailsForRenewal($record);

            // Update the Renewal record to indicate follow-up is done
            $record->update([
                'follow_up_date' => now()->format('Y-m-d'), // Today
                'follow_up_counter' => false, // Stop future follow-ups
            ]);

            $message = 'Admin renewal follow-up process stopped successfully';
            if ($cancelledEmailsCount > 0) {
                $message .= " and {$cancelledEmailsCount} scheduled email(s) were cancelled";
            }

            Notification::make()
                ->title($message)
                ->success()
                ->send();

            return $adminRenewalLog;
        } catch (\Exception $e) {
            Log::error('Error stopping admin renewal follow-up: ' . $e->getMessage());
            Notification::make()
                ->title('Error stopping follow-up')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    private static function cancelScheduledEmailsForRenewal(Renewal $record): int
    {
        try {
            // Find all admin renewal logs related to this renewal
            $adminRenewalLogIds = AdminRenewalLogs::where('subject_id', $record->id)
                ->pluck('id')
                ->toArray();

            if (empty($adminRenewalLogIds)) {
                return 0;
            }

            // Cancel scheduled emails that contain any of these admin renewal log IDs
            $cancelledCount = 0;
            $scheduledEmails = DB::table('scheduled_emails')
                ->where('status', 'New')
                ->whereNotNull('scheduled_date')
                ->whereDate('scheduled_date', '>=', now())
                ->get();

            foreach ($scheduledEmails as $scheduledEmail) {
                try {
                    $emailData = json_decode($scheduledEmail->email_data, true);

                    // Check if this scheduled email is related to our renewal
                    if (isset($emailData['admin_renewal_log_id']) &&
                        in_array($emailData['admin_renewal_log_id'], $adminRenewalLogIds)) {

                        // Cancel the scheduled email
                        DB::table('scheduled_emails')
                            ->where('id', $scheduledEmail->id)
                            ->update([
                                'status' => 'Stop',
                                'updated_at' => now(),
                            ]);

                        $cancelledCount++;

                        Log::info("Cancelled scheduled email for admin renewal log ID: {$emailData['admin_renewal_log_id']}");
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing scheduled email ID {$scheduledEmail->id}: " . $e->getMessage());
                }
            }

            return $cancelledCount;
        } catch (\Exception $e) {
            Log::error('Error cancelling scheduled emails for renewal: ' . $e->getMessage());
            return 0;
        }
    }
}
