@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin-unified.css') }}">
@endpush

@section('content')

{{-- ── Page Header ── --}}
<div class="u-page-header">
    <div class="u-header-info">
        <h1 class="u-header-title">تصنيفات المنتجات</h1>
        <p class="u-header-sub">إدارة تصنيفات المنتجات في صفحة قطع الغيار</p>
    </div>
    <div class="u-header-actions">
        <a href="{{ route('admin.product_categories.create') }}" class="u-btn-primary">
            <i class='bx bx-plus-circle'></i> إضافة تصنيف
        </a>
    </div>
</div>



{{-- ── Table ── --}}
<div class="u-table-wrap">
    <div class="u-table-scroll">
        <table class="u-table">
            <thead>
                <tr>
                    <th width="10%">#</th>
                    <th width="40%">اسم التصنيف</th>
                    <th width="20%" class="text-center">عدد المنتجات</th>
                    <th width="30%" class="text-left">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>{{ $category->id }}</td>
                        <td>
                            @if($category->icon)
                                <i class='bx {{ $category->icon }}'></i>
                            @endif
                            {{ $category->name }}
                        </td>
                        <td class="text-center">
                            <span style="background: #f0f9ff; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                                {{ $category->products_count }}
                            </span>
                        </td>
                        <td class="text-left">
                            <div class="u-action-group" style="justify-content: flex-start;">
                                <a href="{{ route('admin.product_categories.edit', $category->id) }}" class="u-action-btn edit" title="تعديل">
                                    <i class='bx bx-edit-alt'></i>
                                </a>
                                <form action="{{ route('admin.product_categories.destroy', $category->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('هل أنت متأكد من حذف هذا التصنيف؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="u-action-btn delete" title="حذف" style="border: none; background: transparent; cursor: pointer;">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 40px; color: #9ca3af;">
                            <i class='bx bx-folder-open' style="font-size: 2rem;"></i>
                            <p>لا توجد تصنيفات بعد</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
