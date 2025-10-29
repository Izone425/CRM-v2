<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\ImplementerLogs;
use App\Models\SoftwareHandover;
use App\Models\EmailTemplate; // Add this model for email templates
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;

class ImplementerFollowUpTabs
{
    protected static function canEditFollowUp($record): bool
    {
        $user = auth()->user();

        // Admin users (role_id = 3) can always edit
        if ($user->role_id == 3) {
            return true;
        }

        // Get the software handover for this lead
        $swHandover = SoftwareHandover::where('lead_id', $record->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Check if the current user is the assigned implementer
        if ($swHandover && $swHandover->implementer === $user->name) {
            return true;
        }

        // Otherwise, no edit permissions
        return false;
    }

    public static function getSchema(): array
    {
        return [
            Grid::make(1)
                ->schema([
                    Section::make('Implementer Follow Up')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->headerActions([
                            Action::make('add_follow_up')
                                ->label('Add Follow-Up')
                                ->button()
                                ->color('primary')
                                ->icon('heroicon-o-plus')
                                ->visible(function ($record) {
                                    return self::canEditFollowUp($record);
                                })
                                ->modalWidth('6xl')
                                ->modalHeading(function (Lead $record) {
                                    // Get company name
                                    $companyName = 'Unknown Company';

                                    // Try to get company name from Lead's companyDetail first
                                    if ($record->companyDetail && $record->companyDetail->company_name) {
                                        $companyName = $record->companyDetail->company_name;
                                    } else {
                                        // If not available in Lead, try to get it from SoftwareHandover
                                        $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $record->id)
                                            ->orderBy('created_at', 'desc')
                                            ->first();

                                        if ($softwareHandover && $softwareHandover->company_name) {
                                            $companyName = $softwareHandover->company_name;
                                        }
                                    }

                                    return "Add Follow-up for {$companyName}";
                                })
                                ->form([
                                    Grid::make(4)
                                        ->schema([
                                            DatePicker::make('follow_up_date')
                                                ->label('Next Follow-up Date')
                                                ->default(function() {
                                                    $today = now();
                                                    $daysUntilNextTuesday = (9 - $today->dayOfWeek) % 7; // 2 is Tuesday, but we add 7 to ensure positive
                                                    if ($daysUntilNextTuesday === 0) {
                                                        $daysUntilNextTuesday = 7; // If today is Tuesday, we want next Tuesday
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
                                                ->default(function ($component) {
                                                    // Try to get the record from the component's livewire instance
                                                    try {
                                                        $livewire = $component->getLivewire();
                                                        $record = $livewire->getRecord();

                                                        if (!$record) return 1;

                                                        $softwareHandover = SoftwareHandover::where('lead_id', $record->id)
                                                            ->orderBy('created_at', 'desc')
                                                            ->first();

                                                        if (!$softwareHandover) return 1;

                                                        $currentCount = $softwareHandover->manual_follow_up_count ?? 0;
                                                        $nextCount = ($currentCount >= 4) ? 0 : $currentCount + 1;

                                                        return $nextCount;
                                                    } catch (\Exception $e) {
                                                        \Illuminate\Support\Facades\Log::error('Error getting follow-up count: ' . $e->getMessage());
                                                        return 1; // fallback
                                                    }
                                                }),

                                            Toggle::make('send_email')
                                                ->label('Send Email?')
                                                ->onIcon('heroicon-o-bell-alert')
                                                ->offIcon('heroicon-o-bell-slash')
                                                ->onColor('primary')
                                                ->inline(false)
                                                ->offColor('gray')
                                                ->default(false)
                                                ->live(onBlur: true),

                                            // Scheduler Type options
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
                                                ->default(function (Lead $record = null) {
                                                    // Initialize emails array to store all collected emails
                                                    $emails = [];

                                                    if ($record) {
                                                        // 1. Get emails from SoftwareHandover implementation_pics
                                                        $softwareHandover = SoftwareHandover::where('lead_id', $record->id)->latest()->first();

                                                        if ($softwareHandover && !empty($softwareHandover->implementation_pics) && is_string($softwareHandover->implementation_pics)) {
                                                            try {
                                                                $contacts = json_decode($softwareHandover->implementation_pics, true);

                                                                // If it's valid JSON array, extract emails
                                                                if (is_array($contacts)) {
                                                                    foreach ($contacts as $contact) {
                                                                        if (!empty($contact['pic_email_impl'])) {
                                                                            $emails[] = $contact['pic_email_impl'];
                                                                        }
                                                                    }
                                                                }
                                                            } catch (\Exception $e) {
                                                                \Illuminate\Support\Facades\Log::error('Error parsing implementation_pics JSON: ' . $e->getMessage());
                                                            }
                                                        }

                                                        // 2. Get emails from company_detail->additional_pic
                                                        if ($record->companyDetail && !empty($record->companyDetail->additional_pic)) {
                                                            try {
                                                                $additionalPics = json_decode($record->companyDetail->additional_pic, true);

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
                                                                \Illuminate\Support\Facades\Log::error('Error parsing additional_pic JSON: ' . $e->getMessage());
                                                            }
                                                        }
                                                    }

                                                    // Remove duplicates and return as semicolon-separated string
                                                    $uniqueEmails = array_unique($emails);
                                                    return !empty($uniqueEmails) ? implode(';', $uniqueEmails) : null;
                                                })
                                                ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

                                            Select::make('email_template')
                                                ->label('Email Template')
                                                ->options(function () {
                                                    return EmailTemplate::whereIn('type', ['implementer'])
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
                                                ->disableToolbarButtons([
                                                    'attachFiles',
                                                ])
                                                ->required(),
                                        ])
                                        ->visible(fn ($get) => $get('send_email')),

                                    Hidden::make('implementer_name')
                                        ->label('NAME')
                                        ->default(auth()->user()->name ?? '')
                                        ->required(),

                                    Hidden::make('implementer_designation')
                                        ->label('DESIGNATION')
                                        ->default('Implementer')
                                        ->required(),

                                    Hidden::make('implementer_company')
                                        ->label('COMPANY NAME')
                                        ->default('TimeTec Cloud Sdn Bhd')
                                        ->required(),

                                    Hidden::make('implementer_phone')
                                        ->label('PHONE NO')
                                        ->default('03-80709933')
                                        ->required(),

                                    Hidden::make('implementer_email')
                                        ->label('EMAIL')
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
                                ->action(function (Lead $record, array $data) {
                                    // Find the SoftwareHandover record for this lead
                                    $softwareHandover = SoftwareHandover::where('lead_id', $record->id)->latest()->first();

                                    if (!$softwareHandover) {
                                        Notification::make()
                                            ->title('Error: Software Handover record not found')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // Update the SoftwareHandover record with follow-up information
                                    $softwareHandover->update([
                                        'follow_up_date' => $data['follow_up_date'],
                                        'follow_up_counter' => true,
                                        'manual_follow_up_count' => $data['manual_follow_up_count'],
                                    ]);

                                    // Create description for the follow-up
                                    $followUpDescription = 'Implementer Follow Up By ' . auth()->user()->name;

                                    // Create a new implementer_logs entry with reference to SoftwareHandover
                                    $implementerLog = ImplementerLogs::create([
                                        'lead_id' => $record->id,
                                        'description' => $followUpDescription,
                                        'causer_id' => auth()->id(),
                                        'remark' => $data['notes'],
                                        'subject_id' => $softwareHandover->id,
                                        'follow_up_date' => $data['follow_up_date'],
                                        'follow_up_counter' => true,
                                        'manual_follow_up_count' => $data['manual_follow_up_count'],
                                    ]);

                                    if (isset($data['send_email']) && $data['send_email']) {
                                        try {
                                            // Get recipient emails
                                            $recipientStr = $data['required_attendees'] ?? '';

                                            if (!empty($recipientStr)) {
                                                // Get email template content
                                                $subject = $data['email_subject'];
                                                $content = $data['email_content'];

                                                // Add signature to email content if provided
                                                if (isset($data['implementer_name']) && !empty($data['implementer_name'])) {
                                                    $signature = "Regards,<br>";
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
                                                $placeholders = [
                                                    '{customer_name}' => $record->contact_name ?? '',
                                                    '{company_name}' => $softwareHandover->company_name ?? $record->companyDetail->company_name,
                                                    '{implementer_name}' => $data['implementer_name'] ?? auth()->user()->name ?? '',
                                                    '{follow_up_date}' => $data['follow_up_date'] ? date('d M Y', strtotime($data['follow_up_date'])) : '',
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
                                                    // Get authenticated user's email for sender and BCC
                                                    $authUser = auth()->user();
                                                    $senderEmail = $data['implementer_email'] ?? $authUser->email;
                                                    $senderName = $data['implementer_name'] ?? $authUser->name;

                                                    $schedulerType = $data['scheduler_type'] ?? 'instant';

                                                    $template = EmailTemplate::find($data['email_template']);
                                                    $templateName = $template ? $template->name : 'Custom Email';

                                                    // Store email data for scheduling
                                                    $emailData = [
                                                        'content' => $content,
                                                        'subject' => $subject,
                                                        'recipients' => $validRecipients,
                                                        'sender_email' => $senderEmail,
                                                        'sender_name' => $senderName,
                                                        'lead_id' => $record->id,
                                                        'implementer_log_id' => $implementerLog->id,
                                                        'template_name' => $templateName, // Add this line to store the template name
                                                        'scheduler_type' => $schedulerType, // Add this to track how it's scheduled
                                                    ];

                                                    // Handle different scheduler types
                                                    if ($schedulerType === 'instant' || $schedulerType === 'both') {
                                                        // Send email immediately
                                                        self::sendEmail($emailData);

                                                        DB::table('scheduled_emails')->insert([
                                                            'email_data' => json_encode($emailData),
                                                            'scheduled_date' => null,
                                                            'status' => 'Done',
                                                            'created_at' => now(),
                                                            'updated_at' => now(),
                                                        ]);

                                                        Notification::make()
                                                            ->title('Email sent immediately to ' . count($validRecipients) . ' recipient(s)')
                                                            ->success()
                                                            ->send();
                                                    }

                                                    if ($schedulerType === 'scheduled' || $schedulerType === 'both') {
                                                        // Schedule email for follow-up date at 8am
                                                        $scheduledDate = date('Y-m-d 08:00:00', strtotime($data['follow_up_date']));

                                                        // Store scheduled email in database
                                                        // This is just a placeholder - you'll need to implement the actual scheduling logic
                                                        DB::table('scheduled_emails')->insert([
                                                            'email_data' => json_encode($emailData),
                                                            'scheduled_date' => $scheduledDate,
                                                            'status' => 'New',
                                                            'created_at' => now(),
                                                            'updated_at' => now(),
                                                        ]);

                                                        Notification::make()
                                                            ->title('Email scheduled for ' . date('d M Y \a\t 8:00 AM', strtotime($scheduledDate)))
                                                            ->success()
                                                            ->send();
                                                    }
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            Log::error('Error sending follow-up email: ' . $e->getMessage());
                                            Notification::make()
                                                ->title('Error sending email')
                                                ->body($e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    }

                                    Notification::make()
                                        ->title('Follow-up added successfully')
                                        ->success()
                                        ->send();
                                })
                                ->mutateFormDataUsing(function (array $data, Lead $record): array {
                                    // Load contact emails for the lead
                                    if (!isset($data['email_recipients'])) {
                                        $data['email_recipients'] = [];
                                    }

                                    return $data;
                                }),
                        ])
                        ->schema([
                            Card::make()
                                ->schema([
                                    View::make('components.implementer-followup-history')
                                        ->extraAttributes(['class' => 'p-0']),
                                ])
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }

    /**
     * Send email using the provided data with CC to implementer and salesperson
     *
     * @param array $emailData
     * @return void
     */
    private static function sendEmail(array $emailData): void
    {
        try {
            // Get the implementer log record
            $implementerLog = ImplementerLogs::find($emailData['implementer_log_id']);

            if (!$implementerLog) {
                Log::error("Implementer log not found for ID: {$emailData['implementer_log_id']}");
                return;
            }

            // Find the software handover record using subject_id from implementer log
            $softwareHandover = SoftwareHandover::find($implementerLog->subject_id);

            if (!$softwareHandover) {
                Log::error("Software handover not found for subject_id: {$implementerLog->subject_id}");
                return;
            }

            // Initialize CC recipients array
            $ccRecipients = [];

            // Add implementer to CC if available and different from sender
            if ($softwareHandover->implementer) {
                // Look up user by name instead of ID
                $implementer = \App\Models\User::where('name', $softwareHandover->implementer)->first();
                if ($implementer && $implementer->email && $implementer->email !== $emailData['sender_email']) {
                    $ccRecipients[] = $implementer->email;
                    Log::info("Added implementer to CC: {$implementer->name} <{$implementer->email}>");
                } else {
                    Log::info("Implementer not found or no valid email for: {$softwareHandover->implementer}");
                }
            }

            // Add salesperson to CC if available and different from sender and implementer
            if ($softwareHandover->salesperson) {
                // Look up user by name instead of ID
                $salesperson = \App\Models\User::where('name', $softwareHandover->salesperson)->first();
                if ($salesperson && $salesperson->email &&
                    $salesperson->email !== $emailData['sender_email'] &&
                    !in_array($salesperson->email, $ccRecipients)) {
                    $ccRecipients[] = $salesperson->email;
                    Log::info("Added salesperson to CC: {$salesperson->name} <{$salesperson->email}>");
                } else {
                    Log::info("Salesperson not found or no valid email for: {$softwareHandover->salesperson}");
                }
            }

            // Send the email with CC recipients
            Mail::html($emailData['content'], function (Message $message) use ($emailData, $ccRecipients) {
                $message->to($emailData['recipients'])
                    ->subject($emailData['subject'])
                    ->from($emailData['sender_email'], $emailData['sender_name']);

                // Add CC recipients if we have any
                if (!empty($ccRecipients)) {
                    $message->cc($ccRecipients);
                }

                // BCC the sender as well
                $message->bcc($emailData['sender_email']);
            });

            // Log email sent successfully
            Log::info('Follow-up email sent successfully', [
                'to' => $emailData['recipients'],
                'cc' => $ccRecipients,
                'subject' => $emailData['subject'],
                'implementer_log_id' => $emailData['implementer_log_id'],
                'template' => $emailData['template_name'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in sendEmail method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $emailData
            ]);
        }
    }
}
