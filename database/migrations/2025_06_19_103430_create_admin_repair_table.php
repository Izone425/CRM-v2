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
        Schema::create('admin_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('company_details');
            $table->string('pic_name');
            $table->string('pic_phone');
            $table->string('pic_email');
            $table->string('device_model');
            $table->string('device_serial');
            $table->text('remarks');
            $table->json('attachments')->nullable();
            $table->string('video_link')->nullable();
            $table->string('zoho_ticket')->nullable();
            $table->enum('status', ['New', 'In Progress', 'Awaiting Parts', 'Resolved', 'Closed'])->default('New');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_repairs');
    }
};
