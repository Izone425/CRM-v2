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
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Mail\Message;

class ImplementerFollowUpTabs
{
    public static function getSchema(): array
    {
        return [
            Grid::make(1)
                ->schema([
                    Section::make('Follow-up Records')
                        ->description('View and add follow-up tasks for this Project')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->headerActions([
                            Action::make('add_follow_up')
                                ->label('Add Follow-up')
                                ->button()
                                ->color('primary')
                                ->icon('heroicon-o-plus')
                                ->modalWidth('6xl')
                                ->form([
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

                                    Grid::make(2)
                                        ->schema([
                                            Toggle::make('send_email')
                                                ->label('Send Email to Customer?')
                                                ->onIcon('heroicon-o-bell-alert')
                                                ->offIcon('heroicon-o-bell-slash')
                                                ->onColor('primary')
                                                ->offColor('gray')
                                                ->default(false)
                                                ->live(onBlur: true),

                                            // Select::make('follow_up_count')
                                            //     ->label('Follow-up Count')
                                            //     ->inlineLabel()
                                            //     ->required()
                                            //     ->options([
                                            //         0 => '0',
                                            //         1 => '1',
                                            //         2 => '2',
                                            //         3 => '3',
                                            //         4 => '4',
                                            //         5 => '5',
                                            //     ])
                                            //     ->default(1),
                                        ]),

                                    Fieldset::make('Email Details')
                                        ->schema([
                                            TextInput::make('required_attendees')
                                                ->label('REQUIRED ATTENDEES')
                                                ->default(function (Lead $record = null) {
                                                    // First, find the related SoftwareHandover record
                                                    if ($record) {
                                                        $softwareHandover = SoftwareHandover::where('lead_id', $record->id)->latest()->first();

                                                        if ($softwareHandover && !empty($softwareHandover->implementation_pics) && is_string($softwareHandover->implementation_pics)) {
                                                            try {
                                                                $contacts = json_decode($softwareHandover->implementation_pics, true);

                                                                // If it's valid JSON array, extract emails
                                                                if (is_array($contacts)) {
                                                                    $emails = [];
                                                                    foreach ($contacts as $contact) {
                                                                        if (!empty($contact['pic_email_impl'])) {
                                                                            $emails[] = $contact['pic_email_impl'];
                                                                        }
                                                                    }

                                                                    return !empty($emails) ? implode(';', $emails) : null;
                                                                }
                                                            } catch (\Exception $e) {
                                                                \Illuminate\Support\Facades\Log::error('Error parsing implementation_pics JSON: ' . $e->getMessage());
                                                            }
                                                        }
                                                    }
                                                    return null;
                                                })
                                                ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

                                            Select::make('email_template')
                                                ->label('Email Template')
                                                ->options(function () {
                                                    return EmailTemplate::pluck('name', 'id')
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
                                                        'type' => 'implementer',
                                                        'created_by' => auth()->id(),
                                                    ])->id;
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
                                ->modalHeading('Add New Follow-up')
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
                                        'manual_follow_up_count' => $softwareHandover->manual_follow_up_count + 1,
                                    ]);

                                    // Create description for the follow-up
                                    $followUpDescription = 'Implementer Follow Up By ' . auth()->user()->name;

                                    // Create a new implementer_logs entry with reference to SoftwareHandover
                                    ImplementerLogs::create([
                                        'lead_id' => $record->id,
                                        'description' => $followUpDescription,
                                        'causer_id' => auth()->id(),
                                        'remark' => $data['notes'],
                                        'subject_id' => $softwareHandover->id,
                                        'follow_up_date' => $data['follow_up_date'],
                                    ]);

                                    if (isset($data['send_email']) && $data['send_email']) {
                                        try {
                                            // Get recipient emails
                                            $recipientStr = $data['required_attendees'] ?? '';

                                            if (!empty($recipientStr)) {
                                                // Get email template content
                                                $subject = $data['email_subject'];
                                                $content = $data['email_content'];

                                                // Replace placeholders with actual data
                                                $placeholders = [
                                                    '{customer_name}' => $record->contact_name ?? '',
                                                    '{company_name}' => $record->company_name ?? '',
                                                    '{implementer_name}' => auth()->user()->name ?? '',
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
                                                    $senderEmail = $authUser->email;
                                                    $senderName = $authUser->name;

                                                    // Send to all valid recipients at once
                                                    Mail::html($content, function (Message $message) use ($validRecipients, $subject, $senderEmail, $senderName) {
                                                        $message->to($validRecipients)  // This sends to all recipients in the To field
                                                            ->bcc($senderEmail)  // BCC the authenticated user
                                                            ->subject($subject)
                                                            ->from($senderEmail, $senderName);
                                                    });

                                                    Notification::make()
                                                        ->title('Email sent successfully to ' . count($validRecipients) . ' recipient(s)')
                                                        ->success()
                                                        ->send();
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
}
