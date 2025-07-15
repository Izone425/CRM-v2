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
        Schema::create('system_question_phase3s', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id')->nullable();
            $table->string('causer_name', 50)->nullable();
            $table->string('finalise')->nullable();
            $table->string('vendor', 100)->nullable();
            $table->string('additional')->nullable();
            $table->string('plan')->nullable();
            $table->string('percentage')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_question_phase3s');
    }
};
