@extends('layouts.storefront')

@section('title', 'Pesanan diterima')

@section('content')
<main class="order-page" data-order-confirmation>
    <div class="order-card">
        <span class="order-check" aria-hidden="true">✓</span>

        <p class="eyebrow">
            {{ $transaction->status === 'cancelled'
                ? 'PESANAN DIBATALKAN'
                : 'PESANAN BERHASIL DICATAT' }}
        </p>

        <h1>
            Terima kasih,<br>
            <em>{{ $transaction->customer_name }}.</em>
        </h1>

        <p class="order-intro">
            Status pesanan:
            <strong>
                {{ match ($transaction->status) {
                    'pending', 'pending_pickup' => 'Menunggu konfirmasi toko',
                    'processing' => 'Sedang diproses',
                    'delivering' => 'Sedang diantar',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst($transaction->status),
                } }}
            </strong>.
            Muat ulang halaman ini untuk melihat pembaruan status.
        </p>

        <div class="order-reference">
            <span>Nomor pesanan</span>
            <strong>{{ $transaction->transaction_number }}</strong>
        </div>

        <div class="order-reference">
            <span>Status pembayaran</span>
            <strong>
                {{ $transaction->payment_status === 'paid'
                    ? 'Sudah dikonfirmasi'
                    : ($transaction->payment_status === 'refund_required'
                        ? 'Perlu pengembalian dana'
                        : ($transaction->payment_status === 'refunded'
                            ? 'Dana dikembalikan'
                            : ($transaction->payment_method === 'cash_on_pickup'
                                ? 'Bayar saat barang diterima'
                                : 'Menunggu konfirmasi pembayaran'))) }}
            </strong>
        </div>

        @if (
            $transaction->payment_status === 'pending'
            && in_array(
                $transaction->payment_method,
                ['bank_transfer', 'qris'],
                true
            )
        )
            <p class="checkout-note">
                Pembayaran transfer/QRIS diverifikasi secara manual oleh toko.
                Website ini belum memakai payment gateway otomatis.
                Hubungi toko melalui WhatsApp untuk instruksi pembayaran;
                pesanan belum diproses sampai dana terverifikasi.
            </p>
        @endif

        <div class="order-reference">
            <span>Pemenuhan & pembayaran</span>
            <strong>
                {{ $transaction->delivery_type === 'delivery'
                    ? 'Diantar'
                    : 'Ambil di toko' }}
                ·
                {{ match ($transaction->payment_method) {
                    'bank_transfer' => 'Transfer bank',
                    'qris' => 'QRIS',
                    default => 'Tunai / COD',
                } }}
            </strong>
        </div>

        @if ($transaction->shipping_address)
            <div class="order-reference">
                <span>Alamat pengantaran</span>
                <strong>{{ $transaction->shipping_address }}</strong>
            </div>
        @endif

        <div class="order-items">
            @foreach ($transaction->details as $detail)
                <div>
                    <span>
                        {{ $detail->product_name }}
                        <small>
                            {{ rtrim(rtrim((string) $detail->quantity, '0'), '.') }}
                            {{ $detail->unit }}
                        </small>
                    </span>

                    <strong>
                        Rp{{ number_format((float) $detail->subtotal, 0, ',', '.') }}
                    </strong>
                </div>
            @endforeach
        </div>

        <div class="order-total">
            <span>Total pesanan</span>
            <strong>
                Rp{{ number_format((float) $transaction->total, 0, ',', '.') }}
            </strong>
        </div>

        @if ($whatsappLink && $transaction->status !== 'cancelled')
            <a class="whatsapp-order" href="{{ $whatsappLink }}">
                <span aria-hidden="true">◉</span>
                Buka WhatsApp toko
            </a>

            <p class="checkout-note">
                Tekan tombol di atas jika ingin mengirim konfirmasi
                melalui WhatsApp toko.
            </p>
        @elseif ($transaction->status !== 'cancelled')
            <p class="login-error" role="status">
                Pesanan tersimpan. WhatsApp toko belum dikonfigurasi;
                hubungi pemilik toko untuk konfirmasi.
            </p>
        @endif

        <a class="hero-cta" href="{{ route('storefront.index') }}">
            Kembali ke toko <span aria-hidden="true">→</span>
        </a>
    </div>
</main>
@endsection