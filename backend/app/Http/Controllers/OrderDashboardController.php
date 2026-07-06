<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;

class OrderDashboardController extends Controller
{
    public function index(): View
    {
        return view('orders.index', [
            'orders' => Order::query()
                ->orderByDesc('paid_at')
                ->orderByDesc('created_at')
                ->paginate(50)
                ->withQueryString(),
            'totalOrders' => Order::count(),
            'totalRevenue' => (float) Order::sum('price'),
        ]);
    }
}
