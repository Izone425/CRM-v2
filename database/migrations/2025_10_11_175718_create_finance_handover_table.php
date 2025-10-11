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
        Schema::create('finance_handovers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('reseller_id');
            $table->unsignedBigInteger('created_by');

            // Reseller PIC Details
            $table->string('pic_name');
            $table->string('pic_phone');
            $table->string('pic_email');

            // File uploads (stored as JSON arrays)
            $table->json('invoice_by_customer')->nullable();
            $table->json('payment_by_customer')->nullable();
            $table->json('invoice_by_reseller')->nullable();

            // Status and tracking
            $table->enum('status', ['New', 'Processing', 'Completed', 'Rejected'])->default('New');
            $table->timestamp('submitted_at')->nullable();
            $table->text('remarks')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('reseller_id')->references('id')->on('resellers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['lead_id', 'status']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_handovers');
    }
};
