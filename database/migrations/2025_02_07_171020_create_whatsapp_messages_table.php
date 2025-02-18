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
            $table->string('twilio_message_id')->unique(); // Unique Twilio Message ID
            $table->string('profile_name')->nullable(); // Sender's WhatsApp Profile Name
            $table->boolean('is_from_customer')->default(true); // Determine if the message is incoming
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
