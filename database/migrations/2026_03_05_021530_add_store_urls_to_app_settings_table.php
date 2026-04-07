<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('android_store_url')->nullable()->after('android_update_mandatory');
            $table->string('ios_store_url')->nullable()->after('android_store_url');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['android_store_url', 'ios_store_url']);
        });
    }
};
