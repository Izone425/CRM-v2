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

    public string $to = ''; // ✅ Ensure it's a string
    public string $message = ''; // ✅ Ensure it's a string
    public $file;
    public $selectedChat = null;

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
        return ChatMessage::selectRaw('
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

                    // Determine chat participant's name
                    $chatParticipant = ($chat->user1 === env('TWILIO_WHATSAPP_FROM')) ? $chat->user2 : $chat->user1;

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

        $chatParticipant = ($this->selectedChat['user1'] === env('TWILIO_WHATSAPP_FROM'))
            ? $this->selectedChat['user2']
            : $this->selectedChat['user1'];

        // Check in Leads table
        $lead = \App\Models\Lead::where('phone', $chatParticipant)->with('companyDetail')->first();
        if ($lead && $lead->companyDetail) {
            return [
                'name' => $lead->name ?? 'Unknown',
                'email' => $lead->email ?? 'N/A',
                'phone' => $lead->phone ?? 'N/A',
                'company' => $lead->companyDetail->company_name ?? 'N/A',
                'company_url' => url('admin/leads/' . Encryptor::encrypt($lead->id)), // ✅ Generate URL
                'source' => 'Lead'
            ];
        }

        // Check in CompanyDetail table
        $company = \App\Models\CompanyDetail::where('contact_no', $chatParticipant)->first();
        if ($company) {
            return [
                'name' => 'N/A',
                'email' => $company->email ?? 'N/A',
                'phone' => $company->contact_no ?? 'N/A',
                'company' => $company->company_name ?? 'N/A',
                'company_url' => null, // No lead, so no clickable link
                'source' => 'Company'
            ];
        }

        return [
            'name' => 'Unknown',
            'email' => 'N/A',
            'phone' => $chatParticipant,
            'company' => 'N/A',
            'company_url' => null,
            'source' => 'Not Found'
        ];
    }

    public function sendMessage()
    {
        if ($this->selectedChat) {
            $recipient = ($this->selectedChat['user1'] === env('TWILIO_WHATSAPP_FROM'))
                ? $this->selectedChat['user2']
                : $this->selectedChat['user1'];
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
}
