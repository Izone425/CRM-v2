<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hrdf_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->string('hrdf_grant_id')->index(); // Most important field
            $table->json('jd14_form_files')->nullable(); // Box 1 - Max 4 PDF files
            $table->json('autocount_invoice_file')->nullable(); // Box 2 - Max 1 PDF file
            $table->json('hrdf_grant_approval_file')->nullable(); // Box 3 - Max 1 PDF file
            $table->text('salesperson_remark')->nullable(); // Optional remark
            $table->enum('status', ['Draft', 'New', 'Approved', 'Rejected'])->default('New');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->text('reject_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrdf_handovers');
    }
};
