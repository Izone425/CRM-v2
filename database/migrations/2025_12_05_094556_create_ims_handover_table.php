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
        Schema::create('ims_handover', function (Blueprint $table) {
            $table->id();
            $table->string('sender')->nullable();
            $table->text('sender_message')->nullable();
            $table->string('receiver')->nullable();
            $table->text('receiver_message')->nullable();
            $table->enum('status', ['Pending', 'Completed'])->default('Pending');
            $table->string('tracking_number')->unique()->nullable();
            $table->enum('consignment_status', ['Pending', 'Completed'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ims_handover');
    }
};
