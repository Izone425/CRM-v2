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
        Schema::table('hr_terminal_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('software_handover_id')->nullable()->after('id');
            $table->string('handover_id')->nullable()->after('software_handover_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_terminal_devices', function (Blueprint $table) {
            $table->dropColumn(['software_handover_id', 'handover_id']);
        });
    }
};
