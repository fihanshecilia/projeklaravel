@extends('layouts.storefront')

@section('title', 'Riwayat belanja')

@section('content')
<main class="admin-page order-history-page">
    <div class="admin-page-head">
        <div>
            <p class="eyebrow">AKUN PELANGGAN</p>
            <h1>Riwayat belanja</h1>
            <p>Pesanan yang pernah dibuat dengan akunmu.</p>
        </div>

        <a class="admin-button is-secondary"
            href="{{ route('storefront.index') }}">
            Mulai belanja
        </a>
    </div>

    @forelse ($orders as $order)
        <article class="history-order">
            <header>
                <div>
                    <strong>{{ $order->transaction_number }}</strong>
                    <small>
                        {{ $order->created_at->format('d M Y, H:i') }}
                    </small>
                </div>

                <span class="history-status status-{{ $order->status }}">
                    {{ match ($order->status) {
                        'pending', 'pending_pickup' => 'Menunggu konfirmasi',
                        'processing' => 'Diproses',
                        'delivering' => 'Diantar',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($order->status),
                    } }}
                </span>
            </header>

            <div class="history-items">
                @foreach ($order->details as $detail)
                    <div>
                        <span>
                            {{ $detail->product_name }}
                            ×
                            {{ rtrim(rtrim((string) $detail->quantity, '0'), '.') }}
                            {{ $detail->unit }}
                        </span>

                        <strong>
                            Rp{{ number_format((float) $detail->subtotal, 0, ',', '.') }}
                        </strong>
                    </div>
                @endforeach
            </div>

            <p class="checkout-note">
                Pembayaran:
                {{ $order->payment_status === 'paid'
                    ? 'Terkonfirmasi'
                    : ($order->payment_status === 'refund_required'
                        ? 'Perlu pengembalian dana'
                        : ($order->payment_status === 'refunded'
                            ? 'Dana dikembalikan'
                            : ($order->payment_method === 'cash_on_pickup'
                                ? 'Bayar saat barang diterima'
                                : 'Menunggu konfirmasi toko'))) }}
            </p>

            <footer>
                <strong>
                    Total Rp{{ number_format((float) $order->total, 0, ',', '.') }}
                </strong>

                <div>
                    <a class="history-track"
                        href="{{ route('storefront.orders.show', $order->transaction_number) }}">
                        Lihat pesanan
                    </a>

                    @if ($order->details->contains(
                        fn ($detail): bool => $detail->product_id !== null
                    ))
                        <button class="history-buy-again" type="button"
                            data-buy-again='@json($order->details->whereNotNull("product_id")->map(fn ($detail): array => ["product_id" => $detail->product_id, "quantity" => (float) $detail->quantity])->values(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                            data-store-url="{{ route('storefront.index') }}">
                            Beli lagi
                        </button>
                    @endif
                </div>
            </footer>
        </article>
    @empty
        <section class="admin-card">
            <p class="admin-empty">
                Belum ada riwayat pesanan.
                Yuk, mulai belanja kebutuhanmu.
            </p>
        </section>
    @endforelse

    {{ $orders->links() }}
</main>
@endsection