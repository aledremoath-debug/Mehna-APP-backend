@extends('admin.layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">إدارة المستخدمين</h1>
        <p class="page-subtitle">إدارة جميع مستخدمي النظام</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn-add-user">
        <i class='bx bx-plus'></i> إضافة مستخدم
    </a>
</div>

{{-- Statistics Overview - Only on All Users page --}}
@if(!request()->filled('type'))
<div class="stats-overview">
    <div class="stat-card">
        <div class="stat-label">إجمالي المستخدمين</div>
        <div class="stat-value">{{ $users->total() }}</div>
    </div>
    <div class="stat-card customer">
        <div class="stat-label">العملاء</div>
        <div class="stat-value">{{ \App\Models\User::where('user_type', 0)->count() }}</div>
    </div>
    <div class="stat-card provider">
        <div class="stat-label">مقدمو الخدمات</div>
        <div class="stat-value">{{ \App\Models\User::where('user_type', 1)->count() }}</div>
    </div>
    <div class="stat-card vendor">
        <div class="stat-label">التجار (المتاجر)</div>
        <div class="stat-value">{{ \App\Models\User::where('user_type', 2)->count() }}</div>
    </div>
</div>
@endif

{{-- Filter Section --}}
<div class="filter-section">
    <form action="{{ route('admin.users.index') }}" method="GET" class="filter-form">
        <div class="search-wrapper">
            <i class='bx bx-search search-icon'></i>
            <input type="text" 
                   name="search" 
                   placeholder="ابحث بالاسم، البريد، أو الهاتف..." 
                   value="{{ request('search') }}" 
                   class="search-input">
        </div>
        
        @if(!request()->filled('type'))
        <select name="type" class="type-select">
            <option value="">كل الأنواع</option>
            <option value="0" {{ request('type') === '0' ? 'selected' : '' }}>عميل</option>
            <option value="1" {{ request('type') === '1' ? 'selected' : '' }}>مقدم خدمة</option>
            <option value="2" {{ request('type') === '2' ? 'selected' : '' }}>تاجر</option>
        </select>
        @else
            {{-- Keep the type hidden to maintain the filter when searching --}}
            <input type="hidden" name="type" value="{{ request('type') }}">
        @endif
        
        <button type="submit" class="btn-search">
            <i class='bx bx-search'></i>
            <span>بحث</span>
        </button>
        
        @if(request('search') || (request()->filled('type') && !request()->routeIs('admin.users.index')))
            <a href="{{ route('admin.users.index') }}" class="btn-reset">
                <i class='bx bx-x'></i>
                <span>إعادة تعيين</span>
            </a>
        @endif
    </form>
</div>

{{-- Users Table --}}
@if($users->count() > 0)
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>المستخدم / المتجر</th>
                    <th>رقم الهاتف</th>
                    <th>النوع</th>
                    <th>الموقع</th>
                    <th>الحالة</th>
                    <th>العمليات</th>
                </tr>
            </thead>
            <tbody id="usersList">
                @include('admin.users.partials.user_rows', ['users' => $users])
            </tbody>
        </table>
    </div>
    
    <div id="loadingIndicator" style="display: none; text-align: center; padding: 20px; color: #4f46e5;">
        <i class='bx bx-loader-alt bx-spin' style="font-size: 24px;"></i>
        <p style="margin-top: 10px; font-weight: 500;">جاري تحميل المزيد من المستخدمين...</p>
    </div>
    
    <div id="noMoreData" style="display: none; text-align: center; padding: 20px; color: #10b981;">
        <i class='bx bx-check-circle' style="font-size: 24px;"></i>
        <p style="margin-top: 10px; font-weight: 500;">تم تحميل جميع المستخدمين</p>
    </div>
@else
    <div class="empty-users">
        <div class="empty-icon"><i class='bx bx-user'></i></div>
        <h3>لا يوجد مستخدمين بهذا النوع</h3>
        <p>قم بإضافة مستخدمين جدد للنظام</p>
    </div>
@endif

<script>
    let page = 1;
    let loading = false;
    let hasMore = {{ $users->hasMorePages() ? 'true' : 'false' }};
    const usersList = document.getElementById('usersList');
    const loadingEl = document.getElementById('loadingIndicator');
    const noMoreDataEl = document.getElementById('noMoreData');

    // Make sure we hide noMoreData initially if there are more
    if (!hasMore && usersList) {
        if(noMoreDataEl && {{ $users->count() }} > 0) noMoreDataEl.style.display = 'block';
    }

    // Scroll event listener for infinite scrolling
    window.addEventListener('scroll', () => {
        if (loading || !hasMore || !usersList) return;
        
        // Check if user has scrolled near bottom of the page
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 150) {
            loadMore();
        }
    });

    function loadMore() {
        loading = true;
        page++;
        if (loadingEl) loadingEl.style.display = 'block';

        const params = new URLSearchParams(window.location.search);
        params.set('page', page);

        fetch(`${window.location.pathname}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            usersList.insertAdjacentHTML('beforeend', data.html);
            hasMore = data.hasMore;
            loading = false;
            
            if (loadingEl) loadingEl.style.display = 'none';
            if (!hasMore && noMoreDataEl) noMoreDataEl.style.display = 'block';
        })
        .catch(err => {
            console.error('Error loading more users:', err);
            loading = false;
            if (loadingEl) loadingEl.style.display = 'none';
        });
    }

    // Toggle status using event delegation so it works for dynamically loaded rows
    document.body.addEventListener('change', function(e) {
        if (e.target.matches('.status-toggle-ajax')) {
            const toggle = e.target;
            const userId = toggle.dataset.id;
            const status = toggle.checked ? 1 : 0;
            
            fetch(`/admin/users/${userId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if(!data.success) {
                    toggle.checked = !toggle.checked;
                    alert('حدث خطأ أثناء تحديث الحالة');
                }
            });
        }
    });
</script>
@endsection
