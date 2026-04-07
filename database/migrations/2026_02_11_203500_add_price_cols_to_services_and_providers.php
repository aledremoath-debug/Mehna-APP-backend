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
        Schema::table('services', function (Blueprint $table) {
            $table->string('price_range')->nullable()->after('description'); // متوسط السعر العام
        });

        Schema::table('service_providers', function (Blueprint $table) {
            $table->string('price_range')->nullable()->after('rating_average'); // سعر مقدم الخدمة الخاص
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('price_range');
        });

        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropColumn('price_range');
        });
    }
};
