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
        Schema::table('sellers', function (Blueprint $table) {
            if (!Schema::hasColumn('sellers', 'rating_count')) {
                $table->integer('rating_count')->default(0)->after('rating_average');
            }
        });

        Schema::table('service_providers', function (Blueprint $table) {
            if (!Schema::hasColumn('service_providers', 'rating_count')) {
                $table->integer('rating_count')->default(0)->after('rating_average');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn('rating_count');
        });

        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropColumn('rating_count');
        });
    }
};
