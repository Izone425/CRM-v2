<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('product');
            $table->string('module');
            $table->string('device_type')->nullable();
            $table->enum('priority', ['Low', 'Medium', 'High', 'Critical']);
            $table->string('company_name');
            $table->string('zoho_ticket_number')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['Open', 'In Progress', 'Resolved', 'Closed'])->default('Open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
