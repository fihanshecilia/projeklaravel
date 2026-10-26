@extends('layouts.storefront')

@section('title', 'Struk transaksi')

@section('content')
<main class="receipt-page">
    <section class="receipt-actions">
        <a class="login-back" href="{{ route('pos.index') }}">← Kembali ke kasir</a>
    </section>
    <article class="thermal-receipt">
        <header><span class="receipt-mark">S</span><h1>TOKO SILATURIZKY</h1><p>Belanja dekat, hati tenang.</p></header>
        <div class="receipt-rule"></div>
        <div class="receipt-meta">
            <span>No. {{ $transaction->transaction_number }}</span>
            <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
            <span>Kasir: {{ $transaction->user?->name ?? $transaction->handledBy?->name ?? 'Kasir' }}</span>
        </div>
        <div class="receipt-rule dotted"></div>
        <div class="receipt-items">
            @foreach ($transaction->details as $detail)
                <div class="receipt-item">
                    <span>
                        {{ $detail->product_name }}
                        <small>{{ rtrim(rtrim((string) $detail->quantity, '0'), '.') }} {{ $detail->unit }} × Rp{{ number_format((float) $detail->unit_price, 0, ',', '.') }}</small>
                    </span>
                    <strong>Rp{{ number_format((float) $detail->subtotal, 0, ',', '.') }}</strong>
                </div>
            @endforeach
        </div>
        <div class="receipt-rule dotted"></div>
        <div class="receipt-money"><span>Metode pembayaran</span><strong>{{ match ($transaction->payment_method) {
            'cash' => 'Tunai',
            'qris' => 'QRIS',
            'bank_transfer' => 'Transfer bank',
            'cash_on_pickup' => 'Bayar saat diambil',
            default => ucfirst(str_replace('_', ' ', (string) $transaction->payment_method)),
        } }}</strong></div>
        <div class="receipt-money"><span>Total</span><strong>Rp{{ number_format((float) $transaction->total, 0, ',', '.') }}</strong></div>
        <div class="receipt-money"><span>Dibayar</span><strong>Rp{{ number_format((float) $transaction->amount_paid, 0, ',', '.') }}</strong></div>
        <div class="receipt-money"><span>Kembalian</span><strong>Rp{{ number_format((float) $transaction->change_amount, 0, ',', '.') }}</strong></div>
        <div class="receipt-rule"></div>
        <footer><strong>Terima kasih telah berbelanja.</strong><span>Semoga harimu menyenangkan!</span></footer>
    </article>
</main>