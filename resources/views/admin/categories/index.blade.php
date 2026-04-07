@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin-unified.css') }}">
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="u-page-header">
    <div class="u-header-info">
        <h1 class="u-header-title">الخدمات الرئيسية</h1>
        <p class="u-header-sub">إدارة هيكلية الخدمات الرئيسية وتفرعاتها</p>
    </div>
    <div class="u-header-actions">
        <a href="{{ route('admin.categories.create') }}" class="u-btn-primary">
            <i class='bx bx-plus-circle'></i> إضافة رئيسي
        </a>
        <a href="{{ route('admin.sub_categories.create') }}" class="u-btn-outline">
            <i class='bx bx-list-plus'></i> إضافة فرعي
        </a>
    </div>
</div>



{{-- ── Table ── --}}
<div class="u-table-wrap">
    <div class="u-table-scroll" id="categoryScroll">
        <table class="u-table">
            <thead>
                <tr>
                    <th width="42%">الخدمة الرئيسية</th>
                    <th width="18%" class="text-center">الخدمات الفرعية</th>
                    <th width="20%" class="text-center">إجمالي الخدمات</th>
                    <th width="20%" class="text-left">الإجراءات</th>
                </tr>
            </thead>
            <tbody id="categoryList">
                @include('admin.categories.partials.category_rows', ['categories' => $categories])
            </tbody>
        </table>

        <div id="loadingIndicator" class="u-loading no-display">
            <div class="u-spinner"></div>
            جاري تحميل المزيد...
        </div>
    </div>
</div>

<script>
    let page = 1;
    let loading = false;
    let hasMore = {{ $categories->hasMorePages() ? 'true' : 'false' }};
    const scrollContainer = document.getElementById('categoryScroll');
    const listContainer   = document.getElementById('categoryList');
    const loader          = document.getElementById('loadingIndicator');

    window.addEventListener('scroll', () => {
        if (loading || !hasMore) return;
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 50) {
            loadMore();
        }
    });

    function loadMore() {
        loading = true;
        page++;
        loader.classList.remove('no-display');
        loader.style.display = 'flex';

        fetch(`{{ route('admin.categories.index') }}?page=${page}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            listContainer.insertAdjacentHTML('beforeend', data.html);
            hasMore = data.hasMore;
            loading = false;
            loader.style.display = 'none';
        })
        .catch(err => {
            console.error(err);
            loading = false;
            loader.style.display = 'none';
        });
    }
</script>

@endsection
