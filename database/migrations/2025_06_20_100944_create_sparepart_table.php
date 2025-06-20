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
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_model_id')->constrained('device_models')->onDelete('cascade');
            $table->string('name');
            $table->string('picture_url')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->integer('stock_quantity')->default(0);
            $table->string('part_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_parts');
    }
};
