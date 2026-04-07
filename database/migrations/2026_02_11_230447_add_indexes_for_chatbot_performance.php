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
        // 1. ai_chat_sessions
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('session_status');
        });

        // 2. ai_messages
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->index('ai_session_id');
        });

        // 3. services
        Schema::table('services', function (Blueprint $table) {
            $table->index('service_name');
        });

        // 4. service_providers
        Schema::table('service_providers', function (Blueprint $table) {
            $table->index('rating_average');
            $table->index('service_id');
        });

        // 5. users
        Schema::table('users', function (Blueprint $table) {
            $table->index('full_name');
            $table->index('phone');
        });

        // 6. chatbot_knowledge (if exists)
        if (Schema::hasTable('chatbot_knowledge')) {
            Schema::table('chatbot_knowledge', function (Blueprint $table) {
                $table->index('question');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['session_status']);
        });

        Schema::table('ai_messages', function (Blueprint $table) {
            $table->dropIndex(['ai_session_id']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['service_name']);
        });

        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropIndex(['rating_average']);
            $table->dropIndex(['service_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['full_name']);
            $table->dropIndex(['phone']);
        });

        if (Schema::hasTable('chatbot_knowledge')) {
            Schema::table('chatbot_knowledge', function (Blueprint $table) {
                $table->dropIndex(['question']);
            });
        }
    }
};
