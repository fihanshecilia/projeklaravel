<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TransactionService
{
    public function create(array $data, ?User $user = null): Transaction
    {
        $validated = Validator::make($data, [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required', 'integer', 'exists:products,id',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'amount_paid' => ['required', 'numeric', 'gte:0'],
            'payment_method' => [
                'nullable',
                Rule::in([
                    'cash',
                    'qris',
                    'cash_on_pickup',
                    'bank_transfer',
                ]),
            ],
            'payment_verified' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'status' => [
                'nullable',
                Rule::in(['completed', 'pending', 'pending_pickup']),
            ],
            'delivery_type' => [
                'nullable',
                Rule::in(['pickup', 'delivery']),
            ],
            'customer_name' => [
                'required_if:status,pending,pending_pickup',
                'nullable', 'string', 'max:150',
            ],
            'customer_phone' => [
                'required_if:status,pending,pending_pickup',
                'nullable', 'string', 'max:40',
            ],
            'shipping_address' => [
                'nullable', 'string', 'max:2000',
            ],
            'pickup_note' => [
                'nullable', 'string', 'max:2000',
            ],
        ])->validate();

        DB::beginTransaction();

        try {
            $status = $validated['status'] ?? 'completed';

            $isPending = in_array(
                $status,
                ['pending', 'pending_pickup'],
                true
            );

            $paymentMethod = $validated['payment_method']
                ?? ($isPending ? 'cash_on_pickup' : 'cash');

            $allowedMethods = $isPending
                ? ['cash_on_pickup', 'bank_transfer', 'qris']
                : ['cash', 'qris'];

            if (! in_array($paymentMethod, $allowedMethods, true)) {
                throw ValidationException::withMessages([
                    'payment_method' =>
                        'Metode pembayaran tidak sesuai dengan jenis transaksi.',
                ]);
            }

            /*
             * QRIS POS harus dikonfirmasi oleh pegawai yang berwenang.
             * Nilai dari browser saja tidak cukup tanpa pemeriksaan role.
             */
            if (! $isPending && $paymentMethod === 'qris') {
                $isAuthorizedEmployee = $user !== null
                    && $user->isEmployee();

                $isVerified = filter_var(
                    $validated['payment_verified'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );

                if (! $isAuthorizedEmployee || ! $isVerified) {
                    throw ValidationException::withMessages([
                        'payment_verified' =>
                            'Pegawai harus memverifikasi pembayaran QRIS terlebih dahulu.',
                    ]);
                }
            }

            /*
             * Gunakan bilangan bulat dalam satuan 0,001 agar
             * pemeriksaan kuantitas stok pecahan lebih konsisten.
             */
            $quantities = [];

            foreach ($validated['items'] as $item) {
                $productId = (int) $item['product_id'];
                $scaledQuantity = (float) $item['quantity'] * 1000;

                if (
                    abs($scaledQuantity - round($scaledQuantity))
                    > 0.000001
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'Jumlah produk maksimal memiliki tiga angka desimal.',
                    ]);
                }

                $quantityMilli = (int) round($scaledQuantity);

                if ($quantityMilli < 1) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah produk minimal 0,001.',
                    ]);
                }

                $quantities[$productId] =
                    ($quantities[$productId] ?? 0) + $quantityMilli;
            }

            ksort($quantities);

            $products = Product::query()
                ->with('category')
                ->whereIn('id', array_keys($quantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];
            $totalCents = 0;
            $totalCostCents = 0;

            foreach ($quantities as $productId => $quantityMilli) {
                $product = $products->get($productId);

                if (
                    ! $product
                    || ! $product->is_active
                    || ! $product->category?->is_active
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'Salah satu produk atau kategorinya sudah tidak tersedia.',
                    ]);
                }

                if (
                    in_array(
                        strtolower((string) $product->unit),
                        ['pcs', 'tabung'],
                        true
                    )
                    && $quantityMilli % 1000 !== 0
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "Jumlah {$product->name} harus berupa bilangan bulat untuk satuan {$product->unit}.",
                    ]);
                }

                $stockBefore = (int) round(
                    (float) $product->stock * 1000
                );

                if ($stockBefore < $quantityMilli) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "Stok {$product->name} tidak mencukupi.",
                    ]);
                }

                $unitPriceCents = (int) round(
                    (float) $product->selling_price * 100
                );

                $unitCostCents = (int) round(
                    (float) $product->purchase_price * 100
                );

                $lineTotalCents = (int) round(
                    $unitPriceCents * $quantityMilli / 1000
                );

                $lineCostCents = (int) round(
                    $unitCostCents * $quantityMilli / 1000
                );

                $lines[] = [
                    'product' => $product,
                    'quantityMilli' => $quantityMilli,
                    'stockBefore' => $stockBefore,
                    'unitPriceCents' => $unitPriceCents,
                    'unitCostCents' => $unitCostCents,
                    'lineTotalCents' => $lineTotalCents,
                ];

                $totalCents += $lineTotalCents;
                $totalCostCents += $lineCostCents;
            }

            /*
             * Pesanan online belum dianggap dibayar pada saat checkout.
             * Pembayaran baru dicatat setelah diverifikasi.
             */
            $amountPaidCents = $isPending
                ? 0
                : (int) round((float) $validated['amount_paid'] * 100);

            if (! $isPending && $amountPaidCents < $totalCents) {
                throw ValidationException::withMessages([
                    'amount_paid' =>
                        'Uang pembayaran kurang dari total transaksi.',
                ]);
            }

            if (! $isPending && $paymentMethod === 'qris') {
                $amountPaidCents = $totalCents;
            }

            $transaction = Transaction::create([
                'user_id' => $user?->id,
                'transaction_number' =>
                    'TRX-'.now()->format('ymdHis').'-'.Str::upper(
                        Str::random(6)
                    ),
                'subtotal' => $totalCents / 100,
                'total' => $totalCents / 100,
                'total_cost' => $totalCostCents / 100,
                'amount_paid' => $amountPaidCents / 100,
                'change_amount' => (
                    $isPending || $paymentMethod === 'qris'
                )
                    ? 0
                    : max(0, $amountPaidCents - $totalCents) / 100,
                'payment_method' => $paymentMethod,
                'payment_status' => $isPending ? 'pending' : 'paid',
                'status' => $status,
                'completed_at' => $isPending ? null : now(),
                'handled_by' => $user && $user->isEmployee()
                    ? $user->id
                    : null,
                'notes' => $validated['notes'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'shipping_address' =>
                    $validated['shipping_address'] ?? null,
                'pickup_note' => $validated['pickup_note'] ?? null,
                'sales_channel' => $isPending ? 'storefront' : 'pos',
                'delivery_type' =>
                    $validated['delivery_type'] ?? 'pickup',
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                $quantityMilli = $line['quantityMilli'];
                $stockBefore = $line['stockBefore'];
                $stockAfter = $stockBefore - $quantityMilli;
                $quantity = $quantityMilli / 1000;

                $transaction->details()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'quantity' => $quantity,
                    'unit_price' => $line['unitPriceCents'] / 100,
                    'purchase_price' => $line['unitCostCents'] / 100,
                    'subtotal' => $line['lineTotalCents'] / 100,
                ]);

                $product->stock = $stockAfter / 1000;

                if (DB::connection()->getDriverName() === 'mysql') {
                    DB::statement(
                        'SET @silaturizky_user_id = ?, @silaturizky_transaction_id = ?, @silaturizky_stock_type = ?',
                        [
                            $transaction->handled_by ?? $user?->id,
                            $transaction->id,
                            $isPending ? 'reservation' : 'sale',
                        ]
                    );

                    try {
                        $product->save();
                    } finally {
                        DB::statement(
                            'SET @silaturizky_user_id = NULL, @silaturizky_transaction_id = NULL, @silaturizky_stock_type = NULL'
                        );
                    }
                } else {
                    $product->save();

                    StockLog::create([
                        'product_id' => $product->id,
                        'user_id' =>
                            $transaction->handled_by ?? $user?->id,
                        'transaction_id' => $transaction->id,
                        'type' => $isPending ? 'reservation' : 'sale',
                        'quantity_change' => -$quantity,
                        'stock_before' => $stockBefore / 1000,
                        'stock_after' => $stockAfter / 1000,
                        'reference' =>
                            "transaction:{$transaction->id}:product:{$product->id}",
                        'note' =>
                            'Penjualan '.$transaction->transaction_number,
                    ]);
                }
            }

            DB::commit();

            return $transaction->load('details');
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }
}