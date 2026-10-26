@extends('layouts.storefront')

@section('title', 'Belanja Sembako')

@section('content')
<main class="storefront" data-storefront>
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-copy">
            <span class="eyebrow">
                <span></span> TEMAN BELANJA HARIANMU
            </span>

            <h1>Isi dapur,<br><em>lebih mudah.</em></h1>

            <p>
                Sembako pilihan dengan harga bersahabat.
                Pilih kebutuhanmu, lalu tentukan apakah pesanan
                akan diambil di toko atau diantar.
            </p>

            <a class="hero-cta" href="#produk">
                Mulai belanja <span aria-hidden="true">↘</span>
            </a>

            <div class="hero-promise">
                <span>✓</span>
                Pilihan segar · Pesan praktis · Bayar di tempat
            </div>
        </div>

        <div class="hero-visual">
            <img
                src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=90"
                alt="Sayuran segar dan bahan pangan di toko">

            <div class="hero-stamp">
                <strong>Belanja<br>seperlunya.</strong>
                <span>Hemat setiap hari</span>
            </div>

            <div class="hero-index">
                01 <i></i> SEMBAKO HARIAN
            </div>
        </div>

        <div class="hero-side-note" aria-hidden="true">
            TUMBUH BERSAMA KELUARGA
        </div>
    </section>

    <section class="catalog-section" id="produk"
        aria-labelledby="catalog-title">
        <div class="section-topline">
            <span>01 / KATALOG TOKO</span>
            <span>Harga dapat berubah mengikuti pasar</span>
        </div>

        <div class="catalog-heading">
            <div>
                <p class="eyebrow">YANG KAMU BUTUHKAN</p>
                <h2 id="catalog-title">Belanja hari ini</h2>
            </div>

            <label class="search-box">
                <span aria-hidden="true">⌕</span>
                <input type="search" data-product-search
                    placeholder="Cari beras, minyak, ..."
                    aria-label="Cari produk">
            </label>
        </div>

        <div class="shop-layout">
            <div class="catalog-main">
                <div class="category-list" role="group"
                    aria-label="Filter kategori" data-category-list>
                    <button class="category-chip is-active"
                        type="button" data-category="all">
                        Semua produk
                    </button>

                    @foreach ($categories as $category)
                        <button class="category-chip" type="button"
                            data-category="{{ $category->name }}">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                <div class="product-grid"
                    data-product-grid aria-live="polite"></div>

                <p class="empty-products" data-empty-products hidden>
                    Produk belum tersedia. Silakan hubungi toko
                    untuk informasi stok.
                </p>
            </div>
        </div>
    </section>

    <section class="service-strip" aria-label="Layanan toko">
        <div>
            <span>01</span>
            <strong>Harga jelas</strong>
            <p>Total belanja terlihat sebelum pesan.</p>
        </div>

        <div>
            <span>02</span>
            <strong>Stok terpantau</strong>
            <p>Produk mengikuti ketersediaan toko.</p>
        </div>

        <div>
            <span>03</span>
            <strong>Pilih pemenuhan</strong>
            <p>Ambil di toko atau pilih pengantaran.</p>
        </div>
    </section>

    <script type="application/json" data-products>
        @json($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
    </script>
</main>

<button class="cart-backdrop" type="button"
    data-cart-close aria-label="Tutup keranjang" hidden></button>

<aside class="cart-panel" id="keranjang"
    aria-labelledby="cart-title" role="dialog"
    aria-modal="true" hidden>
    <div class="cart-panel-heading">
        <div>
            <span class="eyebrow">PESANANMU</span>
            <h2 id="cart-title">Keranjang</h2>
        </div>

        <div class="cart-panel-actions">
            <span class="cart-panel-count" data-cart-count>0 item</span>
            <button type="button" class="cart-close"
                data-cart-close aria-label="Tutup keranjang">
                ×
            </button>
        </div>
    </div>

    <div class="cart-lines" data-cart-lines>
        <div class="cart-empty">
            <span aria-hidden="true">＋</span>
            <p>Keranjangmu masih kosong.</p>
            <small>Pilih kebutuhan yang ingin dibeli.</small>
        </div>
    </div>

    <div class="cart-summary">
        <div>
            <span>Subtotal</span>
            <strong data-cart-total>Rp0</strong>
        </div>

        <p>
            Pilih metode pembayaran saat checkout.
            Transfer dan QRIS menunggu verifikasi toko.
        </p>
    </div>

    <form class="checkout-form" method="post"
        action="{{ route('storefront.orders.store') }}"
        data-checkout-form>
        @csrf

        <div class="checkout-fields">
            <label>
                Nama pemesan
                <input name="customer_name"
                    value="{{ auth()->user()?->name }}"
                    autocomplete="name" maxlength="150"
                    placeholder="Nama lengkap" required>
            </label>

            <label>
                Nomor WhatsApp
                <input name="customer_phone" type="tel"
                    value="{{ auth()->user()?->wa_number }}"
                    autocomplete="tel" maxlength="40"
                    placeholder="08xxxxxxxxxx" required>
            </label>

            <label>
                Metode pemenuhan
                <select name="delivery_type"
                    data-delivery-type required>
                    <option value="pickup">Ambil di toko</option>
                    <option value="delivery">Diantar ke alamat</option>
                </select>
            </label>

            <label data-shipping-address hidden>
                Alamat pengantaran
                <textarea name="shipping_address"
                    maxlength="2000" rows="2"
                    placeholder="Nama jalan, nomor rumah, dan patokan">{{ auth()->user()?->alamat }}</textarea>
            </label>

            <label>
                Metode pembayaran
                <select name="payment_method" required>
                    <option value="cash_on_pickup">
                        Tunai saat pesanan diterima (COD)
                    </option>
                    <option value="bank_transfer">Transfer bank</option>
                    <option value="qris">QRIS</option>
                </select>
            </label>

            <label>
                Catatan / estimasi jam pengambilan
                <textarea name="pickup_note"
                    maxlength="2000" rows="2"
                    placeholder="Contoh: diambil pukul 16.00"></textarea>
            </label>
        </div>

        <div data-order-items></div>

        <button class="checkout-button" type="submit"
            data-checkout-button disabled>
            Kirim pesanan <span aria-hidden="true">→</span>
        </button>

        <p class="checkout-note">
            Pesanan dicatat terlebih dahulu. Kamu dapat membuka
            WhatsApp untuk mengonfirmasi pesanan.
        </p>
    </form>
</aside>
@endsection