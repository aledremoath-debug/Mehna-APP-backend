<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceImage extends Model
{
    protected $fillable = [
        'service_id',
        'image_path',
        'is_primary',
    ];

    protected $appends = ['image_url'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('media/' . $this->image_path) : null;
    }
}
