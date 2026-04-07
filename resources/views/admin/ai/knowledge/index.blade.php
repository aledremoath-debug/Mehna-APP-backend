@extends('admin.layouts.app')

@section('title', 'إدارة قاعدة المعرفة')

@section('content')
<div class="reports-container">
    <div class="reports-header">
        <h1>إدارة قاعدة المعرفة (المساعد الذكي)</h1>
        <button class="btn-primary" onclick="showAddModal()">إضافة رد جديد</button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-container">
        <table class="reports-table">
            <thead>
                <tr>
                    <th>السؤال / الكلمة المفتاحية</th>
                    <th>الإجابة الثابتة</th>
                    <th>العمليات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($knowledge as $item)
                <tr>
                    <td>{{ $item->question }}</td>
                    <td>{{ Str::limit($item->answer, 100) }}</td>
                    <td>
                        <button class="btn-edit" onclick="showEditModal({{ json_encode($item) }})">تعديل</button>
                        <form action="{{ route('admin.ai.knowledge.destroy', $item->id) }}" method="POST" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete" onclick="return confirm('هل أنت متأكد؟')">حذف</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $knowledge->links() }}
    </div>
</div>

<!-- Modal Logic could be added here or as a separate partial -->
@endsection
