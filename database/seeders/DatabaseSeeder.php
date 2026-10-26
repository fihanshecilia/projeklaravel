<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $catalog = [
            'Beras' => [
                ['BR-005', 'Beras Premium 5kg', 'pcs', 72000, 82000, 38, 10],
            ],
            'Minyak Goreng' => [
                ['MG-001', 'MinyakKita 1L', 'pcs', 15500, 18000, 42, 10],
            ],
            'Telur' => [
                ['TL-001', 'Telor Ayam 1kg', 'kg', 26000, 30000, 27, 10],
            ],
            'Gula' => [
                ['GL-001', 'Gula Pasir 1kg', 'pcs', 15500, 18000, 36, 10],
            ],
            'Gas LPG' => [
                ['GS-003', 'Gas LPG 3kg', 'tabung', 19000, 22000, 16, 10],
            ],
        ];

        foreach ($catalog as $categoryName => $items) {
            $category = Category::firstOrCreate(['name' => $categoryName]);

            foreach ($items as [$sku, $name, $unit, $purchasePrice, $sellingPrice, $stock, $minStock]) {
                $product = Product::firstOrCreate(['sku' => $sku], [
                    'category_id' => $category->id,
                    'name' => $name,
                    'unit' => $unit,
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'stock' => $stock,
                    'min_stock' => $minStock,
                ]);

                if ($product->wasRecentlyCreated) {
                    StockLog::create([
                        'product_id' => $product->id,
                        'type' => 'initial',
                        'quantity_change' => $stock,
                        'stock_before' => 0,
                        'stock_after' => $stock,
                        'reference' => "initial:{$sku}",
                        'note' => 'Stok awal saat setup toko',
                    ]);
                }
            }
        }
    }
}
