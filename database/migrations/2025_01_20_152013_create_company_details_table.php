<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_details', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('lead_id')->nullable(); // Foreign key to leads table
            $table->string('company_name', 50)->nullable(); // Name of the company
            $table->string('company_address1', 50)->nullable(); // Company address line 1
            $table->string('company_address2', 50)->nullable(); // Company address line 2

            
            $table->string('postcode', 50)->nullable(); // Postcode
            $table->string('state', 50)->nullable(); // State
            $table->string('name', 50)->nullable(); // Contact person name
            $table->string('contact_no', 50)->nullable(); // Contact person's phone number
            $table->string('position', 50)->nullable(); // Contact person's position
            $table->string('email', 50)->nullable(); // Contact person's email
            $table->string('industry', 50)->nullable(); // Industry type
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
        Schema::dropIfExists('company_details');
    }
}

