@extends('admin.layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">تعديل الموقع: {{ $location->governorate }} - {{ $location->district }}</h1>
</div>

<div class="form-card">
    <form action="{{ route('admin.locations.update', $location->id) }}" method="POST">
        @csrf
        @method('PUT')



        <div class="form-group">
            <label class="form-label">المحافظة</label>
            <input type="text" name="governorate" class="form-input" value="{{ old('governorate', $location->governorate) }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">المديرية / المنطقة</label>
            <input type="text" name="district" class="form-input" value="{{ old('district', $location->district) }}" required>
        </div>



        <div class="form-actions">
            <a href="{{ route('admin.locations.index') }}" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">
                <i class='bx bx-save mr-2'></i> تحديث الموقع
            </button>
        </div>
    </form>
</div>
@endsection
