<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Mahna Admin')</title>

    {{-- Theme: must load first --}}
    <link rel="stylesheet" href="{{ asset('css/admin/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/layout.css') }}">
    @if(!request()->routeIs('admin.users.*'))
    <link rel="stylesheet" href="{{ asset('css/admin/style.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/users.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/reports.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/professions.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/orders.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/locations.css') }}">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin/toast.css') }}">
    @stack('styles')

    {{-- Apply saved theme instantly to prevent flash --}}
    <script>
        (function(){
            var t = localStorage.getItem('mahna-theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>

<body class="admin-layout">

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar">
        <h2 class="sidebar-title">
            <i class='bx bxs-dashboard'></i>
            Mahna Admin
        </h2>

        <nav class="sidebar-nav">

            <a href="{{ route('admin.dashboard') }}"
               class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bx bxs-dashboard"></i> لوحة التحكم
            </a>

            <div class="sidebar-dropdown {{ request()->routeIs('admin.users.*') ? 'open' : '' }}">
                <div class="nav-item dropdown-toggle">
                    <i class="bx bxs-user-detail"></i> <span>المستخدمين</span>
                    <i class="bx bx-chevron-down arrow"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="{{ route('admin.users.index') }}"
                       class="dropdown-item {{ request()->routeIs('admin.users.index') && !request()->filled('type') ? 'active' : '' }}">
                        <i class="bx bx-group"></i> الكل
                    </a>
                    <a href="{{ route('admin.users.index', ['type' => 0]) }}"
                       class="dropdown-item {{ request('type') === '0' ? 'active' : '' }}">
                        <i class="bx bxs-user"></i> العملاء
                    </a>
                    <a href="{{ route('admin.users.index', ['type' => 1]) }}"
                       class="dropdown-item {{ request('type') === '1' ? 'active' : '' }}">
                        <i class="bx bxs-wrench"></i> مقدمو الخدمات
                    </a>
                    <a href="{{ route('admin.users.index', ['type' => 2]) }}"
                       class="dropdown-item {{ request('type') === '2' ? 'active' : '' }}">
                        <i class="bx bxs-store"></i> التجار
                    </a>
                </div>
            </div>

            <a href="{{ route('admin.locations.index') }}"
               class="nav-item {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}">
                <i class="bx bxs-map"></i> الفروع والمحافظات
            </a>

            <div class="sidebar-dropdown {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.sub_categories.*') || request()->routeIs('admin.services.*') ? 'open' : '' }}">
                <div class="nav-item dropdown-toggle">
                    <i class="bx bxs-briefcase"></i> <span>إدارة الخدمات</span>
                    <i class="bx bx-chevron-down arrow"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="{{ route('admin.categories.index') }}"
                       class="dropdown-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <i class="bx bx-layer"></i> الخدمات الرئيسية
                    </a>
                    <a href="{{ route('admin.sub_categories.index') }}"
                       class="dropdown-item {{ request()->routeIs('admin.sub_categories.*') ? 'active' : '' }}">
                        <i class="bx bx-subdirectory-right"></i> الخدمات الفرعية
                    </a>
                    <a href="{{ route('admin.services.index') }}"
                       class="dropdown-item {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                        <i class="bx bx-wrench"></i> عروض الخدمات
                    </a>
                </div>
            </div>

            <a href="{{ route('admin.orders.index') }}"
               class="nav-item {{ request()->routeIs('admin.orders.*') && !request()->routeIs('admin.join-requests.*') ? 'active' : '' }}">
                <i class="bx bxs-shopping-bag"></i> الطلبات
            </a>

            <a href="{{ route('admin.product_categories.index') }}"
               class="nav-item {{ request()->routeIs('admin.product_categories.*') ? 'active' : '' }}">
                <i class="bx bxs-category"></i> تصنيفات المنتجات
            </a>

            <a href="{{ route('admin.join-requests.index') }}"
               class="nav-item {{ request()->routeIs('admin.join-requests.*') ? 'active' : '' }}">
                <i class="bx bxs-user-plus"></i> طلبات الانضمام
                @php
                    $pendingCount = \App\Models\User::where('approval_status', \App\Models\User::STATUS_PENDING)->count();
                @endphp
                @if($pendingCount > 0)
                    <span class="badge-count">{{ $pendingCount }}</span>
                @endif
            </a>

            <a href="{{ route('admin.reports.index') }}"
               class="nav-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <i class="bx bxs-report"></i> التقارير
            </a>

            <a href="{{ route('admin.complaints.index') }}"
               class="nav-item {{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}">
                <i class="bx bx-message-square-detail"></i> الشكاوي والاقتراحات
            </a>

            <a href="{{ route('admin.settings.index') }}"
               class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="bx bx-cog"></i> إعدادات التطبيق
            </a>

            <a href="{{ route('admin.ai.index') }}"
               class="nav-item {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}">
                <i class="bx bxs-bot"></i> إدارة المساعد الذكي
            </a>

        </nav>

        <!-- Sidebar Footer: Theme Toggle + Logout -->
        <div class="sidebar-footer">
            <button type="button" class="theme-toggle-btn" onclick="toggleTheme()" title="تبديل الوضع">
                <i class='bx bx-moon'></i>
                <i class='bx bx-sun'></i>
                <span class="toggle-label-light">الوضع الليلي</span>
                <span class="toggle-label-dark">الوضع النهاري</span>
            </button>

            <form method="POST" action="{{ route('admin.logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class='bx bx-log-out'></i> تسجيل الخروج
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <main class="main-container">
        
        <!-- Mobile Header -->
        <header class="mobile-header">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class='bx bx-menu'></i>
            </button>
            <h3>Mahna Admin</h3>
            <button class="header-theme-toggle" onclick="toggleTheme()" title="تبديل الوضع">
                <i class='bx bx-moon'></i>
                <i class='bx bx-sun'></i>
            </button>
        </header>

        <!-- Top Header (Desktop) -->
        <div class="top-header">
            <div class="top-header-right">
                <h4 class="top-header-title">@yield('title', 'لوحة التحكم')</h4>
            </div>
            <div class="top-header-left">
                <button class="header-theme-toggle" onclick="toggleTheme()" title="تبديل الوضع">
                    <i class='bx bx-moon'></i>
                    <i class='bx bx-sun'></i>
                </button>
            </div>
        </div>

        <div class="content-wrapper">
            @yield('content')
        </div>
    </main>

    <script>
        // Sidebar Toggle (Mobile)
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
            document.querySelector('.sidebar-overlay').classList.toggle('active');
        }

        // Sidebar Dropdown Toggle
        document.querySelectorAll('.dropdown-toggle').forEach(button => {
            button.addEventListener('click', function() {
                this.parentElement.classList.toggle('open');
            });
        });

        // Theme Toggle
        function toggleTheme() {
            var html = document.documentElement;
            var current = html.getAttribute('data-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('mahna-theme', next);
        }
    </script>

    <!-- Toast Notifications System -->
    <script src="{{ asset('js/admin/toast.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                toast.success('نجاح', '{{ session('success') }}');
            @endif
            
            @if(session('error'))
                toast.error('خطأ', '{{ session('error') }}');
            @endif
            
            @if(session('warning'))
                toast.warning('تنبيه', '{{ session('warning') }}');
            @endif
            
            @if(session('info'))
                toast.info('معلومة', '{{ session('info') }}');
            @endif
            
            @if($errors->any())
                @foreach($errors->all() as $error)
                    toast.error('تنبيه', '{{ $error }}');
                @endforeach
            @endif
        });
    </script>
</body>
</html>
