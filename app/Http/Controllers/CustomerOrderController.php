<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Transaction::query()
            ->with('details')
            ->where('user_id', $request->user()->id)
            ->where('sales_channel', 'storefront')
            ->latest()
            ->paginate(10);

        return view('storefront.history', compact('orders'));
    }
}
