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
            $table->integer('company_id');
            $table->date('training_date');
            $table->integer('pax_count');
            $table->enum('status', ['confirmed','cancelled','completed'])->default('confirmed');
            $table->text('additional_notes')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();
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
