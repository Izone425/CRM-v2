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
        Schema::create('hardware_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('New');

            // Section 1: Company Details
            $table->string('company_name')->nullable();
            $table->string('industry')->nullable();
            $table->string('headcount')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('salesperson')->nullable();

            // Section 2: Superadmin Details
            $table->string('pic_name')->nullable();
            $table->string('pic_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();

            // Section 3: Invoice Details
            $table->string('company_name_invoice')->nullable();
            $table->text('company_address')->nullable();
            $table->string('salesperson_invoice')->nullable();
            $table->string('pic_name_invoice')->nullable();
            $table->string('pic_email_invoice')->nullable();
            $table->string('pic_phone_invoice')->nullable();

            // Section 4: Implementation PICs
            $table->text('implementation_pics')->nullable(); // Stored as JSON

            // Section 5: Module Subscription
            $table->text('modules')->nullable(); // Stored as JSON

            // Section 6: Other Details
            $table->text('customization_details')->nullable();
            $table->text('enhancement_details')->nullable();
            $table->text('special_remark')->nullable();
            $table->string('device_integration')->nullable();
            $table->string('existing_hr_system')->nullable();
            $table->string('experience_implementing_hr_system')->nullable();
            $table->string('vip_package')->nullable();
            $table->string('fingertec_device')->nullable();

            // Section 7: Onsite Package
            $table->boolean('onsite_kick_off_meeting')->default(false);
            $table->boolean('onsite_webinar_training')->default(false);
            $table->boolean('onsite_briefing')->default(false);

            // Section 8: Payment Terms
            $table->string('payment_term')->nullable(); // Options: full_payment, payment_via_ibgc, payment_via_term

            // Section 9: Proforma Invoices
            $table->string('proforma_invoice_number')->nullable();

            // Section 10: Attachments
            $table->text('confirmation_order_file')->nullable(); // Stored as JSON
            $table->text('payment_slip_file')->nullable(); // Stored as JSON

            // Section 11: Installation Details
            // Note: special_remark is already defined in Section 6
            $table->string('installation_media')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hardware_handovers');
    }
};
