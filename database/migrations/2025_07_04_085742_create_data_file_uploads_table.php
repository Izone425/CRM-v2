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
        Schema::create('data_file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('company_id');
            $table->string('module');
            $table->string('file_type');
            $table->string('category');
            $table->string('status');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamp('processed_at')->nullable();
            $table->text('result_log')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('company_details');
            $table->foreign('uploaded_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_file_uploads');
    }
};
