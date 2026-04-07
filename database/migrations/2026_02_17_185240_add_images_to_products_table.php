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
        Schema::table('products', function (Blueprint $table) {
            // Rename existing product_image if it exists and we want to keep it as primary
            // However, to keep it simple and safe, we can just add new ones or rename.
            // Let's rename product_image to image_primary if it exists.
            if (Schema::hasColumn('products', 'product_image')) {
                $table->renameColumn('product_image', 'image_primary');
            } else {
                $table->string('image_primary')->nullable()->after('stock_quantity');
            }

            $table->string('image_sub1')->nullable()->after('image_primary');
            $table->string('image_sub2')->nullable()->after('image_sub1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('image_primary', 'product_image');
            $table->dropColumn(['image_sub1', 'image_sub2']);
        });
    }
};
