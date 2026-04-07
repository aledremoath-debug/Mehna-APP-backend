<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    public $timestamps = true;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($seller) {
            // Delete shop image file
            if ($seller->shop_image) {
                $path = public_path($seller->shop_image);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            // Delete all products belonging to this seller
            foreach ($seller->products as $product) {
                $product->delete();
            }
        });
    }

    /**
     * الحقول القابلة للتعبئة الجماعية
     * user_id: معرف المستخدم (Foreign Key → users.user_id)
     * shop_name: اسم المتجر
     * shop_description: وصف المتجر
     * commercial_register: رقم السجل التجاري
     * shop_image: مسار صورة/شعار المتجر
     */
    protected $fillable = [
        'user_id',
        'shop_name',
        'email',
        'phone',
        'location',
        'shop_description',
        'commercial_register',
        'shop_image',
        'rating_average',
        'rating_count',
    ];

    /**
     * علاقة: المتجر ينتمي لمستخدم واحد
     * الربط: sellers.user_id → users.user_id
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * علاقة: المتجر له عدة منتجات
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    /**
     * هل المتجر مكتمل البيانات؟
     */
    public function isComplete(): bool
    {
        return !empty($this->shop_name);
    }
}
