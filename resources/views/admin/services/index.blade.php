@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin-unified.css') }}">
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="u-page-header">
    <div class="u-header-info">
        <h1 class="u-header-title">عروض الخدمات</h1>
        <p class="u-header-sub">استعراض وإدارة جميع عروض الخدمات المقدمة وسرعة الوصول للمزودين</p>
    </div>
    <div class="u-header-actions">
        <a href="{{ route('admin.services.create') }}" class="u-btn-primary">
            <i class='bx bx-plus-circle'></i> إضافة عرض خدمة جديد
        </a>
    </div>
</div>



{{-- ── Table ── --}}
<div class="u-table-wrap">
    <div class="u-table-scroll" id="scrollContainer">
        <table class="u-table">
            <thead>
                <tr>
                    <th width="8%">#</th>
                    <th width="28%">عنوان عرض الخدمة</th>
                    <th width="20%">الخدمة الفرعية المرتبطة</th>
                    <th width="18%">المزود</th>
                    <th width="12%">السعر</th>
                    <th width="14%" class="text-left">العمليات</th>
                </tr>
            </thead>
            <tbody id="servicesList">
                @include('admin.services.partials.service_rows', ['services' => $services])
            </tbody>
        </table>

        <div id="loadingIndicator" class="u-loading no-display">
            <div class="u-spinner"></div>
            جاري تحميل المزيد من عروض الخدمات...
        </div>

        <div id="noMoreData" class="u-loading no-display" style="color: var(--u-success);">
            <i class='bx bx-check-double'></i> تم تحميل جميع عروض الخدمات
        </div>
    </div>
</div>

<script>
    let page = 1;
    let loading = false;
    let hasMore = {{ $services->hasMorePages() ? 'true' : 'false' }};
    const scrollContainer   = document.getElementById('scrollContainer');
    const servicesList      = document.getElementById('servicesList');
    const loadingIndicator  = document.getElementById('loadingIndicator');
    const noMoreData        = document.getElementById('noMoreData');

    window.addEventListener('scroll', function() {
        if (loading || !hasMore) return;
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 100) {
            loadMoreServices();
        }
    });

    function loadMoreServices() {
        loading = true;
        page++;
        loadingIndicator.style.display = 'flex';

        fetch('{{ route("admin.services.index") }}?page=' + page, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            servicesList.insertAdjacentHTML('beforeend', data.html);
            hasMore = data.hasMore;
            loading = false;
            loadingIndicator.style.display = 'none';
            if (!hasMore) { noMoreData.style.display = 'flex'; }
            attachDeleteListeners();
        })
        .catch(error => {
            console.error('Error:', error);
            loading = false;
            loadingIndicator.style.display = 'none';
        });
    }

    function attachDeleteListeners() {
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.onclick = function() {
                if (confirm('هل أنت متأكد من رغبتك في حذف هذا العرض نهائياً؟')) {
                    this.closest('form').submit();
                }
            };
        });
    }

    attachDeleteListeners();
</script>

@endsection
