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
        Schema::create('users_leave', function (Blueprint $table) {
            $table->id();
            $table->integer('user_ID');
            $table->string('leave_type');
            $table->date('date');
            $table->integer('day_of_week');
            $table->enum('session', ['full', 'am', 'pm'])->default('full');
            $table->enum('status', ['Approved', 'RequestCancel', 'Pending', 'PendingCancel'])->default('Pending');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_leave');
    }
};
