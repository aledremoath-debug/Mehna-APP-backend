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
    Schema::create('ai_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ai_session_id')->constrained('ai_chat_sessions')->onDelete('cascade');
    $table->enum('role', ['user', 'assistant']); // من المتحدث؟
    $table->text('content'); // نص الرسالة
    
    // الحقول التالية لتحليل الذكاء الاصطناعي (Metadata)
    $table->string('detected_intent')->nullable(); // مثال: 'search_provider'
    $table->string('entity_type')->nullable();    // مثال: 'plumber'
    $table->json('suggested_ids')->nullable();    // تخزين الـ IDs لأفضل المهنيين المقترحين وقتها
    
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
