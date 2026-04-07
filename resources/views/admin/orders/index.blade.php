@extends('admin.layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">مراقبة الطلبات</h1>
</div>

<div class="content-card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>العميل</th>
                    <th>مقدم الخدمة</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th style="text-align: left;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td><strong>{{ $order->user->full_name ?? 'مستخدم محذوف' }}</strong></td>
                    <td>{{ $order->provider && $order->provider->user ? $order->provider->user->full_name : 'قيد الانتظار' }}</td>
                    <td>
                        <span class="status-badge {{ $order->status === 'completed' ? 'status-completed' : ($order->status === 'cancelled' ? 'status-cancelled' : 'status-pending') }}">
                            {{ $order->status === 'completed' ? 'مكتمل' : ($order->status === 'cancelled' ? 'ملغي' : 'قيد الانتظار') }}
                        </span>
                    </td>
                    <td>{{ $order->created_at->format('Y-m-d') }}</td>
                    <td style="text-align: left;">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="link-edit">عرض التفاصيل</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">
        {{ $orders->links() }}
    </div>
</div>
@endsection
