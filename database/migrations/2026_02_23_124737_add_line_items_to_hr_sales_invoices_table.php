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
            $table->json('line_items')->nullable()->after('invoice_amount');
        });
    }

    public function down(): void
    {
        Schema::table('hr_sales_invoices', function (Blueprint $table) {
            $table->dropColumn('line_items');
        });
    }
};
