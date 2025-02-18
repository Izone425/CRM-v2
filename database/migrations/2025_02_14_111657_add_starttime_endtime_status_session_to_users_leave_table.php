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
        Schema::table('users_leave', function (Blueprint $table) {
            $table->enum('session', ['full', 'am', 'pm'])->default('full');
            $table->enum('status', ['Approved', 'RequestCancel', 'Pending', 'PendingCancel'])->default('Pending');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_leave', function (Blueprint $table) {
            $table->dropColumn(['session','status', 'start_time', 'end_time']);
        });
    }
};
