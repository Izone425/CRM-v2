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
            $table->string('company_name')->nullable();
            $table->string('website_url')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_logo')->nullable();

            // Payment Gateway
            $table->enum('payment_gateway', ['stripe', 'paypal', 'billplz', 'ipay88', 'senangpay', 'molpay', 'manual'])->default('manual');
            $table->string('gateway_api_key')->nullable();
            $table->string('gateway_secret_key')->nullable();
            $table->string('gateway_merchant_id')->nullable();
            $table->boolean('gateway_test_mode')->default(true);
            $table->text('gateway_webhook_url')->nullable();
            $table->json('gateway_settings')->nullable(); // Additional gateway-specific settings

            // Invoice Information
            $table->string('invoice_prefix')->default('INV');
            $table->integer('invoice_next_number')->default(1);
            $table->string('invoice_number_format')->default('INV-{YEAR}-{MONTH}-{NUMBER}'); // e.g., INV-2026-01-00001
            $table->integer('invoice_due_days')->default(30);
            $table->text('invoice_terms')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->string('invoice_currency')->default('MYR');
            $table->decimal('invoice_tax_rate', 5, 2)->default(0.00); // e.g., 6.00 for 6% tax
            $table->string('invoice_tax_label')->default('SST'); // e.g., SST, GST, VAT

            // Commission Settings
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('commission_rate', 8, 2)->default(0.00); // Percentage or fixed amount
            $table->decimal('reseller_commission_rate', 8, 2)->default(0.00);
            $table->decimal('distributor_commission_rate', 8, 2)->default(0.00);
            $table->decimal('referral_commission_rate', 8, 2)->default(0.00);
            $table->enum('commission_calculation', ['net', 'gross'])->default('net'); // Calculate on net or gross amount
            $table->integer('commission_payout_days')->default(30); // Days after invoice payment
            $table->json('tier_based_commission')->nullable(); // For tier-based commission structure

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
