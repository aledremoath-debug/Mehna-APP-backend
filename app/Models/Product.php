<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($product) {
            // Delete all product images
            foreach ($product->images as $image) {
                $image->delete();
            }
        });
    }

    protected $fillable = [
        'seller_id',
        'main_category_id',
        'product_category_id',
        'product_name',
        'description',
        'price',
        'stock_quantity',
        'additional_specs'
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
