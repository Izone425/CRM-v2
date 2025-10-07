<?php
// filepath: database/migrations/xxxx_xx_xx_create_sales_pricing_pages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_pricing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_pricing_id')->constrained()->onDelete('cascade');
            $table->string('title')->default('Page 1');
            $table->longText('content');
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('last_updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_pricing_pages');
    }
};
