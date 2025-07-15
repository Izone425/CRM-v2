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
        Schema::create('admin_repairs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->nullable();
            $table->bigInteger('software_handover_id')->unsigned()->nullable();
            $table->string('company_name')->nullable();
            $table->string('handover_pdf')->nullable();
            $table->string('pic_name');
            $table->string('pic_phone');
            $table->string('pic_email');
            $table->string('device_model');
            $table->string('device_serial');
            $table->string('devices')->nullable();
            $table->string('spare_parts')->nullable();
            $table->date('spare_parts_unused')->nullable();
            $table->text('onsite_repair_remark')->nullable();
            $table->text('remarks')->nullable();
            $table->text('repair_remark')->nullable();

            $table->string('video_files')->nullable();
            $table->string('quotation_hrdf')->nullable();
            $table->string('quotation_product')->nullable();
            $table->string('new_attachment_file')->nullable();
            $table->string('payment_slip_file')->nullable();
            $table->string('invoice_file')->nullable();
            $table->string('sales_order_file')->nullable();
            $table->string('delivery_order_files')->nullable();
            $table->string('repair_form_files')->nullable();
            $table->string('repair_image_files')->nullable();
            $table->string('zoho_ticket')->nullable();
            $table->string('address')->nullable();

            $table->enum('status', ['Draft', 'New', 'Pending Confirmation','Pending Onsite Repair','Accepted','Completed','Inactive'])->default('Draft');
            $table->json('devices_warranty')->nullable();


            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->date('invoice_date')->nullable();
            $table->date('pending_confirmation_date')->nullable();
            $table->date('completed_date')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_repairs');
    }
};
