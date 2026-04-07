<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    protected $fillable = [
        'customer_id',
        'provider_id',
        'service_id',
        'problem_description',
        'attachment_images',
        'status',
        'scheduled_at',
        'address',
        'latitude',
        'longitude',
        'product_id',
        'cancel_reason',
        'provider_notes',
        'added_cost',
        'cost_description'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'attachment_images' => 'array',
    ];

    protected $appends = [
        'customer_name',
        'product_name',
        'product_primary_image',
        'shop_name',
        'seller_id',
        'customer_full_address',
        'is_shared_product'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'user_id');
    }

    public function provider()
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'maintenance_request_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'maintenance_request_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getAttachmentImagesAttribute($value)
    {
        if (empty($value)) return [];

        $images = is_array($value) ? $value : json_decode($value, true);
        
        if (!is_array($images)) return [];

        return array_values(array_filter(array_map(function ($image) {
            if (empty($image)) return null;
            if (str_starts_with($image, 'http')) return $image;
            return asset('media/' . ltrim($image, '/'));
        }, $images)));
    }

    public function getCustomerNameAttribute()
    {
        return $this->customer?->full_name ?? 'عميل مجهول';
    }

    public function getProductNameAttribute()
    {
        return $this->product?->product_name;
    }

    public function getProductPrimaryImageAttribute()
    {
        $product = $this->product;
        if ($product && $product->images->count() > 0) {
            $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first();
            return url('media/' . $primaryImage->image_path);
        }
        return null;
    }

    public function getShopNameAttribute()
    {
        return $this->product?->seller?->shop_name;
    }

    public function getSellerIdAttribute()
    {
        return $this->product?->seller_id;
    }

    public function getCustomerFullAddressAttribute()
    {
        return $this->address ?? $this->customer?->address_description ?? 'غير محدد';
    }

    public function getIsSharedProductAttribute()
    {
        return !empty($this->product_id);
    }
}
