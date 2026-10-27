<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreOverview extends BaseWidget
{
    protected static bool $isLazy = false;  

    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        $reportService = app(ReportService::class);
        $summary = $reportService->getTodaySummary();

        $dailySales = $summary['dailyTotal'];
        $dailyCount = $summary['dailyCount'];
        $dailyProfit = $summary['dailyProfit'];
        $bestSeller = $summary['bestSeller']?->product_name;
        $lowStockCount = $reportService->getLowStockCount();

        return [
            Stat::make(
                'Omzet Hari Ini',
                'Rp ' . number_format((float) $dailySales, 0, ',', '.')
            )
                ->description('Total transaksi yang selesai hari ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make(
                'Laba Kotor Hari Ini',
                'Rp ' . number_format((float) $dailyProfit, 0, ',', '.')
            )
                ->description('Penjualan dikurangi modal produk')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('success'),

            Stat::make(
                'Transaksi Hari Ini',
                number_format($dailyCount, 0, ',', '.')
            )
                ->description('Transaksi yang telah diselesaikan')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('info'),

            Stat::make(
                'Produk Terlaris Hari Ini',
                $bestSeller ?: 'Belum ada penjualan'
            )
                ->description('Produk dengan unit terjual terbanyak')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),

            Stat::make(
                'Stok Perlu Dicek',
                number_format($lowStockCount, 0, ',', '.') . ' produk'
            )
                ->description(
                    $lowStockCount > 0
                        ? 'Ada stok habis atau mencapai batas minimum'
                        : 'Semua stok berada di atas batas minimum'
                )
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'design' => 'sage',
        ];
    }
}
