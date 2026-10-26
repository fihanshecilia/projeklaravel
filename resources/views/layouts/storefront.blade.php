<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'TOKO SILATURIZKY') | Kasir & Manajemen Toko
    </title>

    <meta name="description"
        content="Sistem Kasir dan Manajemen Toko Sembako TOKO SILATURIZKY.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap"
        rel="stylesheet">

    <script>
        (function () {
            try {
                var theme = localStorage.getItem('silaturizky-theme') || 'light';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    @unless (request()->is('kasir*', 'admin*', 'profil', 'login*', 'masuk*'))
    <header class="site-header">
        <a class="brand" href="{{ auth()->check() ? (auth()->user()->isStoreAdmin() ? url('/admin') : route('pos.index')) : route('employee.login') }}"
            aria-label="TOKO SILATURIZKY, beranda">
            <span class="brand-mark" aria-hidden="true">S</span>

            <span class="brand-copy">
                <strong>TOKO</strong>
                <b>SILATURIZKY</b>
                <small>SEMBAKO PILIHAN KELUARGA</small>
            </span>
        </a>

        <nav class="main-nav" aria-label="Navigasi utama">
            @auth
                @if (auth()->user()->can('use-pos'))
                    <a href="{{ route('pos.index') }}">Kasir POS</a>
                    <a href="{{ route('pos.history') }}">Histori</a>
                @endif
                @if (auth()->user()->can('manage-store'))
                    <a href="{{ route('reports.index') }}">Laporan</a>
                    <a href="{{ route('reports.stock') }}">Stok</a>
                    <a href="{{ url('/admin') }}">Panel Admin</a>
                @endif
            @endauth
        </nav>

        <div style="display: flex; align-items: center; gap: 0.75rem;">
            @auth
                <span style="font-size: 0.875rem; color: #475569; font-weight: 500;">
                    {{ auth()->user()->name }}
                </span>
                <form method="post" action="{{ route('employee.logout') }}" style="display: inline; margin: 0;">
                    @csrf
                    <button class="header-cart" type="submit" style="cursor: pointer; padding: 0.45rem 0.9rem; border-radius: 9999px;">
                        Keluar
                    </button>
                </form>
            @else
                <a class="header-account" href="{{ route('employee.login') }}">
                    Masuk
                </a>
            @endauth
        </div>
    </header>
    @endunless

    @yield('content')

    @unless (request()->is('kasir*', 'admin*', 'profil', 'login*', 'masuk*'))
    <footer class="site-footer" id="tentang">
        <div class="footer-brand">
            <span class="brand-mark" aria-hidden="true">S</span>

            <div>
                <strong>TOKO SILATURIZKY</strong>
                <small>Sistem Kasir & Operasional Toko.</small>
            </div>
        </div>

        <p>
            Manajemen sembako, kasir cerdas, pencatatan stok, dan laporan berkala.
        </p>

        <span class="footer-note">
            © {{ date('Y') }} TOKO SILATURIZKY
        </span>
    </footer>
    @endunless
</body>
</html>
