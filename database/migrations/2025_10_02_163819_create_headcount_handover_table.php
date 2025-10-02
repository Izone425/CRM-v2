<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('headcount_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->json('proforma_invoice_product')->nullable(); // Product PIs
            $table->json('proforma_invoice_hrdf')->nullable(); // HRDF PIs
            $table->json('payment_slip_file')->nullable(); // Payment slip files
            $table->json('confirmation_order_file')->nullable(); // Confirmation order files
            $table->text('salesperson_remark')->nullable(); // Salesperson remarks
            $table->enum('status', ['Draft', 'New', 'Completed', 'Rejected'])->default('New');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->text('reject_reason')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('headcount_handovers');
    }
};
