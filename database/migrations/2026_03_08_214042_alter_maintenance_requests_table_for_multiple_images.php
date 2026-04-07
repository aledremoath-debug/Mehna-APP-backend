<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->json('attachment_images')->nullable();
        });
        
        // Copy old data if necessary (we'll just wrap existing strings in an array)
        \Illuminate\Support\Facades\DB::statement("UPDATE maintenance_requests SET attachment_images = JSON_ARRAY(attachment_image) WHERE attachment_image IS NOT NULL");

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn('attachment_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->string('attachment_image')->nullable();
        });

        // Best-effort reverse copy
        \Illuminate\Support\Facades\DB::statement("UPDATE maintenance_requests SET attachment_image = JSON_UNQUOTE(JSON_EXTRACT(attachment_images, '$[0]')) WHERE attachment_images IS NOT NULL");

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn('attachment_images');
        });
    }
};
