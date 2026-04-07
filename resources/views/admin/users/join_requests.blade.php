@extends('admin.layouts.app')

@section('title', 'طلبات الانضمام')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/user-join-requests.css') }}">
@endpush

@section('content')
    <div class="page-header mb-30">
        <div>
            <h1 class="page-title fs-xl fw-800">طلبات الانضمام</h1>
            <p class="text-muted mt-5">مراجعة واعتماد حسابات التجار ومقدمي الخدمات الجدد</p>
        </div>
    </div>

    <div class="tabs-navigation mb-25">
        <a href="{{ route('admin.join-requests.index', ['status' => 'pending']) }}"
            class="tab-link {{ $status == 'pending' ? 'active-pending' : 'inactive-tab' }}">
            <i class='bx bx-time-five'></i> قيد الانتظار
        </a>
        <a href="{{ route('admin.join-requests.index', ['status' => 'approved']) }}"
            class="tab-link {{ $status == 'approved' ? 'active-approved' : 'inactive-tab' }}">
            <i class='bx bx-check-circle'></i> تم القبول
        </a>
        <a href="{{ route('admin.join-requests.index', ['status' => 'rejected']) }}"
            class="tab-link {{ $status == 'rejected' ? 'active-rejected' : 'inactive-tab' }}">
            <i class='bx bx-x-circle'></i> مرفوض
        </a>
    </div>

    <div class="premium-table-card">
        <div class="table-responsive sticky-scroll">
            <table class="modern-data-table">
                <thead class="sticky-thead">
                    <tr>
                        <th class="w-250">مقدم الطلب</th>
                        <th class="text-center w-140">نوع الحساب</th>
                        <th class="text-center w-150">التفاصيل الأساسية</th>
                        <th class="text-center w-150">الحالة</th>
                        <th class="text-center w-140">تاريخ الطلب</th>
                        <th class="text-center w-140">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $user)
                        <tr class="modern-row">
                            <td>
                                <div class="user-info-cell">
                                    <div
                                        class="initial-avatar {{ $user->serviceProvider ? 'avatar-provider' : 'avatar-seller' }}">
                                        {{ strtoupper(mb_substr($user->full_name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="user-text">
                                        <div class="user-name">{{ $user->full_name ?? 'بدون اسم' }}</div>
                                        <div class="user-meta-small">{{ $user->email }}<br>{{ $user->phone }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($user->serviceProvider)
                                    <span class="badge badge-provider">
                                        <i class="bx bxs-wrench"></i> مقدم خدمة
                                    </span>
                                @else
                                    <span class="badge badge-seller">
                                        <i class="bx bxs-store-alt"></i> تاجر
                                    </span>
                                @endif
                            </td>
                            <td class="text-center fs-sm text-muted">
                                @if($user->serviceProvider)
                                    @if(!$user->serviceProvider->main_category_id || !$user->serviceProvider->experience_years)
                                        <span class="text-danger fw-600"><i class="bx bx-error-circle"></i> بيانات ناقصة</span>
                                    @else
                                        <div class="fw-600 text-slate-700">
                                            {{ $user->serviceProvider->mainCategory->name ?? 'غير محدد' }}</div>
                                        <div>الخبرة: {{ $user->serviceProvider->experience_years ?? 0 }} سنوات</div>
                                    @endif
                                @elseif($user->seller)
                                    @if(!$user->seller->shop_name)
                                        <span class="text-danger fw-600"><i class="bx bx-error-circle"></i> بيانات ناقصة</span>
                                    @else
                                        <div class="fw-600 text-slate-700">متجر: {{ Str::limit($user->seller->shop_name, 15) }}</div>
                                        <div>{{ $user->seller->commercial_register ?? 'لا يوجد سجل' }}</div>
                                    @endif
                                @else
                                    <span class="text-danger fw-600"><i class="bx bx-error-circle"></i> بيانات ناقصة</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex-center">
                                    @if($user->approval_status == 'pending')
                                        <span class="status-badge status-pending">
                                            <span class="status-dot"></span> قيد الانتظار
                                        </span>
                                    @elseif($user->approval_status == 'approved')
                                        <span class="status-badge status-resolved">
                                            <span class="status-dot"></span> تم القبول
                                        </span>
                                    @elseif($user->approval_status == 'rejected')
                                        <span class="status-badge status-rejected">
                                            <span class="status-dot"></span> مرفوض
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="date-cell">
                                    <span
                                        class="d-block">{{ optional($user->created_at)->format('Y-m-d') ?? 'غير متوفر' }}</span>
                                    <span class="time-small">{{ optional($user->created_at)->format('h:i A') ?? '' }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="flex-center-gap-8">
                                    <button type="button" class="btn-action-view" title="عرض التفاصيل الكاملة" onclick="showDetailsModal({
                                            id: '{{ $user->user_id }}',
                                            name: '{{ addslashes($user->full_name) }}',
                                            email: '{{ addslashes($user->email) }}',
                                            phone: '{{ addslashes($user->phone) }}',
                                            type: {{ $user->serviceProvider ? 1 : 2 }},
                                            type_label: '{{ $user->serviceProvider ? "مقدم خدمة" : "تاجر" }}',
                                            status: '{{ $user->approval_status }}',
                                            location_label: '{{ addslashes($user->location ? ($user->location->governorate . " - " . $user->location->district) : "غير محدد") }}',
                                            address_desc: '{{ addslashes($user->address_description ?? "") }}',
                                            provider_exp: '{{ $user->serviceProvider->experience_years ?? "" }}',
                                            provider_license: '{{ $user->serviceProvider->work_license ?? "" }}',
                                            provider_bio: '{{ preg_replace("/\r|\n/", " ", addslashes($user->serviceProvider->bio ?? "")) }}',
                                            seller_shop: '{{ addslashes($user->seller->shop_name ?? "") }}',
                                            seller_reg: '{{ addslashes($user->seller->commercial_register ?? "") }}',
                                            seller_image: '{{ $user->seller->shop_image ?? "" }}',
                                            seller_desc: '{{ preg_replace("/\r|\n/", " ", addslashes($user->seller->shop_description ?? "")) }}'
                                        })">
                                        <i class="bx bx-show-alt"></i> التفاصيل
                                    </button>

                                    @if($user->approval_status == 'pending')
                                        <form action="{{ route('admin.join-requests.approve', $user->user_id) }}" method="POST"
                                            class="m-0">
                                            @csrf
                                            <button type="submit" class="btn-action-approve" title="قبول وتفعيل">
                                                <i class="bx bx-check"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn-action-reject" title="رفض الطلب"
                                            onclick="showRejectModal({{ $user->user_id }})">
                                            <i class="bx bx-x"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-content">
                                    <i class="bx bx-user-x"></i>
                                    <span>لا توجد طلبات انضمام حالياً.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="custom-modal-backdrop" style="display: none;">
        <div class="custom-modal-dialog">
            <div class="custom-modal-header">
                <h3 class="custom-modal-title"><i class="bx bx-id-card"></i> تفاصيل مقدم الطلب الكاملة</h3>
                <button class="custom-modal-close" onclick="closeDetailsModal()"><i class="bx bx-x"></i></button>
            </div>
            <div class="custom-modal-body">

                <div class="user-profile-header">
                    <div class="profile-avatar" id="modal-avatar-initial">U</div>
                    <div class="profile-info">
                        <h4 id="detail-name">الاسم المكتمل</h4>
                        <p id="detail-email-phone" class="fw-600 text-slate-700 mb-5">البريد والهاتف</p>
                        <div id="detail-type-badge" class="badge">نوع الحساب</div>
                    </div>
                    <div id="modal-image-container" class="profile-img-container">
                        <img id="modal-profile-img" src="" class="full-img">
                    </div>
                </div>

                <div class="details-section mb-4">
                    <h5 class="section-title"><i class="bx bx-map-pin"></i> الموقع والعنوان</h5>
                    <div class="details-grid">
                        <div class="detail-box">
                            <label>المنطقة (المحافظة - المديرية)</label>
                            <div class="detail-val" id="detail-location-label">-</div>
                        </div>
                        <div class="detail-box">
                            <label>رقم الهاتف المعتمد</label>
                            <div class="detail-val ltr-text" id="detail-phone-val">-</div>
                        </div>
                        <div class="detail-box full-width">
                            <label>العنوان التفصيلي</label>
                            <div class="detail-val" id="detail-address-desc">-</div>
                        </div>
                    </div>
                </div>

                <div class="details-section" id="seller-details-section" style="display: none;">
                    <h5 class="section-title"><i class="bx bxs-store"></i> بيانات المتجر</h5>
                    <div class="details-grid">
                        <div class="detail-box">
                            <label>اسم المتجر</label>
                            <div class="detail-val" id="detail-shop-name">-</div>
                        </div>
                        <div class="detail-box">
                            <label>رقم السجل التجاري / وثيقة العمل</label>
                            <div class="detail-val" id="detail-commercial-reg">-</div>
                        </div>
                        <div class="detail-box full-width">
                            <label>وصف المتجر</label>
                            <div class="detail-val text-content" id="detail-shop-desc">-</div>
                        </div>
                    </div>
                </div>

                <div class="details-section" id="provider-details-section" style="display: none;">
                    <h5 class="section-title"><i class="bx bxs-wrench"></i> بيانات الخدمة والمهنة</h5>
                    <div class="details-grid">
                        <div class="detail-box">
                            <label>سنوات الخبرة</label>
                            <div class="detail-val" id="detail-experience">-</div>
                        </div>
                        <div class="detail-box">
                            <label>ترخيص العمل / الهوية</label>
                            <div class="detail-val" id="detail-work-license">لا يوجد ملف مرفق</div>
                        </div>
                        <div class="detail-box full-width">
                            <label>نبذة تعريفية (Bio)</label>
                            <div class="detail-val text-content" id="detail-bio">-</div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="custom-modal-footer">
                <button class="btn-cancel-modal" onclick="closeDetailsModal()">إغلاق</button>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="custom-modal-backdrop" style="display: none;">
        <div class="custom-modal-dialog" style="max-width: 450px;">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="custom-modal-header danger-header">
                    <h3 class="custom-modal-title"><i class="bx bx-error-circle"></i> رفض طلب الانضمام</h3>
                    <button type="button" class="custom-modal-close" onclick="closeRejectModal()"><i
                            class="bx bx-x"></i></button>
                </div>
                <div class="custom-modal-body">
                    <label class="fw-600 text-slate-700 d-block mb-8">
                        سبب الرفض <span class="text-danger">*</span>
                    </label>
                    <textarea name="reason" id="reason" rows="4" class="form-textarea-modal"
                        placeholder="اكتب سبب الرفض (سيتم إرساله للمستخدم لتصحيحه)..." required></textarea>
                </div>
                <div class="custom-modal-footer">
                    <button type="button" class="btn-cancel-modal" onclick="closeRejectModal()">إلغاء</button>
                    <button type="submit" class="btn-confirm-danger">تأكيد الرفض</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        function showDetailsModal(data) {
            document.getElementById('detail-name').innerText = data.name || 'غير متوفر';
            document.getElementById('detail-email-phone').innerText = (data.email || 'لا يوجد إيميل') + ' | ' + (data.phone || 'لا يوجد هاتف');
            document.getElementById('detail-phone-val').innerText = data.phone || 'غير متوفر';
            document.getElementById('modal-avatar-initial').innerText = data.name ? data.name.substring(0, 1) : 'U';

            document.getElementById('detail-location-label').innerText = data.location_label;
            document.getElementById('detail-address-desc').innerText = data.address_desc || 'لم يتم إدخال عنوان تفصيلي';

            const typeBadge = document.getElementById('detail-type-badge');
            typeBadge.innerText = data.type_label;

            const imgContainer = document.getElementById('modal-image-container');
            const modalImg = document.getElementById('modal-profile-img');
            imgContainer.style.display = 'none';

            if (data.type == 1) {
                typeBadge.className = 'badge badge-provider';
                document.getElementById('modal-avatar-initial').className = 'profile-avatar avatar-provider';

                document.getElementById('seller-details-section').style.display = 'none';
                document.getElementById('provider-details-section').style.display = 'block';

                document.getElementById('detail-experience').innerText = data.provider_exp ? data.provider_exp + ' سنوات' : 'غير متوفر';
                document.getElementById('detail-bio').innerText = data.provider_bio || 'لا يوجد نبذة تعريفية';

                if (data.provider_license) {
                    document.getElementById('detail-work-license').innerHTML = `<a href="/storage/${data.provider_license}" target="_blank" style="color: #4f46e5; text-decoration: underline;">مشاهدة المرفق</a>`;
                } else {
                    document.getElementById('detail-work-license').innerText = 'لا يوجد ملف مرفق';
                }
            } else {
                typeBadge.className = 'badge badge-seller';
                document.getElementById('modal-avatar-initial').className = 'profile-avatar avatar-seller';

                document.getElementById('provider-details-section').style.display = 'none';
                document.getElementById('seller-details-section').style.display = 'block';

                document.getElementById('detail-shop-name').innerText = data.seller_shop || 'غير متوفر';
                document.getElementById('detail-commercial-reg').innerText = data.seller_reg || 'غير متوفر';
                document.getElementById('detail-shop-desc').innerText = data.seller_desc || 'لا يوجد وصف للمتجر';

                if (data.seller_image) {
                    imgContainer.style.display = 'block';
                    modalImg.src = '/storage/' + data.seller_image;
                }
            }

            const detailsModal = document.getElementById('detailsModal');
            detailsModal.style.display = 'flex';
            detailsModal.offsetHeight;
            detailsModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeDetailsModal() {
            const detailsModal = document.getElementById('detailsModal');
            detailsModal.classList.remove('show');
            setTimeout(() => {
                detailsModal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }

        function showRejectModal(userId) {
            const form = document.getElementById('rejectForm');
            form.action = `/admin/join-requests/${userId}/reject`;
            document.getElementById('reason').value = '';

            const rejectModal = document.getElementById('rejectModal');
            rejectModal.style.display = 'flex';
            rejectModal.offsetHeight;
            rejectModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeRejectModal() {
            const rejectModal = document.getElementById('rejectModal');
            rejectModal.classList.remove('show');
            setTimeout(() => {
                rejectModal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('custom-modal-backdrop')) {
                if (e.target.id === 'detailsModal') closeDetailsModal();
                if (e.target.id === 'rejectModal') closeRejectModal();
            }
        });
    </script>



@endsection