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
        Schema::table('orders', function (Blueprint $table) {
            // 1. Add buyer_type column
            $table->string('buyer_type')->default('user')->after('buyer_id'); // defaults to user, can be 'provider'

            // 2. Add seller_id column
            $table->foreignId('seller_id')->nullable()->constrained('sellers')->onDelete('set null')->after('provider_id');

            // 3. Status column already exists with 'pending', 'processing', 'completed', 'cancelled'.
            // If we need to modify it to include 'cancel' explicitly or change default, we can do it here.
            // For now, assuming existing 'status' enum is sufficient as per earlier check.

            // 4. Add location column
            $table->string('location')->nullable()->after('status');

            // 5. Add latitude and longitude columns
            $table->decimal('latitude', 10, 8)->nullable()->after('location');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['buyer_type', 'seller_id', 'location', 'latitude', 'longitude']);
        });
    }
};
