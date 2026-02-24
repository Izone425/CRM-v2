<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_auto_renewals', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no');
            $table->string('company_name');
            $table->string('country')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->string('status')->default('PENDING');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedBigInteger('software_handover_id')->nullable();
            $table->string('handover_id')->nullable();
            $table->timestamps();

            $table->index('invoice_no');
            $table->index('next_billing_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_auto_renewals');
    }
};
