<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('lead_code', 10)->nullable(); // Lead code, up to 10 characters
            $table->string('salesperson', 20)->nullable(); // Salesperson, up to 20 characters
            $table->string('platform', 20)->nullable(); // Platform, up to 20 characters
            $table->timestamps(); // Created_at and Updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lead_sources');
    }
}
