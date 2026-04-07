@forelse($services as $service)
<tr>
    <td class="u-row-sub fw-700">#{{ $service->id }}</td>
    <td>
        <div class="u-row-title">{{ $service->title }}</div>
        <div class="u-row-sub">{{ Str::limit($service->description, 65) }}</div>
    </td>
    <td>
        <div class="d-flex" style="flex-direction: column; gap: 5px;">
            <span class="u-badge u-badge-indigo">
                <i class='bx bxs-category'></i>
                {{ $service->mainCategory->name ?? '---' }}
            </span>
            <span class="u-badge u-badge-slate" style="font-size:.78rem;">
                <i class='bx bx-subdirectory-left'></i>
                {{ $service->subCategory->name ?? '---' }}
            </span>
        </div>
    </td>
    <td>
        <span class="u-badge u-badge-emerald">
            <i class='bx bxs-user-badge'></i>
            {{ optional(optional($service->provider)->user)->full_name ?? 'غير معروف' }}
        </span>
    </td>
    <td>
        <span class="u-price-amount">{{ number_format($service->price) }}</span>
        <span class="u-price-unit">ج.س / عرض</span>
    </td>
    <td class="text-left">
        <div class="u-action-group">
            <a href="{{ route('admin.services.edit', $service->id) }}" class="u-action-btn edit" title="تعديل">
                <i class='bx bx-edit-alt'></i>
            </a>
            <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" class="d-inline delete-form">
                @csrf
                @method('DELETE')
                <button type="button" class="u-action-btn delete delete-btn" title="حذف">
                    <i class='bx bx-trash'></i>
                </button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="u-empty-state">
        <i class='bx bx-info-circle'></i>
        <h4>لا توجد خدمات</h4>
        <p>لم يتم إضافة أي خدمات حتى الآن.</p>
    </td>
</tr>
@endforelse
