<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hr_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('handover_id')->unique(); // SW_YYXXXX format
            $table->string('type'); // PAID, TRIAL
            $table->string('invoice_no')->nullable();
            $table->string('auto_count_invoice_no')->nullable();
            $table->string('company_name');
            $table->string('license_type');
            $table->integer('unit')->default(0);
            $table->integer('user_limit')->default(0);
            $table->integer('total_user')->default(0);
            $table->integer('total_login')->default(0);
            $table->integer('month')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('Enabled'); // Enabled, Disabled
            $table->string('auto_renewal')->default('Disabled'); // Enabled, Disabled
            $table->timestamps();
        });

        // Insert dummy data with SW_YYXXXX handover IDs
        DB::table('hr_licenses')->insert([
            [
                'handover_id' => 'SW_260044',
                'type' => 'PAID',
                'invoice_no' => 'TT2601000307',
                'auto_count_invoice_no' => 'EPIN2601-0160',
                'company_name' => 'KOPERASI PERBADANAN PUTRAJAYA BERHAD',
                'license_type' => 'TimeTec Payroll (1 Payroll License)',
                'unit' => 11,
                'user_limit' => 1,
                'total_user' => 11,
                'total_login' => 0,
                'month' => 12,
                'start_date' => '2025-12-15',
                'end_date' => '2026-11-30',
                'status' => 'Enabled',
                'auto_renewal' => 'Enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handover_id' => 'SW_260043',
                'type' => 'PAID',
                'invoice_no' => 'TT2601000306',
                'auto_count_invoice_no' => 'EPIN2601-0159',
                'company_name' => 'SYARIKAT BEKALAN AIR SELANGOR SDN BHD',
                'license_type' => 'TimeTec TA (10 User License)',
                'unit' => 50,
                'user_limit' => 500,
                'total_user' => 487,
                'total_login' => 152,
                'month' => 12,
                'start_date' => '2025-12-10',
                'end_date' => '2026-12-09',
                'status' => 'Enabled',
                'auto_renewal' => 'Enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handover_id' => 'SW_260042',
                'type' => 'TRIAL',
                'invoice_no' => '-',
                'auto_count_invoice_no' => '-',
                'company_name' => 'ABC MANUFACTURING SDN BHD',
                'license_type' => 'TimeTec Leave (1 User License)',
                'unit' => 10,
                'user_limit' => 10,
                'total_user' => 8,
                'total_login' => 5,
                'month' => 1,
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
                'status' => 'Enabled',
                'auto_renewal' => 'Disabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handover_id' => 'SW_260041',
                'type' => 'PAID',
                'invoice_no' => 'TT2601000305',
                'auto_count_invoice_no' => 'EHIN2601-0088',
                'company_name' => 'TENAGA NASIONAL BERHAD',
                'license_type' => 'TimeTec Claim (10 User License)',
                'unit' => 100,
                'user_limit' => 1000,
                'total_user' => 956,
                'total_login' => 423,
                'month' => 12,
                'start_date' => '2025-11-01',
                'end_date' => '2026-10-31',
                'status' => 'Enabled',
                'auto_renewal' => 'Enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handover_id' => 'SW_260040',
                'type' => 'PAID',
                'invoice_no' => 'TT2601000304',
                'auto_count_invoice_no' => 'EPIN2601-0158',
                'company_name' => 'PETRONAS CHEMICALS GROUP BERHAD',
                'license_type' => 'TimeTec TA (10 User License)',
                'unit' => 200,
                'user_limit' => 2000,
                'total_user' => 1854,
                'total_login' => 892,
                'month' => 12,
                'start_date' => '2025-10-15',
                'end_date' => '2026-10-14',
                'status' => 'Enabled',
                'auto_renewal' => 'Enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handover_id' => 'SW_250039',
                'type' => 'PAID',
                'invoice_no' => 'TT2601000303',
                'auto_count_invoice_no' => 'EPIN2601-0157',
                'company_name' => 'MALAYAN BANKING BERHAD',
                'license_type' => 'TimeTec Payroll (10 Payroll License)',
                'unit' => 500,
                'user_limit' => 5000,
                'total_user' => 4521,
                'total_login' => 1205,
                'month' => 12,
                'start_date' => '2025-09-01',
                'end_date' => '2026-08-31',
                'status' => 'Enabled',
                'auto_renewal' => 'Enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handover_id' => 'SW_260038',
                'type' => 'TRIAL',
                'invoice_no' => '-',
                'auto_count_invoice_no' => '-',
                'company_name' => 'STARTUP INNOVATIONS SDN BHD',
                'license_type' => 'TimeTec TA (1 User License)',
                'unit' => 5,
                'user_limit' => 5,
                'total_user' => 3,
                'total_login' => 2,
                'month' => 1,
                'start_date' => '2026-01-15',
                'end_date' => '2026-02-14',
                'status' => 'Enabled',
                'auto_renewal' => 'Disabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handover_id' => 'SW_250037',
                'type' => 'PAID',
                'invoice_no' => 'TT2601000302',
                'auto_count_invoice_no' => 'EHIN2601-0087',
                'company_name' => 'TELEKOM MALAYSIA BERHAD',
                'license_type' => 'Additional Storage License',
                'unit' => 100,
                'user_limit' => 0,
                'total_user' => 0,
                'total_login' => 0,
                'month' => 12,
                'start_date' => '2025-08-01',
                'end_date' => '2026-07-31',
                'status' => 'Enabled',
                'auto_renewal' => 'Enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handover_id' => 'SW_250036',
                'type' => 'PAID',
                'invoice_no' => 'TT2601000301',
                'auto_count_invoice_no' => 'EPIN2601-0156',
                'company_name' => 'AXIATA GROUP BERHAD',
                'license_type' => 'TimeTec Leave (10 User License)',
                'unit' => 300,
                'user_limit' => 3000,
                'total_user' => 2876,
                'total_login' => 654,
                'month' => 12,
                'start_date' => '2025-07-15',
                'end_date' => '2026-07-14',
                'status' => 'Enabled',
                'auto_renewal' => 'Enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handover_id' => 'SW_250035',
                'type' => 'PAID',
                'invoice_no' => 'TT2601000300',
                'auto_count_invoice_no' => 'EPIN2601-0155',
                'company_name' => 'SIME DARBY PLANTATION BERHAD',
                'license_type' => 'Additional Database License',
                'unit' => 5,
                'user_limit' => 0,
                'total_user' => 0,
                'total_login' => 0,
                'month' => 12,
                'start_date' => '2025-06-01',
                'end_date' => '2026-05-31',
                'status' => 'Disabled',
                'auto_renewal' => 'Disabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_licenses');
    }
};
