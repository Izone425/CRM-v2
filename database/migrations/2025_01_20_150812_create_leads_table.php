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
            $table->unsignedInteger('demo_appointment')->nullable();
            $table->timestamps();
            $table->timestamp('rfq_followup_at')->nullable();
            $table->boolean('follow_up_needed')->default(0);
            $table->unsignedInteger('follow_up_counter')->default(0);
            $table->unsignedInteger('follow_up_count')->default(0);
            $table->unsignedInteger('demo_follow_up_count')->default(0);
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
