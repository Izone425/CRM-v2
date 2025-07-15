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
        Schema::create('system_questions', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id')->nullable();
            $table->string('modules')->nullable();
            $table->string('existing_system')->nullable();
            $table->string('usage_duration')->nullable();
            $table->date('expired_date')->nullable();
            $table->string('reason_for_change')->nullable();
            $table->string('staff_count')->nullable();
            $table->string('subsidiaries')->nullable();
            $table->string('branches')->nullable();
            $table->string('industry')->nullable();
            $table->string('additional')->nullable();
            $table->enum('status', ['Yes','No'])->nullable();
            $table->string('causer_name', 50)->nullable();
            $table->string('updated_at_phase_1')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_questions');
    }
};
