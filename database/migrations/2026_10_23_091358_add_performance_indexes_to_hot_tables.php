<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->index(['schedule_id', 'status'], 'claims_schedule_id_status_index');
            $table->index(['sender_id', 'status'], 'claims_sender_id_status_index');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['type', 'user_a_id', 'user_b_id'], 'conversations_type_user_pair_index');
            $table->index('last_message_at', 'conversations_last_message_at_index');
        });

        Schema::table('user_blocks', function (Blueprint $table) {
            $table->index('blocked_id', 'user_blocks_blocked_id_index');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'tickets_user_id_status_index');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->index(['ticket_id', 'created_at'], 'ticket_messages_ticket_id_created_at_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'schedules_status_created_at_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'sender_id', 'read_at'], 'messages_conversation_sender_read_index');
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropIndex('claims_schedule_id_status_index');
            $table->dropIndex('claims_sender_id_status_index');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_type_user_pair_index');
            $table->dropIndex('conversations_last_message_at_index');
        });

        Schema::table('user_blocks', function (Blueprint $table) {
            $table->dropIndex('user_blocks_blocked_id_index');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_user_id_status_index');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropIndex('ticket_messages_ticket_id_created_at_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_status_created_at_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_sender_read_index');
        });
    }
};
