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
        Schema::create('admin_portal_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_invoice_id')->nullable()->constrained('finance_invoices')->onDelete('cascade');
            $table->string('reseller_name')->nullable();
            $table->string('subscriber_name')->nullable();
            $table->string('tt_invoice')->nullable();
            $table->string('autocount_invoice')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_portal_invoices');
    }
};
