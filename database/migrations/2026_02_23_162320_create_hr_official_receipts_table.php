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
        Schema::create('hr_official_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('or_no')->unique();
            $table->date('receipt_date');
            $table->string('company_name');
            $table->string('description');
            $table->string('currency', 10)->default('MYR');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('PAID');
            $table->string('created_by')->nullable();
            $table->string('invoice_no')->nullable();
            $table->unsignedBigInteger('software_handover_id')->nullable();
            $table->string('handover_id')->nullable();
            $table->timestamps();

            $table->index('or_no');
            $table->index('receipt_date');
            $table->index('invoice_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_official_receipts');
    }
};
