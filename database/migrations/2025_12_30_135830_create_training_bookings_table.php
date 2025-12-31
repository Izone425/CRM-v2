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
            $table->unsignedBigInteger('training_session_id');
            $table->unsignedBigInteger('lead_id');
            $table->enum('training_type', ['HRDF', 'WEBINAR']);
            $table->enum('training_category', ['NEW_TRAINING', 'RE_TRAINING']);
            $table->string('pic_name');
            $table->string('pic_email');
            $table->string('pic_phone');
            $table->enum('status', ['ACTIVE', 'BOOKED', 'CANCELLED', 'APPLY'])->default('BOOKED');
            $table->string('submitted_by');
            $table->timestamp('submitted_at');
            $table->enum('hrdf_application_status', ['BOOKED', 'CANCEL', 'APPLY'])->default('BOOKED');
            $table->timestamps();

            $table->foreign('training_session_id')->references('id')->on('training_sessions')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');

            $table->index(['training_session_id', 'training_type']);
            $table->index(['status']);
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
