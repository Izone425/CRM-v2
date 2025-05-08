<?php
// New migration file
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('software_handovers', function (Blueprint $table) {
            // Section 1: Company Details
            $table->string('company_name')->nullable()->after('lead_id');
            $table->string('industry')->nullable()->after('company_name');
            $table->integer('headcount')->nullable()->after('industry');
            $table->string('country')->nullable()->after('headcount');
            $table->string('state')->nullable()->after('country');
            $table->string('salesperson')->nullable()->after('state');

            // Section 2: Superadmin Details
            $table->string('pic_name')->nullable()->after('salesperson');
            $table->string('pic_phone')->nullable()->after('pic_name');
            $table->string('email')->nullable()->after('pic_phone');
            $table->string('password')->nullable()->after('email');

            // Section 3: Invoice Details
            $table->string('company_name_invoice')->nullable()->after('password');
            $table->text('company_address')->nullable()->after('company_name_invoice');
            $table->string('salesperson_invoice')->nullable()->after('company_address');
            $table->string('pic_name_invoice')->nullable()->after('salesperson_invoice');
            $table->string('pic_email_invoice')->nullable()->after('pic_name_invoice');
            $table->string('pic_phone_invoice')->nullable()->after('pic_email_invoice');
        });
    }

    public function down()
    {
        Schema::table('software_handovers', function (Blueprint $table) {
            // Section 1: Company Details
            $table->dropColumn([
                'company_name',
                'industry',
                'headcount',
                'country',
                'state',
                'salesperson',
            ]);

            // Section 2: Superadmin Details
            $table->dropColumn([
                'pic_name',
                'pic_phone',
                'email',
                'password',
            ]);

            // Section 3: Invoice Details
            $table->dropColumn([
                'company_name_invoice',
                'company_address',
                'salesperson_invoice',
                'pic_name_invoice',
                'pic_email_invoice',
                'pic_phone_invoice',
            ]);
        });
    }
};
