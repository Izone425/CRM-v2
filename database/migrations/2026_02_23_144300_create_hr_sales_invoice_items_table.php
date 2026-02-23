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
        Schema::create('hr_sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_sales_invoice_id');
            $table->string('invoice_no');
            $table->date('invoice_date');
            $table->string('company_name');
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->string('currency', 10)->default('MYR');
            $table->unsignedBigInteger('software_handover_id')->nullable();
            $table->string('handover_id')->nullable();
            $table->string('created_by_name')->nullable();
            $table->string('status')->default('PENDING');
            $table->string('license_type');
            $table->integer('user_limit')->default(1);
            $table->integer('total_user')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('month')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index('end_date');
            $table->index('status');
            $table->index('invoice_no');
            $table->foreign('hr_sales_invoice_id')->references('id')->on('hr_sales_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_sales_invoice_items');
    }
};
