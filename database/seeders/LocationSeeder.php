<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void {
    \App\Models\Location::create([
        'governorate' => 'تعز',
        'district' => 'القاهرة',
        'latitude' => 13.5813,
        'longitude' => 44.0134
    ]);
}
}
