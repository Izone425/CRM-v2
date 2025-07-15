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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->nullable();
            $table->integer('headcount')->unsigned()->nullable();
            $table->date('quotation_date')->nullable();
            $table->string('quotation_reference_no')->nullable();
            $table->string('pi_reference_no')->nullable();

            $table->enum('quotation_type', ['product','hrdf'])->default('product');

            $table->bigInteger('sales_person_id')->unsigned()->nullable();

            $table->enum('currency', ['MYR','USD'])->default('MYR');
            $table->enum('sales_type', ['NEW SALES','RENEWAL SALES'])->default('NEW SALES');
            $table->enum('hrdf_status', ['HRDF','NON HRDF'])->default('HRDF');
            $table->integer('subscription_period')->unsigned()->default(12);
            $table->enum('status', ['new','email_sent','accepted','rejected'])->default('new');

            $table->unsignedTinyInteger('tax_rate')->default(8);
            $table->timestamp('email_sent_at')->nullable();
            $table->string('confirmation_order_document')->nullable();
            $table->unsignedTinyInteger('payment_status')->default(0);
            $table->unsignedTinyInteger('mark_as_final')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
