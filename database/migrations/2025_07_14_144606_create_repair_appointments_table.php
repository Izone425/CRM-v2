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
        Schema::create('repair_appointments', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id')->nullable();
            $table->integer('repair_handover_id')->nullable();

            $table->enum('type', ['NEW INSTALLATION','REPAIR','FINGERTEC TASK']);
            $table->enum('appointment_type', ['ONSITE']);

            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->string('technician')->nullable();
            $table->string('causer_id')->nullable();
            $table->string('remarks')->nullable();
            $table->string('title')->nullable();
            $table->string('required_attendees')->nullable();

            $table->enum('status', ['Done','Cancelled','New'])->default('New');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_appointments');
    }
};
