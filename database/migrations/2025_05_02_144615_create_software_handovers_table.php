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

            $table->string('status', 50)->nullable(); // Status of the handover
            $table->string('handover_type', 50)->nullable(); // Type of handover
            $table->string('handover_pdf')->nullable(); // PDF file for the handover
            $table->string('company_name')->nullable(); // Name of the company
            $table->string('headcount', 50)->nullable();
            $table->string('salesperson')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('pic_phone')->nullable();
            $table->json('implementation_pics')->nullable();
            $table->json('remarks')->nullable();

            $table->bigInteger('ta')->default(0);
            $table->bigInteger('tl')->default(0);
            $table->bigInteger('tc')->default(0);
            $table->bigInteger('tp')->default(0);
            $table->bigInteger('tapp')->default(0);
            $table->bigInteger('thire')->default(0);
            $table->bigInteger('tacc')->default(0);
            $table->bigInteger('tpbi')->default(0);

            $table->string('reject_reason')->nullable();
            $table->string('admin_remarks')->nullable();
            $table->string('payroll_code')->nullable();
            $table->string('implementer')->nullable();
            $table->string('training_type')->nullable();
            $table->string('speaker_category')->nullable();
            $table->string('proforma_invoice_hrdf')->nullable();
            $table->string('proforma_invoice_product')->nullable();
            $table->string('confirmation_order_file')->nullable();
            $table->string('payment_slip_file')->nullable();
            $table->string('hrdf_grant_file')->nullable();
            $table->string('invoice_file')->nullable();
            $table->string('new_attachment_file')->nullable();

            $table->boolean('license_activated')->default(false);
            $table->boolean('data_migrated')->default(false);
            $table->boolean('follow_up_counter')->default(false);

            $table->integer('manual_follow_up_count')->nullable();

            $table->date('follow_up_date')->nullable();
            $table->bigInteger('license_certification_id')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('db_creation')->nullable();
            $table->timestamp('kick_off_meeting')->nullable();
            $table->timestamp('webinar_training')->nullable();
            $table->timestamp('total_days')->nullable();
            $table->timestamp('go_live_date')->nullable();
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
