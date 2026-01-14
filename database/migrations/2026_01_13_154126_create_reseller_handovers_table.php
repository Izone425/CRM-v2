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
        Schema::create('reseller_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('reseller_id')->nullable();
            $table->string('reseller_name')->nullable();
            $table->string('reseller_company_name')->nullable();
            $table->string('subscriber_id')->nullable();
            $table->string('subscriber_name')->nullable();
            $table->string('subscriber_status', 10)->nullable()->comment('A = Active, I = Inactive');
            $table->integer('attendance_qty')->default(0);
            $table->integer('leave_qty')->default(0);
            $table->integer('claim_qty')->default(0);
            $table->integer('payroll_qty')->default(0);
            $table->text('reseller_remark')->nullable();
            $table->string('status', 20)->default('new')->comment('new, pending, approved, rejected');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_handovers');
    }
};
