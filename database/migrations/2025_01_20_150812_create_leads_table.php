<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('zoho_id')->nullable();
            $table->string('name', 20);
            $table->string('email', 50)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('company_name', 30)->nullable();
            $table->string('company_size', 20)->nullable();
            $table->string('country', 20)->nullable();
            $table->string('products', 100)->nullable();
            $table->string('lead_code', 15)->nullable();
            $table->enum('categories', ['New', 'Active', 'Inactive'])->default('New');
            $table->enum('stage', ['New', 'Transfer', 'Demo', 'Follow Up'])->default('New');
            $table->enum('lead_status', ['None', 'New', 'RFQ-Transfer', 'Pending Demo', 'Under Review', 'Demo Cancelled', 'Demo-Assigned', 'RFQ-Follow Up',
            'Hot', 'Warm', 'Cold', 'Junk', 'On Hold', 'Lost', 'No Response', 'Closed'])->default('None');
            $table->date('follow_up_date')->nullable();
            $table->string('remark', 100)->nullable();
            $table->string('salesperson', 50)->nullable();
            $table->string('lead_owner', 50)->nullable();
            $table->integer('demo_appointment')->nullable();
            $table->enum('customer_type', ['END USER', 'RESELLER'])->default('END USER');
            $table->enum('region', ['LOCAL', 'OVERSEA'])->default('LOCAL');
            $table->timestamp('salesperson_assigned_date')->nullable();
            $table->timestamp('rfq_followup_at')->nullable();
            $table->timestamp('rfq_transfer_at')->nullable();
            $table->timestamp('pickup_date')->nullable();
            $table->timestamp('closing_date')->nullable();
            $table->boolean('follow_up_needed')->default(0);
            $table->boolean('follow_up_counter')->nullable();
            $table->integer('follow_up_count')->default(0);
            $table->integer('manual_follow_up_count')->default(0);
            $table->integer('done_call')->default(0)->nullable();
            $table->integer('call_attempt')->default(0)->nullable();
            $table->decimal('deal_amount', 10, 2)->nullable();
            $table->string('contact_id')->nullable();
            $table->integer('reseller_id')->nullable();
            $table->integer('visible_in_repairs')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leads');
    }
}
