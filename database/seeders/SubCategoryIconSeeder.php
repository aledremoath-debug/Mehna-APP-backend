<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubCategory;

class SubCategoryIconSeeder extends Seeder
{
    public function run(): void
    {
        $updates = [
            1 => 'plumbing',
            2 => 'plumbing',
            3 => 'Icons.lightbulb',
            4 => 'electrical_services',
            5 => 'ac_unit',
            6 => 'Icons.devices_other',
            7 => 'Icons.devices_other'
        ];

        foreach ($updates as $id => $icon) {
            SubCategory::where('id', $id)->update(['icon' => $icon]);
        }
    }
}
