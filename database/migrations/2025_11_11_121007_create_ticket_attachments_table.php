<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('file_path', 255);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_attachments');
    }
};
