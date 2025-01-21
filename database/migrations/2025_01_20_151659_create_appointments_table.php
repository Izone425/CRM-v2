<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('lead_id')->nullable(); // Foreign key to leads table
            $table->enum('type', ['New Demo', 'Second Demo', 'System Discussion', 'HRDF Discussion'])->nullable(); // Appointment type
            $table->enum('appointment_type', ['Onsite Demo', 'Online Demo', 'Webinar'])->nullable(); // Specific appointment type
            $table->date('date')->nullable(); // Date of the appointment
            $table->time('start_time')->nullable(); // Start time of the appointment
            $table->time('end_time')->nullable(); // End time of the appointment
            $table->string('salesperson', 50)->nullable(); // Salesperson assigned
            $table->string('remarks', 100)->nullable(); // Additional remarks
            $table->string('title', 50)->nullable(); // Title of the appointment
            $table->string('required_attendees', 255)->nullable(); // Required attendees
            $table->string('optional_attendees', 255)->nullable(); // Optional attendees
            $table->string('location', 255)->nullable(); // Location of the appointment
            $table->string('event_id', 255)->nullable(); // Event ID (e.g., from a calendar system)
            $table->string('details', 2000)->nullable(); // Detailed description of the appointment
            $table->enum('status', ['Done', 'Cancelled', 'Pending'])->nullable(); // Status of the appointment
            $table->timestamps(); // Created_at and Updated_at columns

            // Foreign key constraint
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointments');
    }
}
