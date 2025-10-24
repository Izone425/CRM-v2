<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hrdf_mails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique(); // Microsoft Graph message ID
            $table->string('subject');
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->json('to_recipients'); // Store as JSON array
            $table->json('cc_recipients')->nullable();
            $table->json('bcc_recipients')->nullable();
            $table->datetime('received_date');
            $table->datetime('sent_date')->nullable();
            $table->text('body_preview')->nullable();
            $table->longText('body_content'); // Full email content
            $table->string('body_type')->default('html'); // html or text
            $table->boolean('has_attachments')->default(false);
            $table->string('importance')->default('normal');
            $table->boolean('is_read')->default(false);

            // HRDCorp specific fields
            $table->string('application_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('programme_name')->nullable();
            $table->date('training_start_date')->nullable();
            $table->date('training_end_date')->nullable();
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->integer('number_of_people')->nullable();
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->decimal('duration', 5, 1)->nullable();
            $table->decimal('course_fee_approved', 10, 2)->nullable();
            $table->decimal('balance_amount', 10, 2)->nullable();
            $table->date('approval_date')->nullable();

            $table->enum('status', ['pending', 'processed', 'claimed', 'expired'])->default('pending');
            $table->json('raw_email_data')->nullable(); // Store original API response
            $table->timestamps();

            $table->index(['application_number', 'status']);
            $table->index(['company_name', 'approval_date']);
            $table->index(['from_email', 'received_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrdf_mails');
    }
};
