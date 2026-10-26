<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with('category:id,name')
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category?->name ?? 'Lainnya',
                'price' => (float) $product->selling_price,
                'unit' => $product->unit,
                'stock' => (float) $product->stock,
                'min_stock' => (float) $product->min_stock,
                'image' => $product->image_path
                    ? asset('storage/'.$product->image_path)
                    : null,
            ]);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $todayTransactions = Transaction::query()
            ->where('status', 'completed')
            ->where('sales_channel', 'pos')
            ->whereDate('completed_at', today());

        $dailyTotal = (clone $todayTransactions)->sum('total');
        $dailyCount = (clone $todayTransactions)->count();

        return view('pos.index', compact(
            'products',
            'categories',
            'dailyTotal',
            'dailyCount'
        ));
    }

    public function store(
        Request $request,
        TransactionService $transactions
    ): RedirectResponse {
        $data = $request->all();
        $data['status'] = 'completed';
        $data['delivery_type'] = 'pickup';

        $transaction = $transactions->create(
            $data,
            $request->user()
        );

        return redirect()->route(
            'pos.receipts.show',
            $transaction->transaction_number
        );
    }

    public function history(Request $request): View
    {
        $search = trim((string) $request->input('search', $request->input('q', $request->input('transaction_number', $request->input('number', '')))));
        $date = $request->input('date', $request->input('tanggal'));
        $from = $request->input('from', $request->input('start_date', $request->input('start', $request->input('date_from'))));
        $to = $request->input('to', $request->input('end_date', $request->input('end', $request->input('date_to'))));

        $orders = Transaction::query()
            ->with(['details', 'handledBy'])
            ->where('sales_channel', 'pos')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('transaction_number', 'like', '%'.$search.'%');
            })
            ->when($date, function ($query) use ($date): void {
                $query->whereDate('created_at', $date);
            })
            ->when($from && ! $date, function ($query) use ($from): void {
                $query->whereDate('created_at', '>=', $from);
            })
            ->when($to && ! $date, function ($query) use ($to): void {
                $query->whereDate('created_at', '<=', $to);
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('pos.history', compact(
            'orders',
            'search',
            'date',
            'from',
            'to'
        ));
    }

    public function receipt(string $transactionNumber): View
    {
        $transaction = Transaction::query()
            ->with('details')
            ->where('transaction_number', $transactionNumber)
            ->where('sales_channel', 'pos')
            ->firstOrFail();

        abort_unless(
            $transaction->user_id === Auth::id()
                || Auth::user()->isStoreAdmin(),
            403
        );

        return view('pos.receipt', compact('transaction'));
    }
}