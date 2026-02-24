<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_official_receipts', function (Blueprint $table) {
            $table->string('subscriber_name')->nullable()->after('company_name');
            $table->string('ref_no')->nullable()->after('payment_method');
            $table->string('autocount_invoice_no', 20)->nullable()->after('ref_no');
        });
    }

    public function down(): void
    {
        Schema::table('hr_official_receipts', function (Blueprint $table) {
            $table->dropColumn(['subscriber_name', 'ref_no', 'autocount_invoice_no']);
        });
    }
};
