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
        Schema::create('service_providers', function (Blueprint $table) {
    $table->id();
    // ربط مع جدول المستخدمين الأساسي
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('profession'); // سباك، كهربائي، إلخ
    $table->text('bio')->nullable();
    $table->integer('experience_years')->default(0);
    $table->string('work_license')->nullable(); // صورة الترخيص أو الهوية
    $table->boolean('is_available')->default(true);
    $table->decimal('rating_average', 3, 2)->default(0); // متوسط التقييمات
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
