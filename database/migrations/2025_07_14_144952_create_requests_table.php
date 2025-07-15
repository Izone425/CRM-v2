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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned();
            $table->bigInteger('requested_by')->unsigned();
            $table->bigInteger('current_owner_id')->unsigned();
            $table->bigInteger('requested_owner_id')->unsigned();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
