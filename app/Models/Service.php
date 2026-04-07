<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'main_category_id', 
        'sub_category_id', 
        'service_provider_id', 
        'title', 
        'description', 
        'price',
        'price_type',
        'status'
    ];

    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function provider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'service_id');
    }

    public function images()
    {
        return $this->hasMany(ServiceImage::class);
    }
}
