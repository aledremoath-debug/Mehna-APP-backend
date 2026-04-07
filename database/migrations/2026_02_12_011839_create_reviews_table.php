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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rater_id')->constrained('users')->onDelete('cascade'); // العميل الذي يقيم
            $table->foreignId('rated_id')->constrained('users')->onDelete('cascade'); // الفني أو التاجر الذي يتم تقييمه
            
            // ربط اختياري بالطلب لضمان الموثوقية
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('maintenance_request_id')->nullable()->constrained('maintenance_requests')->onDelete('set null');
            
            $table->integer('rating'); // من 1 إلى 5
            $table->text('comment')->nullable();
            $table->timestamps();
            
            // إضافة فهرس لتحسين سرعة البحث والاستعلام عن التقييمات
            $table->index(['rated_id', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
