<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($provider) {
            // Delete all services
            foreach ($provider->services as $service) {
                $service->delete();
            }
            
            // Delete maintenance requests
            $provider->maintenanceRequests()->delete();
        });
    }

    protected $fillable = [
        'user_id',
        'main_category_id',
        'bio',
        'experience_years',
        'work_license',
        'is_available',
        'rating_average',
        'price_range'
    ];

    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'service_provider_id');
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'provider_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'rated_id', 'user_id');
    }
}
