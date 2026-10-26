@extends('layouts.storefront')

@section('title', 'Histori Transaksi')

@section('content')
<main class="admin-page order-history-page">
    <div class="admin-page-head">
        <div>
            <p class="eyebrow">RIWAYAT TRANSAKSI</p>
            <h1>Histori Transaksi</h1>
            <p>
                Rekam jejak seluruh transaksi kasir POS toko.
            </p>
        </div>

        <nav class="admin-actions" aria-label="Menu histori">
            <a class="admin-button is-secondary"
                href="{{ route('pos.index') }}">
                ← Kasir POS
            </a>

            @if (auth()->user()->can('manage-store'))
                <a class="admin-button" href="{{ route('reports.index') }}">
                    Dashboard Admin
                </a>
            @endif
        </nav>
    </div>

    <section class="admin-card" style="margin-bottom: 1rem;">
        <form method="get" action="{{ route('pos.history') }}" class="report-filter" style="display: flex; gap: .75rem; flex-wrap: wrap; align-items: end;">
            <div>
                <label for="history-search">Nomor transaksi</label>
                <input id="history-search" name="search" type="search" value="{{ $search ?? '' }}" placeholder="TRX-...">
            </div>
            <div>
                <label for="history-date">Tanggal</label>
                <input id="history-date" name="date" type="date" value="{{ $date ?? '' }}">
            </div>
            <div>
                <label for="history-from">Dari</label>
                <input id="history-from" name="from" type="date" value="{{ $from ?? '' }}">
            </div>
            <div>
                <label for="history-to">Sampai</label>
                <input id="history-to" name="to" type="date" value="{{ $to ?? '' }}">
            </div>
            <button class="admin-button" type="submit">Cari</button>
            <a class="admin-button is-secondary" href="{{ route('pos.history') }}">Reset</a>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <div>
                <p class="eyebrow">SEMUA TRANSAKSI</p>
                <h2>Riwayat penjualan</h2>
            </div>
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

                <footer>
                    <strong>
                        Total Rp{{ number_format((float) $order->total, 0, ',', '.') }}
                    </strong>

                    <span class="history-channel">
                        Pembayaran:
                        {{ $order->payment_status === 'paid'
                            ? 'Terkonfirmasi'
                            : ($order->payment_status === 'refund_required'
                                ? 'Perlu pengembalian dana'
                                : ($order->payment_status === 'refunded'
                                    ? 'Dana dikembalikan'
                                    : ($order->payment_method === 'cash_on_pickup'
                                        ? 'Bayar saat diterima'
                                        : 'Menunggu verifikasi'))) }}
                    </span>

                    <div>
                        <span class="history-channel">
                            Kasir
                        </span>

                        <a class="history-track"
                            href="{{ route('pos.receipts.show', $order->transaction_number) }}">
                            Struk
                        </a>
                    </div>
                </footer>
            </article>
        @empty
            <p class="admin-empty">Belum ada riwayat transaksi.</p>
        @endforelse

        {{ $orders->links() }}
    </section>
</main>
@endsection