<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('android_update_disabled')->default(false)->after('android_update_mandatory');
            $table->boolean('ios_update_disabled')->default(false)->after('ios_update_mandatory');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['android_update_disabled', 'ios_update_disabled']);
        });
    }
};
