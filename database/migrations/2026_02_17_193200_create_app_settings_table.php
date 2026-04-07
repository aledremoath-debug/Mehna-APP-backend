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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('android_version')->default('1.0.0');
            $table->string('ios_version')->default('1.0.0');
            $table->boolean('android_update_mandatory')->default(false);
            $table->boolean('ios_update_mandatory')->default(false);
            $table->timestamps();
        });

        // Insert default record
        \Illuminate\Support\Facades\DB::table('app_settings')->insert([
            'android_version' => '1.0.0',
            'ios_version' => '1.0.0',
            'android_update_mandatory' => false,
            'ios_update_mandatory' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
