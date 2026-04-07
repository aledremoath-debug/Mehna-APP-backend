<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'users', 
            'locations',
            'main_categories',
            'sub_categories',
            'sellers', 
            'products', 
            'product_images',
            'service_providers', 
            'services', 
            'orders', 
            'order_details',
            'notifications', 
            'reviews',
            'user_passwords',
            'complaints',
            'app_settings',
            'maintenance_requests',
            'ai_chat_sessions',
            'ai_messages',
            'chat_sessions',
            'chatbot_knowledges'
        ];

        // 1. Convert all tables to InnoDB (Safe for MyISAM -> InnoDB)
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE `$table` ENGINE = InnoDB");
            }
        }

        // 2. Clear orphans
        DB::statement("DELETE FROM products WHERE seller_id NOT IN (SELECT id FROM sellers)");
        DB::statement("DELETE FROM sellers WHERE user_id NOT IN (SELECT user_id FROM users)");
        DB::statement("DELETE FROM service_providers WHERE user_id NOT IN (SELECT user_id FROM users)");
        DB::statement("DELETE FROM services WHERE service_provider_id NOT IN (SELECT id FROM service_providers)");

        // 3. Add Foreign Keys using raw SQL to be more precise and avoid index name clashes
        $this->addForeignKey('sellers', 'user_id', 'users', 'user_id');
        $this->addForeignKey('products', 'seller_id', 'sellers', 'id');
        $this->addForeignKey('service_providers', 'user_id', 'users', 'user_id');
        $this->addForeignKey('services', 'service_provider_id', 'service_providers', 'id');
        
        if (Schema::hasTable('order_details')) {
            $this->addForeignKey('order_details', 'order_id', 'orders', 'id');
        }
    }

    private function addForeignKey($table, $column, $refTable, $refColumn)
    {
        try {
            // First try to drop if it exists to avoid duplication errors
            $constraintName = "fk_{$table}_{$column}";
            
            // Check if constraint exists (MySQL specific)
            $exists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND CONSTRAINT_NAME = '$constraintName'");
            
            if ($exists) {
                DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$constraintName`");
            }

            // Also check for Laravel's default naming convention
            $laravelName = "{$table}_{$column}_foreign";
            $existsLaravel = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND CONSTRAINT_NAME = '$laravelName'");
            
            if ($existsLaravel) {
                DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$laravelName`");
            }

            // Add the new foreign key with ON DELETE CASCADE
            DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$laravelName` FOREIGN KEY (`$column`) REFERENCES `$refTable` (`$refColumn`) ON DELETE CASCADE");
            
        } catch (\Exception $e) {
            // Log or ignore if already set
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
