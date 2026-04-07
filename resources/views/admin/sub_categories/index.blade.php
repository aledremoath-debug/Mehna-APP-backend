@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin-unified.css') }}">
@endpush

@section('content')

{{-- ── Breadcrumb ── --}}
<nav class="u-breadcrumb">
    <a href="{{ route('admin.dashboard') }}">الرئيسية</a>
    <i class='bx bx-chevron-left'></i>
    <span>الخدمات الفرعية</span>
</nav>

{{-- ── Page Header ── --}}
<div class="u-page-header">
    <div class="u-header-info">
        <h1 class="u-header-title">الخدمات الفرعية</h1>
        <p class="u-header-sub">إدارة وتصنيف الخدمات المندرجة تحت كل خدمة رئيسية</p>
    </div>
    <div class="u-header-actions">
        <a href="{{ route('admin.sub_categories.create') }}" class="u-btn-primary">
            <i class='bx bx-plus-circle'></i> إضافة خدمة فرعية
        </a>
    </div>
</div>

{{-- ── Table ── --}}
<div class="u-table-wrap">
    <div class="u-table-scroll" id="subCategoryScroll">
        <table class="u-table">
            <thead>
                <tr>
                    <th width="35%">اسم الخدمة الفرعية</th>
                    <th width="25%">الخدمة الرئيسية التابعة لها</th>
                    <th width="20%" class="text-center">إجمالي العروض</th>
                    <th width="20%" class="text-left">الإجراءات</th>
                </tr>
            </thead>
            <tbody id="subCategoryList">
                @include('admin.sub_categories.partials.subcategory_rows', ['subCategories' => $subCategories])
            </tbody>
        </table>

        <div id="loadingIndicator" class="u-loading" style="display: none; align-items: center; justify-content: center; padding: 20px;">
            <div class="u-spinner"></div>
            جاري تحميل المزيد من الخدمات الفرعية...
        </div>

        <div id="noMoreData" class="u-loading" style="display: none; text-align: center; color: var(--u-success); padding: 20px;">
            <i class='bx bx-check-double'></i> تم تحميل جميع الخدمات الفرعية
        </div>
    </div>
</div>

<script>
    let page = 1;
    let loading = false;
    let hasMore = {{ $subCategories->hasMorePages() ? 'true' : 'false' }};
    const subCategoryList = document.getElementById('subCategoryList');
    const loadingEl = document.getElementById('loadingIndicator');
    const noMoreDataEl = document.getElementById('noMoreData');

    if (!hasMore && {{ $subCategories->count() }} > 0) {
        noMoreDataEl.style.display = 'block';
    }

    window.addEventListener('scroll', () => {
        if (loading || !hasMore) return;
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 100) {
            loadMore();
        }
    });

    function loadMore() {
        loading = true;
        page++;
        loadingEl.style.display = 'flex';

        const params = new URLSearchParams(window.location.search);
        params.set('page', page);

        fetch(`${window.location.pathname}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            subCategoryList.insertAdjacentHTML('beforeend', data.html);
            hasMore = data.hasMore;
            loading = false;
            
            loadingEl.style.display = 'none';
            if (!hasMore) {
                noMoreDataEl.style.display = 'block';
            }
        })
        .catch(err => {
            console.error(err);
            loading = false;
            loadingEl.style.display = 'none';
        });
    }
</script>

@endsection
