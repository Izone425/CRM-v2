<?php

namespace App\Filament\Actions;

use App\Models\AdminRenewalLogs;
use App\Models\Renewal;
use App\Models\EmailTemplate;
use Carbon\Carbon;
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
use Illuminate\Support\HtmlString;

class AdminRenewalActions
{
    public static function viewAction(): Action
    {
        return Action::make('view')
            ->label('View')
            ->icon('heroicon-o-eye')
            ->color('secondary')
            ->url(function (Renewal $record) {
                if ($record->lead_id) {
                    $encryptedId = \App\Classes\Encryptor::encrypt($record->lead_id);
                    return url('admin/leads/' . $encryptedId);
                }
                return '#';
            })
            ->openUrlInNewTab();
    }

    public static function viewLastFollowUpAction(): Action
    {
        return Action::make('view_last_follow_up')
            ->label('View Last Follow Up')
            ->icon('heroicon-o-eye')
            ->color('secondary')
            ->modalHeading('Last Follow Up Information')
            ->modalContent(function (Renewal $record) {
                $data = AdminRenewalLogs::where('subject_id', $record->id)
                    ->latest()
                    ->first();

                if (! $data) {
                    return new \Illuminate\Support\HtmlString(
                        "<div class='p-6 text-center'>
                            <p class='text-gray-500'>No follow-up records found for this renewal.</p>
                        </div>"
                    );
                }

                $followUpDate = $data->created_at ? Carbon::parse($data->created_at)->format('d M Y, h:i A') : 'N/A';
                $followUpBy = $data->causer ? $data->causer->name : 'System';
                $nextFollowUpDate = $data->follow_up_date ? Carbon::parse($data->follow_up_date)->format('d M Y') : 'N/A';
                $followUpCount = $data->manual_follow_up_count ? "Follow-up #{$data->manual_follow_up_count}" : '';

                return new HtmlString(
                    "<div class='space-y-6'>
                        <div class='p-4 rounded-lg bg-gray-50'>
                            <h3 class='mb-3 text-lg font-semibold text-gray-900'>Follow Up Details</h3><br>
                            <div class='grid grid-cols-2 text-sm gap'>
                                <div>
                                    <span class='font-medium text-gray-700'>Follow Up Date:</span>
                                    <span class='ml-2 text-gray-900'>{$followUpDate}</span>
                                </div>
                                <div>
                                    <span class='font-medium text-gray-700'>Follow Up By:</span>
                                    <span class='ml-2 text-gray-900'>{$followUpBy}</span>
                                </div>
                                <div>
                                    <span class='font-medium text-gray-700'>Next Follow Up:</span>
                                    <span class='ml-2 text-gray-900'>{$nextFollowUpDate}</span>
                                </div>
                                ".($followUpCount ? "<div><span class='font-medium text-gray-700'>Count:</span><span class='ml-2 text-gray-900'>{$followUpCount}</span></div>" : '')."
                            </div>
                        </div>
                        <div class='p-4 rounded-lg bg-gray-50'>
                            <h3 class='mb-3 text-lg font-semibold text-gray-900'>Remarks</h3>
                            <br>
                            <div class='grid grid-cols-2 text-sm gap'>
                                <div>
                                    {$data->remark}
                                </div>
                            </div>
                        </div>
                    </div>"
                );
            })
            ->modalWidth('2xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    public static function viewProcessDataAction(): Action
    {
        return Action::make('view_process_data')
            ->label('View Process Data')
            ->icon('heroicon-o-eye')
            ->color('secondary')
            ->url(function (Renewal $record) {
                $padded = str_pad($record->f_company_id, 10, '0', STR_PAD_LEFT);

                $data = \App\Filament\Pages\RenewalDataMyr::where('f_company_id', $padded)
                    ->first();

                if ($data && $data->f_currency == 'MYR') {
                    return url('/admin/admin-renewal-process-data-myr');
                } else {
                    return url('/admin/admin-renewal-process-data-usd');
                }

                return '#';
            })
            ->openUrlInNewTab();
    }

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
                                $workingDaysAdded = 0;
                                $currentDate = $today->copy();

                                while ($workingDaysAdded < 2) {
                                    $currentDate->addDay();
                                    // Check if it's a weekday (Monday = 1, Sunday = 7)
                                    if ($currentDate->dayOfWeek >= 1 && $currentDate->dayOfWeek <= 5) {
                                        $workingDaysAdded++;
                                    }
                                }

                                return $currentDate;
                            })
                            ->minDate(now()->subDay())
                            ->required(),

