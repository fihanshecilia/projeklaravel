@extends('layouts.storefront')

@section('title', 'Laporan toko')

@section('content')
<main class="admin-page">
    <div class="admin-page-head">
        <div>
            <p class="eyebrow">RINGKASAN OPERASIONAL</p>
            <h1>Laporan toko</h1>
            <p>Omzet, laba kotor dan stok yang perlu diperhatikan.</p>
        </div>

<nav class="admin-actions" aria-label="Menu laporan">
            <a class="admin-button is-secondary"
                href="{{ route('reports.stock') }}">
                Log & stok
            </a>
        </nav>
    </div>

    <form class="report-filter" method="get"
        action="{{ route('reports.index') }}">
        <label for="report-start">Dari tanggal</label>
        <input id="report-start" name="start_date" type="date"
            value="{{ $start ?? $date }}">

        <label for="report-end">Sampai tanggal</label>
        <input id="report-end" name="end_date" type="date"
            value="{{ $end ?? $date }}">

        <button class="admin-button" type="submit">
            Tampilkan
        </button>
    </form>

    <section class="report-stats" aria-label="Ringkasan penjualan">
        <article class="report-stat">
            <span>Omzet</span>
            <strong>
                Rp{{ number_format((float) $dailyTotal, 0, ',', '.') }}
            </strong>
            <small>
                {{ \Illuminate\Support\Carbon::parse($start)->translatedFormat('d F Y') }}
                @if ($start !== $end)
                    – {{ \Illuminate\Support\Carbon::parse($end)->translatedFormat('d F Y') }}
                @endif
            </small>
        </article>

        <article class="report-stat">
            <span>Laba kotor</span>
            <strong>
                Rp{{ number_format((float) $dailyProfit, 0, ',', '.') }}
            </strong>
            <small>
                Harga jual dikurangi modal produk; belum termasuk biaya operasional
            </small>
        </article>

        <article class="report-stat">
            <span>Transaksi selesai</span>
            <strong>{{ number_format($dailyCount, 0, ',', '.') }}</strong>
            <small>transaksi</small>
        </article>

        <article class="report-stat">
            <span>Produk terlaris</span>
            <strong>{{ $bestSeller?->product_name ?? 'Belum ada' }}</strong>
            <small>
                {{ $bestSeller
                    ? rtrim(rtrim((string) $bestSeller->sold_quantity, '0'), '.').' unit'
                    : 'Tidak ada penjualan pada tanggal ini' }}
            </small>
        </article>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <div>
                <p class="eyebrow">PEMBAYARAN</p>
                <h2>Metode pembayaran</h2>
            </div>
        </div>

        @if (($paymentMethods ?? collect())->isEmpty())
            <p class="admin-empty">Belum ada transaksi pada periode ini.</p>
        @else
            <div class="stock-log-list">
                @foreach ($paymentMethods as $paymentMethod)
                    <article class="stock-log-row">
                        <div>
                            <strong>
                                {{ match ($paymentMethod->payment_method) {
                                    'cash' => 'Tunai',
                                    'qris' => 'QRIS',
                                    'bank_transfer' => 'Transfer bank',
                                    'cash_on_pickup' => 'Bayar saat diambil',
                                    default => ucfirst(str_replace('_', ' ', (string) $paymentMethod->payment_method)),
                                } }}
                            </strong>
                            <small>{{ number_format((int) $paymentMethod->transaction_count, 0, ',', '.') }} transaksi</small>
                        </div>

                        <strong>
                            Rp{{ number_format((float) $paymentMethod->total_amount, 0, ',', '.') }}
                        </strong>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <div>
                <p class="eyebrow">PERSEDIAAN</p>
                <h2>Peringatan stok rendah</h2>
            </div>

            <a href="{{ route('reports.stock') }}">
                Lihat seluruh riwayat →
            </a>
        </div>

        @forelse ($lowStockProducts as $product)
            <div class="stock-warning-row">
                <span>
                    <strong>{{ $product->name }}</strong>
                    <small>
                        {{ $product->category?->name ?? 'Lainnya' }}
                    </small>
                </span>

                <strong class="{{ (float) $product->stock <= 0 ? 'is-danger' : 'is-warning' }}">
                    {{ rtrim(rtrim((string) $product->stock, '0'), '.') }}
                    {{ $product->unit }}
                </strong>
            </div>
        @empty
            <p class="admin-empty">
                Semua produk memiliki stok yang cukup.
            </p>
        @endforelse
    </section>
</main>
@endsection