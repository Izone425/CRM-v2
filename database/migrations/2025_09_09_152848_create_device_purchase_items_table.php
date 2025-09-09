<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->string('model');
            $table->integer('qty')->default(0);
            $table->integer('england')->default(0);
            $table->integer('america')->default(0);
            $table->integer('europe')->default(0);
            $table->integer('australia')->default(0);
            $table->string('sn_no_from')->nullable();
            $table->string('sn_no_to')->nullable();
            $table->string('po_no')->nullable();
            $table->string('order_no')->nullable();
            $table->integer('balance_not_order')->default(0);
            $table->integer('rfid_card_foc')->default(0);
            $table->text('languages')->nullable();
            $table->text('features')->nullable();
            $table->timestamps();

            // Create composite unique key
            $table->unique(['year', 'month', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_purchase_items');
    }
};
