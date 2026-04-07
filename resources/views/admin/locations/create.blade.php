@extends('admin.layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">إضافة موقع جديد</h1>
</div>

<div class="form-card">
    <form action="{{ route('admin.locations.store') }}" method="POST">
        @csrf



        <div class="form-group">
            <label class="form-label">المحافظة</label>
            <input type="text" name="governorate" class="form-input" value="{{ old('governorate') }}" required placeholder="مثال: صنعاء">
        </div>

        <div class="form-group">
            <label class="form-label">المديرية / المنطقة</label>
            <input type="text" name="district" class="form-input" value="{{ old('district') }}" required placeholder="مثال: حدة">
        </div>



        <div class="form-actions">
            <a href="{{ route('admin.locations.index') }}" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">
                <i class='bx bx-save mr-2'></i> حفظ الموقع
            </button>
        </div>
    </form>
</div>
@endsection
