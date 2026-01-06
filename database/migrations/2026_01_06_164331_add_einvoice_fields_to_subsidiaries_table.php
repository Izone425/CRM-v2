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
        Schema::table('subsidiaries', function (Blueprint $table) {
            // Add E-Invoice related fields
            $table->string('business_register_number', 12)->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->string('msic_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 3)->nullable()->default('MYS');
            $table->string('currency', 3)->nullable()->default('MYR');
            $table->string('business_type')->nullable()->default('local_business');
            $table->string('business_category')->nullable()->default('business');
            $table->string('billing_category')->nullable()->default('billing_to_subscriber');

            // Add Finance person fields
            $table->string('finance_person_name')->nullable();
            $table->string('finance_person_email')->nullable();
            $table->string('finance_person_contact', 20)->nullable();
            $table->string('finance_person_position')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subsidiaries', function (Blueprint $table) {
            $table->dropColumn([
                'business_register_number',
                'tax_identification_number',
                'msic_code',
                'city',
                'country',
                'currency',
                'business_type',
                'business_category',
                'billing_category',
                'finance_person_name',
                'finance_person_email',
                'finance_person_contact',
                'finance_person_position'
            ]);
        });
    }
};
