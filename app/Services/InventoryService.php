<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Tambah stok produk (barang datang / pembelian / restok).
     */
    public function restock(Product|int $product, float $quantity, string $note, ?User $user = null): Product
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah barang masuk harus lebih dari 0.',
            ]);
        }

        $productId = $product instanceof Product ? $product->id : $product;

        return DB::transaction(function () use ($productId, $quantity, $note, $user): Product {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            if (
                in_array(strtolower((string) $lockedProduct->unit), ['pcs', 'tabung'], true)
                && floor($quantity) !== (float) $quantity
            ) {
                throw ValidationException::withMessages([
                    'quantity' => "Jumlah {$lockedProduct->name} harus berupa bilangan bulat untuk satuan {$lockedProduct->unit}.",
                ]);
            }

            $before = (float) $lockedProduct->stock;
            $after = $before + $quantity;

            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement(
                    'SET @silaturizky_user_id = ?, @silaturizky_transaction_id = NULL, @silaturizky_stock_type = ?',
                    [$user?->id, 'incoming']
                );

                try {
                    $lockedProduct->update(['stock' => $after]);
                    // Perbarui keterangan dan user pada log terakhir jika trigger yang membuatnya
                    StockLog::query()
                        ->where('product_id', $lockedProduct->id)
                        ->where('type', 'incoming')
                        ->where('stock_before', $before)
                        ->where('stock_after', $after)
                        ->latest('id')
                        ->limit(1)
                        ->update([
                            'user_id' => $user?->id,
                            'note' => $note,
                        ]);
                } finally {
                    DB::statement('SET @silaturizky_user_id = NULL, @silaturizky_transaction_id = NULL, @silaturizky_stock_type = NULL');
                }
            } else {
                $lockedProduct->update(['stock' => $after]);

                StockLog::create([
                    'product_id' => $lockedProduct->id,
                    'user_id' => $user?->id,
                    'type' => 'incoming',
                    'quantity_change' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference' => 'restock:' . $lockedProduct->id . ':' . Str::uuid(),
                    'note' => $note,
                ]);
            }

            return $lockedProduct;
        });
    }

    /**
     * Penyesuaian stok (stock opname / penyesuaian manual).
     */
    public function adjustStock(Product|int $product, float $newStock, string $reason, ?User $user = null): Product
    {
        if ($newStock < 0) {
            throw ValidationException::withMessages([
                'stock' => 'Stok produk tidak boleh bernilai negatif.',
            ]);
        }

        $productId = $product instanceof Product ? $product->id : $product;

        return DB::transaction(function () use ($productId, $newStock, $reason, $user): Product {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            if (
                in_array(strtolower((string) $lockedProduct->unit), ['pcs', 'tabung'], true)
                && floor($newStock) !== (float) $newStock
            ) {
                throw ValidationException::withMessages([
                    'stock' => "Stok {$lockedProduct->name} harus berupa bilangan bulat untuk satuan {$lockedProduct->unit}.",
                ]);
            }

            $before = (float) $lockedProduct->stock;
            $diff = $newStock - $before;

            if (abs($diff) < 0.0001) {
                return $lockedProduct;
            }

            $type = $diff > 0 ? 'adjustment_in' : 'adjustment_out';

            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement(
                    'SET @silaturizky_user_id = ?, @silaturizky_transaction_id = NULL, @silaturizky_stock_type = ?',
                    [$user?->id, $type]
                );

                try {
                    $lockedProduct->update(['stock' => $newStock]);
                    StockLog::query()
                        ->where('product_id', $lockedProduct->id)
                        ->where('type', $type)
                        ->where('stock_before', $before)
                        ->where('stock_after', $newStock)
                        ->latest('id')
                        ->limit(1)
                        ->update([
                            'user_id' => $user?->id,
                            'note' => $reason,
                        ]);
                } finally {
                    DB::statement('SET @silaturizky_user_id = NULL, @silaturizky_transaction_id = NULL, @silaturizky_stock_type = NULL');
                }
            } else {
                $lockedProduct->update(['stock' => $newStock]);

                StockLog::create([
                    'product_id' => $lockedProduct->id,
                    'user_id' => $user?->id,
                    'type' => $type,
                    'quantity_change' => $diff,
                    'stock_before' => $before,
                    'stock_after' => $newStock,
                    'reference' => 'adjustment:' . $lockedProduct->id . ':' . Str::uuid(),
                    'note' => $reason,
                ]);
            }

            return $lockedProduct;
        });
    }
}
