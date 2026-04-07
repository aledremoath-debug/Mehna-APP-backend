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
        // 1. Drop the temporary pivot table from earlier refactor if it exists
        Schema::dropIfExists('provider_sub_service');
        Schema::dropIfExists('provider_sub_services'); // variant name check

        // 2. Drop foreign keys referencing services table before renaming it
        Schema::table('sub_services', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });
        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        // 3. Rename tables
        Schema::rename('services', 'main_categories');
        Schema::rename('sub_services', 'sub_categories');

        // 4. Update main_categories table
        Schema::table('main_categories', function (Blueprint $table) {
            $table->renameColumn('main_category', 'name');
            $table->renameColumn('service_image', 'image');
        });

        // 5. Update sub_categories table
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->renameColumn('service_id', 'main_category_id');
            $table->renameColumn('sub_category_name', 'name');
            $table->foreign('main_category_id')->references('id')->on('main_categories')->onDelete('cascade');
        });

        // 6. Create the NEW services table (Provider Offerings)
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_category_id')->constrained('main_categories')->onDelete('cascade');
            $table->foreignId('sub_category_id')->constrained('sub_categories')->onDelete('cascade');
            $table->foreignId('service_provider_id')->constrained('service_providers')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        // 7. Update service_providers table
        Schema::table('service_providers', function (Blueprint $table) {
            $table->renameColumn('service_id', 'main_category_id');
            $table->foreign('main_category_id')->references('id')->on('main_categories')->onDelete('set null');
        });

        // 8. Update maintenance_requests table to point to the new service offer
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse steps in opposite order
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::dropIfExists('services');

        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropForeign(['main_category_id']);
            $table->renameColumn('main_category_id', 'service_id');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropForeign(['main_category_id']);
            $table->renameColumn('main_category_id', 'service_id');
            $table->renameColumn('name', 'sub_category_name');
        });

        Schema::table('main_categories', function (Blueprint $table) {
            $table->renameColumn('name', 'main_category');
            $table->renameColumn('image', 'service_image');
        });

        Schema::rename('sub_categories', 'sub_services');
        Schema::rename('main_categories', 'services');

        // Restore foreign keys
        Schema::table('sub_services', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
        Schema::table('service_providers', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services');
        });
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services');
        });
    }
};
