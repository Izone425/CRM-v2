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
            $table->string('full_name', 50)->nullable(); // Full name of the account holder
            $table->string('ic', 50)->nullable(); // IC (Identity Card) number
            $table->string('tin', 50)->nullable(); // Tax Identification Number (TIN)
            $table->string('bank_name', 50)->nullable(); // Name of the bank
            $table->string('bank_account_no', 50)->nullable(); // Bank account number
            $table->string('contact_no', 50)->nullable(); // Contact number
            $table->string('email', 50)->nullable(); // Email address
            $table->enum('referral_payment_status', ['PENDING', 'COMPLETED'])->default('PENDING'); // Referral payment status
            $table->string('remark', 50)->nullable(); // Additional remarks
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
