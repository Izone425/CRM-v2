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
        Schema::create('quotation_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('quotation_id')->unsigned();
            $table->bigInteger('product_id')->unsigned();
            $table->text('description')->nullable();
            $table->integer('quantity')->unsigned()->nullable();
            $table->integer('subscription_period')->unsigned()->nullable();

            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('taxation', 10, 2);
            $table->decimal('total_before_tax', 10, 2);
            $table->decimal('total_after_tax', 10, 2);

            $table->integer('sort_order')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_details');
    }
};
