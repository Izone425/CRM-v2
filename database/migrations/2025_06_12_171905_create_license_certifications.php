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
        Schema::create('license_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
           $table->bigInteger('software_handover_id')->unsigned()->nullable(); // Assuming this is a foreign key to a users table
            $table->date('kick_off_date');
            $table->date('buffer_license_start');
            $table->date('buffer_license_end');
            $table->date('paid_license_start');
            $table->date('paid_license_end');
            $table->date('next_renewal_date');
            $table->integer('license_years');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_certificates');
    }
};
