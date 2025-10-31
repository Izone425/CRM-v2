<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('project_task_id'); // Reference to master task
            $table->date('plan_start_date')->nullable();
            $table->date('plan_end_date')->nullable();
            $table->integer('plan_duration')->nullable(); // in days
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->integer('actual_duration')->nullable(); // in days
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->integer('percentage')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('project_task_id')->references('id')->on('project_tasks')->onDelete('cascade');

            // Unique constraint - one plan per lead per task
            $table->unique(['lead_id', 'project_task_id']);

            // Indexes
            $table->index(['lead_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_plans');
    }
};
