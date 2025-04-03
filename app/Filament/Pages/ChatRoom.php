<?php
namespace App\Filament\Pages;

use App\Classes\Encryptor;
use App\Models\ChatMessage;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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

    public static function canAccess(): bool
    {
        return auth()->user()->role_id != '2';
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
        $contacts = ChatMessage::selectRaw('
                    LEAST(sender, receiver) AS user1,
                    GREATEST(sender, receiver) AS user2,
                    MAX(created_at) as last_message_time
                ')
                ->groupBy('user1', 'user2')
                ->orderByDesc('last_message_time')
                ->get()
                ->map(function ($chat) {
                    // Get the last message for display
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

                    // ✅ Check if the last message is from the customer AND has no reply yet
                    $hasNoReply = ChatMessage::where(function ($query) use ($chat) {
                            $query->where('is_read', false)
                                ->where('sender', $chat->user2)
                                ->where('receiver', $chat->user1)
                                ->where('is_from_customer', true); // Look for replies from system user
                        });

                    $chat->has_no_reply = $lastMessage->is_from_customer && $hasNoReply;

                    // Determine chat participant's name

                    // Remove "whatsapp:" and "+" from the Twilio number
                    $twilioNumber = preg_replace('/^whatsapp:\+?/', '', env('TWILIO_WHATSAPP_FROM'));

                    // Remove "+" from user1 (in case it's stored differently)
                    $user1 = preg_replace('/^\+/', '', $chat->user1);
                    $user2 = preg_replace('/^\+/', '', $chat->user2);

                    // Now compare properly
                    $chatParticipant = ($user1 === $twilioNumber) ? $user2 : $user1;

                    // Check in Leads table
                    $lead = Lead::where('phone', $chatParticipant)->first();
                    if ($lead) {
                        $chat->participant_name = $lead->companyDetail->name ?? $lead->name;
                    } else {
                        // Check in CompanyDetail table
                        $company = CompanyDetail::where('contact_no', $chatParticipant)->first();
                        $chat->participant_name = $company->name ?? $chatParticipant;
                    }

                    return $chat;
                });

        // ✅ Apply the filter only when the checkbox is checked
        if ($this->filterUnreplied) {
            $contacts = $contacts->filter(fn($chat) => $chat->has_no_reply);
        }

        if ($this->selectedLeadOwner) {
            $contacts = $contacts->filter(function ($chat) {
                $participant = $chat->participant_name;
                $lead = Lead::where('name', $participant)->where('lead_owner', $this->selectedLeadOwner)->first();
                return $lead !== null;
            });
        }

        return $contacts;
    }

    public function fetchParticipantDetails()
    {
        if (!$this->selectedChat || !isset($this->selectedChat['user1'], $this->selectedChat['user2'])) {
            return [
                'name' => 'Unknown',
                'email' => 'N/A',
                'phone' => 'N/A',
                'company' => 'N/A',
                'company_url' => null, // No link
                'source' => 'Not Found'
            ];
        }

        // Clean Twilio WhatsApp number (remove "whatsapp:" and "+")
        $twilioNumber = preg_replace('/^whatsapp:\+?/', '', env('TWILIO_WHATSAPP_FROM'));

        // Clean user1 and user2 (remove "+" if present)
        $user1 = preg_replace('/^\+/', '', $this->selectedChat['user1']);
        $user2 = preg_replace('/^\+/', '', $this->selectedChat['user2']);

        // Compare properly and assign chat participant
        $chatParticipant = ($user1 === $twilioNumber) ? $user2 : $user1;

        // Check in Leads table
        $lead = \App\Models\Lead::where('phone', $chatParticipant)->with('companyDetail')->first();
        if ($lead && $lead->companyDetail) {
            return [
                'name' => $lead->name ?? 'Unknown',
                'email' => $lead->email ?? 'N/A',
                'phone' => $lead->phone ?? 'N/A',
                'company' => $lead->companyDetail->company_name ?? 'N/A',
                'company_url' => url('admin/leads/' . Encryptor::encrypt($lead->id)), // ✅ Generate URL
                'source' => $lead->lead_code ?? 'N/A',
                'lead_status' => $lead->lead_status,
            ];
        }

        // Check in CompanyDetail table
        $company = \App\Models\CompanyDetail::where('contact_no', $chatParticipant)->first();
        if ($company) {
            return [
                'name' => $company->name,
                'email' => $company->email ?? 'N/A',
                'phone' => $company->contact_no ?? 'N/A',
                'company' => $company->company_name ?? 'N/A',
                'company_url' => url('admin/leads/' . Encryptor::encrypt($company->lead->id)), // ✅ Generate URL
                'source' => $company->lead->lead_code ?? 'N/A',
                'lead_status' => $company->lead->lead_status,
            ];
        }

        return [
            'name' => 'Unknown',
            'email' => 'N/A',
            'phone' => $chatParticipant,
            'company' => 'N/A',
            'company_url' => null,
            'source' => 'Not Found',
            'lead_status' => 'N/A',
        ];
    }

    public function sendMessage()
    {
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
                'media_url' => $fileUrl, // ✅ Store public file URL
                'media_type' => $fileMimeType, // ✅ Store file type
            ]);

            $this->reset(['message', 'file']);
            session()->flash('success', 'Message sent successfully!');
        } else {
            session()->flash('error', 'Failed to send message.');
        }
    }

    public static function getNavigationBadge(): ?string
    {
        // Get all distinct chat pairs with unread customer messages
        $unreadChats = ChatMessage::selectRaw('LEAST(sender, receiver) AS user1, GREATEST(sender, receiver) AS user2')
            ->where('is_from_customer', true)
            ->where('is_read', false)
            ->groupBy('user1', 'user2')
            ->get();

        return (string) $unreadChats->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $unreadCount = ChatMessage::where('is_from_customer', true)
            ->where('is_read', false)
            ->selectRaw('LEAST(sender, receiver) AS user1, GREATEST(sender, receiver) AS user2')
            ->groupBy('user1', 'user2')
            ->get()
            ->count();

        return $unreadCount > 0 ? 'danger' : null;
    }
}
