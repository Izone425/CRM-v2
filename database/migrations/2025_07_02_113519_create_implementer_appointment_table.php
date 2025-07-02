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
        // Check if the table already exists, if not create it
        if (!Schema::hasTable('implementer_appointments')) {
            Schema::create('implementer_appointments', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('lead_id');
                $table->string('type');
                $table->string('appointment_type')->default('ONLINE');
                $table->date('date');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('implementer');
                $table->string('session')->nullable();
                $table->text('remarks')->nullable();
                $table->string('title')->nullable();
                $table->json('required_attendees')->nullable();
                $table->json('optional_attendees')->nullable();
                $table->string('status')->default('New');
                $table->timestamps();
            });
        } else {
            // If table already exists, add any missing columns
            Schema::table('implementer_appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('implementer_appointments', 'session')) {
                    $table->string('session')->nullable()->after('implementer');
                }

                if (!Schema::hasColumn('implementer_appointments', 'implementer')) {
                    $table->string('implementer')->nullable()->after('end_time');
                }

                if (!Schema::hasColumn('implementer_appointments', 'causer_id')) {
                    $table->foreignId('causer_id')->nullable()->after('title')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('implementer_appointments', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('status');
                }

                if (!Schema::hasColumn('implementer_appointments', 'cancelled_by')) {
                    $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('implementer_appointments', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('cancelled_by');
                }

                if (!Schema::hasColumn('implementer_appointments', 'completed_by')) {
                    $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('implementer_appointments', 'implementer_assigned_date')) {
                    $table->timestamp('implementer_assigned_date')->nullable()->after('implementer');
                }

                if (!Schema::hasColumn('implementer_appointments', 'location')) {
                    $table->string('location')->nullable()->after('optional_attendees');
                }

                if (!Schema::hasColumn('implementer_appointments', 'event_id')) {
                    $table->string('event_id')->nullable()->after('location');
                }

                if (!Schema::hasColumn('implementer_appointments', 'details')) {
                    $table->text('details')->nullable()->after('event_id');
                }

                // Make sure required_attendees is JSON
                if (Schema::hasColumn('implementer_appointments', 'required_attendees')) {
                    $table->json('required_attendees')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table if it's an existing table with data
        // Instead, remove the columns we added
        if (Schema::hasTable('implementer_appointments')) {
            Schema::table('implementer_appointments', function (Blueprint $table) {
                // Only drop columns we added in this migration
                $columnsToRemove = [
                    'session',
                    'causer_id',
                    'cancelled_at',
                    'cancelled_by',
                    'completed_at',
                    'completed_by',
                    'implementer_assigned_date',
                    'location',
                    'event_id',
                    'details'
                ];

                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('implementer_appointments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
