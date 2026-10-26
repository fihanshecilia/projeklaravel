@extends('layouts.storefront')

@section('title', 'Struk transaksi')

@section('content')
<main class="receipt-page">
    <style>
        @media print {
            .receipt-actions, .site-header, .site-footer {
                display: none !important;
            }
            body, .receipt-page {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .thermal-receipt {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: 80mm !important;
                margin: 0 auto !important;
                padding: 10px 0 !important;
            }
        }
    </style>
    <section class="receipt-actions" style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem;">
        <a class="login-back" href="{{ route('pos.index') }}">← Kembali ke kasir</a>
        <button type="button" onclick="window.print()" class="login-back" style="cursor: pointer; background: #527d59; color: #ffffff; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak Struk Thermal
        </button>
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
@endsection
