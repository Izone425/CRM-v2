<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('call_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('tier');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add category_id to call_logs table
        Schema::table('call_logs', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('call_categories')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('call_categories');
    }
};
