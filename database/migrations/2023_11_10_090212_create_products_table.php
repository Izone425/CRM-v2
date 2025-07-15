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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->integer('sort_order')->nullable();
            $table->string('description')->nullable();
            $table->enum('solution', ['software', 'hardware', 'hrdf', 'other']);
            $table->float('unit_price', 8, 2)->nullable();
            $table->string('package_group')->nullable();
            $table->integer('package_sort_order')->nullable();
            $table->boolean('taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
