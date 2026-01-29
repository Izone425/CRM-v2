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
        Schema::create('reseller_v2_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_v2_id')->unique();
            $table->unsignedTinyInteger('commission_rate')->default(0);
            $table->timestamps();

            $table->foreign('reseller_v2_id')
                  ->references('id')
                  ->on('reseller_v2')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_v2_commissions');
    }
};
