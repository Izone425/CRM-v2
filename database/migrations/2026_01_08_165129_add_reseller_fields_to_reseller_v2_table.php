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
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->string('ssm_number')->nullable()->after('postal_code');
            $table->string('tax_identification_number')->nullable()->after('ssm_number');
            $table->enum('sst_category', ['EXEMPTED', 'NON-EXEMPTED'])->default('NON-EXEMPTED')->after('tax_identification_number');
            $table->unsignedBigInteger('reseller_id')->nullable()->after('sst_category');

            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->dropColumn(['ssm_number', 'tax_identification_number', 'sst_category', 'reseller_id']);
        });
    }
};