                        TextInput::make('earliest_expiry_display')
                            ->label('License Expiry')
                            ->disabled()
                            ->default(function ($record) {
                                // Get renewal record for this lead
                                $renewal = Renewal::where('lead_id', $record->lead_id)->first();
                                if (!$renewal || !$renewal->f_company_id) {
                                    return 'Not Available';
                                }

                                // Get earliest expiry date for this company
                                $earliestExpiry = self::getEarliestExpiryDate($renewal->f_company_id);

                                if ($earliestExpiry) {
                                    $expiryDate = Carbon::parse($earliestExpiry);
                                    return $expiryDate->format('d M Y');
                                }

                                return 'Not Available';
                            })
                            ->dehydrated(false)
                            ->extraInputAttributes([
                                'style' => 'font-weight: 600; color: #374151;'
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

                        Select::make('scheduler_type')
                            ->label('Scheduler Type')
                            ->options([
                                'instant' => 'Email Immediately',
                                'scheduled' => 'Next Follow Up Date at 8am',
                                // 'both' => 'Both'
                            ])
                            ->visible(fn ($get) => $get('send_email'))
                            ->required(),
                    ]),

                \Filament\Forms\Components\Section::make('Quotation Attachments')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('quotation_product')
                                    ->label('Product Quotations')
                                    ->options(function ($record) {
                                        if (!$record || !$record->lead_id) {
                                            return [];
                                        }

                                        return \App\Models\Quotation::where('lead_id', $record->lead_id)
                                            ->where('quotation_type', 'product')
                                            ->where('sales_type', 'RENEWAL SALES')
                                            ->get()
                                            ->mapWithKeys(function ($quotation) {
                                                $label = $quotation->quotation_reference_no ?? 'No Reference - ID: ' . $quotation->id;
                                                return [$quotation->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select product quotations to attach to the email'),

                                Select::make('quotation_hrdf')
                                    ->label('HRDF Quotations')
                                    ->options(function ($record) {
                                        if (!$record || !$record->lead_id) {
                                            return [];
                                        }

                                        return \App\Models\Quotation::where('lead_id', $record->lead_id)
                                            ->where('quotation_type', 'hrdf')
                                            ->where('sales_type', 'RENEWAL SALES')
                                            ->get()
                                            ->mapWithKeys(function ($quotation) {
                                                $label = $quotation->quotation_reference_no ?? 'No Reference - ID: ' . $quotation->id;
                                                return [$quotation->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select HRDF quotations to attach to the email'),
                            ])
                    ])
                    ->visible(fn ($get) => $get('send_email'))
                    ->collapsible()
                    ->collapsed(),

                Fieldset::make('Email Details')
                    ->schema([
                        TextInput::make('required_attendees')
                            ->label('Required Attendees')
                            ->default(function ($record) {
                                // Initialize emails array to store all collected emails
                                $emails = [];

                                // $record is a Renewal object, so we need to access the lead through the relationship
                                $lead = $record->lead;

                                if ($lead) {
                                    $emails[] = $lead->email;

                                    // 1. Get email from companyDetail->email (primary company email)
                                    if ($lead->companyDetail && !empty($lead->companyDetail->email)) {
                                        $emails[] = $lead->companyDetail->email;
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
                                return EmailTemplate::whereIn('type', ['admin_renewal', 'admin_renewal_v1', 'admin_renewal_v2'])
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
                            }),

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
            ->modalHeading(function ($record) {
                $companyName = 'Unknown Company';

                if ($record) {
                    // Try to get company name from the renewal record first
                    if (!empty($record->company_name)) {
                        $companyName = $record->company_name;
                    }
                    // Fallback to lead's company detail
                    elseif ($record->lead && $record->lead->companyDetail && !empty($record->lead->companyDetail->company_name)) {
                        $companyName = $record->lead->companyDetail->company_name;
                    }
                    // Last fallback to lead's company name field
                    elseif ($record->lead && !empty($record->lead->company)) {
                        $companyName = $record->lead->company;
                    }
                }

                return "Add New Follow-up - {$companyName}";
            });
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
        ]);

        // Handle email sending if enabled
        if (isset($data['send_email']) && $data['send_email']) {
            try {
                $recipientStr = $data['required_attendees'] ?? '';

                if (!empty($recipientStr)) {
                    $subject = $data['email_subject'];
                    $content = $data['email_content'];

                    // Add signature to email content
                    if (isset($data['admin_name']) && ! empty($data['admin_name'])) {
                        $signature = 'Regards,<br>';
                        $signature .= "{$data['admin_name']} | {$data['admin_designation']}<br>";
                        $signature .= "Office: 03-8070 9933 (Ext 307) | Mobile: 013-677 0597<br>";
                        $signature .= "Email: renewal.timetec.hr@timeteccloud.com<br>";

                        $content .= $signature;
                    }

                    // Replace placeholders with actual data
                    $lead = $record->lead;
                    $placeholders = [
                        '{customer_name}' => $lead->contact_name ?? '',
                        '{company_name}' => $record->company_name ?? $lead->companyDetail->company_name,
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

                    if (!empty($validRecipients)) {
                        $authUser = auth()->user();
                        $senderEmail = $data['admin_email'] ?? $authUser->email;
                        $senderName = $data['admin_name'] ?? $authUser->name;
                        $schedulerType = $data['scheduler_type'] ?? 'instant';

                        $template = EmailTemplate::find($data['email_template']);
                        $templateName = $template ? $template->name : 'Custom Email';

                        $emailData = [
                            'content' => $content,
                            'subject' => $subject,
                            'recipients' => $validRecipients,
                            'sender_email' => $senderEmail,
                            'sender_name' => $senderName,
                            'lead_id' => $record->lead_id,
                            'admin_renewal_log_id' => $adminRenewalLog->id,
                            'template_name' => $templateName,
                            'scheduler_type' => $schedulerType,
                            'quotation_product' => $data['quotation_product'] ?? [],
                            'quotation_hrdf' => $data['quotation_hrdf'] ?? [],
                        ];

                        // Handle different scheduler types
                        if ($schedulerType === 'instant' || $schedulerType === 'both') {
                            self::sendEmail($emailData);
                            Notification::make()
                                ->title('Email sent immediately to ' . count($validRecipients) . ' recipient(s)')
                                ->success()
                                ->send();
                        }

                        if ($schedulerType === 'scheduled' || $schedulerType === 'both') {
                            $scheduledDate = date('Y-m-d 08:00:00', strtotime($data['follow_up_date']));

                            // Prepare attachments data for scheduled email
                            $attachmentsData = self::prepareAttachmentsData($emailData);

                            // Add attachments data to email data for scheduled sending
                            $emailData['attachments_data'] = $attachmentsData;

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
    }

    private static function sendEmail(array $emailData): void
    {
        try {
            $adminRenewalLog = AdminRenewalLogs::find($emailData['admin_renewal_log_id']);
            if (!$adminRenewalLog) {
                Log::error("Admin renewal log not found for ID: {$emailData['admin_renewal_log_id']}");
                return;
            }

            $renewal = Renewal::find($adminRenewalLog->subject_id);
            if (!$renewal) {
                Log::error("Renewal not found for subject_id: {$adminRenewalLog->subject_id}");
                return;
            }

            $ccRecipients = [];

            // Add admin renewal to CC
            if ($renewal->admin_renewal) {
                $adminUser = \App\Models\User::where('name', $renewal->admin_renewal)->first();
                if ($adminUser && $adminUser->email && $adminUser->email !== $emailData['sender_email']) {
                    $ccRecipients[] = $adminUser->email;
                }
            }

            // Prepare attachments data for both instant and scheduled emails
            $attachmentsData = self::prepareAttachmentsData($emailData);

            Mail::html($emailData['content'], function ($message) use ($emailData, $ccRecipients, $attachmentsData) {
                $message->to($emailData['recipients'])
                    ->subject($emailData['subject'])
                    ->from($emailData['sender_email'], $emailData['sender_name']);

                if (!empty($ccRecipients)) {
                    $message->cc($ccRecipients);
                }

                $message->bcc($emailData['sender_email']);

                // Add PDF attachments
                foreach ($attachmentsData as $attachment) {
                    if (file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'],
                            'mime' => $attachment['mime']
                        ]);
                    }
                }
            });

            Log::info('Admin renewal follow-up email sent successfully', [
                'to' => $emailData['recipients'],
                'cc' => $ccRecipients,
                'subject' => $emailData['subject'],
                'admin_renewal_log_id' => $emailData['admin_renewal_log_id'],
                'attachments_count' => count($attachmentsData),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in sendEmail method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $emailData,
            ]);
        }
    }

    private static function prepareAttachmentsData(array $emailData): array
    {
        $attachmentsData = [];

        // Get quotation IDs for attachments
        $quotationIds = array_merge(
            $emailData['quotation_product'] ?? [],
            $emailData['quotation_hrdf'] ?? []
        );

        if (!empty($quotationIds)) {
            $quotations = \App\Models\Quotation::whereIn('id', $quotationIds)->get();

            foreach ($quotations as $quotation) {
                try {
                    $pdfPath = self::findQuotationPDF($quotation);

                    if ($pdfPath && file_exists($pdfPath)) {
                        $attachmentsData[] = [
                            'path' => $pdfPath,
                            'name' => self::getQuotationFileName($quotation),
                            'mime' => 'application/pdf',
                            'quotation_id' => $quotation->id
                        ];

                        Log::info("Added PDF attachment for quotation ID: {$quotation->id}", [
                            'file_path' => $pdfPath,
                            'file_name' => self::getQuotationFileName($quotation)
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error preparing PDF for quotation ID: {$quotation->id}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $attachmentsData;
    }

    private static function findQuotationPDF(\App\Models\Quotation $quotation): ?string
    {
        try {
            // Generate the expected filename based on your existing logic
            $companyName = '';
            if (!empty($quotation->subsidiary_id)) {
                $subsidiary = \App\Models\Subsidiary::find($quotation->subsidiary_id);
                $companyName = $subsidiary ? $subsidiary->company_name : 'Unknown';
            } else {
                $companyName = $quotation->lead->companyDetail->company_name ?? 'Unknown';
            }

            // Primary filename attempt
            $quotationFilename = 'TIMETEC_' . $quotation->sales_person->code . '_' . quotation_reference_no($quotation->id) . '_' . \Illuminate\Support\Str::replace('-','_',\Illuminate\Support\Str::slug($companyName));
            $quotationFilename = \Illuminate\Support\Str::upper($quotationFilename) . '.pdf';

            // Check multiple possible paths
            $possiblePaths = [
                storage_path('app/public/quotations/' . $quotationFilename),
                storage_path('app/public/quotations/TIMETEC_' . $quotation->sales_person->code . '_' . $quotation->id . '_' . \Illuminate\Support\Str::replace('-','_',\Illuminate\Support\Str::slug($companyName)) . '.pdf'),
            ];

            // Also try to find any PDF file that starts with the quotation pattern
            $quotationsDir = storage_path('app/public/quotations/');
            if (is_dir($quotationsDir)) {
                $pattern = 'TIMETEC_' . $quotation->sales_person->code . '_' . quotation_reference_no($quotation->id) . '_*';
                $matches = glob($quotationsDir . $pattern . '.pdf');

                if (!empty($matches)) {
                    $possiblePaths = array_merge($possiblePaths, $matches);
                }
            }

            // Try each possible path
            foreach ($possiblePaths as $storagePath) {
                if (file_exists($storagePath)) {
                    Log::info("Found existing PDF for quotation {$quotation->id}: " . basename($storagePath));
                    return $storagePath;
                }
            }

            Log::warning("PDF not found for quotation {$quotation->id}");
            return null;

        } catch (\Exception $e) {
            Log::error("Error finding PDF for quotation {$quotation->id}: " . $e->getMessage());
            return null;
        }
    }

    private static function getQuotationFileName(\App\Models\Quotation $quotation): string
    {
        $refNo = $quotation->pi_reference_no ?? $quotation->quotation_reference_no ?? 'Quotation-' . $quotation->id;
        $quotationType = ucfirst($quotation->quotation_type ?? 'Unknown');

        return 'Renewal_Quotation.pdf';
    }

    protected static function getEarliestExpiryDate($companyId)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            $earliestExpiry = DB::connection('frontenddb')
                ->table('crm_expiring_license')
                ->where('f_company_id', $companyId)
                ->where('f_expiry_date', '>=', $today)
                ->where('f_currency', 'MYR')
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
            Log::error("Error fetching earliest expiry date for company {$companyId}: " . $e->getMessage());
            return null;
        }
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
