<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // Index for sender/receiver combinations
            $table->index(['sender', 'receiver']);
            $table->index(['receiver', 'sender']);

            // Index for filtering unread customer messages
            $table->index(['is_from_customer', 'is_read', 'receiver']);

            // Index for date range filtering
            $table->index('created_at');

            // Composite index for common queries
            $table->index(['sender', 'receiver', 'created_at']);
            $table->index(['is_from_customer', 'is_read', 'created_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            // Index for phone lookup
            $table->index('phone');
            $table->index('lead_owner');
        });

        Schema::table('company_details', function (Blueprint $table) {
            // Index for contact number lookup
            $table->index('contact_no');
            $table->index('company_name');
        });
    }

    public function down()
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex(['sender', 'receiver']);
            $table->dropIndex(['receiver', 'sender']);
            $table->dropIndex(['is_from_customer', 'is_read', 'receiver']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['sender', 'receiver', 'created_at']);
            $table->dropIndex(['is_from_customer', 'is_read', 'created_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropIndex(['lead_owner']);
        });

        Schema::table('company_details', function (Blueprint $table) {
            $table->dropIndex(['contact_no']);
            $table->dropIndex(['company_name']);
        });
    }
};
