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
        Schema::create('software_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Section 5: Module Subscription
            $table->integer('attendance_headcount')->nullable();
            $table->integer('attendance_subscription_months')->nullable();
            $table->string('attendance_purchase_type')->nullable();
            $table->integer('leave_headcount')->nullable();
            $table->integer('leave_subscription_months')->nullable();
            $table->string('leave_purchase_type')->nullable();
            $table->integer('claim_headcount')->nullable();
            $table->integer('claim_subscription_months')->nullable();
            $table->string('claim_purchase_type')->nullable();
            $table->integer('payroll_headcount')->nullable();
            $table->integer('payroll_subscription_months')->nullable();
            $table->string('payroll_purchase_type')->nullable();
            $table->integer('appraisal_headcount')->nullable();
            $table->integer('appraisal_subscription_months')->nullable();
            $table->string('appraisal_purchase_type')->nullable();
            $table->integer('recruitment_headcount')->nullable();
            $table->integer('recruitment_subscription_months')->nullable();
            $table->string('recruitment_purchase_type')->nullable();
            $table->integer('power_bi_headcount')->nullable();
            $table->integer('power_bi_subscription_months')->nullable();
            $table->string('power_bi_purchase_type')->nullable();

            // Section 6: Other Details
            $table->text('customization_details')->nullable();
            $table->text('enhancement_details')->nullable();
            $table->text('special_remark')->nullable();
            $table->text('device_integration')->nullable();
            $table->text('existing_hr_system')->nullable();
            $table->text('experience_implementing_hr_system')->nullable();
            $table->boolean('vip_package')->default(false);
            $table->text('fingertec_device')->nullable();

            // Section 7: Onsite Package
            $table->boolean('onsite_kick_off_meeting')->default(false);
            $table->boolean('onsite_webinar_training')->default(false);
            $table->boolean('onsite_briefing')->default(false);

            // Section 8: Payment Terms
            $table->string('payment_term')->nullable(); // Options: full_payment, payment_via_ibgc, payment_via_term

            // Section 9: Proforma Invoices
            $table->string('proforma_invoice_number')->nullable();

            // Section 10: Attachments
            $table->string('confirmation_order_file')->nullable();
            $table->string('payment_slip_file')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_handovers');
    }
};
