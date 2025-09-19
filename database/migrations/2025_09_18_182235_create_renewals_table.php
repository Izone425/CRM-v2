<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('renewals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable(); // Reference to leads table
            $table->string('f_company_id'); // From crm_expiring_license
            $table->string('company_name');

            // Status fields
            $table->enum('mapping_status', ['pending_mapping', 'completed_mapping', 'onhold_mapping'])->default('pending_mapping');
            $table->string('admin_renewal')->nullable();
            $table->enum('renewal_progress', ['new', 'pending_confirmation', 'pending_payment', 'completed_renewal'])->default('new');

            $table->json('progress_history')->nullable(); // Track status changes

            $table->timestamps();

            // Indexes
            $table->index('f_company_id');
            $table->index('lead_id');
            $table->index('mapping_status');
            $table->index('renewal_progress');
        });
    }

    public function down()
    {
        Schema::dropIfExists('renewals');
    }
};
