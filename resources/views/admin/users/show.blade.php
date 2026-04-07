@extends('admin.layouts.app')

@section('title', 'تفاصيل المستخدم')

@section('content')
<div class="profile-view-wrapper">
    {{-- Profile Header --}}
    <div class="profile-header">
        <div class="profile-avatar-lg">
            {{ substr($user->full_name, 0, 1) }}
        </div>
        <div class="profile-info">
            <span class="profile-badge">{{ $user->user_type_label }}</span>
            <h1>{{ $user->full_name }}</h1>
            <p><i class="bx bx-envelope"></i> {{ $user->email }} | <i class="bx bx-phone"></i> {{ $user->phone ?? 'لا يوجد' }}</p>
        </div>
        <div class="m-right-auto">
             <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn-premium-edit">
                <i class="bx bx-edit-alt"></i> تعديل البيانات
             </a>
        </div>
    </div>

    <div class="profile-grid">
        {{-- Main Details --}}
        <div>
            <div class="detail-card mb-4">
                <h3><i class="bx bx-info-circle"></i> المعلومات الأساسية</h3>
                <div class="info-row">
                    <span class="info-label">تاريخ التسجيل</span>
                    <span class="info-value">{{ $user->created_at ? $user->created_at->format('Y-m-d') : 'غير مسجل' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">الموقع</span>
                    <span class="info-value">
                        {{ $user->location ? $user->location->governorate . ' - ' . $user->location->district : 'غير محدد' }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">العنوان بالتفصيل</span>
                    <span class="info-value">{{ $user->address_description ?? 'لا يوجد' }}</span>
                </div>
                <div class="info-row border-none">
                    <span class="info-label">حالة الحساب</span>
                    <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                        {{ $user->is_active ? 'نشط' : 'معطل' }}
                    </span>
                </div>
            </div>

            {{-- Service Provider Specific --}}
            @if($user->user_type == 1 && $user->serviceProvider)
                <div class="detail-card mb-4">
                    <h3><i class="bx bx-wrench"></i> التصنيف الرئيسي</h3>
                    @if($user->serviceProvider->mainCategory)
                        <div class="info-row">
                            <span class="info-label">المجال</span>
                            <span class="info-value">{{ $user->serviceProvider->mainCategory->name }}</span>
                        </div>
                    @endif

                    <h3 class="mt-4"><i class="bx bx-list-ul"></i> الخدمات المعروضة</h3>
                    <div class="product-mini-list">
                        @foreach($user->serviceProvider->services as $service)
                            <div class="product-mini-item">
                                <div>
                                    <span class="fw-700">{{ $service->title }}</span>
                                    <div class="fs-xs text-muted">( {{ $service->subCategory->name ?? '' }} )</div>
                                </div>
                                <span class="text-primary fw-700">{{ $service->price }} ج.س</span>
                            </div>
                        @endforeach
                    </div>

                    <h3><i class="bx bx-history"></i> المهام الأخيرة المنجزة</h3>
                    <div class="activity-list">
                        @forelse($user->serviceProvider->maintenanceRequests()->latest()->limit(5)->get() as $task)
                            <div class="activity-item">
                                <div>
                                    <div class="activity-title">{{ $task->service ? $task->service->title : 'خدمة عامة' }}</div>
                                    <div class="activity-meta">للعميل: {{ $task->customer ? $task->customer->full_name : 'غير معروف' }}</div>
                                </div>
                                <span class="badge badge-{{ $task->status == 'completed' ? 'success' : 'warning' }}">
                                    {{ $task->status }}
                                </span>
                            </div>
                        @empty
                            <p class="text-muted text-center">لا توجد مهام منجزة حالياً.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Vendor Specific --}}
            @if($user->user_type == 2 && $user->seller)
                <div class="detail-card mb-4">
                    <h3><i class="bx bx-store"></i> المتجر والمنتجات</h3>
                    <div class="info-row">
                        <span class="info-label">اسم المتجر</span>
                        <span class="info-value">{{ $user->seller->shop_name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">الوصف</span>
                        <span class="info-value">{{ $user->seller->shop_description ?? 'لا يوجد' }}</span>
                    </div>
                    
                    <h3 class="mt-4"><i class="bx bx-package"></i> أحدث المنتجات</h3>
                    <div class="product-grid-sm">
                        @forelse($user->seller->products()->latest()->limit(4)->get() as $product)
                            <div class="product-mini">
                                @if($product->images->count() > 0)
                                    <img src="{{ asset('storage/' . $product->images->first()->image_path) }}" alt="Product">
                                @else
                                    <div class="product-image-placeholder"><i class="bx bx-image"></i></div>
                                @endif
                                <div class="fw-700 fs-xs">{{ $product->product_name }}</div>
                                <div class="text-primary fs-sm">{{ $product->price }} ج.س</div>
                            </div>
                        @empty
                            <p class="text-muted col-12 text-center">لا توجد منتجات معروضة حالياً.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Customer Specific (Order History) --}}
            @if($user->user_type == 0)
                <div class="detail-card mb-4">
                    <h3><i class="bx bx-cart"></i> سجل الطلبات الأخيرة</h3>
                    <div class="activity-list">
                        @forelse(\App\Models\Order::where('user_id', $user->user_id)->latest()->limit(5)->get() as $order)
                            <div class="activity-item">
                                <div>
                                    <div class="activity-title">طلب رقم #{{ $order->order_id }}</div>
                                    <div class="activity-meta">التاريخ: {{ $order->created_at->format('Y-m-d') }}</div>
                                </div>
                                <div class="text-left">
                                    <div class="fw-700 text-slate-800">{{ $order->total_price }} ج.س</div>
                                    <span class="badge badge-info">{{ $order->status }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center">لا توجد طلبات سابقة.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        {{-- Side Activity & Reviews --}}
        <div>
            <div class="detail-card mb-4 stats-summary-card">
                <h3><i class="bx bx-bar-chart-alt-2"></i> ملخص الإحصائيات</h3>
                <div class="stats-mini">
                    @if($user->user_type == 0)
                        <div class="info-row">
                            <span class="info-label">إجمالي الطلبات</span>
                            <span class="info-value">{{ \App\Models\Order::where('user_id', $user->user_id)->count() }}</span>
                        </div>
                    @elseif($user->user_type == 1)
                        <div class="info-row">
                            <span class="info-label">المهام المنجزة</span>
                            <span class="info-value">{{ \App\Models\MaintenanceRequest::where('provider_id', $user->user_id)->count() }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">متوسط التقييم</span>
                            <span class="info-value text-warning">★ {{ number_format($user->serviceProvider->rating_average ?? 0, 1) }}</span>
                        </div>
                    @elseif($user->user_type == 2)
                        <div class="info-row">
                            <span class="info-label">عدد المنتجات</span>
                            <span class="info-value">{{ $user->seller ? $user->seller->products->count() : 0 }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">إجمالي المبيعات</span>
                            <span class="info-value">{{ $user->seller ? \App\Models\Order::where('seller_id', $user->seller->seller_id)->count() : 0 }} طلب</span>
                        </div>
                    @endif
                </div>
            </div>

            @if($user->user_type != 0)
            <div class="detail-card">
                <h3><i class="bx bx-star"></i> التقييمات وآراء العملاء</h3>
                @forelse($user->reviewsReceived()->latest()->limit(5)->get() as $review)
                    <div class="review-card">
                        <div class="review-header">
                            <span class="reviewer-name">{{ $review->rater ? $review->rater->full_name : 'عميل مجهول' }}</span>
                            <div class="review-stars">
                                @for($i=1; $i<=5; $i++)
                                    <i class="bx {{ $i <= $review->rating ? 'bxs-star' : 'bx-star' }}"></i>
                                @endfor
                            </div>
                        </div>
                        <p class="review-comment">{{ $review->comment }}</p>
                        <div class="activity-meta text-left">{{ $review->created_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <div class="text-center py-40">
                        <i class="bx bx-message-square-detail fs-xxl text-light-gray d-block mb-10"></i>
                        <p class="text-muted mt-2">لا توجد تقييمات حالياً.</p>
                    </div>
                @endforelse
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
