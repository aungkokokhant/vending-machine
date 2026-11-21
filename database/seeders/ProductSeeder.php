<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Coke', 'slug' => 'coke', 'price' => '3.990'],
            ['name' => 'Pepsi', 'slug' => 'pepsi', 'price' => '6.885'],
            ['name' => 'Water', 'slug' => 'water', 'price' => '0.500'],
        ] as $product) {
            Product::withTrashed()->firstOrCreate(
                ['slug' => $product['slug']],
                [...$product, 'quantity_available' => 20],
            );
        }
    }
}
