<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('module'); // e.g., 'attendance', 'leave', 'payroll'
            $table->string('module_name'); // Display name
            $table->integer('module_percentage')->default(0); // Overall module weight
            $table->string('phase_name'); // e.g., 'Planning', 'Development', 'Testing'
            $table->string('task_name'); // Specific task name
            $table->integer('task_percentage')->default(0); // Task weight within module
            $table->integer('order')->default(0); // Sort order
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
