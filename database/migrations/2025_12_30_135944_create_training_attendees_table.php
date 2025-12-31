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
            $table->unsignedBigInteger('training_booking_id');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->enum('attendance_status', ['REGISTERED', 'ATTENDED', 'ABSENT', 'CANCELLED'])->default('REGISTERED');
            $table->timestamp('registered_at');
            $table->timestamps();

            $table->foreign('training_booking_id')->references('id')->on('training_bookings')->onDelete('cascade');

            $table->index(['training_booking_id', 'attendance_status']);
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
