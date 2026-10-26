<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StoreOverview extends BaseWidget
{
    protected static bool $isLazy = false;  

    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        $today = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('completed_at', today());

        $completedDetails = fn () => TransactionDetail::query()
            ->whereHas(
                'transaction',
                fn (Builder $query): Builder => $query
                    ->where('status', 'completed')
                    ->whereDate('completed_at', today())
            );

        $bestSeller = $completedDetails()
            ->selectRaw(
                'product_id, product_name, unit, SUM(quantity) as sold_quantity'
            )
            ->groupBy('product_id', 'product_name', 'unit')
            ->orderByDesc('sold_quantity')
            ->value('product_name');

        $dailySales = (clone $today)->sum('total');
        $dailyCount = (clone $today)->count();

        $profitExpression = DB::connection()->getDriverName() === 'mysql'
            ? 'hitung_laba(unit_price, purchase_price) * quantity'
            : '(unit_price - purchase_price) * quantity';

        $dailyProfit = $completedDetails()
            ->sum(DB::raw($profitExpression));

        $lowStockCount = Product::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->where('stock', '<=', 0)
                    ->orWhereColumn('stock', '<=', 'min_stock');
            })
            ->count();

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