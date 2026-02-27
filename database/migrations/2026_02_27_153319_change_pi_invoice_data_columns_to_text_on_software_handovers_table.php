<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software_handovers', function (Blueprint $table) {
            $table->text('type_1_pi_invoice_data')->nullable()->change();
            $table->text('type_2_pi_invoice_data')->nullable()->change();
            $table->text('type_3_pi_invoice_data')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('software_handovers', function (Blueprint $table) {
            $table->string('type_1_pi_invoice_data', 255)->nullable()->change();
            $table->string('type_2_pi_invoice_data', 255)->nullable()->change();
            $table->string('type_3_pi_invoice_data', 255)->nullable()->change();
        });
    }
};
