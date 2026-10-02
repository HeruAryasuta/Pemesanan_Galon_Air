<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            'air-galon' => 'Air Galon',
            'lpg' => 'LPG',
            'air-mineral' => 'Air Mineral',
        ])->mapWithKeys(fn (string $name, string $slug): array => [
            $slug => Category::firstOrCreate(['slug' => $slug], ['name' => $name]),
        ]);

        $products = [
            [
                'slug' => 'galon-aqua-19-liter',
                'category' => 'air-galon',
                'name' => 'Galon Aqua 19L (Isi Ulang)',
                'description' => 'Air minum dalam kemasan galon isi ulang untuk kebutuhan harian.',
                'price' => 21000,
                'stock' => 25,
                'unit' => 'galon',
                'image_path' => 'images/products/galon-aqua-19l.svg',
            ],
            [
                'slug' => 'lpg-pertamina-3kg-refill',
                'category' => 'lpg',
                'name' => 'LPG Pertamina 3kg (Refill)',
                'description' => 'Tabung LPG 3 kg untuk kebutuhan memasak sehari-hari.',
                'price' => 19500,
                'stock' => 8,
                'unit' => 'tabung',
                'image_path' => 'images/products/lpg-pertamina-3kg.svg',
            ],
            [
                'slug' => 'galon-cleo-19-liter',
                'category' => 'air-galon',
                'name' => 'Galon Cleo 19L (Isi Ulang)',
                'description' => 'Air minum dalam kemasan galon isi ulang untuk keluarga.',
                'price' => 18500,
                'stock' => 12,
                'unit' => 'galon',
                'image_path' => 'images/products/galon-cleo-19l.svg',
            ],
            [
                'slug' => 'lpg-pertamina-12kg-biru',
                'category' => 'lpg',
                'name' => 'LPG Pertamina 12kg (Biru)',
                'description' => 'Tabung LPG 12 kg untuk penggunaan rumah tangga.',
                'price' => 215000,
                'stock' => 0,
                'unit' => 'tabung',
                'image_path' => 'images/products/lpg-pertamina-12kg.svg',
            ],
            [
                'slug' => 'aqua-dus-600ml-24',
                'category' => 'air-mineral',
                'name' => 'Aqua Dus (600ml x 24)',
                'description' => 'Satu dus berisi 24 botol air mineral ukuran 600 ml.',
                'price' => 48000,
                'stock' => 15,
                'unit' => 'dus',
                'image_path' => 'images/products/aqua-dus-24.svg',
            ],
        ];

        foreach ($products as $product) {
            $category = $categories->get($product['category']);
            unset($product['category']);

            Product::firstOrCreate(
                ['slug' => $product['slug']],
                ['category_id' => $category->id, ...$product],
            );
        }
    }
}
