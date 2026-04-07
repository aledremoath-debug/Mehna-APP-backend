<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainCategory extends Model
{
    protected $table = 'main_categories';
    
    protected $fillable = ['name', 'image'];

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class, 'main_category_id');
    }

    public function serviceProviders()
    {
        return $this->hasMany(ServiceProvider::class, 'main_category_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'main_category_id');
    }
}
