<?php
namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender',
        'receiver',
        'message',
        'twilio_message_id',
        'reply_to_sid',
        'profile_name',
        'is_from_customer',
        'is_read',
        'media_url',
        'media_type',
    ];

    // Automatically remove "+" and "whatsapp:" prefix from sender
    public function setSenderAttribute($value)
    {
        $this->attributes['sender'] = preg_replace('/^\+|^whatsapp:/', '', $value);
    }

    // Automatically remove "+" and "whatsapp:" prefix from receiver
    public function setReceiverAttribute($value)
    {
        $this->attributes['receiver'] = preg_replace('/^\+|^whatsapp:/', '', $value);
    }

    protected static function booted()
    {
        static::created(function ($message) {
            if ($message->is_from_customer) {
                Notification::make()
                    ->title('New Message from Customer')
                    ->body("You have a new message!")
                    ->success()
                    ->send();
            }
        });
    }

    public function markAsRead()
    {
        $this->update(['is_read' => true]); // ✅ Mark message as read
    }

    public function repliedMessage()
    {
        return $this->hasOne(ChatMessage::class, 'twilio_message_id', 'reply_to_sid');
    }
}
