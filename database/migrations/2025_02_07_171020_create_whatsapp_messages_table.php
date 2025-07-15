<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('sender'); // WhatsApp Number
            $table->string('receiver'); // Your WhatsApp Number
            $table->text('message'); // Message Content
            $table->string('voice_url')->nullable(); // URL for voice messages
            $table->string('twilio_message_id')->unique(); // Unique Twilio Message ID
            $table->string('profile_name')->nullable(); // Sender's WhatsApp Profile Name
            $table->boolean('is_from_customer')->default(true); // Determine if the message is incoming
            $table->boolean('is_read')->default(false); // Read status of the message
            $table->string('media_url')->nullable(); // URL for media files
            $table->string('media_type')->nullable(); // Type of media (image, video
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
