<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('caller_number');
            $table->string('receiver_number');
            $table->integer('call_duration');
            $table->string('call_status');  // completed, no-answer, busy, etc.
            $table->string('call_type');    // inbound, outbound
            $table->timestamp('started_at');
            $table->string('call_recording_url')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('call_logs');
    }
};
