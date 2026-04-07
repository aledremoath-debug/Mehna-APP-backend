<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_providers' => User::where('user_type', 'provider')->count(),
            'total_orders' => Order::count(),
            'total_revenue' => 0, // Placeholder for now
            'orders_by_status' => Order::select('status', DB::raw('count(*) as total'))
                                       ->groupBy('status')
                                       ->pluck('total', 'status'),
            'recent_complaints' => Complaint::latest()->take(5)->get()
        ];

        return view('admin.reports.index', compact('stats'));
    }
}
