<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $categoryImages = [
            'Beras' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=720&q=85',
            'Minyak Goreng' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=720&q=85',
            'Telur' => 'https://images.unsplash.com/photo-1518569656558-1f25e69d93d7?auto=format&fit=crop&w=720&q=85',
            'Gula' => 'https://images.unsplash.com/photo-1558642452-9d2a7deb7f62?auto=format&fit=crop&w=720&q=85',
            'Gas LPG' => 'https://images.unsplash.com/photo-1603398938378-e54eab446dde?auto=format&fit=crop&w=720&q=85',
        ];

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->with('category:id,name')
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($categoryImages): array {
                $image = $product->image_path
                    ? (
                        Str::startsWith(
                            $product->image_path,
                            ['http://', 'https://']
                        )
                            ? $product->image_path
                            : asset('storage/'.$product->image_path)
                    )
                    : (
                        $categoryImages[$product->category?->name]
                        ?? $categoryImages['Beras']
                    );

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category?->name ?? 'Lainnya',
                    'price' => (float) $product->selling_price,
                    'unit' => $product->unit,
                    'stock' => (float) $product->stock,
                    'min_stock' => (float) $product->min_stock,
                    'image' => $image,
                ];
            });

        return view('storefront.index', compact(
            'categories',
            'products'
        ));
    }

    public function store(
        Request $request,
        TransactionService $transactions
    ): RedirectResponse {
        $customer = $request->validate([
            'customer_name' => [
                'required', 'string', 'max:150',
            ],
            'customer_phone' => [
                'required', 'string', 'max:40',
            ],
            'pickup_note' => [
                'nullable', 'string', 'max:2000',
            ],
            'delivery_type' => [
                'required', 'in:pickup,delivery',
            ],
            'payment_method' => [
                'required', 'in:cash_on_pickup,bank_transfer,qris',
            ],
            'shipping_address' => [
                'required_if:delivery_type,delivery',
                'nullable', 'string', 'max:2000',
            ],
        ]);

        $transaction = $transactions->create([
            ...$customer,
            'items' => $request->input('items', []),
            'amount_paid' => 0,
            'status' => 'pending_pickup',
        ], $request->user());

        $orderUrl = URL::temporarySignedRoute(
            'storefront.orders.show',
            now()->addDays(30),
            [
                'transactionNumber' => $transaction->transaction_number,
            ]
        );

        return redirect()->to($orderUrl);
    }

    public function show(
        Request $request,
        string $transactionNumber
    ): View {
        $transaction = Transaction::query()
            ->with('details')
            ->where('transaction_number', $transactionNumber)
            ->where('sales_channel', 'storefront')
            ->firstOrFail();

        $isOwner = $request->user() !== null
            && $transaction->user_id !== null
            && (int) $transaction->user_id
                === (int) $request->user()->id;

        $isAuthorizedEmployee =
            $request->user()?->canAccessPos() ?? false;

        abort_unless(
            $isOwner
                || $isAuthorizedEmployee
                || $request->hasValidSignature(),
            404
        );

        $items = $transaction->details->map(
            function ($detail): string {
                $quantity = rtrim(
                    rtrim((string) $detail->quantity, '0'),
                    '.'
                );

                return sprintf(
                    '• %s × %s %s — Rp%s',
                    $detail->product_name,
                    $quantity,
                    $detail->unit,
                    number_format(
                        (float) $detail->subtotal,
                        0,
                        ',',
                        '.'
                    )
                );
            }
        );

        $deliveryLabel = $transaction->delivery_type === 'delivery'
            ? 'Diantar'
            : 'Ambil di toko';

        $paymentLabel = match ($transaction->payment_method) {
            'bank_transfer' => 'Transfer bank',
            'qris' => 'QRIS',
            default => 'Tunai saat pesanan diterima (COD)',
        };

        $message = implode("\n", [
            'Halo TOKO SILATURIZKY, saya ingin konfirmasi pesanan:',
            'No. pesanan: '.$transaction->transaction_number,
            'Nama: '.$transaction->customer_name,
            'WhatsApp: '.$transaction->customer_phone,
            'Pemenuhan: '.$deliveryLabel,
            ...(
                $transaction->delivery_type === 'delivery'
                    ? [
                        'Alamat antar: '
                        .$transaction->shipping_address,
                    ]
                    : [
                        'Lokasi pengambilan: Toko Silaturizky, '
                        .config('services.store_address'),
                    ]
            ),
            'Pembayaran: '.$paymentLabel,
            'Catatan: '.($transaction->pickup_note ?: '—'),
            '',
            ...$items->all(),
            '',
            'Total pesanan: Rp'.number_format(
                (float) $transaction->total,
                0,
                ',',
                '.'
            ),
        ]);

        $phone = preg_replace(
            '/\D+/',
            '',
            (string) config('services.store_whatsapp_number')
        );

        $whatsappLink = $phone
            ? 'https://wa.me/'.$phone.'?text='.rawurlencode($message)
            : null;

        return view('storefront.order', compact(
            'transaction',
            'whatsappLink'
        ));
    }
}