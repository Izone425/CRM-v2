<?php
// Migration filephp artisan migrate --path=database/migrations/2025_05_02_092914_create_e_invoice_details_table.php

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
        Schema::create('e_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->string('pic_email')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('new_business_reg_no')->nullable();
            $table->string('old_business_reg_no')->nullable();
            $table->string('registration_name')->nullable();
            $table->string('identity_type')->nullable();
            $table->string('tax_classification')->nullable();
            $table->string('sst_reg_no')->nullable();
            $table->string('msic_code')->nullable();
            $table->string('msic_code_2')->nullable();
            $table->string('msic_code_3')->nullable();
            $table->text('business_address')->nullable();
            $table->string('postcode')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('MYS');
            $table->string('state')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_invoice_details');
    }
};
