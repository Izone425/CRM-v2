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

            $table->string('handover_pdf')->nullable();
            $table->string('courier')->nullable();
            $table->string('courier_address')->nullable();
            $table->string('installation_type')->nullable();

            $table->json('category2')->nullable();

            $table->string('contact_detail')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('pic_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('installer')->nullable();
            $table->string('reseller')->nullable();
            $table->string('implementer')->nullable();
            $table->string('proforma_invoice_hrdf')->nullable();
            $table->string('proforma_invoice_product')->nullable();
            $table->string('reject_reason')->nullable();

            $table->json('remarks')->nullable();
            $table->json('admin_remarks')->nullable();

            $table->integer('tc10_quantity')->nullable();
            $table->integer('tc20_quantity')->nullable();
            $table->integer('face_id5_quantity')->nullable();
            $table->integer('face_id6_quantity')->nullable();
            $table->integer('time_beacon_quantity')->nullable();
            $table->integer('nfc_tag_quantity')->nullable();

            $table->string('invoice_type')->nullable();
            $table->text('related_software_handovers')->nullable();

            $table->string('video_files')->nullable();
            $table->string('confirmation_order_file')->nullable();
            $table->string('hrdf_grant_file')->nullable();
            $table->string('payment_slip_file')->nullable();
            $table->string('new_attachment_file')->nullable();
            $table->string('invoice_file')->nullable();
            $table->string('sales_order_file')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('pending_stock_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('pending_migration_at')->nullable();

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
