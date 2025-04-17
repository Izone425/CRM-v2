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

class ChatRoom extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static string $view = 'filament.pages.chat-room';
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
        return auth()->user()->role_id != '2';
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

        ChatMessage::where('sender', $user1)
        ->where('receiver', $user2)
        ->where('is_from_customer', true)
        ->where('is_read', false)
        ->update(['is_read' => true]);
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

        // STEP 1: Try to find lead by phone
        $lead = \App\Models\Lead::with('companyDetail')
            ->where('phone', $chatParticipant)
            ->first();

        // STEP 2: If not found, try finding via company contact_no
        if (!$lead) {
            $company = \App\Models\CompanyDetail::where('contact_no', $chatParticipant)->first();

            if ($company && $company->lead) {
                $lead = $company->lead->load('companyDetail');
            }
        }

        // STEP 3: Fallback - Create lead if message found and no lead exists
        if (!$lead && $chatParticipant !== $twilioNumber && strtolower($chatParticipant) !== 'unknown') {
            $lastMessage = \App\Models\ChatMessage::where(function ($query) use ($chatParticipant) {
                $query->where('sender', $chatParticipant)
                    ->orWhere('receiver', $chatParticipant);
            })->latest()->first();

            if ($lastMessage) {
                // 1. Create the Lead
                $lead = \App\Models\Lead::create([
                    'name' => $lastMessage->profile_name ?? 'Unknown',
                    'phone' => $chatParticipant,
                    'categories' => 'New',
                    'stage' => 'New',
                    'lead_status' => 'None',
                    'lead_code' => 'WhatsApp - TimeTec',
                ]);

                // 2. Create the CompanyDetail
                $companyDetail = \App\Models\CompanyDetail::create([
                    'lead_id' => $lead->id,
                    'contact_no' => $chatParticipant,
                    'company_name' => 'Unknown',
                    'name' => $lastMessage->profile_name ?? 'Unknown',
                ]);

                // 3. Quietly update the lead's `company_name` field with the company_detail id
                $lead->company_name = $companyDetail->id;
                $lead->saveQuietly();

                // 4. Load the relationship for return use
                $lead->load('companyDetail');
            }

            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();

            // ✅ Update the latest activity log description
            if ($latestActivityLog) {
                $latestActivityLog->update([
                    'description' => 'New lead created from WhatsApp',
                ]);
            }
        }

        // STEP 4: Build final response
        if ($lead) {
            return [
                'name' => $lead->companyDetail->name
                    ?? $lead->name
                    ?? 'Unknown',
                'email' => $lead->companyDetail->email ?? $lead->email ?? 'N/A',
                'phone' => $chatParticipant ?? $lead->companyDetail->contact_no ?? $lead->phone,
                'company' => $lead->companyDetail->company_name ?? 'N/A',
                'company_url' => url('admin/leads/' . Encryptor::encrypt($lead->id)),
                'source' => $lead->lead_code ?? 'N/A',
                'lead_status' => $lead->lead_status ?? 'N/A',
            ];
        }

        // STEP 5: No match found at all
        return $this->defaultParticipantResponse($chatParticipant);
    }

    // Reusable fallback
    private function defaultParticipantResponse($phone)
    {
        return [
            'name' => 'Unknown',
            'email' => 'N/A',
            'phone' => $phone,
            'company' => 'N/A',
            'company_url' => null,
            'source' => 'Not Found',
            'lead_status' => 'N/A',
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
            $path = $this->file->store('uploads', 'public'); // ✅ Store in public disk
            $fileUrl = Storage::url($path); // ✅ Get accessible URL
            $fileMimeType = $this->file->getMimeType();

            // ✅ Convert local storage URL to a full URL
            $fileUrl = asset(str_replace('public/', 'storage/', $fileUrl));
        }

        if (!$recipient) {
            session()->flash('error', 'No recipient selected');
            return;
        }

        // Send Message via WhatsApp API
        $whatsappService = new WhatsAppService();

        if ($fileUrl) {
            // ✅ Send file if uploaded
            $result = $whatsappService->sendFile($recipient, $fileUrl, $fileMimeType);
        } else {
            // ✅ Send text message
            $result = $whatsappService->sendMessage($recipient, $this->message);
        }

        if ($result) {
            // Store message in database
            ChatMessage::create([
                'sender' => preg_replace('/^\+|^whatsapp:/', '', env('TWILIO_WHATSAPP_FROM')),
                'receiver' => preg_replace('/^\+|^whatsapp:/', '', $recipient),
                'message' => $this->message ?: '[File Sent]',
                'twilio_message_id' => $result,
                'is_from_customer' => false,
                'media_url' => $fileUrl,
                'media_type' => $fileMimeType,
            ]);
        }

        $this->dispatch('messageSent');
        $this->reset(['message', 'file']);
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
