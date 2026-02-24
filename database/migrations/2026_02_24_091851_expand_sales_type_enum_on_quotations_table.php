<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE quotations MODIFY COLUMN sales_type ENUM('NEW SALES','ADD ON NEW SALES','RENEWAL SALES','ADD ON RENEWAL SALES') DEFAULT 'NEW SALES'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE quotations MODIFY COLUMN sales_type ENUM('NEW SALES','RENEWAL SALES') DEFAULT 'NEW SALES'");
    }
};
