<?php
// filepath: /var/www/html/timeteccrm/database/migrations/create_training_sessions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->enum('trainer_profile', ['TRAINER_1', 'TRAINER_2', 'TRAINER_3']);
            $table->integer('year');
            $table->enum('training_category', ['HRDF', 'WEBINAR']);
            $table->enum('training_module', ['OPERATIONAL', 'STRATEGIC']);
            $table->string('session_number'); // SESSION 1, SESSION 2, etc.

            // Combined session details
            $table->date('day1_date');
            $table->time('day1_start_time')->default('09:00:00');
            $table->time('day1_end_time')->default('17:00:00');
            $table->string('day1_module')->default('ATTENDANCE');
            $table->string('day1_deck_link')->nullable();
            $table->string('day1_meeting_link')->nullable();
            $table->string('day1_meeting_id')->nullable();
            $table->string('day1_meeting_password')->nullable();

            $table->date('day2_date');
            $table->time('day2_start_time')->default('09:00:00');
            $table->time('day2_end_time')->default('17:00:00');
            $table->string('day2_module')->default('LEAVE & CLAIM');
            $table->string('day2_deck_link')->nullable();
            $table->string('day2_meeting_link')->nullable();
            $table->string('day2_meeting_id')->nullable();
            $table->string('day2_meeting_password')->nullable();

            $table->date('day3_date');
            $table->time('day3_start_time')->default('09:00:00');
            $table->time('day3_end_time')->default('17:00:00');
            $table->string('day3_module')->default('PAYROLL');
            $table->string('day3_deck_link')->nullable();
            $table->string('day3_meeting_link')->nullable();
            $table->string('day3_meeting_id')->nullable();
            $table->string('day3_meeting_password')->nullable();

            $table->integer('max_participants')->default(50); // 50 for HRDF, 100 for WEBINAR
            $table->enum('status', ['DRAFT', 'SCHEDULED', 'COMPLETED', 'CANCELLED'])->default('DRAFT');
            $table->boolean('is_manual_schedule')->default(false);
            $table->timestamps();

            // Indexes
            $table->index(['year', 'trainer_profile']);
            $table->index(['year', 'training_category']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_sessions');
    }
};
