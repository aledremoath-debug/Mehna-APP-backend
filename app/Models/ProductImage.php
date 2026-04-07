<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary'
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            // Delete physical file from storage
            if ($image->image_path) {
                $path = public_path($image->image_path);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        });
    }

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
