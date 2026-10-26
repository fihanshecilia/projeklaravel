@extends('layouts.storefront')

@section('title', 'Masuk')

@section('content')
<style>
    .sr-login-page,
    .sr-login-page * {
        box-sizing: border-box;
    }

    .sr-login-page {
        --sr-green: #a1cca6;
        --sr-green-dark: #527d59;
        --sr-text: #26352b;
        --sr-muted: #7b857d;
        --sr-border: #e4eae4;

        min-height: calc(100vh - 80px);
        padding: 36px 5%;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            radial-gradient(ellipse at top left, #edf5ed 0%, transparent 42%),
            #f8faf7;
        font-family: inherit;
    }

    .sr-login-shell {
        width: 100%;
        max-width: 1120px;
        min-height: 610px;
        display: grid;
        grid-template-columns: 0.9fr 1.1fr;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(225, 234, 225, .9);
        border-radius: 28px;
        box-shadow: 0 24px 70px rgba(43, 70, 47, .10);
        animation: srFadeIn .7s ease both;
    }

    .sr-login-panel {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 54px 48px;
    }

    .sr-login-brand {
        display: flex;
        align-items: center;
        gap: 13px;
        margin-bottom: 55px;
    }

    .sr-brand-mark {
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: var(--sr-green);
        color: #24472c;
        font-size: 25px;
        font-weight: 800;
        box-shadow: 0 7px 18px rgba(91, 139, 98, .16);
    }

    .sr-brand-name {
        display: block;
        color: var(--sr-text);
        font-size: 15px;
        font-weight: 800;
        letter-spacing: 1px;
    }

    .sr-brand-caption {
        display: block;
        margin-top: 5px;
        color: var(--sr-muted);
        font-size: 9px;
        font-weight: 600;
        letter-spacing: 1.4px;
    }

    .sr-login-eyebrow {
        display: flex;
        align-items: center;
        gap: 9px;
        margin: 0 0 13px;
        color: var(--sr-green-dark);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 2px;
        text-transform: uppercase;
    }

    .sr-login-eyebrow::before {
        content: "";
        width: 23px;
        height: 2px;
        border-radius: 2px;
        background: var(--sr-green-dark);
    }

    .sr-login-title {
        margin: 0;
        color: var(--sr-text);
        font-size: clamp(32px, 3vw, 42px);
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -1.5px;
    }

    .sr-login-description {
        margin: 13px 0 31px;
        color: var(--sr-muted);
        font-size: 14px;
        line-height: 1.8;
    }

    .sr-login-error {
        margin-bottom: 22px;
        padding: 13px 15px;
        border: 1px solid #f1caca;
        border-radius: 12px;
        background: #fff5f5;
        color: #b42318;
        font-size: 13px;
        line-height: 1.6;
    }

    .sr-login-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .sr-login-form label {
        margin-top: 8px;
        color: #344438;
        font-size: 13px;
        font-weight: 700;
    }

    .sr-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .sr-input-icon {
        position: absolute;
        left: 16px;
        width: 19px;
        height: 19px;
        color: #8a9b8d;
        pointer-events: none;
    }

    .sr-login-form input {
        width: 100%;
        min-width: 0;
        height: 53px;
        padding: 0 46px;
        border: 1px solid var(--sr-border);
        border-radius: 13px;
        outline: none;
        background: #fbfcfa;
        color: var(--sr-text);
        font: inherit;
        font-size: 14px;
        transition: border-color .2s ease, box-shadow .2s ease,
                    background .2s ease;
    }

    .sr-login-form input::placeholder {
        color: #a5aea6;
    }

    .sr-login-form input:hover {
        border-color: #cbdacb;
    }

    .sr-login-form input:focus {
        border-color: var(--sr-green-dark);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(161, 204, 166, .23);
    }

    .sr-password-toggle {
        position: absolute;
        right: 12px;
        display: grid;
        place-items: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #849087;
        cursor: pointer;
        transition: background .2s, color .2s;
    }

    .sr-password-toggle:hover {
        background: #edf5ed;
        color: var(--sr-green-dark);
    }

    .sr-password-toggle:focus-visible {
        outline: 2px solid var(--sr-green-dark);
        outline-offset: 2px;
    }

    .sr-password-toggle svg {
        width: 18px;
        height: 18px;
    }

    .sr-login-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        min-height: 53px;
        margin-top: 20px;
        padding: 13px 20px;
        border: 0;
        border-radius: 13px;
        background: var(--sr-green);
        color: #263f2b;
        font: inherit;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 8px 20px rgba(104, 155, 111, .17);
        transition: transform .2s ease, background .2s ease,
                    box-shadow .2s ease;
    }

    .sr-login-submit:hover {
        transform: translateY(-2px);
        background: #8fbd96;
        box-shadow: 0 12px 24px rgba(104, 155, 111, .24);
    }

    .sr-login-submit:active {
        transform: translateY(0);
    }

    .sr-login-submit:focus-visible {
        outline: 3px solid #527d59;
        outline-offset: 3px;
    }

    .sr-login-submit svg {
        width: 18px;
        height: 18px;
        transition: transform .2s ease;
    }

    .sr-login-submit:hover svg {
        transform: translateX(3px);
    }

    .sr-login-note {
        margin: 24px 0 0;
        color: #9aa39b;
        font-size: 11px;
        line-height: 1.8;
        text-align: center;
    }

    .sr-login-visual {
        position: relative;
        min-height: 610px;
        overflow: hidden;
        background: #dce8d9;
    }

    .sr-login-visual img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform .8s ease;
    }

    .sr-login-shell:hover .sr-login-visual img {
        transform: scale(1.025);
    }

    .sr-login-visual::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(
                180deg,
                rgba(20, 39, 24, .03) 25%,
                rgba(20, 39, 24, .18) 52%,
                rgba(20, 39, 24, .82) 100%
            );
        pointer-events: none;
    }

    .sr-visual-content {
        position: absolute;
        z-index: 1;
        right: 36px;
        bottom: 38px;
        left: 36px;
        color: #fff;
    }

    .sr-visual-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 17px;
        padding: 9px 13px;
        border: 1px solid rgba(255, 255, 255, .35);
        border-radius: 30px;
        background: rgba(255, 255, 255, .14);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1.2px;
        backdrop-filter: blur(10px);
    }

    .sr-visual-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #b8e8bd;
        box-shadow: 0 0 10px rgba(184, 232, 189, .7);
    }

    .sr-visual-title {
        max-width: 440px;
        margin: 0 0 12px;
        color: #fff;
        font-size: clamp(25px, 3vw, 37px);
        font-weight: 800;
        line-height: 1.25;
        letter-spacing: -.8px;
    }

    .sr-visual-description {
        max-width: 390px;
        margin: 0;
        color: rgba(255, 255, 255, .83);
        font-size: 13px;
        line-height: 1.8;
    }

    .sr-visual-divider {
        width: 46px;
        height: 3px;
        margin-top: 23px;
        border-radius: 3px;
        background: var(--sr-green);
    }

    @keyframes srFadeIn {
        from {
            opacity: 0;
            transform: translateY(14px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 900px) {
        .sr-login-page {
            padding: 25px 4%;
        }

        .sr-login-shell {
            max-width: 560px;
            grid-template-columns: 1fr;
        }

        .sr-login-panel {
            padding: 38px clamp(24px, 7vw, 52px);
        }

        .sr-login-brand {
            margin-bottom: 36px;
        }

        .sr-login-visual {
            min-height: 330px;
        }

        .sr-visual-content {
            right: 25px;
            bottom: 25px;
            left: 25px;
        }

        .sr-visual-title {
            font-size: 28px;
        }
    }

    @media (max-width: 480px) {
        .sr-login-page {
            min-height: auto;
            padding: 16px 12px;
        }

        .sr-login-shell {
            border-radius: 21px;
        }

        .sr-login-panel {
            padding: 29px 22px 31px;
        }

        .sr-login-brand {
            gap: 10px;
            margin-bottom: 34px;
        }

        .sr-brand-mark {
            width: 43px;
            height: 43px;
            flex-basis: 43px;
            border-radius: 13px;
        }

        .sr-brand-name {
            font-size: 13px;
        }

        .sr-brand-caption {
            font-size: 8px;
            letter-spacing: 1px;
        }

        .sr-login-title {
            font-size: 34px;
        }

        .sr-login-description {
            font-size: 13px;
        }

        .sr-login-visual {
            min-height: 290px;
        }

        .sr-visual-title {
            font-size: 25px;
        }

        .sr-visual-description {
            font-size: 12px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .sr-login-page *,
        .sr-login-page *::before,
        .sr-login-page *::after {
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .01ms !important;
            scroll-behavior: auto !important;
        }
    }
</style>

<main class="sr-login-page">
    <div class="sr-login-shell">

        <section class="sr-login-panel">
            <div class="sr-login-brand">
                <span class="sr-brand-mark" aria-hidden="true">S</span>

                <span>
                    <strong class="sr-brand-name">TOKO SILATURIZKY</strong>
                    <small class="sr-brand-caption">
                        SEMBAKO • SEGAR • TERPERCAYA
                    </small>
                </span>
            </div>

            <p class="sr-login-eyebrow">Selamat Datang</p>

            <h1 class="sr-login-title">Masuk ke Akun</h1>

            <p class="sr-login-description">
                Silakan masuk untuk melanjutkan aktivitas dan mengelola
                kebutuhan toko dengan mudah.
            </p>

            @if ($errors->any())
                <div class="sr-login-error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('employee.login.store') }}"
                class="sr-login-form"
            >
                @csrf

                <label for="employee-email">Alamat Email</label>

                <div class="sr-input-wrap">
                    <svg class="sr-input-icon"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.7"
                         aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="3"/>
                        <path d="m4 7 8 6 8-6"/>
                    </svg>

                    <input
                        id="employee-email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="nama@email.com"
                        autocomplete="username"
                        maxlength="255"
                        required
                        autofocus
                    >
                </div>

                <label for="employee-password">Kata Sandi</label>

                <div class="sr-input-wrap">
                    <svg class="sr-input-icon"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.7"
                         aria-hidden="true">
                        <rect x="4" y="10" width="16" height="11" rx="3"/>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        <path d="M12 14v3"/>
                    </svg>

                    <input
                        id="employee-password"
                        type="password"
                        name="password"
                        placeholder="Masukkan kata sandi"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        class="sr-password-toggle"
                        type="button"
                        id="sr-toggle-password"
                        aria-label="Tampilkan kata sandi"
                        aria-pressed="false"
                    >
                        <svg id="sr-eye-icon"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.7"
                             stroke-linecap="round"
                             stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>

                <button class="sr-login-submit" type="submit">
                    <span>Masuk Sekarang</span>

                    <svg viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-hidden="true">
                        <path d="M5 12h14"/>
                        <path d="m12 5 7 7-7 7"/>
                    </svg>
                </button>
            </form>

            <p class="sr-login-note">
                Akses khusus karyawan yang terdaftar.<br>
                TOKO SILATURIZKY &copy; {{ date('Y') }}
            </p>
        </section>

        <aside class="sr-login-visual"
               aria-label="Informasi Toko Silaturizky">

            <img
                src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=85"
                alt="Berbagai sayuran dan bahan pangan segar"
                fetchpriority="high"
            >

            <div class="sr-visual-content">
                <span class="sr-visual-tag">
                    <span class="sr-visual-dot"></span>
                    MELAYANI DENGAN SEPENUH HATI
                </span>

                <h2 class="sr-visual-title">
                    Kebutuhan Harian,
                    Lebih Mudah dan Terpercaya.
                </h2>

                <p class="sr-visual-description">
                    Pilihan sembako untuk kebutuhan sehari-hari,
                    dengan pelayanan yang mengutamakan kualitas,
                    ketelitian, dan kepercayaan.
                </p>

                <div class="sr-visual-divider"></div>
            </div>
        </aside>

    </div>
</main>

<script>
    (() => {
        const passwordInput = document.getElementById('employee-password');
        const toggleButton = document.getElementById('sr-toggle-password');
        const eyeIcon = document.getElementById('sr-eye-icon');

        if (!passwordInput || !toggleButton || !eyeIcon) {
            return;
        }

        toggleButton.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';

            passwordInput.type = isPassword ? 'text' : 'password';

            toggleButton.setAttribute(
                'aria-label',
                isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
            );

            toggleButton.setAttribute(
                'aria-pressed',
                isPassword ? 'true' : 'false'
            );

            eyeIcon.innerHTML = isPassword
                ? '<path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15 15 0 0 1-3.1 3.9"/><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4-.8"/>'
                : '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>';
        });
    })();
</script>
@endsection