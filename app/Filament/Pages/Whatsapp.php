<?php
namespace App\Filament\Pages;

use App\Classes\Encryptor;
use App\Models\ChatMessage;
use App\Models\ActivityLog;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\WithPolling;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class Whatsapp extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static string $view = 'filament.pages.whatsapp';
    protected ?string $heading = '';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Whatsapp';


    public string $to = ''; // ✅ Ensure it's a string
    public string $message = ''; // ✅ Ensure it's a string
    public $file;
    public $selectedChat = null;
    public bool $filterUnreplied = false; // ✅ Default: Show all chats
    public string $selectedLeadOwner = '';

    public int $contactsLimit = 15;
    public int $filteredContactsCount = 0;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $searchCompany = '';
    public string $searchPhone = '';

    public string $errorMessage = '';
    public bool $showError = false;
    public int $errorTimestamp = 0;

    public function mount()
    {
        $this->endDate = Carbon::now()->toDateString(); // default to today
        $this->startDate = Carbon::now()->subWeek()->toDateString();
    }

    public function loadMoreContacts()
    {
        $this->contactsLimit += 15;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.chat-room');
    }

    public function getTotalContactsCountProperty()
    {
        return ChatMessage::selectRaw('LEAST(sender, receiver) AS user1, GREATEST(sender, receiver) AS user2')
            ->groupBy('user1', 'user2')
            ->get()
            ->count();
    }

    public function updatedStartDate()
    {
        $this->fetchContacts();
    }

    public function updatedEndDate()
    {
        $this->fetchContacts();
    }

    public function selectChat($user1, $user2)
    {
        $this->selectedChat = [
            'user1' => $user1,
            'user2' => $user2
        ];

        // Get the participant phone number
        $twilioNumber = preg_replace('/^whatsapp:\+?/', '', env('TWILIO_WHATSAPP_FROM'));
        $cleanUser1 = preg_replace('/^\+/', '', $user1);
        $cleanUser2 = preg_replace('/^\+/', '', $user2);
        $chatParticipant = ($cleanUser1 === $twilioNumber) ? $cleanUser2 : $cleanUser1;

        // Check if we can send freeform messages
        $whatsappService = new WhatsAppService();
        $canSendFreeform = $whatsappService->canSendFreeformMessage($chatParticipant);

        // Set the error message if outside the 24-hour window
        if (!$canSendFreeform) {
            $this->errorMessage = 'Cannot send message: The 24-hour customer service window has expired. Please use a template message instead.';
            $this->showError = true;
            $this->errorTimestamp = time();
        } else {
            // Clear any existing error
            $this->showError = false;
            $this->errorMessage = '';
        }
    }

    public function markMessagesAsRead($data, $forceState = null)
    {
        $user1 = $data['user1'] ?? null;
        $user2 = $data['user2'] ?? null;

        if (!$user1 || !$user2) {
            return;
        }

        // Clean phone numbers by removing any prefixes
        $user1 = preg_replace('/[^0-9]/', '', $user1);
        $user2 = preg_replace('/[^0-9]/', '', $user2);

        // Get our Twilio WhatsApp number (or whatever system number you use)
        $twilioNumber = preg_replace('/[^0-9]/', '', env('TWILIO_WHATSAPP_FROM', ''));

        // Determine which user is the customer
        $customerNumber = ($user1 === $twilioNumber) ? $user2 : $user1;

        try {
            // First, check the current read status for toggle
            $hasUnreadMessages = \App\Models\ChatMessage::where('sender', $customerNumber)
                ->where('receiver', $twilioNumber)
                ->where('is_from_customer', true)
                ->where('is_read', false)
                ->exists();

            $markAsRead = $forceState ?? $hasUnreadMessages;

            if ($markAsRead) {
                // Mark as READ
                $updated = \App\Models\ChatMessage::where('sender', $customerNumber)
                    ->where('receiver', $twilioNumber)
                    ->where('is_from_customer', true)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);

                $actionText = 'marked as read';
                $actionType = 'success';
            } else {
                // Mark as UNREAD
                $updated = \App\Models\ChatMessage::where('sender', $customerNumber)
                    ->where('receiver', $twilioNumber)
                    ->where('is_from_customer', true)
                    ->update(['is_read' => false]);

                $actionText = 'marked as unread';
                $actionType = 'warning';
            }

            // Send notification
            $this->dispatch('notify', [
                'title' => 'Success',
                'message' => $updated . ' messages ' . $actionText,
                'type' => $actionType,
            ]);

            // Refresh the contacts list to update the UI
            $this->fetchContacts();

            // Send read state info to update button appearance
            $this->dispatch('read-state-updated', [
                'isRead' => $markAsRead,
                'user1' => $data['user1'],
                'user2' => $data['user2'],
                'hasUnread' => !$markAsRead,
            ]);
        } catch (\Exception $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error('Error toggling read status: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());

            // Send error notification
            $this->dispatch('notify', [
                'title' => 'Error',
                'message' => 'Failed to update read status: ' . $e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    public function checkHasUnreadMessages($user1, $user2)
    {
        // Clean phone numbers
        $user1 = preg_replace('/[^0-9]/', '', $user1);
        $user2 = preg_replace('/[^0-9]/', '', $user2);

        // Get Twilio number
        $twilioNumber = preg_replace('/[^0-9]/', '', env('TWILIO_WHATSAPP_FROM', ''));

        // Determine which user is the customer
        $customerNumber = ($user1 === $twilioNumber) ? $user2 : $user1;

        // Check for unread messages
        $hasUnread = \App\Models\ChatMessage::where('sender', $customerNumber)
            ->where('receiver', $twilioNumber)
            ->where('is_from_customer', true)
            ->where('is_read', false)
            ->exists();

        return $hasUnread;
    }

    public function checkNewMessages()
    {
        // Count unread messages from customers
        $newMessages = ChatMessage::where('is_from_customer', true)
            ->where('is_read', false) // Optional: If tracking read/unread messages
            ->count();

        if ($newMessages > 0) {
            Notification::make()
                ->title('New Customer Message')
                ->body("You have $newMessages new message(s). Click to view.")
                ->success()
                // ->actions([
                //     \Filament\Notifications\Actions\Action::make('View Messages')
                //         ->url(route('filament.admin.resources.chat-messages.index')) // Adjust route
                //         ->button()
                // ])
                ->send();
        }
    }

    public function fetchMessages()
    {
        if (!$this->selectedChat) return [];

        return ChatMessage::where(function ($query) {
                $query->where('sender', $this->selectedChat['user1'])
                    ->where('receiver', $this->selectedChat['user2']);
            })
            ->orWhere(function ($query) {
                $query->where('sender', $this->selectedChat['user2'])
                    ->where('receiver', $this->selectedChat['user1']);
            })
            ->oldest()
            ->get();
    }

    public function fetchContacts()
    {
        $query = ChatMessage::selectRaw('
                LEAST(sender, receiver) AS user1,
                GREATEST(sender, receiver) AS user2,
                MAX(created_at) as last_message_time
            ')
            ->groupBy('user1', 'user2');

        // Date filter
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (!empty($this->selectedLeadOwner)) {
            $query->where(function ($q) {
                $twilioNumber = preg_replace('/^whatsapp:\+?/', '', env('TWILIO_WHATSAPP_FROM'));

                // Filter chats where either sender or receiver is a lead with matching lead_owner
                $q->whereIn(DB::raw("LEAST(sender, receiver)"), function ($sub) use ($twilioNumber) {
                    $sub->select('phone')
                        ->from('leads')
                        ->when($this->selectedLeadOwner === 'none', fn ($query) => $query->whereNull('lead_owner'))
                        ->when($this->selectedLeadOwner !== 'none', fn ($query) => $query->where('lead_owner', $this->selectedLeadOwner));
                })
                ->orWhereIn(DB::raw("GREATEST(sender, receiver)"), function ($sub) use ($twilioNumber) {
                    $sub->select('phone')
                        ->from('leads')
                        ->when($this->selectedLeadOwner === 'none', fn ($query) => $query->whereNull('lead_owner'))
                        ->when($this->selectedLeadOwner !== 'none', fn ($query) => $query->where('lead_owner', $this->selectedLeadOwner));
                });
            });
        }

        if (!empty($this->searchCompany)) {
            $companyNames = CompanyDetail::where('company_name', 'LIKE', '%' . $this->searchCompany . '%')
                ->pluck('contact_no')
                ->merge(
                    Lead::whereHas('companyDetail', fn ($q) => $q->where('company_name', 'LIKE', '%' . $this->searchCompany . '%'))
                        ->pluck('phone')
                )
                ->unique();

            $query->where(function ($q) use ($companyNames) {
                $q->whereIn(DB::raw("LEAST(sender, receiver)"), $companyNames)
                  ->orWhereIn(DB::raw("GREATEST(sender, receiver)"), $companyNames);
            });
        }

        // Then modify the fetchContacts method where it uses this property (around line 154-168)
        if (!empty($this->searchPhone)) {
            // Search by phone number directly in sender/receiver or in lead/company records
            $phoneSearch = '%' . $this->searchPhone . '%';

            $query->where(function ($q) use ($phoneSearch) {
                // Search in chat messages (sender or receiver)
                $q->where(DB::raw("LEAST(sender, receiver)"), 'LIKE', $phoneSearch)
                ->orWhere(DB::raw("GREATEST(sender, receiver)"), 'LIKE', $phoneSearch)
                // Search in leads
                ->orWhereExists(function ($subq) use ($phoneSearch) {
                    $subq->select(DB::raw(1))
                        ->from('leads')
                        ->whereRaw('leads.phone LIKE ?', [$phoneSearch])
                        ->whereRaw('(leads.phone = LEAST(chat_messages.sender, chat_messages.receiver) OR leads.phone = GREATEST(chat_messages.sender, chat_messages.receiver))');
                })
                // Search in company details
                ->orWhereExists(function ($subq) use ($phoneSearch) {
                    $subq->select(DB::raw(1))
                        ->from('company_details')
                        ->whereRaw('company_details.contact_no LIKE ?', [$phoneSearch])
                        ->whereRaw('(company_details.contact_no = LEAST(chat_messages.sender, chat_messages.receiver) OR company_details.contact_no = GREATEST(chat_messages.sender, chat_messages.receiver))');
                });
            });
        }
        // SQL-level filter for unreplied messages
        if ($this->filterUnreplied) {
            $query->where('chat_messages.is_from_customer', true)
                ->where('chat_messages.is_read', false)
                ->whereNotExists(function ($sub) {
                    $sub->selectRaw(1)
                        ->from('chat_messages as replies')
                        ->whereColumn('replies.sender', 'chat_messages.receiver')
                        ->whereColumn('replies.receiver', 'chat_messages.sender')
                        ->where('replies.created_at', '>', DB::raw('chat_messages.created_at'))
                        ->where('replies.is_from_customer', false);
                });
        }

        $contacts = $query->orderByDesc('last_message_time')
            ->limit($this->contactsLimit)
            ->get()
            ->map(function ($chat) {
                // Get the last message in this chat pair
                $lastMessage = ChatMessage::where(function ($query) use ($chat) {
                        $query->where('sender', $chat->user1)
                            ->where('receiver', $chat->user2);
                    })
                    ->orWhere(function ($query) use ($chat) {
                        $query->where('sender', $chat->user2)
                            ->where('receiver', $chat->user1);
                    })
                    ->latest()
                    ->first();

                $chat->latest_message = $lastMessage->message ?? null;
                $chat->is_from_customer = $lastMessage->is_from_customer ?? null;
                $chat->is_read = $lastMessage->is_read ?? null;

                $hasNoReply = ChatMessage::where('is_read', false)
                    ->where('sender', $chat->user2)
                    ->where('receiver', $chat->user1)
                    ->where('is_from_customer', true)
                    ->exists();

                $chat->has_no_reply = $lastMessage->is_from_customer && $hasNoReply;

                $twilioNumber = preg_replace('/^whatsapp:\+?/', '', env('TWILIO_WHATSAPP_FROM'));
                $user1 = preg_replace('/^\+/', '', $chat->user1);
                $user2 = preg_replace('/^\+/', '', $chat->user2);
                $chatParticipant = ($user1 === $twilioNumber) ? $user2 : $user1;

                $lead = Lead::where('phone', $chatParticipant)->first();
                if ($lead) {
                    $chat->participant_name = $lead->companyDetail->name ?? $lead->name;
                } else {
                    $company = CompanyDetail::where('contact_no', $chatParticipant)->first();
                    $chat->participant_name = $company->name ?? $chatParticipant;
                }

                return $chat;
            });

        $this->filteredContactsCount = $contacts->count();

        return $contacts->take($this->contactsLimit);
    }

    public function fetchParticipantDetails()
    {
        if (!$this->selectedChat || !isset($this->selectedChat['user1'], $this->selectedChat['user2'])) {
            return $this->defaultParticipantResponse('Unknown');
        }

        // Clean Twilio number and participants
        $twilioNumber = preg_replace('/^whatsapp:\+?/', '', env('TWILIO_WHATSAPP_FROM'));
        $user1 = preg_replace('/^\+/', '', $this->selectedChat['user1']);
        $user2 = preg_replace('/^\+/', '', $this->selectedChat['user2']);

        $chatParticipant = ($user1 === $twilioNumber) ? $user2 : $user1;

        // CHECK MESSAGING WINDOW HERE
        // Check if we can send freeform messages to this participant
        $whatsappService = new WhatsAppService();
        $canSendFreeform = $whatsappService->canSendFreeformMessage($chatParticipant);

        // Set the error message if the participant is outside the 24-hour window
        if (!$canSendFreeform) {
            $this->errorMessage = 'Cannot send message: The 24-hour customer service window has expired. Please use a template message instead.';
            $this->showError = true;
            $this->errorTimestamp = time(); // Store current time

            // Dispatch event to keep error visible
            $this->dispatch('persistent-error', [
                'message' => $this->errorMessage,
                'duration' => 60 // Show for 60 seconds
            ]);
        } else {
            // Clear any existing error if the participant is within the window
            $this->showError = false;
            $this->errorMessage = '';
        }

        // Rest of your existing fetchParticipantDetails code
        // Array to store all found leads
        $foundLeads = [];
        $primaryLead = null;

        // STEP 1: Try to find lead by phone
        $leadsByPhone = \App\Models\Lead::with('companyDetail')
            ->where('phone', $chatParticipant)
            ->orderBy('id', 'desc') // Most recent first
            ->get();

        if ($leadsByPhone->isNotEmpty()) {
            foreach ($leadsByPhone as $lead) {
                $foundLeads[] = $lead;
            }
            $primaryLead = $leadsByPhone->first(); // Use most recent as primary
        }

        // STEP 2: If not found, try finding via company contact_no
        if (empty($foundLeads)) {
            $companiesByContactNo = \App\Models\CompanyDetail::where('contact_no', $chatParticipant)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($companiesByContactNo as $company) {
                if ($company->lead) {
                    $foundLeads[] = $company->lead->load('companyDetail');
                }
            }

            if (!empty($foundLeads)) {
                $primaryLead = $foundLeads[0]; // Use first one as primary
            }
        }

        // STEP 3: Fallback - Create lead if message found and no lead exists
        if (empty($foundLeads) && $chatParticipant !== $twilioNumber && strtolower($chatParticipant) !== 'unknown') {
            $lastMessage = \App\Models\ChatMessage::where(function ($query) use ($chatParticipant) {
                $query->where('sender', $chatParticipant)
                    ->orWhere('receiver', $chatParticipant);
            })->latest()->first();

            if ($lastMessage) {
                // Create the Lead
                $newLead = \App\Models\Lead::create([
                    'name' => $lastMessage->profile_name ?? 'Unknown',
                    'phone' => $chatParticipant,
                    'company_size' => '1-24',
                    'categories' => 'New',
                    'stage' => 'New',
                    'lead_status' => 'None',
                    'lead_code' => 'WhatsApp - TimeTec',
                ]);

                // Create the CompanyDetail
                $companyDetail = \App\Models\CompanyDetail::create([
                    'lead_id' => $newLead->id,
                    'contact_no' => $chatParticipant,
                    'company_name' => $lastMessage->profile_name ?? 'Unknown',
                    'name' => $lastMessage->profile_name ?? 'Unknown',
                ]);

                // Update the lead's `company_name` field with the company_detail id
                $newLead->company_name = $companyDetail->id;
                $newLead->saveQuietly();

                // Load the relationship for return use
                $newLead->load('companyDetail');
                $foundLeads[] = $newLead;
                $primaryLead = $newLead;

                $latestActivityLog = ActivityLog::where('subject_id', $newLead->id)
                    ->orderByDesc('created_at')
                    ->first();

                // Update the latest activity log description
                if ($latestActivityLog) {
                    $latestActivityLog->update([
                        'description' => 'New lead created from WhatsApp',
                    ]);
                }
            }
        }

        // STEP 4: Build final response with slash-separated names if needed
        if ($primaryLead) {
            $namesList = [];

            // Add names from all found leads, with newest first
            foreach ($foundLeads as $lead) {
                $namesList[] = $lead->companyDetail->name ?? $lead->name ?? 'Unknown';
            }

            // Create a slash-separated list of unique names
            $uniqueNames = array_unique($namesList);
            $displayName = implode(' / ', $uniqueNames);

            return [
                'name' => $displayName, // Slash-separated list
                'email' => $primaryLead->companyDetail->email ?? $primaryLead->email ?? 'N/A',
                'phone' => $chatParticipant ?? $primaryLead->companyDetail->contact_no ?? $primaryLead->phone,
                'company' => $primaryLead->companyDetail->company_name ?? 'N/A',
                'company_url' => url('admin/leads/' . Encryptor::encrypt($primaryLead->id)),
                'source' => $primaryLead->lead_code ?? 'N/A',
                'lead_status' => $primaryLead->lead_status ?? 'N/A',
                'can_send_freeform' => $canSendFreeform, // Add this to the response
            ];
        }

        // STEP 5: No match found at all
        return $this->defaultParticipantResponse($chatParticipant);
    }

    // Reusable fallback
    private function defaultParticipantResponse($phone)
    {
        // Check if we can send freeform messages
        $whatsappService = new WhatsAppService();
        $canSendFreeform = $phone ? $whatsappService->canSendFreeformMessage($phone) : false;

        return [
            'name' => 'Unknown',
            'email' => 'N/A',
            'phone' => $phone,
            'company' => 'N/A',
            'company_url' => null,
            'source' => 'Not Found',
            'lead_status' => 'N/A',
            'can_send_freeform' => $canSendFreeform, // Add this to the response
        ];
    }

    public function sendMessage()
    {
        if (empty($this->message) && !$this->file) {
            session()->flash('error', 'Please enter a message or attach a file.');
            return;
        }

        if ($this->selectedChat) {
            // Clean Twilio WhatsApp number (remove "whatsapp:" and "+")
            $twilioNumber = preg_replace('/^whatsapp:\+?/', '', env('TWILIO_WHATSAPP_FROM'));

            // Clean user1 and user2 (remove "+" if present)
            $user1 = preg_replace('/^\+/', '', $this->selectedChat['user1']);
            $user2 = preg_replace('/^\+/', '', $this->selectedChat['user2']);

            // Compare properly and assign recipient
            $recipient = ($user1 === $twilioNumber) ? $user2 : $user1;
        } else {
            $recipient = $this->to;
        }

        // Handle File Upload
        $fileUrl = null;
        $fileMimeType = null;

        if ($this->file) {
            $path = $this->file->store('uploads', 'public');
            $fileUrl = Storage::url($path);
            $fileMimeType = $this->file->getMimeType();
            $fileUrl = asset(str_replace('public/', 'storage/', $fileUrl));
        }

        if (!$recipient) {
            session()->flash('error', 'No recipient selected');
            return;
        }

        // Send Message via WhatsApp API
        $whatsappService = new WhatsAppService();

        // Attempt to send message/file
        if ($fileUrl) {
            $result = $whatsappService->sendFile($recipient, $fileUrl, $fileMimeType);
        } else {
            $result = $whatsappService->sendMessage($recipient, $this->message);
        }

        // Handle the response from the service
        if (is_array($result) && isset($result['success'])) {
            if ($result['success'] === true) {
                // Message sent successfully
                ChatMessage::create([
                    'sender' => preg_replace('/^\+|^whatsapp:/', '', env('TWILIO_WHATSAPP_FROM')),
                    'receiver' => preg_replace('/^\+|^whatsapp:/', '', $recipient),
                    'message' => $this->message ?: '[File Sent]',
                    'twilio_message_id' => $result['sid'],
                    'is_from_customer' => false,
                    'media_url' => $fileUrl,
                    'media_type' => $fileMimeType,
                ]);

                $this->dispatch('messageSent');
                $this->reset(['message', 'file']);

            } else {
                // Message failed
                if (isset($result['error']) && $result['error'] === '24_hour_window_closed') {
                    // Special handling for 24-hour window error
                    $this->dispatchTemplateMessageModal($recipient);

                    // Set persistent error message properties
                    $this->errorMessage = 'Cannot send message: The 24-hour customer service window has expired. Please use a template message instead.';
                    $this->showError = true;
                    $this->errorTimestamp = time(); // Store current time

                    // Dispatch event to trigger JavaScript timer
                    $this->dispatch('persistent-error', [
                        'message' => $this->errorMessage,
                        'duration' => 60 // Show for 60 seconds
                    ]);
                } else {
                    // Other error
                    $this->errorMessage = $result['message'] ?? 'Failed to send message';
                    $this->showError = true;
                    $this->errorTimestamp = time();

                    // Dispatch event to trigger JavaScript timer
                    $this->dispatch('persistent-error', [
                        'message' => $this->errorMessage,
                        'duration' => 30 // Show for 30 seconds
                    ]);
                }
            }
        } else {
            // Legacy response handling (for backward compatibility)
            if ($result) {
                ChatMessage::create([
                    'sender' => preg_replace('/^\+|^whatsapp:/', '', env('TWILIO_WHATSAPP_FROM')),
                    'receiver' => preg_replace('/^\+|^whatsapp:/', '', $recipient),
                    'message' => $this->message ?: '[File Sent]',
                    'twilio_message_id' => $result,
                    'is_from_customer' => false,
                    'media_url' => $fileUrl,
                    'media_type' => $fileMimeType,
                ]);

                $this->dispatch('messageSent');
                $this->reset(['message', 'file']);
            } else {
                session()->flash('error', 'Failed to send message');
            }
        }
    }

    public function dispatchTemplateMessageModal($recipient)
    {
        // Get the recipient's lead details
        $recipientDetails = $this->fetchParticipantDetails();

        // Dispatch browser event to open template modal
        $this->dispatch('open-template-modal', [
            'recipient' => $recipient,
            'name' => $recipientDetails['name'],
            'company' => $recipientDetails['company']
        ]);
    }

    public function sendTemplateMessage($phoneNumber, $templateId)
    {
        // Validate inputs
        if (empty($phoneNumber) || empty($templateId)) {
            session()->flash('error', 'Missing required information to send template message');
            return;
        }

        try {
            // Get recipient details
            $details = $this->fetchParticipantDetails();
            $recipientName = $details['name'];

            // Set up template variables
            $variables = [];

            // Configure variables based on template ID
            if ($templateId === 'HX50b95050ff8d2fe33edf0873c4d2e2b4') {
                // Request Details Template
                $variables = [$recipientName];
            } elseif ($templateId === 'HX16773cfc70580af7cea0a8a5587486b5') {
                // Demo Selection Template
                $today = \Carbon\Carbon::now();
                $day1 = $today->copy()->addBusinessDay()->format('d/m (l)') . ' - 10:00 AM / 2:00 PM';
                $day2 = $today->copy()->addBusinessDays(2)->format('d/m (l)') . ' - 11:00 AM / 3:00 PM';

                $variables = [
                    $recipientName,
                    $day1 . "\n\n" . $day2
                ];
            }

            // Send the template message
            $whatsappController = new \App\Http\Controllers\WhatsAppController();
            $response = $whatsappController->sendWhatsAppTemplate($phoneNumber, $templateId, $variables);

            // Log success
            \Illuminate\Support\Facades\Log::info('Template message sent successfully', [
                'phone' => $phoneNumber,
                'templateId' => $templateId,
                'response' => $response
            ]);

            // Show success notification
            Notification::make()
                ->title('Template Message Sent')
                ->body('Template message has been sent successfully.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            // Log error
            \Illuminate\Support\Facades\Log::error('Error sending template message: ' . $e->getMessage());

            // Show error notification
            Notification::make()
                ->title('Error Sending Template')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // public static function getNavigationBadge(): ?string
    // {
    //     $chatPairs = ChatMessage::selectRaw('LEAST(sender, receiver) AS user1, GREATEST(sender, receiver) AS user2')
    //         ->groupBy('user1', 'user2')
    //         ->get();

    //     $unreadChats = $chatPairs->filter(function ($chat) {
    //         $lastMessage = ChatMessage::where(function ($query) use ($chat) {
    //                 $query->where('sender', $chat->user1)
    //                     ->where('receiver', $chat->user2);
    //             })
    //             ->orWhere(function ($query) use ($chat) {
    //                 $query->where('sender', $chat->user2)
    //                     ->where('receiver', $chat->user1);
    //             })
    //             ->latest()
    //             ->first();

    //         if (!$lastMessage || !$lastMessage->is_from_customer) {
    //             return false;
    //         }

    //         // Look for unread message from customer to system (i.e. no reply yet)
    //         return ChatMessage::where('sender', $chat->user2)
    //             ->where('receiver', $chat->user1)
    //             ->where('is_from_customer', true)
    //             ->where('is_read', false)
    //             ->exists();
    //     });

    //     return (string) $unreadChats->count();
    // }

    // public static function getNavigationBadgeColor(): ?string
    // {
    //     $chatPairs = ChatMessage::selectRaw('LEAST(sender, receiver) AS user1, GREATEST(sender, receiver) AS user2')
    //         ->groupBy('user1', 'user2')
    //         ->get();

    //     $hasUnread = $chatPairs->contains(function ($chat) {
    //         $lastMessage = ChatMessage::where(function ($query) use ($chat) {
    //                 $query->where('sender', $chat->user1)
    //                     ->where('receiver', $chat->user2);
    //             })
    //             ->orWhere(function ($query) use ($chat) {
    //                 $query->where('sender', $chat->user2)
    //                     ->where('receiver', $chat->user1);
    //             })
    //             ->latest()
    //             ->first();

    //         if (!$lastMessage || !$lastMessage->is_from_customer) {
    //             return false;
    //         }

    //         return ChatMessage::where('sender', $chat->user2)
    //             ->where('receiver', $chat->user1)
    //             ->where('is_from_customer', true)
    //             ->where('is_read', false)
    //             ->exists();
    //     });

    //     return $hasUnread ? 'danger' : null;
    // }
}
