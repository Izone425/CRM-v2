<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_details', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('lead_id')->nullable(); // Foreign key to leads table
            $table->string('referral_name')->nullable(); // Full name of the account holder
            $table->string('tin', 50)->nullable(); // Tax Identification Number (TIN)
            $table->string('hp_number', 50)->nullable();
            $table->string('email', 50)->nullable(); // Email address
            $table->string('referral_address')->nullable();
            $table->string('postcode', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('country')->nullable();
            $table->string('referral_bank_name')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->timestamps(); // Created_at and Updated_at columns

            // Foreign key constraint
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_details');
    }
}
