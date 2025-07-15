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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3);
            $table->string('email')->unique();
            $table->string('mobile_number', 50);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->enum('department', ['HR Solutions', 'Sales', 'IT', 'Support'])->nullable();
            $table->string('position');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->rememberToken()->nullable();
            $table->unsignedBigInteger('additional_role')->nullable();
            $table->json('route_permissions')->nullable();
            $table->integer('api_user_id')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('msteam_link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
