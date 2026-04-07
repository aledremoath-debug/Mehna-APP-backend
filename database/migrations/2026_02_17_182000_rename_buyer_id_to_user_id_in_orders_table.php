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
            // Drop existing foreign key constraint if it exists
            $table->dropForeign(['buyer_id']);
            
            // Rename the column
            $table->renameColumn('buyer_id', 'user_id');

            // Add the foreign key constraint for the new column name
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['user_id']);

            // Rename the column back
            $table->renameColumn('user_id', 'buyer_id');

            // Restore the original foreign key constraint
            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
