@forelse($subCategories as $sub)
<tr>
    <td>
        <div class="u-row-title">{{ $sub->name }}</div>
        <div class="u-row-sub"># {{ $sub->id }}</div>
    </td>
    <td>
        <span class="u-badge u-badge-emerald">
            <i class='bx bx-layer'></i>
            {{ optional($sub->mainCategory)->name ?? 'غير محدد' }}
        </span>
    </td>
    <td class="text-center">
        <span class="u-count">
            <i class='bx bx-list-check'></i>
            {{ $sub->services->count() }} عروض
        </span>
    </td>
    <td class="text-left">
        <div class="u-action-group">
            <a href="{{ route('admin.sub_categories.edit', $sub->id) }}" class="u-action-btn edit" title="تعديل">
                <i class='bx bx-edit-alt'></i>
            </a>
            <form action="{{ route('admin.sub_categories.destroy', $sub->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الخدمة الفرعية؟')">
                @csrf @method('DELETE')
                <button type="submit" class="u-action-btn delete" title="حذف">
                    <i class='bx bx-trash'></i>
                </button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="4" class="u-empty-state">
        <i class='bx bx-list-check'></i>
        <h4>لا توجد خدمات فرعية</h4>
        <p>ابدأ بإضافة خدماتك الفرعية للربط بين الرئيسية والعروض.</p>
    </td>
</tr>
@endforelse
