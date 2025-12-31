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
        Schema::create('crm_hrdf_invoice_v2s', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->date('invoice_date');
            $table->string('company_name');
            $table->enum('handover_type', ['SW', 'HW', 'RW']);
            $table->unsignedBigInteger('handover_id');
            $table->string('tt_invoice_number')->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->string('debtor_code')->nullable();
            $table->string('salesperson')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->json('handover_data')->nullable(); // Store handover details
            $table->integer('proforma_invoice_data')->nullable(); // Store quotation ID
            $table->timestamps();

            $table->index(['handover_type', 'handover_id']);
            $table->index('invoice_date');
            $table->index('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_hrdf_invoice_v2s');
    }
};
