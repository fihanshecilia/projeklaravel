<?php

namespace App\Http\Controllers;

use App\Models\StockLog;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

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

        $report = $this->reportService->getSummary((string) $start, (string) $end);

        return view('admin.reports', [
            'start' => $report['startDate'],
            'end' => $report['endDate'],
            'date' => $report['startDate'],
            'dailyProfit' => $report['dailyProfit'],
            'dailyTotal' => $report['dailyTotal'],
            'dailyCount' => $report['dailyCount'],
            'bestSeller' => $report['bestSeller'],
            'lowStockProducts' => $report['lowStockProducts'],
            'paymentMethods' => $report['paymentMethods'],
            'payment_methods' => $report['paymentMethods'],
        ]);
    }

    public function stock(): View
    {
        $stockLogs = StockLog::query()
            ->with([
                'product:id,name,unit',
                'user:id,name',
            ])
            ->latest('id')
            ->paginate(30);

        $lowStockProducts = $this->reportService->getLowStockProducts();

        return view('admin.stock', compact(
            'stockLogs',
            'lowStockProducts'
        ));
    }
}
