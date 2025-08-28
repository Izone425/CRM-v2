<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('revenue_targets', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->string('salesperson');
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->timestamps();

            // Create a unique index to prevent duplicate entries
            $table->unique(['year', 'month', 'salesperson']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('revenue_targets');
    }
};
