<?php
// filepath: /var/www/html/timeteccrm/database/migrations/2025_12_12_150306_create_renewal_handovers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('renewal_handovers', function (Blueprint $table) {
            $table->id();

            // ✅ Use foreignId() instead of unsignedBigInteger for better compatibility
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');

            $table->string('handover_id')->unique(); // RW_250001 format
            $table->string('company_name');
            $table->json('selected_quotation_ids'); // Store array of quotation IDs
            $table->json('invoice_numbers')->nullable(); // Store array of created invoice numbers
            $table->string('debtor_code')->default('ARM-P0062');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');

            // ✅ Use foreignId() for created_by as well
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('autocount_response')->nullable(); // Store API response for debugging
            $table->timestamps();

            // Indexes (foreignId already creates indexes, but adding extra ones)
            $table->index('handover_id');
            $table->index('status');
            $table->index('processed_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('renewal_handovers');
    }
};
