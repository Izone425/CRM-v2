<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\AdminRenewalLogs;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\Renewal;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ARFollowUpTabs
{
    protected static function canEditFollowUp($record): bool
    {
        $user = auth()->user();

        // Admin users (role_id = 3) can always edit
        if ($user->role_id == 3) {
            return true;
        }

        // Get the renewal record for this lead
        $renewal = Renewal::where('lead_id', $record->id)->first();

        // Check if the current user is the assigned admin_renewal
        if ($renewal && $renewal->admin_renewal === $user->name) {
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
                    Section::make('Renewal Follow Up')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->description(function ($record) {
                            // Get renewal record for this lead
                            $renewal = Renewal::where('lead_id', $record->id)->first();

                            if (! $renewal || ! $renewal->f_company_id) {
                                return null;
                            }

                            // Get earliest expiry date for this company
                            $earliestExpiry = self::getEarliestExpiryDate($renewal->f_company_id);

                            if ($earliestExpiry) {
                                $expiryDate = Carbon::parse($earliestExpiry);
                                $today = Carbon::now();

                                // Calculate days until expiry
                                $daysUntilExpiry = $today->diffInDays($expiryDate, false);

                                // Format the message with color coding based on urgency
                                if ($daysUntilExpiry < 0) {
                                    $urgency = '🔴 EXPIRED';
                                    $message = 'License expired '.abs($daysUntilExpiry).' days ago';
                                } elseif ($daysUntilExpiry <= 7) {
                                    $urgency = '🟠 URGENT';
                                    $message = "License expires in {$daysUntilExpiry} days";
                                } elseif ($daysUntilExpiry <= 30) {
                                    $urgency = '🟡 SOON';
                                    $message = "License expires in {$daysUntilExpiry} days";
                                } else {
                                    $urgency = '🟢 NORMAL';
                                    $message = "License expires in {$daysUntilExpiry} days";
                                }

                                return new \Illuminate\Support\HtmlString("<span><span style='color: red; font-weight: bold;'>License Expiry: {$expiryDate->format('d M Y')} ({$message})</span></span>");
                            }

                            return null;
                        })
                        ->headerActions([
                            Action::make('add_follow_up')
                                ->label('Add Follow Up')
                                ->button()
                                ->color('primary')
                                ->icon('heroicon-o-plus')
                                ->visible(function ($record) {
                                    return self::canEditFollowUp($record);
                                })
                                ->modalWidth('6xl')
                                ->form([
                                    Grid::make(4)
                                        ->schema([
                                            DatePicker::make('follow_up_date')
                                                ->label('Next Follow-up Date')
                                                ->default(function () {
                                                    $today = now();
                                                    $daysUntilNextTuesday = (9 - $today->dayOfWeek) % 7; // 2 is Tuesday, but we add 7 to ensure positive
                                                    if ($daysUntilNextTuesday === 0) {
                                                        $daysUntilNextTuesday = 7; // If today is Tuesday, we want next Tuesday
                                                    }

                                                    return $today->addDays($daysUntilNextTuesday);
                                                })
                                                ->minDate(now()->subDay())
                                                ->required(),

                                            TextInput::make('earliest_expiry_display')
                                                ->label('License Expiry')
                                                ->disabled()
                                                ->default(function ($record) {
                                                    // Get renewal record for this lead
                                                    $renewal = Renewal::where('lead_id', $record->id)->first();

                                                    if (! $renewal || ! $renewal->f_company_id) {
                                                        return 'Not Available';
                                                    }

                                                    // Get earliest expiry date for this company
                                                    $earliestExpiry = self::getEarliestExpiryDate($renewal->f_company_id);

                                                    if ($earliestExpiry) {
                                                        $expiryDate = Carbon::parse($earliestExpiry);
                                                        $today = Carbon::now();

                                                        return $expiryDate->format('d M Y');
                                                    }

                                                    return 'Not Available';
                                                })
                                                ->dehydrated(false) // Don't include this field in form submission
                                                ->extraInputAttributes([
                                                    'style' => 'font-weight: 600; color: #374151;',
                                                ]),

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
                                                    'both' => 'Both',
                                                ])
                                                ->visible(fn ($get) => $get('send_email'))
                                                ->required(),
                                        ]),

                                    Fieldset::make('Email Details')
                                        ->schema([
                                            TextInput::make('required_attendees')
                                                ->label('Required Attendees')
                                                ->default(function (?Lead $record = null) {
                                                    // Initialize emails array to store all collected emails
                                                    $emails = [];

                                                    if ($record) {
                                                        $emails[] = $record->email;

                                                        // 1. Get email from companyDetail->email (primary company email)
                                                        if ($record->companyDetail && ! empty($record->companyDetail->email)) {
                                                            $emails[] = $record->companyDetail->email;
                                                        }

                                                        // 2. Get emails from company_detail->additional_pic
                                                        if ($record->companyDetail && ! empty($record->companyDetail->additional_pic)) {
                                                            try {
                                                                $additionalPics = json_decode($record->companyDetail->additional_pic, true);

                                                                if (is_array($additionalPics)) {
                                                                    foreach ($additionalPics as $pic) {
                                                                        // Only include contacts with "Available" status
                                                                        if (
                                                                            ! empty($pic['email']) &&
                                                                            isset($pic['status']) &&
                                                                            $pic['status'] === 'Available'
                                                                        ) {
                                                                            $emails[] = $pic['email'];
                                                                        }
                                                                    }
                                                                }
                                                            } catch (\Exception $e) {
                                                                \Illuminate\Support\Facades\Log::error('Error parsing additional_pic JSON: '.$e->getMessage());
                                                            }
                                                        }
                                                    }

                                                    // Remove duplicates and return as semicolon-separated string
                                                    $uniqueEmails = array_unique($emails);

                                                    return ! empty($uniqueEmails) ? implode(';', $uniqueEmails) : null;
                                                })
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
                                                ->createOptionForm([
                                                    TextInput::make('name')
                                                        ->label('Template Name')
                                                        ->required(),
                                                    TextInput::make('subject')
                                                        ->label('Email Subject')
                                                        ->required(),
                                                    RichEditor::make('content')
                                                        ->label('Email Content')
                                                        ->disableToolbarButtons([
                                                            'attachFiles',
                                                        ])
                                                        ->required(),
                                                ])
                                                ->createOptionAction(function (Action $action) {
                                                    $action->modalHeading('Create Email Template')
                                                        ->modalWidth('xl');
                                                })
                                                ->createOptionUsing(function (array $data) {
                                                    return EmailTemplate::create([
                                                        'name' => $data['name'],
                                                        'subject' => $data['subject'],
                                                        'content' => $data['content'],
                                                        'type' => 'admin_renewal',
                                                        'created_by' => auth()->id(),
                                                    ])->id;
                                                }),

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

                                    Hidden::make('admin_name')
                                        ->label('NAME')
                                        ->default(auth()->user()->name ?? '')
                                        ->required(),

                                    Hidden::make('admin_designation')
                                        ->label('DESIGNATION')
                                        ->default('Admin Renewal')
                                        ->required(),

                                    Hidden::make('admin_company')
                                        ->label('COMPANY NAME')
                                        ->default('TimeTec Cloud Sdn Bhd')
                                        ->required(),

                                    Hidden::make('admin_phone')
                                        ->label('PHONE NO')
                                        ->default('03-80709933')
                                        ->required(),

                                    Hidden::make('admin_email')
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
                                        ->afterStateHydrated(fn ($state) => Str::upper($state))
                                        ->afterStateUpdated(fn ($state) => Str::upper($state))
                                        ->placeholder('Add your follow-up details here...')
                                        ->required(),
                                ])
                                ->modalHeading('Add New Follow-up')
                                ->action(function (Lead $record, array $data) {
                                    // Find or create the Renewal record for this lead
                                    $renewal = Renewal::firstOrCreate(
                                        ['lead_id' => $record->id],
                                        [
                                            'company_name' => $record->companyDetail->company_name ?? '',
                                            'admin_renewal' => auth()->user()->name,
                                        ]
                                    );

                                    // Update the Renewal record with follow-up information
                                    $renewal->update([
                                        'follow_up_date' => $data['follow_up_date'],
                                        'follow_up_counter' => true,
                                    ]);

                                    // Create description for the follow-up
                                    $followUpDescription = 'Admin Renewal Follow Up By '.auth()->user()->name;

                                    // Create a new admin_renewal_logs entry with reference to Renewal
                                    $adminRenewalLog = AdminRenewalLogs::create([
                                        'lead_id' => $record->id,
                                        'description' => $followUpDescription,
                                        'causer_id' => auth()->id(),
                                        'remark' => $data['notes'],
                                        'subject_id' => $renewal->id,
                                        'follow_up_date' => $data['follow_up_date'],
                                        'follow_up_counter' => true,
                                    ]);

                                    if (isset($data['send_email']) && $data['send_email']) {
                                        try {
                                            // Get recipient emails
                                            $recipientStr = $data['required_attendees'] ?? '';

                                            if (! empty($recipientStr)) {
                                                // Get email template content
                                                $subject = $data['email_subject'];
                                                $content = $data['email_content'];

                                                // Add signature to email content if provided
                                                if (isset($data['admin_name']) && ! empty($data['admin_name'])) {
                                                    $signature = 'Regards,<br>';
                                                    $signature .= "{$data['admin_name']}<br>";
                                                    $signature .= "{$data['admin_designation']}<br>";
                                                    $signature .= "{$data['admin_company']}<br>";
                                                    $signature .= "Phone: {$data['admin_phone']}<br>";

                                                    if (! empty($data['admin_email'])) {
                                                        $signature .= "Email: {$data['admin_email']}<br>";
                                                    }

                                                    $content .= $signature;
                                                }

                                                // Replace placeholders with actual data
                                                $placeholders = [
                                                    '{customer_name}' => $record->contact_name ?? '',
                                                    '{company_name}' => $renewal->company_name ?? $record->companyDetail->company_name,
                                                    '{admin_name}' => $data['admin_name'] ?? auth()->user()->name ?? '',
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

                                                if (! empty($validRecipients)) {
                                                    // Get authenticated user's email for sender and BCC
                                                    $authUser = auth()->user();
                                                    $senderEmail = $data['admin_email'] ?? $authUser->email;
                                                    $senderName = $data['admin_name'] ?? $authUser->name;

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
                                                        'admin_renewal_log_id' => $adminRenewalLog->id,
                                                        'template_name' => $templateName,
                                                        'scheduler_type' => $schedulerType,
                                                    ];

                                                    // Handle different scheduler types
                                                    if ($schedulerType === 'instant' || $schedulerType === 'both') {
                                                        // Send email immediately
                                                        self::sendEmail($emailData);

                                                        Notification::make()
                                                            ->title('Email sent immediately to '.count($validRecipients).' recipient(s)')
                                                            ->success()
                                                            ->send();
                                                    }

                                                    if ($schedulerType === 'scheduled' || $schedulerType === 'both') {
                                                        // Schedule email for follow-up date at 8am
                                                        $scheduledDate = date('Y-m-d 08:00:00', strtotime($data['follow_up_date']));

                                                        // Store scheduled email in database
                                                        DB::table('scheduled_emails')->insert([
                                                            'email_data' => json_encode($emailData),
                                                            'scheduled_date' => $scheduledDate,
                                                            'status' => 'New',
                                                            'created_at' => now(),
                                                            'updated_at' => now(),
                                                        ]);

                                                        Notification::make()
                                                            ->title('Email scheduled for '.date('d M Y \a\t 8:00 AM', strtotime($scheduledDate)))
                                                            ->success()
                                                            ->send();
                                                    }
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            Log::error('Error sending follow-up email: '.$e->getMessage());
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
                                    if (! isset($data['email_recipients'])) {
                                        $data['email_recipients'] = [];
                                    }

                                    return $data;
                                }),
                        ])
                        ->schema([
                            Card::make()
                                ->schema([
                                    View::make('components.admin-renewal-followup-history')
                                        ->extraAttributes(['class' => 'p-0']),
                                ])
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }

    /**
     * Send email using the provided data with CC to admin renewal and salesperson
     */
    private static function sendEmail(array $emailData): void
    {
        try {
            // Get the admin renewal log record
            $adminRenewalLog = AdminRenewalLogs::find($emailData['admin_renewal_log_id']);

            if (! $adminRenewalLog) {
                Log::error("Admin renewal log not found for ID: {$emailData['admin_renewal_log_id']}");

                return;
            }

            // Find the renewal record using subject_id from admin renewal log
            $renewal = Renewal::find($adminRenewalLog->subject_id);

            if (! $renewal) {
                Log::error("Renewal not found for subject_id: {$adminRenewalLog->subject_id}");

                return;
            }

            // Initialize CC recipients array
            $ccRecipients = [];

            // Add admin renewal to CC if available and different from sender
            if ($renewal->admin_renewal) {
                // Look up user by name
                $adminUser = \App\Models\User::where('name', $renewal->admin_renewal)->first();
                if ($adminUser && $adminUser->email && $adminUser->email !== $emailData['sender_email']) {
                    $ccRecipients[] = $adminUser->email;
                    Log::info("Added admin renewal to CC: {$adminUser->name} <{$adminUser->email}>");
                } else {
                    Log::info("Admin renewal user not found or no valid email for: {$renewal->admin_renewal}");
                }
            }

            // Get lead and add salesperson to CC if available
            $lead = Lead::find($renewal->lead_id);
            if ($lead && $lead->salesperson) {
                $salesperson = \App\Models\User::find($lead->salesperson);
                if ($salesperson && $salesperson->email &&
                    $salesperson->email !== $emailData['sender_email'] &&
                    ! in_array($salesperson->email, $ccRecipients)) {
                    $ccRecipients[] = $salesperson->email;
                    Log::info("Added salesperson to CC: {$salesperson->name} <{$salesperson->email}>");
                } else {
                    Log::info("Salesperson not found or no valid email for ID: {$lead->salesperson}");
                }
            }

            // Send the email with CC recipients
            Mail::html($emailData['content'], function (Message $message) use ($emailData, $ccRecipients) {
                $message->to($emailData['recipients'])
                    ->subject($emailData['subject'])
                    ->from($emailData['sender_email'], $emailData['sender_name']);

                // Add CC recipients if we have any
                if (! empty($ccRecipients)) {
                    $message->cc($ccRecipients);
                }

                // BCC the sender as well
                $message->bcc($emailData['sender_email']);
            });

            // Log email sent successfully
            Log::info('Admin renewal follow-up email sent successfully', [
                'to' => $emailData['recipients'],
                'cc' => $ccRecipients,
                'subject' => $emailData['subject'],
                'admin_renewal_log_id' => $emailData['admin_renewal_log_id'],
                'template' => $emailData['template_name'] ?? 'Unknown',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in sendEmail method: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $emailData,
            ]);
        }
    }

    protected static function getEarliestExpiryDate($companyId)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            $earliestExpiry = DB::connection('frontenddb')
                ->table('crm_expiring_license')
                ->where('f_company_id', $companyId)
                ->where('f_expiry_date', '>=', $today)
                ->where('f_currency', 'MYR') // You can modify this or make it dynamic
                ->whereNotIn('f_name', [
                    'TimeTec VMS Corporate (1 Floor License)',
                    'TimeTec VMS SME (1 Location License)',
                    'TimeTec Patrol (1 Checkpoint License)',
                    'TimeTec Patrol (10 Checkpoint License)',
                    'Other',
                    'TimeTec Profile (10 User License)',
                ])
                ->min('f_expiry_date');

            return $earliestExpiry;
        } catch (\Exception $e) {
            Log::error("Error fetching earliest expiry date for company {$companyId}: ".$e->getMessage());

            return null;
        }
    }
}
