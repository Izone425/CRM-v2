<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_details', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('lead_id'); // Foreign key to leads table
            $table->string('company', 50)->nullable(); // Company name
            $table->string('name', 50)->nullable(); // Referral name
            $table->string('email', 50)->nullable(); // Referral email
            $table->string('contact_no', 50)->nullable(); // Referral contact number
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
        Schema::dropIfExists('referral_details');
    }
}
