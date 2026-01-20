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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();

            // Website Information
            $table->string('website_name')->nullable();
            $table->string('website_url')->nullable();
            $table->string('admin_email')->nullable();
            $table->text('disallow_public_email')->nullable();
            $table->string('currency_order_page')->default('USD');
            $table->boolean('disallow_same_ip_signup')->default(false);

            // Payment Gateway
            $table->string('paypal_url')->nullable();
            $table->string('paypal_email')->nullable();
            $table->boolean('paypal_enable')->default(true);

            // Invoice Information
            $table->string('invoice_title')->nullable();
            $table->string('invoice_company_name')->nullable();
            $table->string('invoice_company_tel')->nullable();
            $table->string('invoice_fax_no')->nullable();
            $table->string('invoice_company_email')->nullable();
            $table->text('invoice_company_address')->nullable();
            $table->string('invoice_postcode')->nullable();
            $table->string('invoice_city')->nullable();
            $table->string('invoice_state')->nullable();
            $table->string('invoice_country')->default('Malaysia');
            $table->string('invoice_company_logo')->nullable();
            $table->boolean('include_bank_details')->default(true);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_beneficiary_name')->nullable();
            $table->string('bank_swift_code')->nullable();

            // Commission Settings
            $table->integer('distributor_commission_rate')->default(40);
            $table->integer('dealer_commission_rate')->default(20);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
