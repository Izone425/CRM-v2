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
        Schema::create('training_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_booking_id')->constrained('training_bookings')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('ic_number')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->enum('attendance_status', ['REGISTERED', 'ATTENDED', 'NOT_ATTENDED', 'CANCELLED'])->default('REGISTERED');
            $table->timestamp('registered_at');
            $table->timestamps();

            $table->index('training_booking_id');
            $table->index('attendance_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_attendees');
    }
};
