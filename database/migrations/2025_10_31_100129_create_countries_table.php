<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('countries', 'phone_code')) {
                $table->string('phone_code', 10)->nullable()->after('name');
            }
            if (!Schema::hasColumn('countries', 'iso2')) {
                $table->string('iso2', 2)->nullable()->after('phone_code');
            }
            if (!Schema::hasColumn('countries', 'iso3')) {
                $table->string('iso3', 3)->nullable()->after('iso2');
            }
            if (!Schema::hasColumn('countries', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('iso3');
            }
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['phone_code', 'iso2', 'iso3', 'is_active']);
        });
    }
};
