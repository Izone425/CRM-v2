<?php
// filepath: /var/www/html/timeteccrm/database/migrations/2025_10_03_000000_create_hrdf_claims_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hrdf_claims', function (Blueprint $table) {
            $table->id();
            $table->string('sales_person')->nullable();
            $table->string('company_name');
            $table->decimal('invoice_amount', 10, 2)->default(0);
            $table->string('invoice_number')->nullable();
            $table->text('sales_remark')->nullable();
            $table->string('claim_status')->default('PENDING'); // PENDING, RECEIVED, PROCESSED, etc.
            $table->string('hrdf_grant_id')->nullable()->index();
            $table->string('hrdf_training_date')->nullable();
            $table->string('hrdf_claim_id')->nullable();
            $table->string('programme_name')->nullable();
            $table->date('approved_date')->nullable();
            $table->timestamp('email_processed_at')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index('company_name');
            $table->index('claim_status');
            $table->index(['company_name', 'hrdf_grant_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrdf_claims');
    }
};
