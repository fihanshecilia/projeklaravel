<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $start = $request->input(
            'start_date',
            $request->input(
                'from',
                $request->input(
                    'date_from',
                    $request->input(
                        'start',
                        $request->input('date', today()->toDateString())
                    )
                )
            )
        );
        $end = $request->input(
            'end_date',
            $request->input(
                'to',
                $request->input(
                    'date_to',
                    $request->input(
                        'end',
                        $request->input('date', $start)
                    )
                )
            )
        );

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        $transactions = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('completed_at', '>=', $start)
            ->whereDate('completed_at', '<=', $end);

        $profitExpression = DB::connection()->getDriverName() === 'mysql'
            ? 'hitung_laba(unit_price, purchase_price) * quantity'
            : '(unit_price - purchase_price) * quantity';

        $dailyProfit = TransactionDetail::query()
            ->whereHas('transaction', function ($query) use ($start, $end): void {
                $query->where('status', 'completed')
                    ->whereDate('completed_at', '>=', $start)
                    ->whereDate('completed_at', '<=', $end);
            })
            ->sum(DB::raw($profitExpression));

        $bestSeller = TransactionDetail::query()
            ->whereHas('transaction', function ($query) use ($start, $end): void {
                $query->where('status', 'completed')
                    ->whereDate('completed_at', '>=', $start)
                    ->whereDate('completed_at', '<=', $end);
            })
            ->selectRaw(
                'product_id, product_name, unit, SUM(quantity) as sold_quantity'
            )
            ->groupBy('product_id', 'product_name', 'unit')
            ->orderByDesc('sold_quantity')
            ->first();

        $lowStockProducts = Product::query()
            ->with('category:id,name')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('stock', '<=', 0)
                    ->orWhereColumn('stock', '<=', 'min_stock');
            })
            ->orderBy('stock')
            ->get();

        $dailyTotal = (clone $transactions)->sum('total');
        $dailyCount = (clone $transactions)->count();

        $paymentMethods = (clone $transactions)
            ->selectRaw('payment_method, COUNT(*) as transaction_count, SUM(total) as total_amount')
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->get();

        $date = $start;

        $payment_methods = $paymentMethods;

        return view('admin.reports', compact(
            'start',
            'end',
            'date',
            'dailyProfit',
            'dailyTotal',
            'dailyCount',
            'bestSeller',
            'lowStockProducts',
            'paymentMethods',
            'payment_methods'
        ));
    }

    public function stock(): View
    {
        $stockLogs = StockLog::query()
            ->with([
                'product:id,name,unit',
                'user:id,name',
            ])
            ->latest()
            ->paginate(30);

        $lowStockProducts = Product::query()
            ->with('category:id,name')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('stock', '<=', 0)
                    ->orWhereColumn('stock', '<=', 'min_stock');
            })
            ->orderBy('stock')
            ->get();

        return view('admin.stock', compact(
            'stockLogs',
            'lowStockProducts'
        ));
    }
}