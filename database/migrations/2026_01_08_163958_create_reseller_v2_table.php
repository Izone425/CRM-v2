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
        Schema::create('reseller_v2', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('plain_password')->nullable();
            $table->string('company_name');
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->string('territory')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('ssm_number')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->enum('sst_category', ['EXEMPTED', 'NON-EXEMPTED'])->default('NON-EXEMPTED');
            $table->integer('reseller_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_v2');
    }
};
