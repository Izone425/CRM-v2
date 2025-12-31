<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('training_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('handover_id')->unique();
            $table->foreignId('training_session_id')->constrained('training_sessions');
            $table->foreignId('lead_id')->constrained('leads');
            $table->enum('training_type', ['HRDF', 'WEBINAR']);
            $table->string('training_category')->nullable();
            $table->string('pic_name');
            $table->string('pic_email');
            $table->string('pic_phone');
            $table->enum('status', ['ACTIVE', 'BOOKED', 'CANCELLED', 'APPLY'])->default('ACTIVE');
            $table->string('submitted_by');
            $table->timestamp('submitted_at');
            $table->string('hrdf_application_status')->nullable();
            $table->timestamps();

            $table->index(['training_session_id', 'training_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_bookings');
    }
};
