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
        Schema::create('finance_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('fc_number')->unique(); // FC_260001
            $table->foreignId('reseller_handover_id')->constrained('reseller_handovers')->onDelete('cascade');
            $table->string('autocount_invoice_number');
            $table->string('reseller_name');
            $table->string('subscriber_name');
            $table->decimal('reseller_commission_amount', 10, 2);
            $table->enum('portal_type', ['reseller', 'admin'])->default('reseller');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_invoices');
    }
};
