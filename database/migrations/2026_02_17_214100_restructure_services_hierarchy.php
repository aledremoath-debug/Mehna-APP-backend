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
        // 1. Rename column in services table
        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('service_name', 'main_category');
        });

        // 2. Create sub_services table
        Schema::create('sub_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('sub_category_name');
            $table->timestamps();
        });

        // 3. Create pivot table for providers and sub-services with price
        Schema::create('provider_sub_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained('service_providers')->onDelete('cascade');
            $table->foreignId('sub_service_id')->constrained('sub_services')->onDelete('cascade');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_sub_service');
        Schema::dropIfExists('sub_services');
        
        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('main_category', 'service_name');
        });
    }
};
