@extends('layouts.storefront')

@section('title', 'Kasir POS')

@section('content')
<main class="pos-app" data-pos>
    <div class="pos-topbar">
        <div class="pos-location">
            <span class="pos-live-dot"></span>
            <span>
                <strong>MEJA KASIR</strong>
                <small>{{ auth()->user()->name }} · TOKO SILATURIZKY</small>
            </span>
        </div>

        <div class="pos-day">
            <span>Penjualan hari ini</span>
            <strong>Rp{{ number_format((float) $dailyTotal, 0, ',', '.') }}</strong>
            <i>{{ $dailyCount }} transaksi</i>
        </div>

        @if (auth()->user()->can('manage-store'))
            <a class="pos-logout" href="{{ route('reports.index') }}">
                Dashboard Admin
            </a>
        @endif

        <a class="pos-logout" href="{{ route('pos.history') }}">
            Histori Transaksi
        </a>

        <form method="post" action="{{ route('employee.logout') }}">
            @csrf
            <button class="pos-logout" type="submit">
                Keluar <span aria-hidden="true">↗</span>
            </button>
        </form>
    </div>

    <nav class="pos-tabs" role="tablist" aria-label="Area kasir" data-pos-tablist>
        <button type="button" class="is-active" role="tab"
            aria-selected="true" data-pos-tab="sales">
            Kasir POS
        </button>
    </nav>

    @if (session('status'))
        <p class="pos-flash" role="status">{{ session('status') }}</p>
    @endif

    <div class="pos-workspace" data-pos-tab-panel="sales">
        <section class="pos-catalog" aria-labelledby="pos-title">
            <div class="pos-heading">
                <div>
                    <p class="eyebrow">PENJUALAN TOKO</p>
                    <h1 id="pos-title">Mulai transaksi</h1>
                </div>

                <label class="pos-search">
                    <span aria-hidden="true">⌕</span>
                    <input type="search" data-pos-search
                        placeholder="Cari nama atau SKU"
                        aria-label="Cari produk">
                </label>
            </div>

            <div class="pos-categories" role="group"
                aria-label="Filter kategori" data-pos-categories>
                <button type="button" class="is-active"
                    data-pos-category="all">
                    Semua
                </button>

                @foreach ($categories as $category)
                    <button type="button"
                        data-pos-category="{{ $category->name }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>

            <div class="pos-product-grid" data-pos-grid aria-live="polite"></div>
            <p class="empty-products" data-pos-empty hidden>
                Produk tidak ditemukan.
            </p>
        </section>

        <aside class="pos-checkout" aria-labelledby="pos-cart-title">
            <div class="pos-cart-head">
                <div>
                    <span class="eyebrow">TRANSAKSI BARU</span>
                    <h2 id="pos-cart-title">Keranjang</h2>
                </div>
                <span class="pos-cart-count" data-pos-count>0 item</span>
            </div>

            <div class="pos-cart-lines" data-pos-lines>
                <div class="cart-empty">
                    <span aria-hidden="true">＋</span>
                    <p>Belum ada produk</p>
                    <small>Pilih produk untuk menambahkan</small>
                </div>
            </div>

            <div class="pos-total-line">
                <span>Total belanja</span>
                <strong data-pos-total>Rp0</strong>
            </div>

            <form method="post"
                action="{{ route('pos.transactions.store') }}"
                data-pos-form>
                @csrf

                <input type="hidden" name="status" value="completed">
                <input type="hidden" name="payment_method" value="cash"
                    data-pos-payment-method>

                <div data-pos-item-fields></div>

                <div class="pos-payment-methods" aria-label="Metode pembayaran">
                    <button class="is-active" type="button"
                        data-pos-payment="cash">
                        Tunai
                    </button>

                    <button type="button" data-pos-payment="qris">
                        QRIS
                    </button>
                </div>

                <label class="paid-label" for="pos-paid">
                    Uang diterima
                </label>

                <div class="paid-input-wrap">
                    <span>Rp</span>
                    <input id="pos-paid" type="number"
                        name="amount_paid" min="0" step="1000"
                        inputmode="numeric" placeholder="0" required
                        data-pos-paid>
                </div>

                <label class="checkout-note" data-qris-confirmation hidden>
                    <input type="checkbox" name="payment_verified" value="1"
                        data-qris-verified>
                    Saya sudah memeriksa bukti pembayaran QRIS dan
                    memastikan dana diterima.
                </label>

                <p class="pos-payment-error" data-qris-error hidden>
                    Centang konfirmasi setelah pembayaran QRIS
                    benar-benar diterima.
                </p>

                <div class="cash-shortcuts" aria-label="Nominal cepat">
                    <button type="button" data-cash-value="exact">
                        Uang pas
                    </button>
                    <button type="button" data-cash-value="20000">20 rb</button>
                    <button type="button" data-cash-value="50000">50 rb</button>
                    <button type="button" data-cash-value="100000">100 rb</button>
                </div>

                <div class="change-line">
                    <span>Kembalian</span>
                    <strong data-pos-change>Rp0</strong>
                </div>

                <button class="pos-submit" type="submit"
                    data-pos-submit disabled>
                    Selesaikan transaksi
                    <span aria-hidden="true">→</span>
                </button>

                <p class="pos-payment-error" data-pos-payment-error hidden>
                    Uang diterima belum mencukupi.
                </p>
            </form>

        </aside>
    </div>

    <script type="application/json" data-pos-products>
        @json($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
    </script>
</main>
@endsection
