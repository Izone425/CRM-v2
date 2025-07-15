<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('data_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('category');
            $table->string('subcategory');
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();

            // Prevent duplicate file entries
            $table->unique(['lead_id', 'filename']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_files');
    }
};
