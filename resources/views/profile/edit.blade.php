@extends('layouts.storefront')

@section('title', 'Profil Akun')

@section('content')
    <main class="admin-page" style="max-width: 720px;">
        <div class="admin-page-head">
            <div>
                <p class="eyebrow">Akun</p>
                <h1>Profil Saya</h1>
                <p>Ubah email, kata sandi, atau pilihan tema akun internal Anda.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="admin-card" style="margin-bottom: 16px; color: #166534; background: #dcfce7;">
                {{ session('status') }}
            </div>
        @endif

        <section class="admin-card">
            <div class="profile-theme-row">
                <span>Tema website</span>
                <div class="theme-toggle" role="group" aria-label="Pilihan tema">
                    <button type="button" class="theme-btn" data-theme="light">
                        <x-filament::icon icon="heroicon-o-sun" class="h-4 w-4" /> Terang
                    </button>
                    <button type="button" class="theme-btn" data-theme="dark">
                        <x-filament::icon icon="heroicon-o-moon" class="h-4 w-4" /> Gelap
                    </button>
                </div>
            </div>

            <form method="post" action="{{ route('profile.update') }}" class="login-form">
                @csrf
                @method('PUT')

                <label>
                    Username
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                </label>
                @error('name')
                    <p class="login-error">{{ $message }}</p>
                @enderror

                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </label>
                @error('email')
                    <p class="login-error">{{ $message }}</p>
                @enderror

                <label>
                    Kata sandi lama
                    <input type="password" name="current_password" placeholder="Kosongkan jika tidak mengubah kata sandi">
                </label>
                @error('current_password')
                    <p class="login-error">{{ $message }}</p>
                @enderror

                <label>
                    Kata sandi baru
                    <input type="password" name="password" placeholder="Kosongkan jika tidak mengubah kata sandi">
                </label>
                @error('password')
                    <p class="login-error">{{ $message }}</p>
                @enderror

                <label>
                    Konfirmasi kata sandi baru
                    <input type="password" name="password_confirmation" placeholder="Ulangi kata sandi baru">
                </label>

                <button type="submit">
                    <span>Simpan perubahan</span>
                    <span>→</span>
                </button>
            </form>
        </section>

        @if (auth()->user()->isStoreAdmin())
            <section class="admin-card" style="margin-top: 16px;">
                <div class="profile-card-head">
                    <div>
                        <p class="eyebrow">Akun Lain</p>
                        <h2>Tambah Akun Kasir</h2>
                    </div>
                </div>
                <a class="admin-button" href="{{ route('filament.admin.resources.users.create') }}">
                    <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" /> Tambah akun kasir
                </a>
            </section>
        @endif

        <section class="admin-card" style="margin-top: 16px;">
            <form method="post" action="{{ route('employee.logout') }}">
                @csrf
                <button class="pos-logout" type="submit">
                    <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="h-4 w-4" /> Keluar
                </button>
            </form>
        </section>
    </main>

    <script>
        (function () {
            const storageKey = 'silaturizky-theme';
            const root = document.documentElement;
            const saved = localStorage.getItem(storageKey) || 'light';
            root.setAttribute('data-theme', saved);
            document.querySelectorAll('.theme-btn').forEach((btn) => {
                btn.classList.toggle('is-active', btn.dataset.theme === saved);
                btn.addEventListener('click', () => {
                    const theme = btn.dataset.theme;
                    root.setAttribute('data-theme', theme);
                    localStorage.setItem(storageKey, theme);
                    document.querySelectorAll('.theme-btn').forEach((b) => {
                        b.classList.toggle('is-active', b.dataset.theme === theme);
                    });
                });
            });
        })();
    </script>
@endsection
