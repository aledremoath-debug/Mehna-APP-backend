<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\ServiceProvider;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalUsers'      => User::count(),
            'totalCustomers'  => User::where('user_type', User::TYPE_CUSTOMER)->count(),
            'totalProviders'  => User::where('user_type', User::TYPE_PROVIDER)->count(),
            'totalVendors'    => User::where('user_type', User::TYPE_SELLER)->count(),
            'totalOrders'     => Order::count(),
        ]);
    }
}
