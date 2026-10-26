<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\StockLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Product {
            $initialStock = (float) ($data['stock'] ?? 0);
            $product = Product::create($data);

            if ($initialStock > 0) {
                StockLog::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'initial',
                    'quantity_change' => $initialStock,
                    'stock_before' => 0,
                    'stock_after' => $initialStock,
                    'reference' => 'initial:'.$product->id.':'.Str::uuid(),
                    'note' => 'Stok awal saat produk dibuat',
                ]);
            }

            return $product;
        });
    }
}
