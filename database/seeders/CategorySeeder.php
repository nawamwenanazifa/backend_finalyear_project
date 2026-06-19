<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Gomesi', 'description' => 'Traditional Ugandan formal dress for women, characterized by a square neckline and a sash.'],
            ['name' => 'Busuuti', 'description' => 'A classic Ugandan traditional dress.'],
            ['name' => 'Changing Dresses', 'description' => 'Modern changing dresses for brides during the reception.'],
            ['name' => 'Bridal Gowns', 'description' => 'Stunning white bridal gowns.'],
            ['name' => 'Bridesmaid Dresses', 'description' => 'Beautiful bridesmaid dresses in various colors.'],
            ['name' => 'Accessories', 'description' => 'Bridal accessories including veils, tiaras, and jewelry.'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
