@foreach($categories as $category)
<tr class="clickable" onclick="window.location='{{ route('admin.categories.show', $category->id) }}'">
    <td>
        <div class="d-flex align-center gap-15">
            <div class="u-avatar">
                @if($category->image)
                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}">
                @else
                    <i class='bx bxs-category-alt'></i>
                @endif
            </div>
            <div>
                <div class="u-row-title">{{ $category->name }}</div>
                <div class="u-row-sub"># {{ $category->id }}</div>
            </div>
        </div>
    </td>
    <td class="text-center">
        <span class="u-count">
            <i class='bx bx-git-branch'></i>
            {{ $category->sub_categories_count }} فرعية
        </span>
    </td>
    <td class="text-center">
        <span class="u-badge u-badge-emerald">
            <i class='bx bx-cog'></i> فروع الخدمة
        </span>
    </td>
    <td class="text-left">
        <div class="u-action-group">
            <a href="{{ route('admin.categories.edit', $category->id) }}" class="u-action-btn edit" title="تعديل" onclick="event.stopPropagation()">
                <i class='bx bx-edit-alt'></i>
            </a>
            <span class="u-action-btn view" title="عرض التفاصيل">
                <i class='bx bx-chevron-left'></i>
            </span>
        </div>
    </td>
</tr>
@endforeach
