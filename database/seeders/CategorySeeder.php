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
            ['name' => 'Gomesi', 'icon' => 'heroicon-o-user', 'description' => 'Traditional Ugandan formal dress for women, characterized by a square neckline and a sash.'],
            ['name' => 'Busuuti', 'icon' => 'heroicon-o-sparkles', 'description' => 'A classic Ugandan traditional dress.'],
            ['name' => 'Changing Dresses', 'icon' => 'heroicon-o-star', 'description' => 'Modern changing dresses for brides during the reception.'],
            ['name' => 'Bridal Gowns', 'icon' => 'heroicon-o-check-badge', 'description' => 'Stunning white bridal gowns.'],
            ['name' => 'Bridesmaid Dresses', 'icon' => 'heroicon-o-heart', 'description' => 'Beautiful bridesmaid dresses in various colors.'],
            ['name' => 'Accessories', 'icon' => 'heroicon-o-shopping-bag', 'description' => 'Bridal accessories including veils, tiaras, and jewelry.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
