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
        Schema::create('software_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_handover_id')->constrained('software_handovers')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('files')->nullable(); // Store file info as JSON instead of separate columns
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_attachments'); // Fix the table name here
    }
};
