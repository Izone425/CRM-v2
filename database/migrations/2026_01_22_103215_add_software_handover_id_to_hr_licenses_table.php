<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_licenses', function (Blueprint $table) {
            $table->unsignedBigInteger('software_handover_id')->nullable()->after('id');
            $table->foreign('software_handover_id')
                ->references('id')
                ->on('software_handovers')
                ->onDelete('set null');
        });

        // Link existing dummy data to real SoftwareHandover records
        // Extract the numeric ID from handover_id (e.g., SW_260044 -> 44)
        DB::statement("
            UPDATE hr_licenses
            SET software_handover_id = CAST(SUBSTRING(handover_id, -4) AS UNSIGNED)
            WHERE handover_id IS NOT NULL
            AND CAST(SUBSTRING(handover_id, -4) AS UNSIGNED) IN (SELECT id FROM software_handovers)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_licenses', function (Blueprint $table) {
            $table->dropForeign(['software_handover_id']);
            $table->dropColumn('software_handover_id');
        });
    }
};
