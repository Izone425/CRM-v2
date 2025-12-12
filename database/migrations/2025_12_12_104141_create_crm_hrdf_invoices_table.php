<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('crm_hrdf_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no');
            $table->date('invoice_date');
            $table->string('company_name');
            $table->enum('handover_type', ['SW', 'HW', 'RW'])->comment('SW=Software, HW=Hardware, RW=Renewal');
            $table->string('salesperson');
            $table->unsignedBigInteger('handover_id')->nullable(); // Link to software_handovers
            $table->string('debtor_code')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->timestamps();

            // Add indexes
            $table->index('invoice_no');
            $table->index('invoice_date');
            $table->index('handover_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_hrdf_invoices');
    }
};
