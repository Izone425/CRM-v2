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
            Schema::create('implementer_appointments', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('lead_id');
                $table->string('type');
                $table->string('appointment_type')->default('ONLINE');
                $table->date('date');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('implementer');
                $table->timestamp('implementer_assigned_date')->nullable();
                $table->string('session')->nullable();
                $table->text('remarks')->nullable();
                $table->string('title')->nullable();
                $table->json('required_attendees')->nullable();
                $table->string('status')->default('New');
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('implementer_appointments');
    }
};
