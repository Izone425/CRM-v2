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
        Schema::table('hr_sales_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('reseller_software_handover_id')->nullable()->after('reseller');
            $table->string('reseller_handover_id')->nullable()->after('reseller_software_handover_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['reseller_software_handover_id', 'reseller_handover_id']);
        });
    }
};
