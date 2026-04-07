@foreach($users as $user)
<tr onclick="window.location='{{ route('admin.users.show', $user->user_id) }}'" style="cursor: pointer;">
    <td>
        <div class="user-info-cell">
            <div class="user-avatar-sm {{ $user->user_type == 2 ? 'vendor' : ($user->user_type == 1 ? 'provider' : ($user->user_type == 0 ? 'customer' : '')) }}">
                {{ mb_substr($user->full_name, 0, 1) }}
            </div>
            <div>
                <div style="font-weight: 700; color: #1e293b;">
                    {{ $user->full_name }}
                    @if($user->user_type == 2 && $user->seller)
                        <div style="font-size: 0.8rem; color: #4f46e5; margin-top: 2px;">
                            <i class='bx bxs-store'></i> {{ $user->seller->shop_name }}
                        </div>
                    @endif
                </div>
                <div style="font-size: 0.75rem; color: #64748b;">{{ $user->email }}</div>
            </div>
        </div>
    </td>
    <td>{{ $user->phone ?? 'غير محدد' }}</td>
    <td>
        <span class="badge badge-{{ $user->user_type }}">
            {{ $user->user_type_label ?? ($user->user_type == 0 ? 'عميل' : ($user->user_type == 1 ? 'مزود خدمة' : ($user->user_type == 2 ? 'تاجر' : 'إدارة'))) }}
        </span>
    </td>
    <td>
        @if($user->location)
            <span style="font-size: 0.85rem;">{{ $user->location->governorate ?? $user->location->district ?? '' }}</span>
        @else
            <span class="text-muted">نقص البيانات</span>
        @endif
    </td>
    <td onclick="event.stopPropagation()">
        <label class="switch-status">
            <input type="checkbox" 
                   class="status-toggle-ajax" 
                   data-id="{{ $user->user_id }}" 
                   {{ $user->is_active ? 'checked' : '' }}>
            <span class="slider-status round"></span>
        </label>
    </td>
    <td onclick="event.stopPropagation()">
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn-edit-link" title="تعديل">
                <i class='bx bx-edit'></i>
            </a>
            <form action="{{ route('admin.users.destroy', $user->user_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-delete-link" title="حذف">
                    <i class='bx bx-trash'></i>
                </button>
            </form>
        </div>
    </td>
</tr>
@endforeach
