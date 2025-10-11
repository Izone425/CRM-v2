<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // First, let's check if the table exists and handle accordingly
        if (Schema::hasTable('e_invoice_details')) {
            // Get existing data if any

            // Drop the existing table to recreate with new structure
            Schema::dropIfExists('e_invoice_details');
        }

        // Create new table with updated structure
        Schema::create('e_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');

            // Company Information
            $table->string('company_name');
            $table->string('business_register_number');
            $table->string('tax_identification_number');

            // Address Information
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('postcode');
            $table->string('city');
            $table->string('state')->default('Selangor');
            $table->string('country')->default('Malaysia');

            // Business Configuration
            $table->enum('business_category', ['business', 'government'])->default('business');
            $table->enum('currency', ['MYR', 'USD'])->default('MYR');
            $table->enum('business_type', ['local_business', 'foreign_business'])->default('local_business');
            $table->string('msic_code', 5)->nullable(); // Maximum 5 digits
            $table->enum('billing_category', ['billing_to_subscriber', 'billing_to_reseller'])->default('billing_to_subscriber');

            $table->timestamps();

            // Indexes
            $table->index('lead_id');
            $table->index('company_name');
            $table->index('business_register_number');
        });

        // If there was existing data, you could restore relevant fields here
        // This is optional and depends on your needs
    }

    public function down()
    {
        Schema::dropIfExists('e_invoice_details');

        // Recreate old structure if needed (optional)
        Schema::create('e_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
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
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->timestamps();
        });
    }
};
