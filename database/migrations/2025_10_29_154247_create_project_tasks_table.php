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
            $table->enum('module', ['general', 'attendance', 'leave', 'claim', 'payroll']);
            $table->string('task_name');
            $table->integer('percentage')->default(0);
            $table->integer('order')->default(1);
            $table->timestamps();

            $table->index(['module', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
