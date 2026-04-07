@extends('admin.layouts.app')

@section('title', 'إدارة المساعد الذكي')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin/ai_dashboard.css') }}">

<div class="ai-dashboard-container">
    {{-- Header Section --}}
    <div class="ai-header-card">
        <div class="ai-title-section">
            <h1>إدارة المساعد الذكي</h1>
            <p>تحكم في سلوك الذكاء الاصطناعي، قاعدة المعرفة، وراقب المحادثات من مكان واحد.</p>
        </div>
        
        <div class="ai-status-toggle-card">
            <span class="status-label">حالة المساعد: <span id="status-text">{{ $settings->ai_assistant_enabled ? 'نشط' : 'متوقف' }}</span></span>
            <label class="switch">
                <input type="checkbox" id="unified-ai-toggle" {{ $settings->ai_assistant_enabled ? 'checked' : '' }}>
                <span class="slider round"></span>
            </label>
        </div>
    </div>



    <div class="ai-content-grid">
        {{-- Knowledge Base Section --}}
        <div class="glass-card">
            <div class="card-header">
                <h2><i class="bx bxs-brain"></i> قاعدة المعرفة (Q&A)</h2>
                <button class="btn-premium btn-sm" onclick="openAddModal()">
                    <i class="bx bx-plus"></i> إضافة رد جديد
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="knowledge-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>السؤال / الكلمة المفتاحية</th>
                                <th>الإجابة</th>
                                <th>العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($knowledge as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-500">{{ $item->question }}</td>
                                <td title="{{ $item->answer }}">{{ Str::limit($item->answer, 80) }}</td>
                                <td>
                                    <div class="flex-gap-5">
                                        <button class="btn-edit-link" onclick="openEditModal({{ json_encode($item) }})" title="تعديل">
                                            <i class="bx bx-edit"></i>
                                        </button>
                                        <form action="{{ route('admin.ai.knowledge.destroy', $item->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الرد؟')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-delete-link" title="حذف">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center alt-empty-cell">لا توجد سجلات حالياً. أضف أول رد!</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-15">
                    {{ $knowledge->links() }}
                </div>
            </div>
        </div>

        {{-- Sessions Section --}}
        <div class="glass-card">
            <div class="card-header">
                <h2><i class="bx bxs-chat"></i> آخر جلسات الدردشة</h2>
                <a href="{{ route('admin.ai.sessions.index') }}" class="text-primary session-link-header">مشاهدة الكل</a>
            </div>
            <div class="card-body">
                @forelse($sessions as $session)
                <div class="session-item">
                    <div class="session-info">
                        <div class="session-avatar">
                            <i class="bx bx-user"></i>
                        </div>
                        <div>
                            <span class="session-user-name">{{ $session->user->full_name ?? 'مستخدم' }}</span>
                            <span class="session-time">{{ $session->created_at ? $session->created_at->diffForHumans() : 'غير معروف' }}</span>
                        </div>
                    </div>
                    <a href="{{ route('admin.ai.sessions.show', $session->id) }}" class="btn-view-circle">
                        <i class="bx bx-chevron-left"></i>
                    </a>
                </div>
                @empty
                <p class="text-center text-muted">لا توجد محادثات مسجلة.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ===== Modal: إضافة رد جديد ===== --}}
<div id="addModal" class="ai-modal-overlay d-none" style="display: none;">
    <div class="ai-modal-box">
        <div class="ai-modal-header">
            <h3><i class="bx bx-plus-circle"></i> إضافة رد جديد</h3>
            <button class="ai-modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form action="{{ route('admin.ai.knowledge.store') }}" method="POST">
            @csrf
            <div class="ai-modal-body">
                <div class="form-group mb-15">
                    <label class="form-label fw-600 d-block mb-6">
                        السؤال / الكلمة المفتاحية <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="question" class="form-input w-full form-input-styled" 
                           placeholder="مثال: ما هي خدمات التطبيق؟" 
                           value="{{ old('question') }}" required>
                    <small class="text-muted-alt fs-sm mt-4 d-block">
                        أدخل الكلمة أو الجملة التي سيبحث عنها المستخدم.
                    </small>
                </div>
                <div class="form-group">
                    <label class="form-label fw-600 d-block mb-6">
                        الإجابة / الرد <span class="text-danger">*</span>
                    </label>
                    <textarea name="answer" class="form-input w-full form-input-styled form-textarea" rows="5" 
                               placeholder="اكتب الرد الذي سيظهر للمستخدم..." required>{{ old('answer') }}</textarea>
                </div>
            </div>
            <div class="ai-modal-footer">
                <button type="button" class="btn-cancel-modal" onclick="closeAddModal()">إلغاء</button>
                <button type="submit" class="btn-save-modal">
                    <i class="bx bx-save"></i> حفظ الرد
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Modal: تعديل رد ===== --}}
<div id="editModal" class="ai-modal-overlay d-none" style="display: none;">
    <div class="ai-modal-box">
        <div class="ai-modal-header">
            <h3><i class="bx bx-edit"></i> تعديل الرد</h3>
            <button class="ai-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="ai-modal-body">
                <div class="form-group mb-15">
                    <label class="form-label fw-600 d-block mb-6">
                        السؤال / الكلمة المفتاحية <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="editQuestion" name="question" class="form-input w-full form-input-styled" required>
                </div>
                <div class="form-group">
                    <label class="form-label fw-600 d-block mb-6">
                        الإجابة / الرد <span class="text-danger">*</span>
                    </label>
                    <textarea id="editAnswer" name="answer" class="form-input w-full form-input-styled form-textarea" rows="5" required></textarea>
                </div>
            </div>
            <div class="ai-modal-footer">
                <button type="button" class="btn-cancel-modal" onclick="closeEditModal()">إلغاء</button>
                <button type="submit" class="btn-save-modal">
                    <i class="bx bx-save"></i> حفظ التغييرات
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ─── Toggle AI ────────────────────────────────────────────────────────────
    document.getElementById('unified-ai-toggle').addEventListener('change', function() {
        const isEnabled = this.checked;
        const statusText = document.getElementById('status-text');
        
        fetch('{{ route("admin.ai.toggle") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ enabled: isEnabled })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusText.innerText = data.status ? 'نشط' : 'متوقف';
                statusText.style.color = data.status ? '#10b981' : '#ef4444';
            }
        });
    });

    // ─── Add Modal ────────────────────────────────────────────────────────────
    function openAddModal() {
        document.getElementById('addModal').classList.remove('d-none');
        document.getElementById('addModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeAddModal() {
        document.getElementById('addModal').classList.add('d-none');
        document.getElementById('addModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // ─── Edit Modal ───────────────────────────────────────────────────────────
    function openEditModal(item) {
        document.getElementById('editQuestion').value = item.question;
        document.getElementById('editAnswer').value   = item.answer;
        document.getElementById('editForm').action    = '/admin/ai/knowledge/' + item.id;
        document.getElementById('editModal').classList.remove('d-none');
        document.getElementById('editModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeEditModal() {
        document.getElementById('editModal').classList.add('d-none');
        document.getElementById('editModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Close modals on overlay click
    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target === this) closeAddModal();
    });
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    // Auto-open add modal if there were validation errors (for add form)
    @if($errors->any() && old('question'))
        openAddModal();
    @endif
</script>
@endsection
