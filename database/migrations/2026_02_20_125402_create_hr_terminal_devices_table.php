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
        Schema::create('hr_terminal_devices', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('invoice_no')->nullable();
            $table->string('serial_no');
            $table->string('backend_device_id')->nullable();
            $table->string('status')->default('Enabled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_terminal_devices');
    }
};
