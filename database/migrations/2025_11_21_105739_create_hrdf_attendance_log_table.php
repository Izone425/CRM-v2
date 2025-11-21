<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrdf_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->date('training_date_1');
            $table->date('training_date_2');
            $table->date('training_date_3');
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['new', 'in_progress', 'completed', 'cancelled'])->default('new');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrdf_attendance_logs');
    }
};
