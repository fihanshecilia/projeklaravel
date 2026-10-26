@extends('layouts.storefront')

@section('title', 'Riwayat stok')

@section('content')
<main class="admin-page">
    <div class="admin-page-head">
        <div>
            <p class="eyebrow">PERSEDIAAN TOKO</p>
            <h1>Log & peringatan stok</h1>
            <p>
                Perubahan stok masuk, reservasi, penjualan dan pembatalan.
            </p>
        </div>

        <nav class="admin-actions" aria-label="Menu laporan">
            <a class="admin-button is-secondary"
                href="{{ route('reports.index') }}">
                ← Laporan toko
            </a>

            <a class="admin-button" href="{{ url('/admin/products') }}">
                Kelola produk
            </a>
        </nav>
    </div>

    <section class="admin-card">
        <div class="admin-card-head">
            <div>
                <p class="eyebrow">PERLU DIPERIKSA</p>
                <h2>Stok mencapai batas minimum</h2>
            </div>
        </div>

        <div class="stock-warning-grid">
            @forelse ($lowStockProducts as $product)
                <article class="stock-warning-card">
                    <span>{{ $product->category?->name ?? 'Lainnya' }}</span>
                    <strong>{{ $product->name }}</strong>
                    <small>
                        {{ rtrim(rtrim((string) $product->stock, '0'), '.') }}
                        {{ $product->unit }} tersedia
                    </small>
                </article>
            @empty
                <p class="admin-empty">
                    Tidak ada produk yang mencapai batas stok minimum.
                </p>
            @endforelse
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <div>
                <p class="eyebrow">HISTORI</p>
                <h2>Aktivitas stok</h2>
            </div>
        </div>

        <div class="stock-log-list">
            @forelse ($stockLogs as $log)
                <article class="stock-log-row">
                    <span class="stock-log-mark {{
                        in_array(
                            $log->type,
                            ['incoming', 'initial', 'cancellation'],
                            true
                        ) ? 'is-in' : 'is-out'
                    }}">
                        {{ in_array(
                            $log->type,
                            ['incoming', 'initial', 'cancellation'],
                            true
                        ) ? '+' : '−' }}
                    </span>

                    <div>
                        <strong>
                            {{ $log->product?->name ?? 'Produk dihapus' }}
                        </strong>
                        <small>
                            {{ $log->note ?: ucfirst($log->type) }}
                            · {{ $log->user?->name ?? 'Sistem' }}
                        </small>
                    </div>

                    <strong>
                        {{ rtrim(
                            rtrim((string) abs((float) $log->quantity_change), '0'),
                            '.'
                        ) }}
                        {{ $log->product?->unit ?? 'unit' }}
                    </strong>

                    <time datetime="{{ $log->created_at->toIso8601String() }}">
                        {{ $log->created_at->format('d M Y, H:i') }}
                    </time>
                </article>
            @empty
                <p class="admin-empty">Belum ada histori stok.</p>
            @endforelse
        </div>

        {{ $stockLogs->links() }}
    </section>
</main>
@endsection