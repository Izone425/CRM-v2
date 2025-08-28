<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlySalesTargetsTable extends Migration
{
    public function up()
    {
        Schema::create('monthly_sales_targets', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->integer('salesperson');
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['year', 'month', 'salesperson']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('monthly_sales_targets');
    }
}
