<x-filament-widgets::widget>
    @php
        $stats = $this->getStats();
        $adminUser = auth()->user();
        $formattedDate = \Illuminate\Support\Carbon::now()->locale('id')->translatedFormat('l, d F Y');
        
        $primaryStats = array_slice($stats, 0, 2);
        $secondaryStats = array_slice($stats, 2);
    @endphp

    <div class="sr-botanical-dashboard">
        {{-- ==========================================================================
             3A. HEADER & WELCOME SECTION (Luxury Botanical Aesthetic)
             ========================================================================== --}}
        <header class="sr-hero-banner" role="banner">
            {{-- Botanical leaf watermark decoration (Whisper-soft 4% opacity) --}}
            <div class="sr-hero-botanical-watermark" aria-hidden="true">
                <svg width="280" height="280" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M185 15C135 15 85 55 65 105C45 155 75 195 75 195C75 195 115 165 135 115C155 65 185 15 185 15Z" fill="currentColor" />
                    <path d="M65 105C105 105 145 75 165 35" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    <path d="M95 85C115 80 135 65 145 45" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    <path d="M80 125C95 125 115 115 125 95" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    <path d="M25 175C55 145 75 115 75 115" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                </svg>
            </div>

            <div class="sr-hero-content">
                <div class="sr-hero-main">
                    <div class="sr-brand-pill">
                        <span class="sr-brand-pill-icon" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.577 4.878a.75.75 0 01.919-.53 8.5 8.5 0 016.126 6.127.75.75 0 11-1.449.39 7 7 0 00-5.066-5.067.75.75 0 01-.53-.92zM15 10a5 5 0 11-10 0 5 5 0 0110 0zm-2.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>TOKO SILATURIZKY &bull; ENTERPRISE SUITE</span>
                    </div>

                    <h1 class="sr-hero-title">
                        Ringkasan Operasional & Kinerja Toko
                    </h1>

                    <p class="sr-hero-subtitle">
                        Selamat datang kembali, <strong>{{ $adminUser?->name ?? 'Administrator' }}</strong>. Pantau perputaran omzet harian, pencapaian laba kotor, dan kesehatan inventaris sembako secara real-time.
                    </p>
                </div>

                <div class="sr-hero-meta">
                    <div class="sr-meta-date-badge" title="Tanggal Operasional">
                        <span class="sr-meta-date-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="4" width="14" height="14" rx="3" />
                                <path d="M3 8H17" />
                                <path d="M7 2V5" />
                                <path d="M13 2V5" />
                            </svg>
                        </span>
                        <span class="sr-meta-date-text">{{ $formattedDate }}</span>
                    </div>

                    <div class="sr-meta-status-pill" title="Status Sistem">
                        <span class="sr-pulse-dot" aria-hidden="true"></span>
                        <span>Sistem Kasir & Stok Aktif</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- ==========================================================================
             3B. STATISTIK DASHBOARD (Hierarchical 2-Tier Architecture)
             ========================================================================== --}}
        
        {{-- TIER 1: Primary Executive KPIs (Omzet & Laba Kotor) --}}
        <div class="sr-stats-tier-primary" aria-label="Indikator Keuangan Utama">
            @foreach ($primaryStats as $index => $stat)
                @php
                    $isOmzet = ($index === 0);
                    $badgeText = $isOmzet ? 'Pendapatan Harian' : 'Margin Kotor';
                @endphp
                <article class="sr-card sr-card-primary" data-kpi="{{ $isOmzet ? 'omzet' : 'laba' }}">
                    <div class="sr-card-top-bar" aria-hidden="true"></div>
                    
                    <div class="sr-card-head">
                        <div class="sr-card-label-group">
                            <span class="sr-card-tag">{{ $badgeText }}</span>
                            <h2 class="sr-card-title">{{ $stat->getLabel() }}</h2>
                        </div>

                        <div class="sr-card-icon-wrapper sr-card-icon-primary" aria-hidden="true">
                            @if ($isOmzet)
                                {{-- Banknotes / Revenue Icon --}}
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="6" width="20" height="12" rx="2" />
                                    <circle cx="12" cy="12" r="3" />
                                    <path d="M6 12h.01M18 12h.01" />
                                </svg>
                            @else
                                {{-- Chart Bar / Profit Icon --}}
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18" />
                                    <path d="M18 17V9" />
                                    <path d="M13 17V5" />
                                    <path d="M8 17v-3" />
                                </svg>
                            @endif
                        </div>
                    </div>

                    <div class="sr-card-body">
                        <div class="sr-card-value sr-card-value-lg">
                            {{ $stat->getValue() }}
                        </div>
                    </div>

                    @if ($stat->getDescription())
                        <div class="sr-card-footer">
                            <span class="sr-card-desc-icon" aria-hidden="true">
                                <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v4.59l-2.22-2.22a.75.75 0 10-1.06 1.06l3.5 3.5a.75.75 0 001.06 0l3.5-3.5a.75.75 0 00-1.06-1.06l-2.22 2.22V6.75z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <span class="sr-card-description">
                                {{ $stat->getDescription() }}
                            </span>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        {{-- TIER 2: Operational Cadence Indicators (Transaksi, Produk Terlaris, Stok Perlu Dicek) --}}
        <div class="sr-stats-tier-secondary" aria-label="Indikator Operasional">
            @foreach ($secondaryStats as $index => $stat)
                @php
                    $isTx = ($index === 0);
                    $isBest = ($index === 1);
                    $isStock = ($index === 2);

                    $colorClass = 'sr-icon-info';
                    $tagLabel = 'Kasir POS';

                    if ($isBest) {
                        $colorClass = 'sr-icon-warning';
                        $tagLabel = 'Performa Produk';
                    } elseif ($isStock) {
                        $isAlert = (str_starts_with($stat->getValue(), '0') === false && !str_contains($stat->getValue(), '0 produk'));
                        $colorClass = $isAlert ? 'sr-icon-danger' : 'sr-icon-success';
                        $tagLabel = $isAlert ? 'Perhatian' : 'Kondisi Aman';
                    }
                @endphp
                <article class="sr-card sr-card-secondary">
                    <div class="sr-card-head">
                        <div class="sr-card-label-group">
                            <span class="sr-card-tag sr-card-tag-sm {{ $isStock && isset($isAlert) && $isAlert ? 'sr-tag-danger' : '' }}">
                                {{ $tagLabel }}
                            </span>
                            <h2 class="sr-card-title sr-card-title-sm">{{ $stat->getLabel() }}</h2>
                        </div>

                        <div class="sr-card-icon-wrapper {{ $colorClass }}" aria-hidden="true">
                            @if ($isTx)
                                {{-- Receipt Icon --}}
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z" />
                                    <path d="M8 8h8M8 12h8M8 16h5" />
                                </svg>
                            @elseif ($isBest)
                                {{-- Trophy / Crown Icon --}}
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                                    <path d="M4 22h16" />
                                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                                </svg>
                            @else
                                {{-- Stock Alert / Box Icon --}}
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                    <path d="m3.3 7 8.7 5 8.7-5M12 22V12" />
                                </svg>
                            @endif
                        </div>
                    </div>

                    <div class="sr-card-body">
                        <div class="sr-card-value {{ $isBest ? 'sr-card-value-text' : 'sr-card-value-md' }}" title="{{ $stat->getValue() }}">
                            {{ $stat->getValue() }}
                        </div>
                    </div>

                    @if ($stat->getDescription())
                        <div class="sr-card-footer">
                            <span class="sr-card-description">
                                {{ $stat->getDescription() }}
                            </span>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>