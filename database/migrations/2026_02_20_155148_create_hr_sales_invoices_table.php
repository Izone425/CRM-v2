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
        Schema::create('hr_sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('software_handover_id')->nullable();
            $table->string('handover_id')->nullable();
            $table->string('invoice_no');
            $table->date('invoice_date');
            $table->string('company_name');
            $table->string('country')->nullable();
            $table->string('reseller')->nullable();
            $table->decimal('sales_amount', 12, 2)->nullable();
            $table->string('currency')->default('MYR');
            $table->decimal('commission', 10, 2)->nullable();
            $table->string('pi_no')->nullable();
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('auto_renewal')->default('No');
            $table->string('created_by_name')->nullable();
            $table->string('status')->default('PENDING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_sales_invoices');
    }
};
