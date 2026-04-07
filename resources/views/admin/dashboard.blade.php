@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')

<h1 class="dashboard-title">لوحة التحكم</h1>

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon stat-icon-users">
            <i class='bx bxs-user-detail'></i>
        </div>
        <div class="stat-info">
            <h3>عدد المستخدمين</h3>
            <p class="stat-value">{{ $totalUsers }}</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-customers" style="background-color: #e3f2fd; color: #ffffffff;">
            <i class='bx bxs-user'></i>
        </div>
        <div class="stat-info">
            <h3>العملاء</h3>
            <p class="stat-value">{{ $totalCustomers }}</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-providers" style="background-color: #e8f5e9; color: #ffffffff;">
            <i class='bx bxs-briefcase'></i>
        </div>
        <div class="stat-info">
            <h3>مزودي الخدمة</h3>
            <p class="stat-value">{{ $totalProviders }}</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-vendors" style="background-color: #fff3e0; color: #ffffffff;">
            <i class='bx bxs-store'></i>
        </div>
        <div class="stat-info">
            <h3>التجار</h3>
            <p class="stat-value">{{ $totalVendors }}</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-orders">
            <i class='bx bxs-shopping-bag'></i>
        </div>
        <div class="stat-info">
            <h3>الطلبات</h3>
            <p class="stat-value">{{ $totalOrders }}</p>
        </div>
    </div>

</div>

@endsection
