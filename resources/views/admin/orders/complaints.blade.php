@extends('admin.layouts.app')

@section('title', 'الشكاوي والاقتراحات')

@section('content')
<div class="page-header mb-20">
    <div>
        <h1 class="page-title fs-xl fw-800">الشكاوي والاقتراحات</h1>
        <p class="text-muted mt-5">إدارة ومتابعة طلبات المستخدمين والرد عليها</p>
    </div>
</div>

@if(session('success'))
    <div class="u-alert-success" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:13px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-weight:600;">
        <i class='bx bx-check-circle'></i> {{ session('success') }}
    </div>
@endif

<div class="premium-table-card">
    <div class="table-responsive">
        <table class="modern-data-table">
            <thead>
                <tr>
                    <th class="w-250">المستخدم</th>
                    <th class="text-center w-140">النوع</th>
                    <th>الموضوع والتفاصيل</th>
                    <th class="text-center w-150">الحالة</th>
                    <th class="text-center w-140">التاريخ</th>
                    <th class="text-center w-100">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($complaints as $complaint)
                <tr class="modern-row">
                    <td>
                        <div class="user-info-cell">
                            <div class="initial-avatar">
                                {{ strtoupper(substr($complaint->user->full_name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="user-text">
                                <div class="user-name">{{ $complaint->user->full_name ?? 'مستخدم غير معروف' }}</div>
                                <div class="user-phone">{{ $complaint->user->phone ?? '-' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $complaint->type === 'suggestion' ? 'badge-info' : 'badge-danger' }}">
                            <i class="bx {{ $complaint->type === 'suggestion' ? 'bx-bulb' : 'bx-error-circle' }}"></i>
                            {{ $complaint->type === 'suggestion' ? 'اقتراح' : 'شكوى' }}
                        </span>
                    </td>
                    <td>
                        <div class="subject-cell">
                            <div class="subject-title">{{ $complaint->subject }}</div>
                            <div class="message-preview">{{ Str::limit($complaint->message, 60) }}</div>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="flex-center">
                            <span class="status-badge status-{{ $complaint->status }}">
                                <span class="status-dot"></span>
                                @if($complaint->status === 'pending') قيد الانتظار
                                @elseif($complaint->status === 'processing') جاري المعالجة
                                @elseif($complaint->status === 'resolved') تم الحل
                                @elseif($complaint->status === 'ignored') تم التجاهل
                                @else {{ $complaint->status }} @endif
                            </span>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="date-cell">
                            <span>{{ $complaint->created_at->format('Y-m-d') }}</span>
                            <i class="bx bx-calendar"></i>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="flex-center">
                            <button type="button" class="btn-action-view" title="عرض التفاصيل والرد"
                                onclick="viewComplaint({
                                    id: {{ $complaint->id }},
                                    name: '{{ addslashes($complaint->user->full_name ?? 'غير معروف') }}',
                                    phone: '{{ addslashes($complaint->user->phone ?? '-') }}',
                                    type_label: '{{ $complaint->type === 'suggestion' ? 'اقتراح' : 'شكوى' }}',
                                    is_suggestion: {{ $complaint->type === 'suggestion' ? 'true' : 'false' }},
                                    subject: '{{ addslashes($complaint->subject) }}',
                                    message: '{{ preg_replace('/\r|\n/', ' ', addslashes($complaint->message)) }}',
                                    admin_reply: '{{ preg_replace('/\r|\n/', ' ', addslashes($complaint->admin_reply ?? '')) }}',
                                    status: '{{ $complaint->status }}',
                                    date: '{{ $complaint->created_at->format('Y-m-d h:i A') }}',
                                    update_url: '{{ route('admin.complaints.status', $complaint->id) }}'
                                })">
                                <i class="bx bx-show-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        <div class="empty-content">
                            <i class="bx bx-message-square-minus"></i>
                            <span>لا توجد شكاوي أو اقتراحات حالياً.</span>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-container">
        {{ $complaints->links() }}
    </div>
</div>

{{-- ═══════════ Complaint Detail + Reply Modal ═══════════ --}}
<div id="complaintModal" style="
    display: none;
    position: fixed; inset: 0; z-index: 99999;
    background: rgba(15,23,42,.65);
    backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
    padding: 20px;
    opacity: 0; transition: opacity .3s ease;">

    <div style="
        background: #fff; border-radius: 24px; width: 100%; max-width: 640px;
        box-shadow: 0 30px 60px -15px rgba(0,0,0,.3);
        transform: translateY(30px) scale(.96);
        transition: all .35s cubic-bezier(.175,.885,.32,1.275);
        display: flex; flex-direction: column; max-height: 92vh; overflow: hidden;">

        {{-- ── Header ── --}}
        <div style="padding: 22px 28px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; border-radius: 24px 24px 0 0;
                    display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modal-type-label" style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                <i class="bx bx-detail" style="font-size: 1.4rem; color: #4f46e5;"></i>
                تفاصيل الشكوى والرد
            </h3>
            <button onclick="closeModal()" style="
                background: #f1f5f9; border: none; width: 34px; height: 34px;
                border-radius: 50%; color: #64748b; font-size: 1.4rem;
                display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="bx bx-x"></i>
            </button>
        </div>

        {{-- ── Body ── --}}
        <div style="padding: 24px 28px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 18px;">

            {{-- User Info --}}
            <div style="display: flex; align-items: center; gap: 14px; padding: 16px; background: #f8fafc; border-radius: 14px; border: 1px solid #f1f5f9;">
                <div id="modal-avatar" style="width: 50px; height: 50px; border-radius: 14px;
                     background: linear-gradient(135deg, #4f46e5, #3730a3);
                     display: flex; align-items: center; justify-content: center;
                     color: #fff; font-size: 1.4rem; font-weight: 800; flex-shrink: 0; font-family: monospace;">U</div>
                <div style="flex: 1;">
                    <div id="modal-name" style="font-size: 1rem; font-weight: 800; color: #0f172a;"></div>
                    <div id="modal-phone" style="font-size: .88rem; color: #64748b; margin-top: 2px;"></div>
                </div>
                <div id="modal-type-badge"></div>
            </div>

            {{-- Subject --}}
            <div>
                <label style="font-size: .78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 7px;">الموضوع</label>
                <div id="modal-subject" style="font-size: 1rem; font-weight: 700; color: #1e293b; padding: 12px 16px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;"></div>
            </div>

            {{-- Message --}}
            <div>
                <label style="font-size: .78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 7px;">الرسالة</label>
                <div id="modal-message" style="font-size: .93rem; color: #334155; line-height: 1.7; padding: 14px 16px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; min-height: 70px;"></div>
            </div>

            {{-- Previous Reply --}}
            <div id="prev-reply-section" style="display: none;">
                <label style="font-size: .78rem; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 7px;">
                    <i class="bx bx-reply"></i> الرد السابق للإدارة
                </label>
                <div id="modal-prev-reply" style="font-size: .93rem; color: #065f46; line-height: 1.7; padding: 14px 16px; background: #f0fdf4; border-radius: 10px; border: 1px solid #bbf7d0;"></div>
            </div>

            {{-- Date --}}
            <div style="font-size: .85rem; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <i class="bx bx-calendar"></i>
                <span id="modal-date"></span>
            </div>

            {{-- ─── Reply Form ─── --}}
            <form id="replyForm" method="POST" action="" style="display: flex; flex-direction: column; gap: 14px; border-top: 2px dashed #e2e8f0; padding-top: 18px; margin-top: 4px;">
                @csrf
                @method('POST')

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    {{-- Status --}}
                    <div>
                        <label for="status-select" style="font-size: .78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 7px;">تغيير الحالة</label>
                        <select id="status-select" name="status" style="width:100%; padding: 11px 14px; border-radius: 10px; border: 2px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-weight: 700; font-size: .9rem; appearance: auto;">
                            <option value="pending">قيد الانتظار</option>
                            <option value="processing">جاري المعالجة</option>
                            <option value="resolved">تم الحل</option>
                            <option value="ignored">تم التجاهل</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <div style="font-size: .82rem; color: #64748b; background: #f1f5f9; padding: 10px 14px; border-radius: 10px; border: 1px dashed #cbd5e1; flex: 1;">
                            <i class="bx bx-bell" style="color: #4f46e5;"></i> سيتلقى المستخدم إشعاراً فورياً بالرد والحالة الجديدة.
                        </div>
                    </div>
                </div>

                {{-- Reply textarea --}}
                <div>
                    <label for="admin-reply-input" style="font-size: .78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 7px;">
                        رد الإدارة (اختياري)
                    </label>
                    <textarea id="admin-reply-input" name="admin_reply" rows="4"
                        placeholder="اكتب ردك على المستخدم هنا..."
                        style="width: 100%; padding: 13px 16px; border-radius: 12px; border: 2px solid #e2e8f0; background: #f8fafc;
                               color: #1e293b; font-size: .93rem; line-height: 1.6; resize: vertical; font-family: inherit;
                               transition: border-color .2s; box-sizing: border-box;"
                        onfocus="this.style.borderColor='#4f46e5'; this.style.background='#fff';"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';"></textarea>
                </div>

                {{-- Submit --}}
                <button type="submit" style="
                    background: linear-gradient(135deg, #4f46e5, #3730a3);
                    color: #fff; border: none; border-radius: 12px;
                    padding: 13px 28px; font-weight: 700; font-size: .95rem;
                    cursor: pointer; transition: all .25s; display: flex; align-items: center; justify-content: center; gap: 8px;
                    box-shadow: 0 4px 15px rgba(79,70,229,.3);"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(79,70,229,.4)'"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 15px rgba(79,70,229,.3)'">
                    <i class="bx bx-send"></i> إرسال الرد وتحديث الحالة
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const statusMap = {
        pending:    'قيد الانتظار',
        processing: 'جاري المعالجة',
        resolved:   'تم الحل',
        ignored:    'تم التجاهل',
    };

    function viewComplaint(data) {
        // Fill user info
        const initials = data.name ? data.name.substring(0, 1).toUpperCase() : 'U';
        document.getElementById('modal-avatar').innerText = initials;
        document.getElementById('modal-name').innerText    = data.name;
        document.getElementById('modal-phone').innerText   = data.phone;
        document.getElementById('modal-subject').innerText = data.subject;
        document.getElementById('modal-message').innerText = data.message;
        document.getElementById('modal-date').innerText    = data.date;

        // Header title
        document.getElementById('modal-type-label').innerHTML =
            '<i class="bx bx-detail" style="font-size:1.4rem;color:#4f46e5;"></i> ' +
            (data.is_suggestion ? 'تفاصيل الاقتراح' : 'تفاصيل الشكوى');

        // Type badge
        const badge = document.getElementById('modal-type-badge');
        if (data.is_suggestion) {
            badge.innerHTML = '<span style="background:#eff6ff;color:#1d4ed8;padding:5px 13px;border-radius:30px;font-size:.8rem;font-weight:700;border:1px solid #bfdbfe;display:inline-flex;gap:5px;align-items:center;"><i class=\'bx bx-bulb\'></i>' + data.type_label + '</span>';
        } else {
            badge.innerHTML = '<span style="background:#fef2f2;color:#b91c1c;padding:5px 13px;border-radius:30px;font-size:.8rem;font-weight:700;border:1px solid #fecaca;display:inline-flex;gap:5px;align-items:center;"><i class=\'bx bx-error-circle\'></i>' + data.type_label + '</span>';
        }

        // Previous reply
        if (data.admin_reply && data.admin_reply.trim() !== '') {
            document.getElementById('prev-reply-section').style.display = 'block';
            document.getElementById('modal-prev-reply').innerText = data.admin_reply;
            document.getElementById('admin-reply-input').value = data.admin_reply;
        } else {
            document.getElementById('prev-reply-section').style.display = 'none';
            document.getElementById('admin-reply-input').value = '';
        }

        // Status dropdown
        const sel = document.getElementById('status-select');
        sel.value = data.status;

        // Form action URL
        document.getElementById('replyForm').action = data.update_url;

        // Show modal
        const modal = document.getElementById('complaintModal');
        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.style.opacity = '1';
            modal.firstElementChild.style.transform = 'translateY(0) scale(1)';
        });
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('complaintModal');
        modal.style.opacity = '0';
        modal.firstElementChild.style.transform = 'translateY(30px) scale(.96)';
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }

    // Close on backdrop click
    document.getElementById('complaintModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>

@endsection
