<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * إصلاح هيكل جدول sellers:
     * 1. إصلاح Foreign Key ليشير إلى users.user_id بدلاً من users.id
     * 2. جعل shop_name nullable (التاجر ينشئ حسابه أولاً ثم يملأ بيانات المتجر)
     * 3. التأكد من وجود عمود shop_image
     * 4. إضافة unique constraint على user_id (كل مستخدم له متجر واحد فقط)
     */
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            // 1. حذف الـ Foreign Key القديم إذا وجد
            //    نستخدم try-catch لأن الاسم قد يختلف
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist or have a different name
            }
        });

        Schema::table('sellers', function (Blueprint $table) {
            // 2. جعل shop_name اختيارياً (nullable)
            $table->string('shop_name')->nullable()->change();

            // 3. إضافة shop_image إذا لم يكن موجوداً
            if (!Schema::hasColumn('sellers', 'shop_image')) {
                $table->string('shop_image')->nullable()->after('shop_description');
            }

            // 4. إعادة إنشاء Foreign Key بشكل صحيح يشير إلى users.user_id
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // 5. إضافة unique index على user_id لمنع تكرار المتجر لنفس المستخدم
        // نتحقق أولاً من عدم وجوده
        $indexExists = collect(DB::select("SHOW INDEX FROM sellers WHERE Column_name = 'user_id' AND Non_unique = 0"))
            ->isNotEmpty();

        if (!$indexExists) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->unique('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            try { $table->dropForeign(['user_id']); } catch (\Exception $e) {}
            try { $table->dropUnique(['user_id']); } catch (\Exception $e) {}

            $table->string('shop_name')->nullable(false)->change();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};
