<?php

namespace App\Filament\Actions;

use App\Services\TemplateSelector;
use App\Classes\Encryptor;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\QuotationStatusEnum;
use App\Mail\CancelDemoNotification;
use App\Mail\DemoNotification;
use App\Mail\FollowUpNotification;
use App\Mail\SalespersonNotification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Enums\ActionSize;
use App\Models\Lead;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\InvalidLeadReason;
use App\Models\Request;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\QuotationService;
use Beta\Microsoft\Graph\Model\Event;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event as FacadesEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Microsoft\Graph\Graph;
use Illuminate\Support\Str;
use Livewire\Component;

class LeadActions
{
    public static function getViewAction(): Action
    {
        return Action::make('view_lead')
            ->label('View Details')
            ->icon('heroicon-o-eye')
            ->color('primary')
            ->requiresConfirmation()
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalHeading('Lead Details')
            ->modalDescription('Here are the details for this lead.')
            ->modalContent(fn (?Lead $record) => $record
                ? view('filament.modals.lead-details', [
                    'lead' => $record,
                    'pending_days' => $record->pending_days, // Pass pending_days to the view
                ])
                : null // Return null if no record exists to prevent errors
            )
            ->extraModalFooterActions([
                LeadActions::getAssignToMeAction()
                    ->cancelParentActions(),

                // ✅ Show "Edit Details" button if lead_owner is NOT NULL
                Action::make('edit_lead')
                    ->label('Edit Details')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Lead Details')
                    ->modalDescription('Edit the lead details below.')
                    ->visible(fn (?Lead $record) => $record && !is_null($record->lead_owner) && auth()->user()?->role_id !== 2)
                    ->form(fn (Lead $record) => [
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->default($record->companyDetail->company_name ?? 'N/A')
                            ->extraAlpineAttributes(['@input' => ' $el.value = $el.value.toUpperCase()']),

                        TextInput::make('name')
                            ->label('PIC Name')
                            ->default($record->companyDetail->name ?? $record->name)
                            ->extraAlpineAttributes(['@input' => ' $el.value = $el.value.toUpperCase()']),

                        TextInput::make('contact_no')
                            ->label('PIC Contact No')
                            ->default($record->companyDetail->contact_no ?? $record->phone),

                        TextInput::make('email')
                            ->label('PIC Email Address')
                            ->default($record->companyDetail->email ?? $record->email),

                        Select::make('company_size')
                            ->label('Company Size')
                            ->options([
                                '1-24' => 'Small (1-24)',
                                '25-99' => 'Medium (25-99)',
                                '100-500' => 'Large (100-500)',
                                '501 and Above' => 'Enterprise (501+)',
                            ])
                            ->default($record->company_size),
                    ])
                    ->action(function (array $data, Lead $record) {
                        // Update the lead with the new values
                        $record->companyDetail()->updateOrCreate(
                            ['lead_id' => $record->id], // 🔍 Matching condition
                            [
                                'company_name' => $data['company_name'],
                                'name' => $data['name'],
                                'contact_no' => $data['contact_no'],
                                'email' => $data['email'],
                            ]
                        );

                        $record->update([
                            'company_size' => $data['company_size'],
                        ]);

                        Notification::make()
                            ->title('Lead Updated Successfully')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getDemoViewAction(): Action
    {
        return Action::make('view_lead')
            ->label('View Details')
            ->icon('heroicon-o-eye')
            ->color('primary')
            ->requiresConfirmation()
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalHeading('Lead Details')
            ->modalDescription('Here are the details for this lead.')
            ->modalContent(fn (Appointment $record) => view('filament.modals.lead-details', [
                'lead' => $record->lead,
                'pending_days' => $record->pending_days, // Pass pending_days to the view
            ]));
    }

    public static function getWhatsappAction(): Action
    {
        return Action::make('send_whatsapp')
            ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
            ->label('Send WhatsApp')
            ->color('success')
            ->url(fn (Appointment $record) => self::generateWhatsappUrl($record))
            ->openUrlInNewTab();
    }

    private static function generateWhatsappUrl(Appointment $record): string
    {
        $rawNumber = $record->lead->companyDetail->contact_no ?? $record->lead->phone ?? null;
        $contactNo = preg_replace('/[^0-9]/', '', $rawNumber); // remove +, -, spaces etc.

        if (!$contactNo) {
            return 'javascript:void(0);';
        }

        $formattedDate = Carbon::parse($record->date)->format('d F Y, l');
        $startTime = Carbon::parse($record->start_time)->format('h:i A');

        $authUser = Auth::user();
        $authUserName = $authUser->name ?? 'Your Name';

        // Check if appointment type is "WEBINAR DEMO"
        if ($record->type === 'WEBINAR DEMO') {
            $meetingLink = $authUser->msteam_link ?? 'https://teams.microsoft.com/'; // Use authenticated user's Teams link
        } else {
            $meetingLink = $record->location ?? 'https://teams.microsoft.com/'; // Default to appointment link
        }

        $message = "Hi " . ($record->lead->companyDetail->name ?? $record->lead->name ?? '') . ",\n\n";
        $message .= "My name is {$authUserName}. I’m from TimeTec Cloud Sdn Bhd, I hope you're doing well!\n";
        $message .= "Just a quick reminder about our upcoming online meeting scheduled.\n";
        $message .= "I’m looking forward to meet you.\n\n";
        $message .= "Here are the meeting details:\n\n";
        $message .= "*• Date & Time:* {$formattedDate} at {$startTime}\n";
        $message .= "*• Platform:* Microsoft Teams\n";
        $message .= "*• Link:* {$meetingLink}\n";
        $message .= "*• Timetec HR Brochure:*";
        $message .= "  https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf\n\n";
        $message .= "Please let me know if you need anything before the meeting or if there are any changes.\n";
        $message .= "Looking forward to connecting with you soon!\n\n";
        $message .= "Best regards,\n";
        $message .= "{$authUserName}";

        // Return formatted WhatsApp link
        return "https://wa.me/{$contactNo}?text=" . urlencode($message);
    }

    public static function getAssignToMeAction(): Action
    {
        return Action::make('updateLeadOwner')
            ->label(__('Assign to Me'))
            ->requiresConfirmation()
            ->modalDescription('')
            ->form(function (?Lead $record) {
                $companyName = optional($record?->companyDetail)->company_name;

                // Normalize company name
                $normalizedCompanyName = null;
                if ($companyName) {
                    $normalizedCompanyName = strtoupper($companyName);
                    $normalizedCompanyName = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/\s+/', ' ', $normalizedCompanyName);
                    $normalizedCompanyName = trim($normalizedCompanyName);
                }

                // ✅ Get all existing company names for fuzzy matching
                $allCompanyNames = Lead::query()
                    ->with('companyDetail')
                    ->whereHas('companyDetail')
                    ->where('id', '!=', optional($record)->id)
                    ->get()
                    ->pluck('companyDetail.company_name')
                    ->filter()
                    ->unique();

                $duplicateLeads = collect();
                $fuzzyMatches = [];

                // ✅ Check for fuzzy duplicates using Levenshtein distance
                if ($normalizedCompanyName) {
                    foreach ($allCompanyNames as $existingCompanyName) {
                        $normalizedExisting = strtoupper((string) $existingCompanyName);
                        $normalizedExisting = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedExisting);
                        $normalizedExisting = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedExisting);
                        $normalizedExisting = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedExisting);
                        $normalizedExisting = preg_replace('/\s+/', ' ', $normalizedExisting);
                        $normalizedExisting = trim($normalizedExisting);

                        // ✅ Calculate Levenshtein distance
                        $distance = levenshtein($normalizedCompanyName, $normalizedExisting);

                        // ✅ If distance < 3, consider it a potential duplicate
                        if ($distance > 0 && $distance < 3) {
                            $fuzzyMatches[] = $existingCompanyName;
                        }
                    }
                }

                $duplicateLeads = Lead::query()
                    ->with('companyDetail') // ✅ Eager load company details
                    ->where(function ($query) use ($record, $normalizedCompanyName) {
                        if ($normalizedCompanyName) {
                            $query->whereHas('companyDetail', function ($q) use ($normalizedCompanyName) {
                                $q->whereRaw("UPPER(TRIM(company_name)) LIKE ?", ['%' . $normalizedCompanyName . '%']);
                            });
                        }

                        if (!empty($record?->email)) {
                            $query->orWhere('email', $record->email)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('email', $record->email);
                                });
                        }

                        if (!empty($record?->companyDetail?->email)) {
                            $query->orWhere('email', $record->companyDetail->email)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('email', $record->companyDetail->email);
                                });
                        }

                        if (!empty($record?->phone)) {
                            $query->orWhere('phone', $record->phone)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('contact_no', $record->phone);
                                });
                        }

                        if (!empty($record?->companyDetail?->contact_no)) {
                            $query->orWhere('phone', $record->companyDetail->contact_no)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('contact_no', $record->companyDetail->contact_no);
                                });
                        }
                    })
                    ->where('id', '!=', optional($record)->id)
                    ->get();

                $isDuplicate = $duplicateLeads->isNotEmpty();

                // ✅ Format duplicate info with company name and lead ID
                $duplicateInfo = $duplicateLeads->map(function ($lead) {
                    $dupCompanyName = $lead->companyDetail->company_name ?? 'Unknown Company';
                    $leadId = str_pad($lead->id, 5, '0', STR_PAD_LEFT);
                    return "<strong>{$dupCompanyName}</strong> (LEAD ID {$leadId})";
                })->implode(", ");

                $content = $isDuplicate
                    ? "⚠️⚠️⚠️ <strong style='color: red;'>Warning: This lead is a duplicate!</strong><br><br>"
                    . "Matches found: " . $duplicateInfo . "<br><br>"
                    . "<strong style='color: red;'>Please contact Faiz before proceeding. Assignment is blocked.</strong>"
                    : "Do you want to assign this lead to yourself? Make sure to confirm assignment before contacting the lead to avoid duplicate efforts by other team members.";

                return [
                    Placeholder::make('warning')
                        ->content(Str::of($content)->toHtmlString())
                        ->hiddenLabel()
                        ->extraAttributes([
                            'style' => $isDuplicate ? 'color: red; font-weight: bold;' : '',
                        ]),
                ];
            })
            ->color('success')
            ->icon('heroicon-o-pencil-square')
            ->visible(function (?Lead $record) {
                if (!$record) return false;

                // Check basic conditions first
                if (!is_null($record->lead_owner) || auth()->user()->role_id === 2) {
                    return false;
                }

                // ✅ NEW RULE: Check if there's already a pending bypass duplicate request
                $hasPendingBypassRequest = Request::where('lead_id', $record->id)
                    ->where('request_type', 'bypass_duplicate')
                    ->where('status', 'pending')
                    ->exists();

                if ($hasPendingBypassRequest) {
                    return false; // Hide the action if there's already a pending request
                }

                return true;
            })
            ->modalSubmitAction(function ($action, ?Lead $record) {
                if (!$record) return $action;

                if ($record->lead_code === 'Apollo') {
                    return $action;
                }

                $companyName = optional($record?->companyDetail)->company_name;
                $normalizedCompanyName = null;

                if ($companyName) {
                    $normalizedCompanyName = strtoupper($companyName);
                    $normalizedCompanyName = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/\s+/', ' ', $normalizedCompanyName);
                    $normalizedCompanyName = trim($normalizedCompanyName);
                }

                $allCompanyNames = Lead::query()
                    ->with('companyDetail')
                    ->whereHas('companyDetail')
                    ->where('id', '!=', $record->id)
                    ->get()
                    ->pluck('companyDetail.company_name')
                    ->filter()
                    ->unique();

                $fuzzyMatches = [];
                if ($normalizedCompanyName) {
                    foreach ($allCompanyNames as $existingCompanyName) {
                        $normalizedExisting = strtoupper((string) $existingCompanyName);
                        $normalizedExisting = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedExisting);
                        $normalizedExisting = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedExisting);
                        $normalizedExisting = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedExisting);
                        $normalizedExisting = preg_replace('/\s+/', ' ', $normalizedExisting);
                        $normalizedExisting = trim($normalizedExisting);

                        $distance = levenshtein($normalizedCompanyName, $normalizedExisting);
                        if ($distance > 0 && $distance < 3) {
                            $fuzzyMatches[] = $existingCompanyName;
                        }
                    }
                }

                $hasDuplicates = Lead::query()
                    ->where(function ($query) use ($record, $normalizedCompanyName) {
                        if ($normalizedCompanyName) {
                            $query->whereHas('companyDetail', function ($q) use ($normalizedCompanyName) {
                                $q->whereRaw("UPPER(TRIM(company_name)) LIKE ?", ['%' . $normalizedCompanyName . '%']);
                            });
                        }

                        if (!empty($record?->email)) {
                            $query->orWhere('email', $record->email)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('email', $record->email);
                                });
                        }

                        if (!empty($record?->companyDetail?->email)) {
                            $query->orWhere('email', $record->companyDetail->email)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('email', $record->companyDetail->email);
                                });
                        }

                        if (!empty($record?->phone)) {
                            $query->orWhere('phone', $record->phone)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('contact_no', $record->phone);
                                });
                        }

                        if (!empty($record?->companyDetail?->contact_no)) {
                            $query->orWhere('phone', $record->companyDetail->contact_no)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('contact_no', $record->companyDetail->contact_no);
                                });
                        }
                    })
                    ->where('id', '!=', optional($record)->id)
                    ->exists();

                // ✅ Hide button if duplicates found
                return $hasDuplicates ? $action->hidden() : $action;
            })
            ->extraModalFooterActions([
                Action::make('bypass_duplicate')
                    ->label('Request Bypass Duplicate')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Request Bypass Duplicate Checking')
                    ->modalDescription('Submit a request to bypass duplicate checking for this lead. Admin approval is required.')
                    ->visible(function (?Lead $record) {
                        if (!$record) return false;

                        // Check for duplicates (same logic as above)
                        $companyName = optional($record?->companyDetail)->company_name;
                        $normalizedCompanyName = null;

                        if ($companyName) {
                            $normalizedCompanyName = strtoupper($companyName);
                            $normalizedCompanyName = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedCompanyName);
                            $normalizedCompanyName = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedCompanyName);
                            $normalizedCompanyName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedCompanyName);
                            $normalizedCompanyName = preg_replace('/\s+/', ' ', $normalizedCompanyName);
                            $normalizedCompanyName = trim($normalizedCompanyName);
                        }

                        return Lead::query()
                            ->where(function ($query) use ($record, $normalizedCompanyName) {
                                if ($normalizedCompanyName) {
                                    $query->whereHas('companyDetail', function ($q) use ($normalizedCompanyName) {
                                        $q->whereRaw("UPPER(TRIM(company_name)) LIKE ?", ['%' . $normalizedCompanyName . '%']);
                                    });
                                }

                                if (!empty($record?->email)) {
                                    $query->orWhere('email', $record->email)
                                        ->orWhereHas('companyDetail', function ($q) use ($record) {
                                            $q->where('email', $record->email);
                                        });
                                }

                                if (!empty($record?->companyDetail?->email)) {
                                    $query->orWhere('email', $record->companyDetail->email)
                                        ->orWhereHas('companyDetail', function ($q) use ($record) {
                                            $q->where('email', $record->companyDetail->email);
                                        });
                                }

                                if (!empty($record?->phone)) {
                                    $query->orWhere('phone', $record->phone)
                                        ->orWhereHas('companyDetail', function ($q) use ($record) {
                                            $q->where('contact_no', $record->phone);
                                        });
                                }

                                if (!empty($record?->companyDetail?->contact_no)) {
                                    $query->orWhere('phone', $record->companyDetail->contact_no)
                                        ->orWhereHas('companyDetail', function ($q) use ($record) {
                                            $q->where('contact_no', $record->companyDetail->contact_no);
                                        });
                                }
                            })
                            ->where('id', '!=', optional($record)->id)
                            ->exists(); // Show button only if duplicates exist
                    })
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason for Bypass Request')
                            ->placeholder('Explain why this lead should bypass duplicate checking...')
                            ->required()
                            ->rows(4)
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->maxLength(500),
                    ])
                    ->action(function (Lead $record, array $data) {
                        // Use the same bypass duplicate logic from getBypassDuplicateAction()
                        $user = auth()->user();

                        // Find and store duplicate information
                        $companyName = optional($record?->companyDetail)->company_name;
                        $duplicateInfo = [];

                        // Normalize company name for duplicate checking
                        $normalizedCompanyName = null;
                        if ($companyName) {
                            $normalizedCompanyName = strtoupper($companyName);
                            $normalizedCompanyName = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedCompanyName);
                            $normalizedCompanyName = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedCompanyName);
                            $normalizedCompanyName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedCompanyName);
                            $normalizedCompanyName = preg_replace('/\s+/', ' ', $normalizedCompanyName);
                            $normalizedCompanyName = trim($normalizedCompanyName);
                        }

                        // Get all company names for fuzzy matching
                        $allCompanyNames = Lead::query()
                            ->with('companyDetail')
                            ->whereHas('companyDetail')
                            ->get()
                            ->pluck('companyDetail.company_name', 'id')
                            ->filter();

                        $fuzzyMatches = [];
                        if ($normalizedCompanyName) {
                            foreach ($allCompanyNames as $leadId => $existingCompanyName) {
                                if ($leadId == $record->id) continue;

                                $normalizedExisting = strtoupper($existingCompanyName);
                                $normalizedExisting = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedExisting);
                                $normalizedExisting = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedExisting);
                                $normalizedExisting = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedExisting);
                                $normalizedExisting = preg_replace('/\s+/', ' ', $normalizedExisting);
                                $normalizedExisting = trim($normalizedExisting);

                                $distance = levenshtein($normalizedCompanyName, $normalizedExisting);
                                if ($distance > 0 && $distance < 3) {
                                    $fuzzyMatches[] = $existingCompanyName;
                                }
                            }
                        }

                        // Find duplicate leads
                        $duplicateLeads = Lead::query()
                            ->with('companyDetail')
                            ->where(function ($query) use ($record, $normalizedCompanyName, $fuzzyMatches) {
                                if ($normalizedCompanyName) {
                                    $query->whereHas('companyDetail', function ($q) use ($normalizedCompanyName) {
                                        $q->whereRaw("UPPER(TRIM(company_name)) LIKE ?", ['%' . $normalizedCompanyName . '%']);
                                    });
                                }

                                if (!empty($fuzzyMatches)) {
                                    $query->orWhereHas('companyDetail', function ($q) use ($fuzzyMatches) {
                                        $q->whereIn('company_name', $fuzzyMatches);
                                    });
                                }

                                if (!empty($record?->email)) {
                                    $query->orWhere('email', $record->email)
                                        ->orWhereHas('companyDetail', function ($q) use ($record) {
                                            $q->where('email', $record->email);
                                        });
                                }

                                if (!empty($record?->companyDetail?->email)) {
                                    $query->orWhere('email', $record->companyDetail->email)
                                        ->orWhereHas('companyDetail', function ($q) use ($record) {
                                            $q->where('email', $record->companyDetail->email);
                                        });
                                }

                                if (!empty($record?->phone)) {
                                    $query->orWhere('phone', $record->phone)
                                        ->orWhereHas('companyDetail', function ($q) use ($record) {
                                            $q->where('contact_no', $record->phone);
                                        });
                                }

                                if (!empty($record?->companyDetail?->contact_no)) {
                                    $query->orWhere('phone', $record->companyDetail->contact_no)
                                        ->orWhereHas('companyDetail', function ($q) use ($record) {
                                            $q->where('contact_no', $record->companyDetail->contact_no);
                                        });
                                }
                            })
                            ->where('id', '!=', optional($record)->id)
                            ->get();

                        // Build duplicate info array
                        if ($duplicateLeads->isNotEmpty()) {
                            foreach ($duplicateLeads as $duplicateLead) {
                                $duplicateInfo[] = [
                                    'lead_id' => $duplicateLead->id,
                                    'company_name' => $duplicateLead->companyDetail->company_name ?? 'Unknown Company',
                                    'lead_code' => $duplicateLead->lead_code,
                                    'email' => $duplicateLead->email,
                                    'phone' => $duplicateLead->phone,
                                    'lead_owner' => $duplicateLead->lead_owner,
                                    'categories' => $duplicateLead->categories,
                                    'created_at' => $duplicateLead->created_at->format('Y-m-d H:i:s'),
                                    'match_type' => self::getDuplicateMatchType($record, $duplicateLead, $normalizedCompanyName, $fuzzyMatches),
                                ];
                            }
                        }

                        // Create bypass request with duplicate info
                        Request::create([
                            'lead_id' => $record->id,
                            'requested_by' => $user->id,
                            'current_owner_id' => null,
                            'requested_owner_id' => $user->id,
                            'reason' => $data['reason'],
                            'status' => 'pending',
                            'request_type' => 'bypass_duplicate',
                            'duplicate_info' => json_encode([
                                'current_lead' => [
                                    'lead_id' => $record->id,
                                    'company_name' => $companyName,
                                    'email' => $record->email,
                                    'phone' => $record->phone,
                                    'lead_code' => $record->lead_code,
                                ],
                                'duplicates_found' => $duplicateInfo,
                                'total_duplicates' => count($duplicateInfo),
                                'checked_at' => now()->format('Y-m-d H:i:s'),
                            ]),
                        ]);

                        // Log activity
                        activity()
                            ->causedBy($user)
                            ->performedOn($record)
                            ->withProperties([
                                'reason' => $data['reason'],
                                'request_type' => 'bypass_duplicate',
                                'duplicates_count' => count($duplicateInfo),
                            ])
                            ->log('Requested bypass duplicate checking');

                        Notification::make()
                            ->title('Bypass Request Submitted')
                            ->body('Your request has been submitted and is pending admin approval.')
                            ->success()
                            ->send();
                    })
                    ->cancelParentActions(),
            ])
            ->action(function (Lead $record) {
                // ✅ Re-check for duplicates before assignment
                $companyName = optional($record?->companyDetail)->company_name;

                // Normalize company name
                $normalizedCompanyName = null;
                if ($companyName) {
                    $normalizedCompanyName = strtoupper($companyName);
                    $normalizedCompanyName = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/\s+/', ' ', $normalizedCompanyName);
                    $normalizedCompanyName = trim($normalizedCompanyName);
                }

                $hasDuplicates = Lead::query()
                    ->where(function ($query) use ($record, $normalizedCompanyName) {
                        if ($normalizedCompanyName) {
                            $query->whereHas('companyDetail', function ($q) use ($normalizedCompanyName) {
                                $q->whereRaw("UPPER(TRIM(company_name)) LIKE ?", ['%' . $normalizedCompanyName . '%']);
                            });
                        }

                        if (!empty($record?->email)) {
                            $query->orWhere('email', $record->email)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('email', $record->email);
                                });
                        }

                        if (!empty($record?->companyDetail?->email)) {
                            $query->orWhere('email', $record->companyDetail->email)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('email', $record->companyDetail->email);
                                });
                        }

                        if (!empty($record?->phone)) {
                            $query->orWhere('phone', $record->phone)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('contact_no', $record->phone);
                                });
                        }

                        if (!empty($record?->companyDetail?->contact_no)) {
                            $query->orWhere('phone', $record->companyDetail->contact_no)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('contact_no', $record->companyDetail->contact_no);
                                });
                        }
                    })
                    ->where('id', '!=', optional($record)->id)
                    ->exists();

                if ($hasDuplicates) {
                    Notification::make()
                        ->title('Assignment Blocked')
                        ->body('Duplicate leads detected. Please contact Faiz before proceeding.')
                        ->danger()
                        ->send();
                    return;
                }

                // Update the lead owner and related fields
                $record->update([
                    'lead_owner' => auth()->user()->name,
                    'categories' => 'Active',
                    'stage' => 'Transfer',
                    'lead_status' => 'New',
                    'pickup_date' => now()
                ]);

                // Update the latest activity log
                $latestActivityLog = ActivityLog::where('subject_id', $record->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . auth()->user()->name) {
                    $latestActivityLog->update([
                        'description' => 'Lead assigned to Lead Owner: ' . auth()->user()->name,
                    ]);

                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($record);
                }

                try {
                    Notification::make()
                        ->title('Lead Owner Assigned Successfully')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Notification error: ' . $e->getMessage());
                }
            });
    }

    public static function getAssignLeadAction(): Action
    {
        return Action::make('assignLead')
            ->label(__('Assign Lead To Lead Owner'))
            ->modalHeading('Confirm Lead Assignment')
            ->modalDescription('Select a lead owner to handle this lead.')
            ->form(function (Lead $record) {
                return [
                    Select::make('selected_user')
                        ->label('Assign To')
                        ->options(User::where('role_id', 1)->pluck('name', 'id'))
                        ->required(),
                    Placeholder::make('warning')
                        ->content('Make sure to confirm assignment before proceeding.'),
                ];
            })
            ->color('warning')
            ->icon('heroicon-o-receipt-refund')
            ->visible(fn () => auth()->user()->role_id == 3) // Only for users with role_id = 3
            ->action(function (Lead $record, array $data) {
                $selectedUser = User::find($data['selected_user']);

                if (!$selectedUser) {
                    Notification::make()
                        ->title('User Not Found')
                        ->danger()
                        ->send();
                    return;
                }

                // Update the lead owner
                $record->update([
                    'lead_owner' => $selectedUser->name,
                    'categories' => 'Active',
                    'stage' => 'Transfer',
                    'lead_status' => 'New',
                    'pickup_date' => now(),
                ]);

                // Log the activity
                ActivityLog::create([
                    'description' => 'Lead assigned to Lead Owner: ' . $selectedUser->name,
                    'subject_id' => $record->id,
                    'causer_id' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Lead successfully assigned to ' . $selectedUser->name)
                    ->success()
                    ->send();
            });
    }

    public static function getAddDemoAction(): Action
    {
        return
            Action::make('add_demo')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->label('Add Demo')
                ->modalSubmitAction(false)  // Hide default submit action
                ->modalCancelAction(false)  // Hide default cancel action
                ->modalHeading('Add Demo Confirmation')
                ->modalContent(fn (Lead $record) => view('verification-notification', ['record' => $record]))
                ->extraModalFooterActions([
                    Action::make('add_demo')
                        ->icon('heroicon-o-calendar-days')
                        ->color('success')
                        ->label('Add Demo')
                        ->modalHeading('Add Demo')
                        ->hidden(fn (Lead $record) => is_null($record->lead_owner)) // Use $record instead of getOwnerRecord()
                        ->form(fn (?Lead $record) => $record ? [ // Ensure record exists before running form logic
                            // Schedule
                            ToggleButtons::make('mode')
                                ->label('')
                                ->options([
                                    'auto' => 'Auto',
                                    'custom' => 'Custom',
                                ]) // Define custom options
                                ->reactive()
                                ->inline()
                                ->grouped()
                                ->default('auto')
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    if ($state === 'custom') {
                                        $set('date', null);
                                        $set('start_time', null);
                                        $set('end_time', null);
                                    }else{
                                        $set('date', Carbon::today()->toDateString());
                                        $set('start_time', Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30))->format('H:i'));
                                        $set('end_time', Carbon::parse($get('start_time'))->addHour()->format('H:i'));
                                    }
                                }),

                            Grid::make(3) // 3 columns for Date, Start Time, End Time
                            ->schema([
                                DatePicker::make('date')
                                    ->required()
                                    ->label('DATE')
                                    ->default(Carbon::today()->toDateString()),

                                TimePicker::make('start_time')
                                    ->label('START TIME')
                                    ->required()
                                    ->seconds(false)
                                    ->reactive()
                                    ->default(function () {
                                        // Get current time
                                        $now = Carbon::now();

                                        // Define business hours
                                        $businessStart = Carbon::today()->setHour(9)->setMinute(0)->setSecond(0);
                                        $businessEnd = Carbon::today()->setHour(18)->setMinute(0)->setSecond(0);

                                        // If before business hours, return 9am
                                        if ($now->lt($businessStart)) {
                                            return '08:00';
                                        }

                                        // If after business hours, return 8am
                                        if ($now->gt($businessEnd)) {
                                            return '08:00';
                                        }

                                        // Otherwise round to next 30 min interval within business hours
                                        $rounded = $now->copy()->addMinutes(30 - ($now->minute % 30))->setSecond(0);

                                        // If rounded time is after business hours, return 8am next day
                                        if ($rounded->gt($businessEnd)) {
                                            return '08:00';
                                        }

                                        return $rounded->format('H:i');
                                    })
                                    ->datalist(function (callable $get) {
                                        $user = Auth::user();
                                        $date = $get('date');

                                        if ($get('mode') === 'custom') {
                                            return [];
                                        }

                                        // Get current time for reference
                                        $currentTime = Carbon::now();
                                        $currentTimeString = $currentTime->format('H:i');

                                        // Generate all possible time slots in business hours (9am-6pm)
                                        $allTimes = [];

                                        if ($user && $user->role_id == 2 && $date) {
                                            // Fetch all booked appointments
                                            $appointments = Appointment::where('salesperson', $user->id)
                                                ->whereDate('date', $date)
                                                ->whereIn('status', ['New', 'Done'])
                                                ->get(['start_time', 'end_time']);

                                            // Generate all possible time slots
                                            $startTime = Carbon::createFromTime(9, 0, 0);
                                            $endTime = Carbon::createFromTime(18, 0, 0);

                                            // Generate time slots from 9am to 6pm
                                            while ($startTime < $endTime) {
                                                $slotStart = $startTime->copy();
                                                $slotEnd = $startTime->copy()->addMinutes(30);
                                                $formattedTime = $slotStart->format('H:i');

                                                // Check if slot is already booked
                                                $isBooked = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                                                    $apptStart = Carbon::createFromFormat('H:i:s', $appointment->start_time);
                                                    $apptEnd = Carbon::createFromFormat('H:i:s', $appointment->end_time);

                                                    return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
                                                });

                                                if (!$isBooked) {
                                                    $allTimes[] = $formattedTime;
                                                }

                                                $startTime->addMinutes(30);
                                            }
                                        } else {
                                            // Generate all possible time slots without checking for booked slots
                                            $startTime = Carbon::createFromTime(8, 0, 0);
                                            $endTime = Carbon::createFromTime(18, 0, 0);

                                            while ($startTime < $endTime) {
                                                $allTimes[] = $startTime->format('H:i');
                                                $startTime->addMinutes(30);
                                            }
                                        }

                                        // Sort times based on proximity to current time in a circular manner
                                        usort($allTimes, function($a, $b) use ($currentTimeString) {
                                            $aTime = Carbon::createFromFormat('H:i', $a);
                                            $bTime = Carbon::createFromFormat('H:i', $b);
                                            $currentTime = Carbon::createFromFormat('H:i', $currentTimeString);

                                            // If current time is after business hours, consider 9am as the reference
                                            if ($currentTime->format('H') >= 18) {
                                                return $aTime <=> $bTime; // Just sort by normal time order starting from 9am
                                            }

                                            // For times after current time, they come first and are sorted by proximity to current
                                            if ($aTime >= $currentTime && $bTime >= $currentTime) {
                                                return $aTime <=> $bTime;
                                            }

                                            // For times before current time, they come after times that are after current
                                            if ($aTime < $currentTime && $bTime < $currentTime) {
                                                return $aTime <=> $bTime;
                                            }

                                            // If one is after and one is before current time, the after one comes first
                                            return $bTime >= $currentTime ? 1 : -1;
                                        });

                                        return $allTimes;
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($get('mode') === 'auto' && $state) {
                                            $endTime = Carbon::parse($state)->addHour();

                                            // Cap end time at 6pm
                                            $maxEndTime = Carbon::createFromTime(18, 0, 0);
                                            if ($endTime->gt($maxEndTime)) {
                                                $endTime = $maxEndTime;
                                            }

                                            $set('end_time', $endTime->format('H:i'));
                                        }
                                    }),

                                TimePicker::make('end_time')
                                    ->label('END TIME')
                                    ->required()
                                    ->seconds(false)
                                    ->reactive()
                                    ->default(function (callable $get) {
                                        $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));
                                        return $startTime->addHour()->format('H:i');
                                    })
                                    ->datalist(function (callable $get) {
                                        $user = Auth::user();
                                        $date = $get('date');

                                        if ($get('mode') === 'custom') {
                                            return []; // Custom mode: empty list
                                        }

                                        $times = [];
                                        $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));

                                        if ($user && $user->role_id == 2 && $date) {
                                            // Fetch booked time slots for this salesperson on the selected date
                                            $bookedAppointments = Appointment::where('salesperson', $user->id)
                                                ->whereDate('date', $date)
                                                ->pluck('end_time', 'start_time') // Start as key, End as value
                                                ->toArray();

                                            for ($i = 0; $i < 48; $i++) {
                                                $formattedTime = $startTime->format('H:i');

                                                // Check if time is booked
                                                $isBooked = collect($bookedAppointments)->contains(function ($end, $start) use ($formattedTime) {
                                                    return $formattedTime >= $start && $formattedTime <= $end;
                                                });

                                                if (!$isBooked) {
                                                    $times[] = $formattedTime;
                                                }

                                                $startTime->addMinutes(30);
                                            }
                                        } else {
                                            // Default available slots
                                            for ($i = 0; $i < 48; $i++) {
                                                $times[] = $startTime->format('H:i');
                                                $startTime->addMinutes(30);
                                            }
                                        }

                                        return $times;
                                    }),
                            ]),

                            Grid::make(3) // 3 columns for 3 Select fields
                            ->schema([
                                Select::make('type')
                                ->options(function () use ($record) {
                                    // Check if the lead has an appointment with 'new' or 'done' status
                                        $leadHasNewAppointment = Appointment::where('lead_id', $record->id)
                                            ->whereIn('status', ['New', 'Done'])
                                            ->exists();

                                        // Dynamically set options
                                        $options = [
                                            'NEW DEMO' => 'NEW DEMO',
                                            'WEBINAR DEMO' => 'WEBINAR DEMO',
                                        ];

                                        if ($leadHasNewAppointment) {
                                            $options = [
                                                'HRMS DEMO' => 'HRMS DEMO',
                                                'HRDF DISCUSSION' => 'HRDF DISCUSSION',
                                                'SYSTEM DISCUSSION' => 'SYSTEM DISCUSSION',
                                            ];
                                        }

                                        return $options;
                                    })
                                    ->default('NEW DEMO')
                                    ->required()
                                    ->label('DEMO TYPE')
                                    ->reactive(),

                                Select::make('appointment_type')
                                    ->options([
                                        'ONLINE' => 'ONLINE',
                                        'ONSITE' => 'ONSITE',
                                        'INHOUSE' => 'INHOUSE'
                                    ])
                                    ->required()
                                    ->default('ONLINE')
                                    ->label('APPOINTMENT TYPE'),

                                Select::make('salesperson')
                                    ->label('SALESPERSON')
                                    ->options(function () {
                                        // if ($lead->salesperson) {
                                        //     $salesperson = User::where('id', $lead->salesperson)->first();
                                        //     return [
                                        //         $lead->salesperson => $salesperson->name,
                                        //     ];
                                        // }

                                        if (auth()->user()->role_id == 3) {
                                            return \App\Models\User::query()
                                                ->where('role_id', 2)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        } else {
                                            return \App\Models\User::query()
                                                ->where('role_id', 2)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        }
                                    })
                                    ->disableOptionWhen(function ($value, $get) {
                                        $date = $get('date');
                                        $startTime = $get('start_time');
                                        $endTime = $get('end_time');
                                        $demo_type = $get('type');

                                        // If the demo type is 'WEBINAR DEMO', do not disable any options
                                        if ($demo_type === 'WEBINAR DEMO') {
                                            return false; // Allow selection without restrictions
                                        }

                                        $parsedDate = Carbon::parse($date)->format('Y-m-d'); // Ensure it's properly formatted
                                        $parsedStartTime = Carbon::parse($startTime)->format('H:i:s'); // Ensure proper time format
                                        $parsedEndTime = Carbon::parse($endTime)->format('H:i:s');

                                        $hasOverlap = Appointment::where('salesperson', $value)
                                            ->where('status', 'New')
                                            ->whereDate('date', $parsedDate) // Ensure date is formatted correctly
                                            ->where(function ($query) use ($parsedStartTime, $parsedEndTime) {
                                                $query->whereBetween('start_time', [$parsedStartTime, $parsedEndTime])
                                                    ->orWhereBetween('end_time', [$parsedStartTime, $parsedEndTime])
                                                    ->orWhere(function ($query) use ($parsedStartTime, $parsedEndTime) {
                                                        $query->where('start_time', '<', $parsedStartTime)
                                                                ->where('end_time', '>', $parsedEndTime);
                                                    });
                                            })
                                            ->exists();

                                            if ($hasOverlap) {
                                                return true;
                                            }
                                    })
                                    ->required()
                                    ->hidden(fn () => auth()->user()->role_id === 2)
                                    ->placeholder('Select a salesperson'),
                                ]),

                            Toggle::make('skip_notifications')
                                ->label('Skip Email & Teams Meeting')
                                ->helperText('Check this to create appointment without sending emails or creating a Teams meeting')
                                ->default(false)
                                ->inline(false),

                            Textarea::make('remarks')
                                ->label('REMARKS')
                                ->rows(3)
                                ->autosize()
                                ->reactive()
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                            TextInput::make('required_attendees')
                                ->label('Required Attendees')
                                ->helperText('Separate each email and name pair with a semicolon (e.g., email1;email2;email3).'),
                                // ->rules([
                                //     'regex:/^([^;]+;[^;]+;)*([^;]+;[^;]+)$/', // Validates the email-name pairs separated by semicolons
                                // ]),
                        ] : []) // Return empty form if no record is found
                        ->action(function (array $data, Lead $lead) {
                            // Create a new Appointment and store the form data in the appointments table
                            $appointment = new \App\Models\Appointment();
                            $appointment->fill([
                                'lead_id' => $lead->id,
                                'type' => $data['type'],
                                'appointment_type' => $data['appointment_type'],
                                'date' => $data['date'],
                                'start_time' => $data['start_time'],
                                'end_time' => $data['end_time'],
                                'salesperson' => $data['salesperson'] ?? auth()->user()->id,
                                'causer_id' => auth()->user()->id,
                                'remarks' => $data['remarks'],
                                'title' => $data['type']. ' | '. $data['appointment_type']. ' | TIMETEC HR | ' . $lead->companyDetail->company_name,
                                'required_attendees' => json_encode($data['required_attendees']), // Serialize to JSON
                                'salesperson_assigned_date' => now(),

                            ]);
                            $appointment->save();

                            // try {
                            //     $metaService = new \App\Services\MetaConversionsApiService();

                            //     // Get social_lead_id from utm_details
                            //     $socialLeadId = $lead->utmDetail->social_lead_id ?? null;

                            //     if ($socialLeadId) {
                            //         $leadData = [
                            //             'id' => $lead->id,
                            //             'email' => $lead->companyDetail->email ?? $lead->email,
                            //             'phone_number' => $lead->companyDetail->contact_no ?? $lead->phone,
                            //             'first_name' => $lead->companyDetail->name ?? $lead->name ?? null,
                            //             'last_name' => null, // If you have last name field
                            //             'city' => $lead->city ?? null,
                            //             'state' => $lead->state ?? null,
                            //             'zip' => $lead->zip ?? null,
                            //             'country' => $lead->country ?? null,
                            //             'social_lead_id' => $socialLeadId, // ✅ Meta's lead_id from utm_details
                            //             'fbclid' => $lead->utmDetail->fbclid ?? null,
                            //         ];

                            //         $result = $metaService->sendLeadEvent($leadData);

                            //         if ($result['success']) {
                            //             Log::info('Meta Conversions API: Demo-Assigned event sent successfully', [
                            //                 'lead_id' => $lead->id,
                            //                 'social_lead_id' => $socialLeadId,
                            //                 'demo_type' => $data['type'],
                            //                 'appointment_type' => $data['appointment_type'],
                            //             ]);
                            //         } else {
                            //             Log::warning('Meta Conversions API: Failed to send Demo-Assigned event', [
                            //                 'lead_id' => $lead->id,
                            //                 'social_lead_id' => $socialLeadId,
                            //                 'error' => $result['error'] ?? 'Unknown error',
                            //             ]);
                            //         }
                            //     } else {
                            //         Log::info('Meta Conversions API: No social_lead_id found, skipping event', [
                            //             'lead_id' => $lead->id,
                            //         ]);
                            //     }
                            // } catch (\Exception $e) {
                            //     Log::error('Meta Conversions API: Exception during Demo-Assigned event', [
                            //         'lead_id' => $lead->id,
                            //         'error' => $e->getMessage(),
                            //         'trace' => $e->getTraceAsString(),
                            //     ]);
                            // }

                            if (!($data['skip_notifications'] ?? false)) {
                                // Retrieve the related Lead model from ActivityLog
                                $accessToken = MicrosoftGraphService::getAccessToken(); // Implement your token generation method

                                $graph = new Graph();
                                $graph->setAccessToken($accessToken);

                                // $startTime = $data['date'] . 'T' . $data['start_time'] . 'Z'; // Format as ISO 8601
                                $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
                                // $endTime = $data['date'] . 'T' . $data['end_time'] . 'Z';
                                $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

                                // Retrieve the organizer's email dynamically
                                $salespersonId = $appointment->salesperson; // Assuming `salesperson` holds the user ID
                                $salesperson = User::find($salespersonId); // Find the user in the User table

                                if (!$salesperson || !$salesperson->email) {
                                    Notification::make()
                                        ->title('Salesperson Not Found')
                                        ->danger()
                                        ->body('The salesperson assigned to this appointment could not be found or does not have an email address.')
                                        ->send();
                                    return; // Exit if no valid email is found
                                }

                                $organizerEmail = $salesperson->email;

                                if ($appointment->type !== 'WEBINAR DEMO') {
                                    $meetingPayload = [
                                        'start' => [
                                            'dateTime' => $startTime,
                                            'timeZone' => 'Asia/Kuala_Lumpur'
                                        ],
                                        'end' => [
                                            'dateTime' => $endTime,
                                            'timeZone' => 'Asia/Kuala_Lumpur'
                                        ],
                                        'subject' => 'TIMETEC HRMS | ' . $lead->companyDetail->company_name,
                                        'isOnlineMeeting' => true,
                                        'onlineMeetingProvider' => 'teamsForBusiness',

                                        // ✅ Add attendees only if it's NOT a WEBINAR DEMO
                                        'attendees' => [
                                            [
                                                'emailAddress' => [
                                                    'address' => $lead->email, // Lead's email as required attendee
                                                    'name' => $lead->name ?? 'Lead Attendee' // Fallback in case name is null
                                                ],
                                                'type' => 'required' // Required attendee
                                            ]
                                        ]
                                    ];
                                } else {
                                    $meetingPayload = [
                                        'start' => [
                                            'dateTime' => $startTime,
                                            'timeZone' => 'Asia/Kuala_Lumpur'
                                        ],
                                        'end' => [
                                            'dateTime' => $endTime,
                                            'timeZone' => 'Asia/Kuala_Lumpur'
                                        ],
                                        'subject' => 'TIMETEC HRMS | ' . $lead->companyDetail->company_name,
                                        'isOnlineMeeting' => true,
                                        'onlineMeetingProvider' => 'teamsForBusiness',
                                    ];
                                }

                                try {
                                    // Use the correct endpoint for app-only authentication
                                    $onlineMeeting = $graph->createRequest("POST", "/users/$organizerEmail/events")
                                        ->attachBody($meetingPayload)
                                        ->setReturnType(Event::class)
                                        ->execute();

                                    $appointment->update([
                                        'location' => $onlineMeeting->getOnlineMeeting()->getJoinUrl(), // Update location with meeting join URL
                                        'event_id' => $onlineMeeting->getId(),
                                    ]);

                                    Notification::make()
                                        ->title('Teams Meeting Created Successfully')
                                        ->success()
                                        ->body('The meeting has been scheduled successfully.')
                                        ->send();
                                } catch (\Exception $e) {
                                    Log::error('Failed to create Teams meeting: ' . $e->getMessage(), [
                                        'request' => $meetingPayload, // Log the request payload for debugging
                                        'user' => $organizerEmail, // Log the user's email or ID
                                    ]);

                                    Notification::make()
                                        ->title('Failed to Create Teams Meeting')
                                        ->danger()
                                        ->body('Error: ' . $e->getMessage())
                                        ->send();
                                }

                                $salespersonUser = \App\Models\User::find($data['salesperson'] ?? auth()->user()->id);
                                $demoAppointment = $lead->demoAppointment()->latest('created_at')->first();
                                $startTime = Carbon::parse($demoAppointment->start_time);
                                $endTime = Carbon::parse($demoAppointment->end_time); // Assuming you have an end_time field
                                $formattedDate = Carbon::parse($demoAppointment->date)->format('d/m/Y');
                                $contactNo = optional($lead->companyDetail)->contact_no ?? $lead->phone;
                                $picName = optional($lead->companyDetail)->name ?? $lead->name;
                                $email = optional($lead->companyDetail)->email ?? $lead->email;

                                if ($salespersonUser && filter_var($salespersonUser->email, FILTER_VALIDATE_EMAIL)) {
                                    try {
                                        $viewName = 'emails.demo_notification';
                                        $leadowner = User::where('name', $lead->lead_owner)->first();

                                        $emailContent = [
                                            'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                                            'leadOwnerEMail' => $leadowmer->email ?? 'Unknown Email', // Lead Owner/Manager Name
                                            'lead' => [
                                                'lastName' => $lead->companyDetail->name ?? $lead->name, // Lead's Last Name
                                                'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                                                'salespersonName' => $salespersonUser->name ?? 'N/A',
                                                'salespersonPhone' => $salespersonUser->mobile_number ?? 'N/A',
                                                'salespersonEmail' => $salespersonUser->email ?? 'N/A',
                                                'salespersonMeetingLink' => $salespersonUser->msteam_link ?? 'N/A',
                                                'phone' =>$contactNo ?? 'N/A',
                                                'pic' => $picName ?? 'N/A',
                                                'email' => $email ?? 'N/A',
                                                'date' => $formattedDate ?? 'N/A',
                                                'startTime' => $startTime ?? 'N/A',
                                                'endTime' => $endTime ?? 'N/A',
                                                'meetingLink' => $onlineMeeting->getOnlineMeeting()->getJoinUrl() ?? 'N/A',
                                                'position' => $salespersonUser->position ?? 'N/A', // position
                                                'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                                'demo_type' => $appointment->type,
                                                'appointment_type' => $appointment->appointment_type
                                            ],
                                        ];

                                        $demoAppointment = $lead->demoAppointment()->latest()->first(); // Adjust based on your relationship type

                                        $requiredAttendees = $demoAppointment->required_attendees ?? null;

                                        // Parse attendees' emails if not null
                                        $attendeeEmails = [];
                                        if (!empty($requiredAttendees)) {
                                            $cleanedAttendees = str_replace('"', '', $requiredAttendees);
                                            $attendeeEmails = array_filter(array_map('trim', explode(';', $cleanedAttendees))); // Ensure no empty spaces
                                        }

                                        // Get Lead's Email (Primary recipient)
                                        $leadEmail = $lead->companyDetail->email ?? $lead->email;

                                        // Get Salesperson Email
                                        $salespersonId = $lead->salesperson;
                                        $salesperson = User::find($salespersonId);
                                        $salespersonEmail = $salespersonUser->email ?? null; // Prevent null errors

                                        // Get Lead Owner Email
                                        $leadownerName = $lead->lead_owner;
                                        $leadowner = User::where('name', $leadownerName)->first();
                                        $leadOwnerEmail = $leadowner->email ?? null; // Prevent null errors

                                        // Combine CC recipients
                                        $ccEmails = array_filter(array_merge([$salespersonEmail, $leadOwnerEmail], $attendeeEmails), function ($email) {
                                            return filter_var($email, FILTER_VALIDATE_EMAIL); // Validate email format
                                        });

                                        // Send email only if valid
                                        if (!empty($leadEmail)) {
                                            $mail = Mail::to($leadEmail); // ✅ Lead is the main recipient

                                            if (!empty($ccEmails)) {
                                                $mail->cc($ccEmails); // ✅ Others are in CC, so they can see each other
                                            }

                                            $mail->send(new DemoNotification($emailContent, $viewName));

                                            info("Email sent successfully to: " . $leadEmail . " and CC to: " . implode(', ', $ccEmails));
                                        } else {
                                            Log::error("No valid lead email found for sending DemoNotification.");
                                        }
                                    } catch (\Exception $e) {
                                        // Handle email sending failure
                                        Log::error("Email sending failed for salesperson: " . ($data['salesperson'] ?? auth()->user()->name) . ", Error: {$e->getMessage()}");
                                    }
                                }
                            }

                            $lead->update([
                                'categories' => 'Active',
                                'stage' => 'Demo',
                                'lead_status' => 'Demo-Assigned',
                                'follow_up_date' => $data['date'],
                                'demo_appointment' => $appointment->id,
                                'remark' => $data['remarks'],
                                'salesperson' => $data['salesperson'] ?? auth()->user()->id,
                                'salesperson_assigned_date' => now(),
                                'follow_up_counter' => true,
                                'follow_up_needed' => false,
                            ]);

                            $appointment = $lead->demoAppointment()->latest()->first(); // Assuming a relation exists
                            if ($appointment) {
                                $appointment->update([
                                    'status' => 'New',
                                ]);
                            }

                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();

                            if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Salesperson: ' .($data['salesperson'] ?? auth()->user()->name).'. RFQ only') {
                                $salespersonName = \App\Models\User::find($data['salesperson'] ?? auth()->user()->id)?->name ?? 'Unknown Salesperson';

                                $latestActivityLog->update([
                                    'description' => 'Demo created. New Demo Online - ' . $data['date'] . ' - ' . $salespersonName
                                ]);
                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($lead);
                            }

                            $phoneNumber = $lead->companyDetail->contact_no ?? $lead->phone;
                            $recipientName = $lead->companyDetail->name ?? $lead->name;
                            $date = Carbon::parse($demoAppointment->date)->format('j F Y');
                            $day = Carbon::parse($demoAppointment->date)->format('l');
                            $time = Carbon::parse($demoAppointment->start_time)->format('h:i A') . ' - ' .
                                    Carbon::parse($demoAppointment->end_time)->format('h:i A');
                            $demoType = $appointment->appointment_type; // ONLINE/ONSITE/WEBINAR
                            $salespersonName = $salespersonUser->name ?? 'N/A';
                            $salespersonContact = $salespersonUser->mobile_number ?? 'N/A';

                            if (in_array(auth()->user()->role_id, [1, 3]) && !empty($phoneNumber)) {

                                if ($appointment->type === 'WEBINAR DEMO') {
                                    $templateSid = 'HX23b2a24ea30108f54de52c467fdb9e54';
                                } else {
                                    $templateSid = 'HX412a62868446c87862cfe6980de4bdc7';
                                }

                                // For regular templates, include the recipient name
                                $variables = [
                                    $recipientName,
                                    $date,
                                    $day,
                                    $time,
                                    $demoType,
                                    $salespersonName,
                                    $salespersonContact
                                ];

                                // Send the WhatsApp template message
                                try {
                                    $whatsappController = new \App\Http\Controllers\WhatsAppController();
                                    $whatsappController->sendWhatsAppTemplate($phoneNumber, $templateSid, $variables);

                                    // Log successful WhatsApp notification
                                    Log::info("WhatsApp template sent to {$recipientName} at {$phoneNumber} for demo appointment");
                                } catch (\Exception $e) {
                                    // Log error if WhatsApp sending fails
                                    Log::error("Failed to send WhatsApp template: " . $e->getMessage(), [
                                        'phone' => $phoneNumber,
                                        'lead_id' => $lead->id,
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Demo Added Successfully')
                                ->success()
                                ->send();
                        }),
                    Action::make('cancel')
                        ->label('Cancel')
                        ->color('gray')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn () => null)  // Do nothing when clicked
                        ->cancelParentActions()
                ]);
    }

    public static function getAddRFQ(): Action
    {
        return Action::make('add_rfq')
            ->icon('heroicon-o-pencil-square')
            ->color('success')
            ->label('Add RFQ')
            ->modalSubmitAction(false)  // Hide default submit action
            ->modalCancelAction(false)  // Hide default cancel action
            ->modalHeading('Add RFQ')
            ->modalContent(fn (Lead $record) => view('verification-notification', ['record' => $record]))
            ->extraModalFooterActions([
                Action::make('addRFQ')
                ->label(__('Add RFQ'))
                ->visible(function (Lead $record) { // Change from ActivityLog to Lead
                    // Decode properties once and retrieve relevant attributes
                    $leadStatus = $record->lead_status;
                    $category = $record->categories;
                    $stage = $record->stage;

                    // Define invalid lead statuses and stages
                    $invalidLeadStatuses = [
                        LeadStatusEnum::RFQ_TRANSFER->value,
                        LeadStatusEnum::DEMO_CANCELLED->value,
                        LeadStatusEnum::RFQ_FOLLOW_UP->value,
                        LeadStatusEnum::PENDING_DEMO->value,
                        LeadStatusEnum::HOT->value,
                        LeadStatusEnum::WARM->value,
                        LeadStatusEnum::COLD->value,
                    ];

                    $invalidStages = [
                        LeadStageEnum::DEMO->value,
                        LeadStageEnum::FOLLOW_UP->value,
                    ];

                    return !in_array($leadStatus, $invalidLeadStatuses) &&
                        $category !== LeadCategoriesEnum::INACTIVE->value &&
                        !in_array($stage, $invalidStages);
                })
                ->form([
                    Placeholder::make('')
                        ->content(__('You are marking this lead as "RFQ" and assigning it to a salesperson. Confirm?')),

                    Select::make('salesperson')
                        ->label('Salesperson')
                        ->options(function () {
                            if (auth()->user()->role_id == 3) {
                                return User::query()
                                    ->where('role_id', 2)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            } else {
                                return User::query()
                                    ->where('role_id', 2)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }
                        })
                        ->required()
                        ->placeholder('Select a salesperson'),

                    Textarea::make('remark')
                        ->label('Remarks')
                        ->rows(4)
                        ->autosize()
                        ->required()
                        ->placeholder('Enter remarks here...')
                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                ])
                ->color('success')
                ->icon('heroicon-o-pencil-square')
                ->action(function (Lead $record, array $data) { // Change from ActivityLog to Lead
                    // Update the Lead model
                    $record->update([
                        'stage' => 'Transfer',
                        'lead_status' => 'RFQ-Transfer',
                        'remark' => $data['remark'],
                        'salesperson' => $data['salesperson'],
                        'salesperson_assigned_date' => now(),
                        'follow_up_date' => today(),
                        'rfq_transfer_at' => now(),
                        'follow_up_counter' => true,
                        'follow_up_needed' => false,
                    ]);

                    // Fetch the salesperson's name
                    $salespersonName = User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';

                    $latestActivityLog = ActivityLog::where('subject_id', $record->id)
                    ->orderByDesc('created_at')
                    ->first();

                    if ($latestActivityLog) {
                        // Fetch the salesperson's name based on $data['salesperson']
                        $salespersonName = \App\Models\User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';

                        // Check if the latest activity log description needs updating
                        if ($latestActivityLog->description !== 'Lead assigned to Salesperson: ' . $salespersonName . '. RFQ only') {
                            $latestActivityLog->update([
                                'description' => 'Lead assigned to Salesperson: ' . $salespersonName . '. RFQ only', // New description
                            ]);

                            // Log the activity for auditing
                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($record);
                        }
                    }

                    // Fetch lead owner details
                    $leadOwner = User::where('name', $record->lead_owner)->first();
                    $salespersonUser = User::find($data['salesperson']);
                    if ($salespersonUser && filter_var($salespersonUser->email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            // Get logged-in user details
                            $currentUser = auth()->user();
                            if (!$currentUser) {
                                throw new Exception('User not logged in');
                            }

                            // Set email sender details
                            $fromEmail = $currentUser->email;
                            $fromName = $currentUser->name ?? 'CRM User';

                            $emailContent = [
                                'salespersonName' => $salespersonUser->name,
                                'leadOwnerName' => $record->lead_owner ?? 'Unknown Manager',
                                'lead' => [
                                    'lead_code' => isset($record->lead_code) ? 'https://crm.timeteccloud.com:8082/demo-request/' . $record->lead_code : 'N/A',
                                    'lastName' => $record->companyDetail->name ?? $record->name,
                                    'company' => $record->companyDetail->company_name ?? 'N/A',
                                    'companySize' => $record->company_size ?? 'N/A',
                                    'phone' => $record->companyDetail->phone ?? $record->phone,
                                    'email' => $record->companyDetail->email ?? $record->email,
                                    'country' => $record->country ?? 'N/A',
                                    'products' => $record->products ?? 'N/A',
                                ],
                                'remark' => $data['remark'] ?? 'No remarks provided',
                                'formatted_products' => $record->formatted_products,
                            ];

                            // Send email notification
                            Mail::to([$salespersonUser->email, $leadOwner->email])
                                ->send(new SalespersonNotification($emailContent, $fromEmail, $fromName, 'emails.salesperson_notification2'));

                            // Success notification
                            Notification::make()
                                ->title('RFQ Added Successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error("Email sending failed for salesperson: {$data['salesperson']}, Error: {$e->getMessage()}");

                            Notification::make()
                                ->title('Error: Failed to send email')
                                ->danger()
                                ->send();
                        }
                    }
                }),
                Action::make('cancel')
                        ->label('Cancel')
                        ->color('gray')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn () => null)  // Do nothing when clicked
                        ->cancelParentActions()
            ]);
    }

    public static function getAddFollowUp(): Action
    {
        return Action::make('addFollowUp')
        ->label(__('Add Follow Up'))
        ->form([
            Placeholder::make('')
                ->content(__('Fill out the following section to add a follow-up for this lead.
                            Select a follow-up date if the lead requests to be contacted on a specific date.
                            Otherwise, the system will default to sending the follow-up on the next Tuesday.')),

            Textarea::make('remark')
                ->label('Remarks')
                ->rows(3)
                ->autosize()
                ->required()
                ->placeholder('Enter remarks here...')
                ->maxLength(500)
                ->extraAlpineAttributes([
                    'x-on:input' => '
                        const start = $el.selectionStart;
                        const end = $el.selectionEnd;
                        const value = $el.value;
                        $el.value = value.toUpperCase();
                        $el.setSelectionRange(start, end);
                    '
                ])
                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

            Grid::make(3) // 2 columns grid
                ->schema([
                    DatePicker::make('follow_up_date')
                        ->label('Next Follow Up Date')
                        ->required()
                        ->placeholder('Select a follow-up date')
                        ->default(fn ($record) =>
                            optional(optional($record)->lead?->follow_up_date)->addDays(7) ?? now()->addDays(7)
                        )
                        ->minDate(now()->subDay())
                        ->reactive(),
                        // ->minDate(fn ($record) => $record->lead->follow_up_date ? Carbon::parse($record->lead->follow_up_date)->startOfDay() : now()->startOfDay()) // Ensure it gets from DB

                    Select::make('status')
                        ->label('STATUS')
                        ->options([
                            'Hot' => 'Hot',
                            'Warm' => 'Warm',
                            'Cold' => 'Cold'
                        ])
                        ->default(fn ($record) => $record->lead_status ?? 'Hot')
                        ->required()
                        ->visible(fn (?Lead $record) =>
                            $record
                            && in_array(Auth::user()->role_id, [2, 3])
                            && ($record->stage ?? '') === 'Follow Up'
                        ),
                    Select::make('hot_percentage')
                        ->label('HOT PERCENTAGE')
                        ->options([
                            '80' => '80%',
                            '85' => '85%',
                            '90' => '90%',
                            '95' => '95%',
                        ])
                        ->required()
                        ->placeholder('Select percentage')
                        ->visible(function (callable $get, ?Lead $record) {
                            return $record
                                && in_array(Auth::user()->role_id, [2, 3])
                                && $get('status') === 'Hot'
                                && ($record->stage ?? '') === 'Follow Up';
                        })
                        ->rules([
                            function (callable $get) {
                                return function (string $attribute, $value, callable $fail) use ($get) {
                                    if ($get('status') === 'Hot' && empty($value)) {
                                        $fail('Hot percentage is required when status is Hot.');
                                    }
                                };
                            },
                        ]),
                ]),
        ])
        ->color('success')
        // ->visible(fn (Lead $record) => $record->follow_up_needed == 0)
        ->icon('heroicon-o-bell-alert')
        ->action(function (Lead $lead, array $data) {
            // Check if follow_up_date exists in the $data array; if not, set it to next Tuesday
            $followUpDate = $data['follow_up_date'] ?? now()->next(Carbon::TUESDAY);
            // if($lead->lead_status === 'New' || $lead->lead_status === 'Under Review'){

                $updateData = [
                    'follow_up_date' => $followUpDate,
                    'remark' => $data['remark'],
                    'follow_up_needed' => 0,
                    'follow_up_counter' => true,
                    'manual_follow_up_count' => $lead->manual_follow_up_count + 1
                ];

                // Only update 'status' if it exists in $data
                if (isset($data['status'])) {
                    $updateData['lead_status'] = $data['status'];
                }

                if (isset($data['status']) && $data['status'] === 'Hot' && isset($data['hot_percentage'])) {
                    $updateData['hot_percentage'] = $data['hot_percentage'];
                }

                $lead->update($updateData);

                if(auth()->user()->role_id == 1){
                    $role = 'Lead Owner';
                }else if(auth()->user()->role_id == 2){
                    $role = 'Salesperson';
                }else{
                    $role = 'Manager';
                }
                // Increment the follow-up count for the new description
                $followUpDescription = $role .' Follow Up '. $lead->manual_follow_up_count;

                if (isset($data['status']) && $data['status'] === 'Hot' && isset($data['hot_percentage'])) {
                    $followUpDescription .= ' (Hot: ' . $data['hot_percentage'] . '%)';
                }

                // Update or create the latest activity log description
                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestActivityLog) {
                    $latestActivityLog->update([
                        'description' => $followUpDescription,
                    ]);
                } else {
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($lead)
                        ->withProperties(['description' => $followUpDescription]);
                }

                // Send a notification
                Notification::make()
                    ->title('Follow Up Added Successfully')
                    ->success()
                    ->send();
        });
    }

    public static function getAddAutomation(): Action
    {
        return Action::make('addAutomation')
        ->label(__('Add Automation'))
        ->color('primary')
        ->icon('heroicon-o-cog-8-tooth')
        ->requiresConfirmation()
        ->form([
            Placeholder::make('confirmation')
                ->label('Are you sure you want to start the automation to follow up the lead by sending automation email to lead?'),
        ])
        ->modalHeading('Confirm Automation Action')
        ->modalSubmitActionLabel('Confirm')
        ->modalCancelActionLabel('Cancel')
        ->visible(fn (Lead $record) => $record->follow_up_needed == 0)
        ->action(function (Lead $record, array $data) {
            $lead = $record;

            $lead->update([
                'follow_up_count' => 1,
                'follow_up_needed' => 1,
                'lead_status' => 'Under Review',
                'remark' => null,
                'follow_up_date' => null
            ]);

            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestActivityLog) {
                $latestActivityLog->update([
                    'description' => 'Automation Enabled',
                ]);
            }

            // Load template using service
            $utmCampaign = $lead->utmDetail->utm_campaign ?? null;
            $templateSelector = new TemplateSelector();

            if ($lead->lead_code && (
                str_contains($lead->lead_code, '(CN)') ||
                str_contains($lead->lead_code, 'CN')
            )) {
                // Use CN templates
                $template = $templateSelector->getTemplateByLeadSource('CN', 1);
            } else {
                // Use regular templates based on UTM campaign
                $template = $templateSelector->getTemplate($utmCampaign, 1); // first follow-up
            }

            $viewName = $template['email'];
            $contentTemplateSid = $template['sid'];
            $followUpDescription = '1st Automation Follow Up';

            try {
                $leadowner = User::where('name', $lead->lead_owner)->first();

                $emailContent = [
                    'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                    'leadOwnerEmail' => $leadowner->email ?? 'N/A',
                    'lead' => [
                        'lastName' => $lead->name ?? 'N/A',
                        'company' => $lead->companyDetail->company_name ?? 'N/A',
                        'companySize' => $lead->company_size ?? 'N/A',
                        'phone' => $lead->phone ?? 'N/A',
                        'email' => $lead->email ?? 'N/A',
                        'country' => $lead->country ?? 'N/A',
                        'products' => $lead->products ?? 'N/A',
                        'position' => $salespersonUser->position ?? 'N/A',
                        'companyName' => $lead->companyDetail->company_name ?? 'Unknown Company',
                        'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                    ],
                ];

                Mail::to($lead->companyDetail->email ?? $lead->email)
                    ->send(new FollowUpNotification($emailContent, $viewName));
            } catch (\Exception $e) {
                Log::error("Email Error: {$e->getMessage()}");
            }

            $lead->updateQuietly([
                'follow_up_date' => now()->next('Tuesday'),
            ]);

            ActivityLog::create([
                'description' => $followUpDescription,
                'subject_id' => $lead->id,
                'causer_id' => auth()->id(),
            ]);

            Notification::make()
                ->title('Automation Applied')
                ->success()
                ->body('Will auto send email to lead every Tuesday 10am in 3 times')
                ->send();

            try {
                $phoneNumber = $lead->companyDetail->contact_no ?? $lead->phone;

                // Check if it's a Chinese lead
                $isChinese = $lead->lead_code && (
                    str_contains($lead->lead_code, '(CN)') ||
                    str_contains($lead->lead_code, 'CN')
                );

                // Set variables based on language
                if ($isChinese) {
                    // Chinese templates only need one variable for the salesperson's name
                    $variables = [$lead->lead_owner]; // Only the lead's name for Chinese template
                } else {
                    // Regular templates need both lead name and lead owner
                    $variables = [$lead->companyDetail->name ?? $lead->name, $lead->lead_owner];
                }

                $whatsappController = new \App\Http\Controllers\WhatsAppController();
                $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);
            } catch (\Exception $e) {
                Log::error("WhatsApp Error: {$e->getMessage()}");
            }
        });
    }

    public static function getArchiveAction(): Action
    {
        return Action::make('archive')
        ->label(__('Archive'))
        ->modalHeading('Mark Lead as Inactive')
        ->color('warning')
        ->icon('heroicon-o-pencil-square')
        ->form([
            Placeholder::make('')
                ->content(__('Please select the reason to mark this lead as inactive and add any relevant remarks.')),

                Select::make('status')
                ->label('INACTIVE STATUS')
                ->options([
                    'On Hold' => 'On Hold',
                    'Junk' => 'Junk',
                    'Lost' => 'Lost',
                ])
                ->default('On Hold')
                ->required()
                ->reactive(), // Make status field reactive

            Select::make('reason')
                ->label('Select a Reason')
                ->options(fn (callable $get) =>
                    InvalidLeadReason::where('lead_stage', $get('status')) // Filter based on selected status
                        ->pluck('reason', 'id')
                        ->toArray()
                )
                ->required()
                ->reactive() // Make reason field update dynamically
                ->createOptionForm([
                    Select::make('lead_stage')
                        ->options([
                            'On Hold' => 'On Hold',
                            'Junk' => 'Junk',
                            'Lost' => 'Lost',
                            'Closed' => 'Closed'
                        ])
                        ->default(fn (callable $get) => $get('status')) // Default lead_stage based on selected status
                        ->required(),
                    TextInput::make('reason')
                        ->label('New Reason')
                        ->required(),
                ])
                ->createOptionUsing(function (array $data) {
                    $newReason = InvalidLeadReason::create([
                        'lead_stage' => $data['lead_stage'],
                        'reason' => $data['reason'],
                    ]);

                    return $newReason->id; // Return the newly created reason ID
                }),
            Textarea::make('remark')
                ->label('Remarks')
                ->rows(3)
                ->autosize()
                ->required()
                ->reactive()
                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
        ])
        ->action(function (Lead $record, array $data) {
            $statusLabels = [
                'on_hold' => 'On Hold',
                'junk' => 'Junk',
                'lost' => 'Lost',
            ];

            $statusLabel = $statusLabels[$data['status']] ?? $data['status'];

            $lead = $record;

            $lead->update([
                'categories' => 'Inactive',
                'lead_status' => $statusLabel,
                'remark' => $data['remark'],
                'stage' => null,
                'follow_up_date' => null,
                'follow_up_needed' => false,
            ]);

            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();
            $reasonText = InvalidLeadReason::find($data['reason'])?->reason ?? 'Unknown Reason';

            if ($latestActivityLog) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($lead)
                    ->log('Lead marked as inactive.');

                sleep(1);

                $latestActivityLog->update([
                    'description' => 'Marked as ' . $statusLabel . ': ' . $reasonText, // New description
                ]);
            }

            Notification::make()
                ->title('Lead Archived')
                ->success()
                ->body('You have successfully marked the lead as inactive.')
                ->send();
        });
    }

    public static function getViewRemark(){
        return Action::make('view_remark')
            ->label('View Remark')
            ->icon('heroicon-o-eye')
            ->modalHeading('Lead Remark')
            ->requiresConfirmation()
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalHeading('Lead Remarks')
            ->modalDescription('Here are the remark for this lead.')
            ->modalContent(function (Lead $record) {
                // Extract the remark, fallback to '-'
                $remark = $record->remark;

                // Preserve line breaks and return as HTML-safe string
                return new HtmlString(nl2br(e($remark)));
            })
            ->color('primary');
    }

    public static function getTransferCallAttempt()
    {
        return Action::make('transfer_call_attempt')
            ->label('Transfer to Call Attempt')
            ->requiresConfirmation()
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading('Transfer to Call Attempt')
            ->modalDescription('Do you want to transfer this lead to Call Attempt Section? Make sure you have contacted the lead before you transfer')
            ->color('primary')
            ->action(function (Lead $record) {
                $record->timestamps = false; // Avoid updating updated_at
                $record->call_attempt += 1;
                $record->done_call = 1;
                $record->saveQuietly(); // Regular save with event firing

                // ✅ Log activity
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($record)
                    ->withProperties([
                        'call_attempt' => $record->call_attempt,
                        'done_call' => true,
                    ])
                    ->log('Transfer to Call Attempt, Done Call');

                Notification::make()
                    ->title('Call Attempt Recorded')
                    ->success()
                    ->body('The call attempt count has been increased.')
                    ->send();
            });
    }

    public static function getInactiveTransferCallAttempt()
    {
        return Action::make('transfer_call_attempt')
            ->label('Transfer to Follow Up 2')
            ->requiresConfirmation()
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading('Transfer to Follow Up 2')
            ->modalDescription('Do you want to transfer this lead to Inactive Follow Up 2 Section? Make sure you have contacted the lead before you transfer')
            ->color('success')
            ->action(function (Lead $record) {
                $record->timestamps = false; // Avoid updating updated_at
                $record->call_attempt += 1;
                $record->done_call = 1;
                $record->saveQuietly(); // Regular save with event firing

                // ✅ Log activity
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($record)
                    ->withProperties([
                        'call_attempt' => $record->call_attempt,
                        'done_call' => true,
                    ])
                    ->log('Transfer to Inactive Follow Up 2, Done Call');

                Notification::make()
                    ->title('Call Attempt Recorded')
                    ->success()
                    ->body('The call attempt count has been increased.')
                    ->send();
            });
    }

    public static function getLeadDetailAction(): Action
    {
        return Action::make('view_lead_detail')
            ->label('Lead Detail')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('warning') // Orange color
            ->url(fn (Lead $record) => url('admin/leads/' . Encryptor::encrypt($record->id)))
            ->openUrlInNewTab(); // Opens in a new tab
    }

    public static function getLeadDetailActionInDemo(): Action
    {
        return Action::make('view_lead_detail')
            ->label('Lead Detail')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('warning') // Orange color
            ->url(fn (Appointment $record) => url('admin/leads/' . Encryptor::encrypt($record->lead->id)))
            ->openUrlInNewTab(); // Opens in a new tab
    }


    public static function getAddQuotationAction(): Action
    {
        return Action::make('quotation')
            ->label(__('Add Quotation'))
            ->color('success')
            ->icon('heroicon-o-pencil-square')
            ->url(fn (Lead $record) => route('filament.admin.resources.quotations.create', [
                'lead_id' => Encryptor::encrypt($record->id),
            ]), true);
    }

    public static function getDoneDemoAction(): Action
    {
        return Action::make('demo_done')
            ->label(__('Demo Done'))
            ->modalHeading('Demo Completed Confirmation')
            ->form([
                Placeholder::make('')
                    ->content(__('You are marking this demo as completed. Confirm?')),

            TextInput::make('remark')
                    ->label('Remarks')
                    ->required()
                    ->placeholder('Enter remarks here...')
                    ->maxLength(500)
                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
            ])
            ->color('success')
            ->icon('heroicon-o-pencil-square')
            ->action(function (Lead $lead, array $data) {

                // Retrieve the latest demo appointment for the lead
                $latestDemoAppointment = $lead->demoAppointment() // Assuming 'demoAppointments' relation exists
                    ->latest('created_at') // Retrieve the most recent demo
                    ->first();

                if ($latestDemoAppointment) {
                    $latestDemoAppointment->update([
                        'status' => 'Done', // Or whatever status you need to set
                    ]);
                }

                // Update the Lead model
                $lead->update([
                    'stage' => 'Follow Up',
                    'lead_status' => 'Hot',
                    'remark' => $data['remark'] ?? null,
                ]);

                // Update the latest ActivityLog related to the lead
                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestActivityLog) {
                    $latestActivityLog->update([
                        'description' => 'Demo Completed',
                    ]);
                }

                // Log activity
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($lead);

                // Send success notification
                Notification::make()
                    ->title('Demo completed successfully')
                    ->success()
                    ->send();
            });
    }

    public static function getCancelDemoAction(): Action
    {
        return Action::make('demo_cancel')
        ->label(__('Cancel Demo'))
        ->modalHeading('Cancel Demo')
        ->requiresConfirmation()
        ->color('danger')
        ->icon('heroicon-o-x-circle')
        ->action(function (array $data, $record) {
            $appointment = $record;
            $lead = $appointment->lead;

            $updateData = [
                'stage' => 'Transfer',
                'lead_status' => 'Demo Cancelled',
                'remark' => $data['remark'] ?? null,
                'follow_up_date' => null
            ];

            // if (in_array(auth()->user()->role_id, [1, 3])) {
            //     $updateData['salesperson'] = null;
            // }

            $lead->update($updateData);

            $lead->refresh();

            // Get event details
            $eventId = $appointment->event_id;
            $salesperson = User::find($appointment->salesperson);

            if (!$salesperson || !$salesperson->email) {
                Notification::make()
                    ->title('Salesperson Not Found')
                    ->danger()
                    ->body('The salesperson assigned to this appointment could not be found or does not have an email address.')
                    ->send();
                return;
            }

            $organizerEmail = $salesperson->email;

            // ✅ Get all recipients for cancellation email
            $email = $lead->companyDetail->email ?? $lead->email;
            $demoAppointment = $lead->demoAppointment()->latest()->first();

            // Extract required attendees
            $requiredAttendees = $demoAppointment->required_attendees ?? null;
            $attendeeEmails = [];
            if (!empty($requiredAttendees)) {
                $cleanedAttendees = str_replace('"', '', $requiredAttendees);
                $attendeeEmails = array_filter(array_map('trim', explode(';', $cleanedAttendees))); // Ensure no empty spaces
            }

            $salespersonUser = \App\Models\User::find($appointment->salesperson ?? auth()->user()->id);
            $demoAppointment = $lead->demoAppointment->first();
            $startTime = Carbon::parse($demoAppointment->start_time);
            $endTime = Carbon::parse($demoAppointment->end_time); // Assuming you have an end_time field
            $formattedDate = Carbon::parse($demoAppointment->date)->format('d/m/Y');
            $contactNo = optional($lead->companyDetail)->contact_no ?? $lead->phone;
            $picName = optional($lead->companyDetail)->name ?? $lead->name;
            $email = optional($lead->companyDetail)->email ?? $lead->email;

            try {
                if ($eventId) {
                    $accessToken = MicrosoftGraphService::getAccessToken();
                    $graph = new Graph();
                    $graph->setAccessToken($accessToken);

                    // Cancel the Teams meeting
                    $graph->createRequest("DELETE", "/users/$organizerEmail/events/$eventId")->execute();
                    $leadowner = User::where('name', $lead->lead_owner)->first();

                    $viewName = 'emails.cancel_demo_notification';

                    $emailContent = [
                        'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                        'lead' => [
                            'lastName' => $lead->companyDetail->name ?? $lead->name, // Lead's Last Name
                            'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                            'salespersonName' => $salespersonUser->name ?? 'N/A',
                            'salespersonPhone' => $salespersonUser->mobile_number ?? 'N/A',
                            'salespersonEmail' => $salespersonUser->email ?? 'N/A',
                            'phone' =>$contactNo ?? 'N/A',
                            'pic' => $picName ?? 'N/A',
                            'email' => $email ?? 'N/A',
                            'date' => $formattedDate ?? 'N/A',
                            'startTime' => $startTime ?? 'N/A',
                            'endTime' => $endTime ?? 'N/A',
                            'position' => $salespersonUser->position ?? 'N/A', // position
                            'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                            'demo_type' => $appointment->type,
                            'appointment_type' => $appointment->appointment_type
                        ],
                    ];
                    // ✅ Extract CC Recipients
                    $ccEmails = array_filter(array_merge([$salespersonUser->email, $leadowner->email], $attendeeEmails), function ($email) {
                        return filter_var($email, FILTER_VALIDATE_EMAIL); // Validate email format
                    });

                    // ✅ Send email with CC recipients
                    if (!empty($email)) {
                        $mail = Mail::to($email); // Send to Lead

                        if (!empty($ccEmails)) {
                            $mail->cc($ccEmails); // Add CC recipients
                        }

                        $mail->send(new CancelDemoNotification($emailContent, $viewName));

                        info("Email sent successfully to: " . $email . " and CC to: " . implode(', ', $ccEmails));
                    } else {
                        Log::error("No valid lead email found for sending CancelDemoNotification.");
                    }

                    Notification::make()
                        ->title('Teams Meeting Cancelled Successfully')
                        ->warning()
                        ->body('The meeting has been cancelled successfully.')
                        ->send();
                } else {
                    Log::warning('No event ID found for appointment', [
                        'appointment_id' => $appointment->id,
                    ]);

                    Notification::make()
                        ->title('No Meeting Found')
                        ->danger()
                        ->body('The appointment does not have an associated Teams meeting.')
                        ->send();
                }
            } catch (\Exception $e) {
                Log::error('Failed to cancel Teams meeting: ' . $e->getMessage(), [
                    'event_id' => $eventId,
                    'organizer' => $organizerEmail,
                ]);

                Notification::make()
                    ->title('Failed to Cancel Teams Meeting')
                    ->danger()
                    ->body('Error: ' . $e->getMessage())
                    ->send();
            }

            // Count how many times Demo was Cancelled
            $cancelFollowUpCount = ActivityLog::where('subject_id', $lead->id)
                ->whereJsonContains('properties->attributes->lead_status', 'Demo Cancelled')
                ->count();

            // Generate Follow-up Description
            $cancelFollowUpDescription = match ($cancelFollowUpCount) {
                1 => '1st Demo Cancelled Follow Up',
                2 => '2nd Demo Cancelled Follow Up',
                3 => '3rd Demo Cancelled Follow Up',
                default => "{$cancelFollowUpCount}th Demo Cancelled Follow Up",
            };

            // Update or Create the Latest Activity Log
            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestActivityLog) {
                $latestActivityLog->update([
                    'description' => 'Demo Cancelled. ' . $cancelFollowUpDescription,
                ]);
            } else {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($lead)
                    ->withProperties(['description' => $cancelFollowUpDescription])
                    ->log('Demo Cancelled');
            }

            // Update the Appointment status
            $appointment->update([
                'status' => 'Cancelled',
                // 'remarks' => $data['remark'],
            ]);

            Notification::make()
                ->title('You have cancelled a demo')
                ->warning()
                ->send();
        });
    }

    public static function getRescheduleDemoAction(): Action
    {
        return Action::make('reschedule_demo')
            ->label('Reschedule Demo')
            ->icon('heroicon-o-calendar-days')
            ->color('primary')
            ->modalHeading('Reschedule Demo Appointment')
            ->form(function (?Appointment $record) {
                if (! $record) {
                    return [
                        Placeholder::make('noAppointment')->content('No appointment found to reschedule.')
                    ];
                }
                return [
                    ToggleButtons::make('mode')
                        ->label('')
                        ->options([
                            'auto' => 'Auto',
                            'custom' => 'Custom',
                        ])
                        ->reactive()
                        ->inline()
                        ->grouped()
                        ->default('auto')
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state === 'custom') {
                                $set('date', null);
                                $set('start_time', null);
                                $set('end_time', null);
                            } else {
                                $set('date', Carbon::today()->toDateString());
                                $set('start_time', Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30))->format('H:i'));
                                $set('end_time', Carbon::parse($get('start_time'))->addHour()->format('H:i'));
                            }
                        }),

                    Grid::make(3)->schema([
                        DatePicker::make('date')
                            ->required()
                            ->label('DATE')
                            ->default(Carbon::today()->toDateString()),

                        TimePicker::make('start_time')
                            ->label('START TIME')
                            ->required()
                            ->seconds(false)
                            ->reactive()
                            ->default(function () {
                                // Round up to the next 30-minute interval
                                $now = Carbon::now();
                                return $now->addMinutes(30 - ($now->minute % 30))->format('H:i');
                            })
                            ->datalist(function (callable $get) {
                                $user = Auth::user();
                                $date = $get('date');

                                if ($get('mode') === 'custom') {
                                    return [];
                                }

                                $times = [];
                                $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30))->setSeconds(0);

                                if ($user && $user->role_id == 2 && $date) {
                                    // Fetch all booked appointments as full models
                                    $appointments = Appointment::where('salesperson', $user->id)
                                        ->whereDate('date', $date)
                                        ->whereIn('status', ['New', 'Done'])
                                        ->get(['start_time', 'end_time']);

                                    for ($i = 0; $i < 48; $i++) {
                                        $slotStart = $startTime->copy();
                                        $slotEnd = $startTime->copy()->addMinutes(30);
                                        $formattedTime = $slotStart->format('H:i');

                                        $isBooked = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                                            $apptStart = Carbon::createFromFormat('H:i:s', $appointment->start_time);
                                            $apptEnd = Carbon::createFromFormat('H:i:s', $appointment->end_time);

                                            // Check if the slot overlaps with the appointment
                                            return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
                                        });

                                        if (!$isBooked) {
                                            $times[] = $formattedTime;
                                        }

                                        $startTime->addMinutes(30);
                                    }
                                } else {
                                    for ($i = 0; $i < 48; $i++) {
                                        $times[] = $startTime->format('H:i');
                                        $startTime->addMinutes(30);
                                    }
                                }

                                return $times;
                            })
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($get('mode') === 'auto' && $state) {
                                    $set('end_time', Carbon::parse($state)->addHour()->format('H:i'));
                                }
                            }),

                        TimePicker::make('end_time')
                            ->label('END TIME')
                            ->required()
                            ->seconds(false)
                            ->reactive()
                            ->default(function (callable $get) {
                                $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));
                                return $startTime->addHour()->format('H:i');
                            })
                            ->datalist(function (callable $get) {
                                $user = Auth::user();
                                $date = $get('date');

                                if ($get('mode') === 'custom') {
                                    return []; // Custom mode: empty list
                                }

                                $times = [];
                                $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));

                                if ($user && $user->role_id == 2 && $date) {
                                    // Fetch booked time slots for this salesperson on the selected date
                                    $bookedAppointments = Appointment::where('salesperson', $user->id)
                                        ->whereDate('date', $date)
                                        ->pluck('end_time', 'start_time') // Start as key, End as value
                                        ->toArray();

                                    for ($i = 0; $i < 48; $i++) {
                                        $formattedTime = $startTime->format('H:i');

                                        // Check if time is booked
                                        $isBooked = collect($bookedAppointments)->contains(function ($end, $start) use ($formattedTime) {
                                            return $formattedTime >= $start && $formattedTime <= $end;
                                        });

                                        if (!$isBooked) {
                                            $times[] = $formattedTime;
                                        }

                                        $startTime->addMinutes(30);
                                    }
                                } else {
                                    // Default available slots
                                    for ($i = 0; $i < 48; $i++) {
                                        $times[] = $startTime->format('H:i');
                                        $startTime->addMinutes(30);
                                    }
                                }

                                return $times;
                            }),
                    ]),

                    Grid::make(3)->schema([
                        Select::make('type')
                            ->label('DEMO TYPE')
                            ->default('NEW DEMO')
                            ->required()
                            ->options(function () use ($record) {
                                $leadHasNewAppointment = Appointment::where('lead_id', $record->id)
                                    ->whereIn('status', ['New', 'Done'])
                                    ->exists();

                                return $leadHasNewAppointment
                                    ? [
                                        'HRMS DEMO' => 'HRMS DEMO',
                                        'HRDF DISCUSSION' => 'HRDF DISCUSSION',
                                        'SYSTEM DISCUSSION' => 'SYSTEM DISCUSSION',
                                    ]
                                    : [
                                        'NEW DEMO' => 'NEW DEMO',
                                        'WEBINAR DEMO' => 'WEBINAR DEMO',
                                    ];
                            }),

                        Select::make('appointment_type')
                            ->label('APPOINTMENT TYPE')
                            ->required()
                            ->default('ONLINE')
                            ->options([
                                'ONLINE' => 'ONLINE',
                                'ONSITE' => 'ONSITE',
                                'INHOUSE' => 'INHOUSE'
                            ]),

                        Select::make('salesperson')
                            ->label('SALESPERSON')
                            ->options(function () {
                                return User::where('role_id', 2)->pluck('name', 'id')->toArray();
                            })
                            ->disableOptionWhen(function ($value, $get) {
                                $date = $get('date');
                                $startTime = $get('start_time');
                                $endTime = $get('end_time');
                                $demoType = $get('type');

                                if ($demoType === 'WEBINAR DEMO') return false;

                                $parsedDate = Carbon::parse($date)->format('Y-m-d');
                                $parsedStart = Carbon::parse($startTime)->format('H:i:s');
                                $parsedEnd = Carbon::parse($endTime)->format('H:i:s');

                                return Appointment::where('salesperson', $value)
                                    ->where('status', 'New')
                                    ->whereDate('date', $parsedDate)
                                    ->where(function ($query) use ($parsedStart, $parsedEnd) {
                                        $query->whereBetween('start_time', [$parsedStart, $parsedEnd])
                                            ->orWhereBetween('end_time', [$parsedStart, $parsedEnd])
                                            ->orWhere(function ($q) use ($parsedStart, $parsedEnd) {
                                                $q->where('start_time', '<', $parsedStart)
                                                    ->where('end_time', '>', $parsedEnd);
                                            });
                                    })
                                    ->exists();
                            })
                            ->required()
                            ->hidden(fn () => auth()->user()->role_id === 2)
                            ->placeholder('Select a salesperson'),
                    ]),

                    Textarea::make('remarks')
                        ->label('REMARKS')
                        ->rows(3)
                        ->autosize()
                        ->reactive()
                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                    TextInput::make('required_attendees')
                        ->label('Required Attendees')
                        ->helperText('Separate each email and name pair with a semicolon (e.g., email1;email2;email3).'),
                ];
            })
            ->action(function (array $data, Appointment $record) {
                $lead = $record->lead;

                if (! $record) {
                    Notification::make()
                        ->title('No Existing Appointment')
                        ->danger()
                        ->body('No demo appointment found to reschedule.')
                        ->send();
                    return;
                }

                $record->update([
                    'date' => $data['date'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'type' => $data['type'],
                    'appointment_type' => $data['appointment_type'],
                    'remarks' => $data['remarks'],
                    'required_attendees' => $data['required_attendees'],
                ]);

                $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
                $graph = new Graph();
                $graph->setAccessToken($accessToken);

                $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
                $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

                $salesperson = User::find($record->salesperson);
                $organizerEmail = $salesperson->email ?? null;

                if (! $organizerEmail) {
                    Notification::make()
                        ->title('Missing Organizer Email')
                        ->danger()
                        ->body('Salesperson email is not available.')
                        ->send();
                    return;
                }

                try {
                    if ($record->event_id) {
                        $meetingUpdatePayload = [
                            'start' => ['dateTime' => $startTime, 'timeZone' => 'Asia/Kuala_Lumpur'],
                            'end' => ['dateTime' => $endTime, 'timeZone' => 'Asia/Kuala_Lumpur'],
                            'subject' => 'TIMETEC HRMS | ' . $lead->companyDetail->company_name,
                        ];

                        $response = $graph->createRequest("PATCH", "/users/$organizerEmail/events/{$record->event_id}")
                            ->attachBody($meetingUpdatePayload)
                            ->execute();

                        $eventData = $response->getBody(); // associative array

                        $joinUrl = $eventData['onlineMeeting']['joinUrl'] ?? null;

                        Notification::make()
                            ->title('Demo Rescheduled')
                            ->success()
                            ->body('The demo appointment and Teams meeting have been updated.')
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

                $lead->updateQuietly([
                    'follow_up_date' => $data['date'],
                    'remark' => $data['remarks'],
                ]);

                $salespersonUser = \App\Models\User::find($data['salesperson'] ?? auth()->user()->id);
                    $demoAppointment = $lead->demoAppointment()->latest('created_at')->first();
                    $startTime = Carbon::parse($demoAppointment->start_time);
                    $endTime = Carbon::parse($demoAppointment->end_time); // Assuming you have an end_time field
                    $formattedDate = Carbon::parse($demoAppointment->date)->format('d/m/Y');
                    $contactNo = optional($lead->companyDetail)->contact_no ?? $lead->phone;
                    $picName = optional($lead->companyDetail)->name ?? $lead->name;
                    $email = optional($lead->companyDetail)->email ?? $lead->email;
                    if ($salespersonUser && filter_var($salespersonUser->email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $utmCampaign = $lead->utmDetail->utm_campaign ?? null;
                            $templateSelector = new TemplateSelector();

                            if ($lead->lead_code && (
                                str_contains($lead->lead_code, '(CN)') ||
                                str_contains($lead->lead_code, 'CN')
                            )) {
                                // Use CN templates
                                $template = $templateSelector->getTemplateByLeadSource('CN', 0);
                            } else {
                                // Use regular templates based on UTM campaign
                                $template = $templateSelector->getTemplate($utmCampaign, 0); // first follow-up
                            }

                            $viewName = $template['email'] ?? 'emails.demo_notification'; // fallback
                            $leadowner = User::where('name', $lead->lead_owner)->first();

                            $emailContent = [
                                'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                                'lead' => [
                                    'lastName' => $lead->companyDetail->name ?? $lead->name, // Lead's Last Name
                                    'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                                    'salespersonName' => $salespersonUser->name ?? 'N/A',
                                    'salespersonPhone' => $salespersonUser->mobile_number ?? 'N/A',
                                    'salespersonEmail' => $salespersonUser->email ?? 'N/A',
                                    'salespersonMeetingLink' => $salespersonUser->msteam_link ?? 'N/A',
                                    'phone' =>$contactNo ?? 'N/A',
                                    'pic' => $picName ?? 'N/A',
                                    'email' => $email ?? 'N/A',
                                    'date' => $formattedDate ?? 'N/A',
                                    'startTime' => $startTime ?? 'N/A',
                                    'endTime' => $endTime ?? 'N/A',
                                    'meetingLink' => $joinUrl ?? 'N/A',
                                    'position' => $salespersonUser->position ?? 'N/A', // position
                                    'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                    'demo_type' => $record->type,
                                    'appointment_type' => $record->appointment_type
                                ],
                            ];

                            $demoAppointment = $lead->demoAppointment()->latest()->first(); // Adjust based on your relationship type

                            $requiredAttendees = $demoAppointment->required_attendees ?? null;

                            // Parse attendees' emails if not null
                            $attendeeEmails = [];
                            if (!empty($requiredAttendees)) {
                                $cleanedAttendees = str_replace('"', '', $requiredAttendees);
                                $attendeeEmails = array_filter(array_map('trim', explode(';', $cleanedAttendees))); // Ensure no empty spaces
                            }

                            // Get Lead's Email (Primary recipient)
                            $leadEmail = $lead->companyDetail->email ?? $lead->email;

                            // Get Salesperson Email
                            $salespersonId = $lead->salesperson;
                            $salesperson = User::find($salespersonId);
                            $salespersonEmail = $salespersonUser->email ?? null; // Prevent null errors

                            // Get Lead Owner Email
                            $leadownerName = $lead->lead_owner;
                            $leadowner = User::where('name', $leadownerName)->first();
                            $leadOwnerEmail = $leadowner->email ?? null; // Prevent null errors

                            // Combine CC recipients
                            $ccEmails = array_filter(array_merge([$salespersonEmail, $leadOwnerEmail], $attendeeEmails), function ($email) {
                                return filter_var($email, FILTER_VALIDATE_EMAIL); // Validate email format
                            });

                            // Send email only if valid
                            if (!empty($leadEmail)) {
                                $mail = Mail::to($leadEmail); // ✅ Lead is the main recipient

                                if (!empty($ccEmails)) {
                                    $mail->cc($ccEmails); // ✅ Others are in CC, so they can see each other
                                }

                                $mail->send(new DemoNotification($emailContent, $viewName));

                                info("Email sent successfully to: " . $leadEmail . " and CC to: " . implode(', ', $ccEmails));
                            } else {
                                Log::error("No valid lead email found for sending DemoNotification.");
                            }
                        } catch (\Exception $e) {
                            // Handle email sending failure
                            Log::error("Email sending failed for salesperson: " . ($data['salesperson'] ?? auth()->user()->name) . ", Error: {$e->getMessage()}");
                        }
                    }

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($lead)
                    ->log('Demo rescheduled to ' . $data['date'] . ' at ' . $data['start_time']);
            });
    }

    public static function getQuotationFollowUpAction(): Action
    {
        return Action::make('quotationFollowUp')
            ->label(__('Add RFQ Follow Up (QF)'))
            ->color('success')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading('Determine Lead Status')
            ->form([
                Placeholder::make('')
                    ->content(__('Fill out the following section to add a follow-up for this lead.
                                Select a follow-up date if the lead requests to be contacted on a specific date.
                                Otherwise, the system will default to sending the follow-up on the next Tuesday.')),

                TextInput::make('remark')
                    ->label('Remarks')
                    ->required()
                    ->placeholder('Enter remarks here...')
                    ->maxLength(500)
                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                DatePicker::make('follow_up_date')
                    ->label('')
                    ->required()
                    ->placeholder('Select a follow-up date')
                    ->default(now())
                    ->disabled(fn (Get $get) => $get('follow_up_needed'))
                    ->reactive(),
                Placeholder::make('')
                    ->content(__('What status do you feel for this lead at this moment?'))
                    ->hidden(fn (Lead $record) => in_array($record->lead_status, ['Hot', 'Warm', 'Cold'])),

                Select::make('status')
                    ->label('STATUS')
                    ->options([
                        'hot' => 'Hot',
                        'warm' => 'Warm',
                        'cold' => 'Cold',
                    ])
                    ->default('hot')
                    ->required()
                    ->hidden(fn (Lead $record) => in_array($record->lead_status, ['Hot', 'Warm', 'Cold'])),
            ])
            ->action(function (Lead $lead, array $data, Component $livewire) {
                // Check if follow_up_date exists in the $data array; if not, set it to next Tuesday
                $followUpDate = $data['follow_up_date'] ?? now()->next(Carbon::TUESDAY);

                $updateData = [
                    'follow_up_date' => $followUpDate,
                    'remark' => $data['remark'],
                    'follow_up_count' => $lead->follow_up_count + 1,
                ];

                if (!empty($data['status'])) {
                    $updateData['lead_status'] = $data['status'];
                }

                $lead->update($updateData);


                $followUpCount = max(1, ActivityLog::where('subject_id', $lead->id)
                    ->where(function ($query) {
                        $query->whereJsonContains('properties->attributes->lead_status', 'Hot')
                            ->orWhereJsonContains('properties->attributes->lead_status', 'Warm')
                            ->orWhereJsonContains('properties->attributes->lead_status', 'Cold');
                    })
                    ->count() - 1);

                // Increment the follow-up count for the new description
                $followUpDescription = ($followUpCount) . 'st Quotation Transfer Follow Up';
                if ($followUpCount == 2) {
                    $followUpDescription = '2nd Quotation Transfer Follow Up';
                } elseif ($followUpCount == 3) {
                    $followUpDescription = '3rd Quotation Transfer Follow Up';
                } elseif ($followUpCount >= 4) {
                    $followUpDescription = $followUpCount . 'th Quotation Transfer Follow Up';
                }
                // Update or create the latest activity log description
                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestActivityLog) {
                    $latestActivityLog->update([
                        'description' => $followUpDescription,
                    ]);
                } else {
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($lead)
                        ->withProperties(['description' => $followUpDescription]);
                }

                // Send a notification
                Notification::make()
                    ->title('Follow Up Added Successfully')
                    ->success()
                    ->send();
            });
    }

    public static function getNoResponseAction(): Action
    {
        return Action::make('noResponse')
            ->label(__('No Response'))
            ->modalHeading('Mark Lead as No Response')
            ->form([
                Placeholder::make('')
                ->content(__('You are making this lead as No Response after multiple follow-ups. Confirm?')),

                TextInput::make('remark')
                ->label('Remarks')
                ->required()
                ->placeholder('Enter remarks here...')
                ->maxLength(500)
                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
            ])
            ->color('danger')
            ->icon('heroicon-o-pencil-square')
            ->action(function (Lead $lead, array $data) {
                // Update the Lead model for role_id = 1
                $lead->update([
                    'categories' => 'Inactive',
                    'stage' => null,
                    'lead_status' => 'No Response',
                    'remark' => $data['remark'],
                    'follow_up_date' => null,
                ]);

                // Update the latest ActivityLog for role_id = 1
                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    ->orderByDesc('created_at')
                    ->first();

                $latestActivityLog->update([
                    'description' => 'Marked as No Response',
                ]);

                // Send notification for role_id = 1
                Notification::make()
                    ->title('You have marked No Response to a lead')
                    ->success()
                    ->send();

                // Log the activity (for both roles)
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($lead);
            });
    }

    public static function getConfirmOrderAction(): Action
    {
        return Action::make('Confirm Order')
        ->label('Confirm Order')
        ->icon('heroicon-o-clipboard-document-check')
        ->form([
            FileUpload::make('attachment')
                ->label('Upload Confirmation Order Document')
                ->acceptedFileTypes(['application/pdf','image/jpg','image/jpeg'])
                ->uploadingMessage('Uploading document...')
                ->previewable(false)
                ->preserveFilenames()
                ->disk('public')
                ->directory('confirmation_orders')
        ])
        ->action(function (Lead $lead, array $data) {
            $quotation = $lead->quotations()->latest('created_at')->first();

            if (!$quotation) {
                Notification::make()
                    ->title('Quotation Not Found')
                    ->body('No quotation is associated with this lead.')
                    ->danger()
                    ->send();
                return;
            }

            $quotationService = app(QuotationService::class);
            $quotation->confirmation_order_document = $data['attachment'];
            $quotation->pi_reference_no = $quotationService->update_pi_reference_no($quotation);
            $quotation->status = QuotationStatusEnum::accepted;
            $quotation->save();

            $notifyUsers = User::whereIn('role_id', ['2'])->get();
            $currentUser = auth()->user();
            $notifyUsers = $notifyUsers->push($currentUser);

            $lead = $quotation->lead;

            ActivityLog::create([
                'subject_id' => $lead->id,
                'description' => 'Order Uploaded. Pending Approval to close lead.',
                'causer_id' => auth()->id(),
                'causer_type' => get_class(auth()->user()),
                'properties' => json_encode([
                    'attributes' => [
                        'quotation_reference_no' => $quotation->quotation_reference_no,
                        'lead_status' => $lead->lead_status,
                        'stage' => $lead->stage,
                    ],
                ]),
            ]);

            Notification::make()
                ->success()
                ->title('Confirmation Order Document Uploaded!')
                ->body('Confirmation order document for quotation ' . $quotation->quotation_reference_no . ' has been uploaded successfully!')
                ->send();
            }
        );
    }

    public static function getChangeLeadOwnerAction(): Action
    {
        return Action::make('change_lead_owner')
            ->label('Change Lead Owner')
            ->icon('heroicon-o-user-group')
            ->visible(fn () => auth()->user()->role_id == 3) // Only manager
            ->form([
                Select::make('new_lead_owner')
                    ->label('New Lead Owner')
                    ->options(
                        \App\Models\User::where('role_id', 1)->pluck('name', 'name') // uses name since `lead_owner` stores names
                    )
                    ->searchable()
                    ->required()
                    ->placeholder('Select a lead owner'),
            ])
            ->action(function (Lead $record, array $data) {
                $newOwner = $data['new_lead_owner'];

                $record->update([
                    'lead_owner' => $newOwner,
                ]);

                ActivityLog::create([
                    'description' => "Lead ownership changed to: {$newOwner}",
                    'subject_id' => $record->id,
                    'causer_id' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Lead Owner Updated')
                    ->success()
                    ->body("This lead has been reassigned to {$newOwner}.")
                    ->send();
            });
    }

    public static function getRequestChangeLeadOwnerAction(): Action
    {
        return Action::make('request_change_lead_owner')
            ->label('Request Change Lead Owner')
            ->icon('heroicon-o-paper-airplane')
            ->visible(fn () => auth()->user()?->role_id == 1) // Only visible to non-manager roles
            ->form([
                \Filament\Forms\Components\Select::make('requested_owner_id')
                    ->label('New Lead Owner')
                    ->searchable()
                    ->required()
                    ->options(
                        \App\Models\User::where('role_id', 1)->pluck('name', 'id') // Assuming lead owners are role_id = 1
                    ),
                \Filament\Forms\Components\Textarea::make('reason')
                    ->label('Reason for Request')
                    ->rows(3)
                    ->autosize()
                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                    ->required(),
            ])
            ->action(function (Lead $record, array $data) {
                $manager = \App\Models\User::where('role_id', 3)->first();

                // Create the request
                \App\Models\Request::create([
                    'lead_id' => $record->id,
                    'requested_by' => auth()->id(),
                    'current_owner_id' => \App\Models\User::where('name', $record->lead_owner)->value('id'),
                    'requested_owner_id' => $data['requested_owner_id'],
                    'reason' => $data['reason'],
                    'status' => 'pending',
                ]);

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($record)
                    ->withProperties([
                        'lead_id' => $record->id,
                        'requested_by' => auth()->user()->name,
                        'requested_owner_id' => \App\Models\User::find($data['requested_owner_id'])?->name,
                        'reason' => $data['reason'],
                    ])
                    ->log('Requested lead owner change');

                Notification::make()
                    ->title('Request Submitted')
                    ->body('Your request to change the lead owner has been submitted to the manager.')
                    ->success()
                    ->send();

                if ($manager) {
                    Notification::make()
                        ->title('New Lead Owner Change Request')
                        ->body(auth()->user()->name . ' requested to change the owner for Lead ID: ' . $record->id);
                }

                try {
                    $lead = $record;
                    $viewName = 'emails.change_lead_owner';

                    // Set fixed recipient
                    $recipients = collect([
                        (object)[
                            'email' => 'faiz@timeteccloud.com', // ✅ Your desired recipient
                            'name' => 'Faiz'
                        ]
                    ]);

                    foreach ($recipients as $recipient) {
                        $emailContent = [
                            'leadOwnerName' => $recipient->name ?? 'Unknown Person',
                            'lead' => [
                                'lead_code' => 'Website',
                                'lastName' => $lead->name ?? 'N/A',
                                'company' => $lead->companyDetail->company_name ?? 'N/A',
                                'companySize' => $lead->company_size ?? 'N/A',
                                'phone' => $lead->phone ?? 'N/A',
                                'email' => $lead->email ?? 'N/A',
                                'country' => $lead->country ?? 'N/A',
                                'products' => $lead->products ?? 'N/A',
                            ],
                            'remark' => $lead->remark ?? 'No remarks provided',
                            'formatted_products' => is_array($lead->formatted_products)
                                ? implode(', ', $lead->formatted_products)
                                : ($lead->formatted_products ?? 'N/A'),
                        ];

                        Mail::to($recipient->email)
                            ->send(new \App\Mail\ChangeLeadOwnerNotification($emailContent, $viewName));
                    }
                } catch (\Exception $e) {
                    Log::error("New Lead Email Error: {$e->getMessage()}");
                }
            });
    }

    public static function getViewReferralDetailsAction(): Action
    {
        return Action::make('view_referral_details')
            ->label('View Referral Details')
            ->icon('heroicon-o-document-text')
            ->color('success')
            ->visible(fn (Lead $record) => $record->lead_code === 'Refer & Earn')
            ->modalHeading('Referral Details')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalWidth('6xl')
            ->modalContent(function (Lead $record) {
                return view('filament.modals.referral-details', [
                    'record' => $record,
                ]);
            });
    }

    public static function getTimeSinceCreationAction(): Action
    {
        return Action::make('time_since_creation')
            ->label('View Period')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->modalHeading('View Period')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(function (Lead $record) {
                $createdAt = $record->created_at;
                $now = now();

                $diffInDays = $createdAt->diffInDays($now);
                $diffInHours = $createdAt->copy()->addDays($diffInDays)->diffInHours($now);
                $diffInMinutes = $createdAt->copy()->addDays($diffInDays)->addHours($diffInHours)->diffInMinutes($now);

                $humanReadable = $createdAt->diffForHumans();

                // Format detailed time breakdown
                $detailedBreakdown = '';
                if ($diffInDays > 0) {
                    $detailedBreakdown .= "{$diffInDays} day" . ($diffInDays > 1 ? 's' : '') . ", ";
                }
                if ($diffInHours > 0 || $diffInDays > 0) {
                    $detailedBreakdown .= "{$diffInHours} hour" . ($diffInHours > 1 ? 's' : '') . ", ";
                }
                $detailedBreakdown .= "{$diffInMinutes} minute" . ($diffInMinutes > 1 ? 's' : '');

                return view('filament.modals.time-since-creation', [
                    'record' => $record,
                    'created_at' => $createdAt->format('d M Y, h:i A'),
                    'human_readable' => $humanReadable,
                    'detailed_breakdown' => $detailedBreakdown,
                    'diff_in_days' => $diffInDays,
                    'diff_in_hours' => $diffInHours,
                    'diff_in_minutes' => $diffInMinutes,
                ]);
            });
    }

    public static function getBypassDuplicateAction()
    {
        return Action::make('bypassDuplicate')
            ->label('Bypass Duplicate Check')
            ->icon('heroicon-o-shield-exclamation')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Request Bypass Duplicate Checking')
            ->modalDescription('Submit a request to bypass duplicate checking for this lead. Admin approval is required.')
            ->form([
                Textarea::make('reason')
                    ->label('Reason for Bypass Request')
                    ->placeholder('Explain why this lead should bypass duplicate checking...')
                    ->required()
                    ->rows(4)
                    ->extraAlpineAttributes([
                        'x-on:input' => '
                            const start = $el.selectionStart;
                            const end = $el.selectionEnd;
                            const value = $el.value;
                            $el.value = value.toUpperCase();
                            $el.setSelectionRange(start, end);
                        '
                    ])
                    ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                    ->maxLength(500),
            ])
            ->action(function (Lead $record, array $data) {
                $user = auth()->user();

                // Find and store duplicate information
                $companyName = optional($record?->companyDetail)->company_name;
                $duplicateInfo = [];

                // Normalize company name for duplicate checking
                $normalizedCompanyName = null;
                if ($companyName) {
                    $normalizedCompanyName = strtoupper($companyName);
                    $normalizedCompanyName = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedCompanyName);
                    $normalizedCompanyName = preg_replace('/\s+/', ' ', $normalizedCompanyName);
                    $normalizedCompanyName = trim($normalizedCompanyName);
                }

                // Get all company names for fuzzy matching
                $allCompanyNames = Lead::query()
                    ->with('companyDetail')
                    ->whereHas('companyDetail')
                    ->get()
                    ->pluck('companyDetail.company_name', 'id')
                    ->filter();

                $fuzzyMatches = [];
                if ($normalizedCompanyName) {
                    foreach ($allCompanyNames as $leadId => $existingCompanyName) {
                        if ($leadId == $record->id) continue;

                        $normalizedExisting = strtoupper($existingCompanyName);
                        $normalizedExisting = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedExisting);
                        $normalizedExisting = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedExisting);
                        $normalizedExisting = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedExisting);
                        $normalizedExisting = preg_replace('/\s+/', ' ', $normalizedExisting);
                        $normalizedExisting = trim($normalizedExisting);

                        $distance = levenshtein($normalizedCompanyName, $normalizedExisting);
                        if ($distance > 0 && $distance < 3) {
                            $fuzzyMatches[] = $existingCompanyName;
                        }
                    }
                }

                // Find duplicate leads
                $duplicateLeads = Lead::query()
                    ->with('companyDetail')
                    ->where(function ($query) use ($record, $normalizedCompanyName, $fuzzyMatches) {
                        if ($normalizedCompanyName) {
                            $query->whereHas('companyDetail', function ($q) use ($normalizedCompanyName) {
                                $q->whereRaw("UPPER(TRIM(company_name)) LIKE ?", ['%' . $normalizedCompanyName . '%']);
                            });
                        }

                        if (!empty($fuzzyMatches)) {
                            $query->orWhereHas('companyDetail', function ($q) use ($fuzzyMatches) {
                                $q->whereIn('company_name', $fuzzyMatches);
                            });
                        }

                        if (!empty($record?->email)) {
                            $query->orWhere('email', $record->email)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('email', $record->email);
                                });
                        }

                        if (!empty($record?->companyDetail?->email)) {
                            $query->orWhere('email', $record->companyDetail->email)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('email', $record->companyDetail->email);
                                });
                        }

                        if (!empty($record?->phone)) {
                            $query->orWhere('phone', $record->phone)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('contact_no', $record->phone);
                                });
                        }

                        if (!empty($record?->companyDetail?->contact_no)) {
                            $query->orWhere('phone', $record->companyDetail->contact_no)
                                ->orWhereHas('companyDetail', function ($q) use ($record) {
                                    $q->where('contact_no', $record->companyDetail->contact_no);
                                });
                        }
                    })
                    ->where('id', '!=', optional($record)->id)
                    ->get();

                // Build duplicate info array
                if ($duplicateLeads->isNotEmpty()) {
                    foreach ($duplicateLeads as $duplicateLead) {
                        $duplicateInfo[] = [
                            'lead_id' => $duplicateLead->id,
                            'company_name' => $duplicateLead->companyDetail->company_name ?? 'Unknown Company',
                            'lead_code' => $duplicateLead->lead_code,
                            'email' => $duplicateLead->email,
                            'phone' => $duplicateLead->phone,
                            'lead_owner' => $duplicateLead->lead_owner,
                            'categories' => $duplicateLead->categories,
                            'created_at' => $duplicateLead->created_at->format('Y-m-d H:i:s'),
                            'match_type' => self::getDuplicateMatchType($record, $duplicateLead, $normalizedCompanyName, $fuzzyMatches),
                        ];
                    }
                }

                // Create bypass request with duplicate info
                Request::create([
                    'lead_id' => $record->id,
                    'requested_by' => $user->id,
                    'current_owner_id' => null,
                    'requested_owner_id' => $user->id,
                    'reason' => $data['reason'],
                    'status' => 'pending',
                    'request_type' => 'bypass_duplicate',
                    'duplicate_info' => json_encode([
                        'current_lead' => [
                            'lead_id' => $record->id,
                            'company_name' => $companyName,
                            'email' => $record->email,
                            'phone' => $record->phone,
                            'lead_code' => $record->lead_code,
                        ],
                        'duplicates_found' => $duplicateInfo,
                        'total_duplicates' => count($duplicateInfo),
                        'checked_at' => now()->format('Y-m-d H:i:s'),
                    ]),
                ]);

                // Log activity
                activity()
                    ->causedBy($user)
                    ->performedOn($record)
                    ->withProperties([
                        'reason' => $data['reason'],
                        'request_type' => 'bypass_duplicate',
                        'duplicates_count' => count($duplicateInfo),
                    ])
                    ->log('Requested bypass duplicate checking');

                Notification::make()
                    ->title('Bypass Request Submitted')
                    ->body('Your request has been submitted and is pending admin approval.')
                    ->success()
                    ->send();
            });
    }

    // Add this helper method if it doesn't exist
    private static function getDuplicateMatchType($currentLead, $duplicateLead, $normalizedCompanyName, $fuzzyMatches)
    {
        $matchTypes = [];

        // Check company name match
        $duplicateCompanyName = optional($duplicateLead->companyDetail)->company_name;
        if ($duplicateCompanyName) {
            $normalizedDuplicateCompany = strtoupper($duplicateCompanyName);
            $normalizedDuplicateCompany = preg_replace('/\b(SDN\.?\s*BHD\.?|SDN|BHD|BERHAD|SENDIRIAN BERHAD)\b/i', '', $normalizedDuplicateCompany);
            $normalizedDuplicateCompany = preg_replace('/^\s*(\[.*?\]|\(.*?\)|WEBINAR:|MEETING:)\s*/', '', $normalizedDuplicateCompany);
            $normalizedDuplicateCompany = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedDuplicateCompany);
            $normalizedDuplicateCompany = preg_replace('/\s+/', ' ', $normalizedDuplicateCompany);
            $normalizedDuplicateCompany = trim($normalizedDuplicateCompany);

            if ($normalizedCompanyName && strpos($normalizedDuplicateCompany, $normalizedCompanyName) !== false) {
                $matchTypes[] = 'company_name_exact';
            } elseif (in_array($duplicateCompanyName, $fuzzyMatches)) {
                $matchTypes[] = 'company_name_fuzzy';
            }
        }

        // Check email match
        if (!empty($currentLead->email) &&
            ($currentLead->email == $duplicateLead->email ||
            $currentLead->email == optional($duplicateLead->companyDetail)->email)) {
            $matchTypes[] = 'email';
        }

        if (!empty(optional($currentLead->companyDetail)->email) &&
            (optional($currentLead->companyDetail)->email == $duplicateLead->email ||
            optional($currentLead->companyDetail)->email == optional($duplicateLead->companyDetail)->email)) {
            $matchTypes[] = 'company_email';
        }

        // Check phone match
        if (!empty($currentLead->phone) &&
            ($currentLead->phone == $duplicateLead->phone ||
            $currentLead->phone == optional($duplicateLead->companyDetail)->contact_no)) {
            $matchTypes[] = 'phone';
        }

        if (!empty(optional($currentLead->companyDetail)->contact_no) &&
            (optional($currentLead->companyDetail)->contact_no == $duplicateLead->phone ||
            optional($currentLead->companyDetail)->contact_no == optional($duplicateLead->companyDetail)->contact_no)) {
            $matchTypes[] = 'company_phone';
        }

        return implode(', ', $matchTypes ?: ['unknown']);
    }
}
