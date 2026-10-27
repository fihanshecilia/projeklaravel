<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    /**
     * Buat data pesanan pembelian baru (status: ordered).
     */
    public function createPurchase(array $data, ?User $user = null): Purchase
    {
        return DB::transaction(function () use ($data, $user): Purchase {
            $purchaseNumber = 'PO-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(4));

            $totalAmount = 0;
            $items = $data['items'] ?? [];

            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'Item produk pembelian tidak boleh kosong.',
                ]);
            }

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_id' => $data['supplier_id'] ?? null,
                'user_id' => $user?->id,
                'purchase_date' => $data['purchase_date'] ?? today(),
                'status' => 'ordered',
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = (float) $item['quantity'];
                $price = (float) $item['purchase_price'];
                $subtotal = $quantity * $price;
                $totalAmount += $subtotal;

                $purchase->details()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'quantity' => $quantity,
                    'purchase_price' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            $purchase->update(['total_amount' => $totalAmount]);

            return $purchase->load('details');
        });
    }

    /**
     * Konfirmasi penerimaan barang (restok dan tambah stok secara atomik).
     */
    public function receivePurchase(Purchase $purchase, ?User $user = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $user): Purchase {
            $lockedPurchase = Purchase::query()
                ->lockForUpdate()
                ->with('details')
                ->findOrFail($purchase->id);

            if ($lockedPurchase->status === 'received') {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan pembelian ini sudah pernah diterima sebelumnya.',
                ]);
            }

            if ($lockedPurchase->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan pembelian yang sudah dibatalkan tidak dapat diterima.',
                ]);
            }

            foreach ($lockedPurchase->details as $detail) {
                if (! $detail->product_id) {
                    continue;
                }

                $product = Product::query()
                    ->lockForUpdate()
                    ->find($detail->product_id);

                if (! $product) {
                    continue;
                }

                $before = (float) $product->stock;
                $quantity = (float) $detail->quantity;
                $after = $before + $quantity;

                // Perbarui harga beli terbaru jika diisi
                $newPurchasePrice = (float) $detail->purchase_price;
                if ($newPurchasePrice > 0) {
                    $product->purchase_price = $newPurchasePrice;
                }
                $product->stock = $after;

                if (DB::connection()->getDriverName() === 'mysql') {
                    DB::statement(
                        'SET @silaturizky_user_id = ?, @silaturizky_transaction_id = NULL, @silaturizky_stock_type = ?',
                        [$user?->id, 'incoming']
                    );

                    try {
                        $product->save();
                        StockLog::query()
                            ->where('product_id', $product->id)
                            ->where('type', 'incoming')
                            ->where('stock_before', $before)
                            ->where('stock_after', $after)
                            ->latest('id')
                            ->limit(1)
                            ->update([
                                'user_id' => $user?->id,
                                'note' => "Penerimaan PO #{$lockedPurchase->purchase_number}",
                            ]);
                    } finally {
                        DB::statement('SET @silaturizky_user_id = NULL, @silaturizky_transaction_id = NULL, @silaturizky_stock_type = NULL');
                    }
                } else {
                    $product->save();

                    StockLog::create([
                        'product_id' => $product->id,
                        'user_id' => $user?->id,
                        'type' => 'incoming',
                        'quantity_change' => $quantity,
                        'stock_before' => $before,
                        'stock_after' => $after,
                        'reference' => "po:{$lockedPurchase->id}:product:{$product->id}",
                        'note' => "Penerimaan PO #{$lockedPurchase->purchase_number}",
                    ]);
                }
            }

            $lockedPurchase->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            return $lockedPurchase;
        });
    }
}
