<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $gomesiCategory = Category::where('name', 'Gomesi')->first();
        $gownCategory = Category::where('name', 'Bridal Gowns')->first();

        $products = [
            [
                'category_id' => $gomesiCategory?->id,
                'name' => 'Royal Silk Gomesi',
                'description' => 'A beautiful pure silk Gomesi with intricate gold embroidery. Perfect for introduction ceremonies.',
                'image' => 'https://placehold.co/400x400/570013/FFFFFF/png?text=Royal+Silk+Gomesi',
                'price' => 450000,
                'stock_quantity' => 15,
                'low_stock_threshold' => 5,
                'is_featured' => true,
            ],
            [
                'category_id' => $gownCategory?->id,
                'name' => 'Ivory Lace Mermaid Gown',
                'description' => 'Elegant mermaid silhouette with French lace and a sweeping train. Includes a matching veil.',
                'image' => 'https://placehold.co/400x400/570013/FFFFFF/png?text=Ivory+Lace\nMermaid+Gown',
                'price' => 1200000,
                'stock_quantity' => 3,
                'low_stock_threshold' => 2,
                'is_featured' => true,
            ],
            [
                'category_id' => $gomesiCategory?->id,
                'name' => 'Classic Cotton Busuuti',
                'description' => 'Comfortable and stylish cotton Busuuti with vibrant traditional prints.',
                'image' => 'https://placehold.co/400x400/570013/FFFFFF/png?text=Classic+Cotton\nBusuuti',
                'price' => 150000,
                'stock_quantity' => 0, // Out of stock
                'low_stock_threshold' => 10,
                'is_featured' => false,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['name' => $product['name']], $product);
        }
    }
}
