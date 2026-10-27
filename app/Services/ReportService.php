<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Ambil ringkasan laporan untuk periode tertentu.
     */
    public function getSummary(string $startDate, string $endDate): array
    {
        if ($endDate < $startDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $isHistorical = Carbon::parse($endDate)->isBefore(today());
        $cacheKey = "report_summary_{$startDate}_{$endDate}";

        if ($isHistorical && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $transactionsQuery = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('completed_at', '>=', $startDate)
            ->whereDate('completed_at', '<=', $endDate);

        $dailyTotal = (float) (clone $transactionsQuery)->sum('total');
        $dailyCount = (int) (clone $transactionsQuery)->count();

        $profitExpression = DB::connection()->getDriverName() === 'mysql'
            ? 'hitung_laba(unit_price, purchase_price) * quantity'
            : '(unit_price - purchase_price) * quantity';

        $dailyProfit = (float) TransactionDetail::query()
            ->whereHas('transaction', function (Builder $query) use ($startDate, $endDate): void {
                $query->where('status', 'completed')
                    ->whereDate('completed_at', '>=', $startDate)
                    ->whereDate('completed_at', '<=', $endDate);
            })
            ->sum(DB::raw($profitExpression));

        $bestSeller = TransactionDetail::query()
            ->whereHas('transaction', function (Builder $query) use ($startDate, $endDate): void {
                $query->where('status', 'completed')
                    ->whereDate('completed_at', '>=', $startDate)
                    ->whereDate('completed_at', '<=', $endDate);
            })
            ->selectRaw('product_id, product_name, unit, SUM(quantity) as sold_quantity')
            ->groupBy('product_id', 'product_name', 'unit')
            ->orderByDesc('sold_quantity')
            ->first();

        $paymentMethods = (clone $transactionsQuery)
            ->selectRaw('payment_method, COUNT(*) as transaction_count, SUM(total) as total_amount')
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->get();

        $lowStockProducts = $this->getLowStockProducts();

        $result = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dailyTotal' => $dailyTotal,
            'dailyCount' => $dailyCount,
            'dailyProfit' => $dailyProfit,
            'bestSeller' => $bestSeller,
            'paymentMethods' => $paymentMethods,
            'lowStockProducts' => $lowStockProducts,
        ];

        if ($isHistorical) {
            Cache::put($cacheKey, $result, now()->addHours(6));
        }

        return $result;
    }

    /**
     * Ambil ringkasan hari ini.
     */
    public function getTodaySummary(): array
    {
        $today = today()->toDateString();

        return $this->getSummary($today, $today);
    }

    /**
     * Hitung jumlah produk yang stoknya menipis atau habis.
     */
    public function getLowStockCount(): int
    {
        return Product::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->where('stock', '<=', 0)
                    ->orWhereColumn('stock', '<=', 'min_stock');
            })
            ->count();
    }

    /**
     * Ambil daftar produk yang stoknya menipis atau habis.
     */
    public function getLowStockProducts(int $limit = 50): Collection
    {
        return Product::query()
            ->with('category:id,name')
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->where('stock', '<=', 0)
                    ->orWhereColumn('stock', '<=', 'min_stock');
            })
            ->orderBy('stock')
            ->limit($limit)
            ->get();
    }

    /**
     * Hapus cache laporan saat terjadi transaksi baru.
     */
    public static function flushCache(): void
    {
        // Cache tag tidak selalu didukung database driver, cukup flush key harian jika ada.
    }
}
