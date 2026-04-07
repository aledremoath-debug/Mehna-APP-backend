<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceProvider;
use App\Models\MaintenanceRequest;
use App\Models\MainCategory;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Service;
use App\Models\ServiceImage;
use App\Models\Review;
use App\Models\Notification;
use App\Services\FcmService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProviderController extends Controller
{
    /**
     * جلب بيانات الملف الشخصي لمزود الخدمة
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)
            ->with(['mainCategory', 'user', 'services.images'])
            ->first();

        if (!$provider) {
            return response()->json([
                'status' => true,
                'data' => null,
                'message' => 'لم يتم إكمال ملف مقدم الخدمة بعد'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $provider
        ]);
    }

    /**
     * تحديث بيانات الملف الشخصي لمزود الخدمة
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)->first();

        $validator = Validator::make($request->all(), [
            'main_category_id' => 'sometimes|exists:main_categories,id',
            'bio' => 'sometimes|string|max:1000',
            'experience_years' => 'sometimes|integer|min:0',
            'is_available' => 'sometimes|boolean',
            'price_range' => 'sometimes|string|max:255',
            'work_license' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $request->only(['main_category_id', 'bio', 'experience_years', 'is_available', 'price_range']);

        if ($request->hasFile('work_license')) {
            if ($provider && $provider->work_license) {
                Storage::disk('public')->delete($provider->work_license);
            }
            $path = $request->file('work_license')->store('provider/licenses', 'public');
            $data['work_license'] = $path;
        }

        $isFirstTime = !ServiceProvider::where('user_id', $user->user_id)->where('bio', '!=', null)->exists();

        $provider = ServiceProvider::updateOrCreate(
            ['user_id' => $user->user_id],
            $data
        );

        // تحديث حالة الطلب ليكون قيد الانتظار بدلاً من الموافقة الفورية
        $user->update([
            'approval_status' => User::STATUS_PENDING,
        ]);

        // إرسال إشعار للادمن عند أول تقديم
        if ($isFirstTime) {
            \App\Models\Notification::create([
                'title'   => 'طلب انضمام جديد - مزود خدمة',
                'message' => 'قام ' . $user->full_name . ' بتقديم طلب انضمام كمزود خدمة.',
                'is_read' => false,
                'user_id' => null, // للادمن
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث البيانات بنجاح',
            'data' => $provider->load('mainCategory')
        ]);
    }

    /**
     * جلب طلبات الصيانة الخاصة بمزود الخدمة
     */
    public function getOrders(Request $request)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)->first();

        if (!$provider) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم العثور على حساب مزود خدمة'
            ], 404);
        }

        $status = $request->query('status'); // الاختياري: pending, accepted, completed, cancelled

        $orders = MaintenanceRequest::where('provider_id', $provider->id)
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->with(['customer', 'service', 'product.seller', 'product.images'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $orders
        ]);
    }

    /**
     * تحديث حالة طلب الصيانة
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)->first();

        $order = MaintenanceRequest::where('id', $id)
            ->where('provider_id', $provider->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'الطلب غير موجود أو لا تملك صلاحية الوصول إليه'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,accepted,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'الحالة غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate state transitions
        $currentStatus = $order->status;
        $newStatus = $request->status;

        if (in_array($currentStatus, ['completed', 'cancelled'])) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن تغيير حالة طلب مكتمل أو ملغي'
            ], 422);
        }

        if ($currentStatus === 'pending' && $newStatus === 'completed') {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن إكمال الطلب قبل البدء فيه (قبوله)'
            ], 422);
        }

        $order->update(['status' => $request->status]);

        // جلب بيانات العميل لإرسال إشعار
        $customer = $order->customer;
        if ($customer) {
            $statusAr = [
                'accepted' => 'مقبول',
                'completed' => 'مكتمل',
                'cancelled' => 'ملغي',
            ][$request->status] ?? $request->status;

            $title = "تحديث حالة الطلب";
            $message = "تم تغيير حالة طلبك رقم #{$order->id} إلى {$statusAr}";

            // حفظ في جدول الإشعارات
            Notification::create([
                'user_id' => $customer->user_id,
                'maintenance_request_id' => $order->id,
                'title' => $title,
                'message' => $message,
                'is_read' => false,
                'notifiable_type' => MaintenanceRequest::class,
                'notifiable_id' => $order->id,
                'target_role' => 'customer'
            ]);

            // إرسال Push Notification
            $fcm = new FcmService();
            if ($customer->fcm_token) {
                $fcm->sendNotification(
                    $customer->fcm_token,
                    $title,
                    $message,
                    [
                        'type' => 'status_update',
                        'target_role' => 'customer',
                        'status' => $request->status,
                        'request_id' => (string) $order->id,
                    ]
                );
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data' => $order
        ]);
    }

    /**
     * جلب خدمات مزود الخدمة
     */
    public function getServices(Request $request)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)->first();

        if (!$provider) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم العثور على حساب مزود خدمة'
            ], 404);
        }

        $services = Service::where('service_provider_id', $provider->id)
            ->with(['mainCategory', 'subCategory', 'images'])
            ->get();

        return response()->json([
            'status' => true,
            'data' => $services
        ]);
    }

    /**
     * جلب التخصصات الرئيسية مع الفئات الفرعية
     */
    public function getCategories()
    {
        $categories = MainCategory::with('subCategories')->get();
        return response()->json([
            'status' => true,
            'data' => $categories
        ]);
    }

    /**
     * جلب الفئات الفرعية لتصنيف معين
     */
    public function getSubCategories($categoryId)
    {
        $subCategories = SubCategory::where('main_category_id', $categoryId)->get();
        return response()->json([
            'status' => true,
            'data' => $subCategories
        ]);
    }

    /**
     * إضافة خدمة جديدة لمزود الخدمة
     */
    public function addService(Request $request)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)->first();

        if (!$provider) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم العثور على حساب مزود خدمة'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'sub_category_id' => 'required|exists:sub_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'price_type' => 'required|string|in:fixed,per_meter,per_point,per_unit,hour,day,range,custom',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $validator->errors()
            ], 422);
        }

        $service = Service::create([
            'service_provider_id' => $provider->id,
            'main_category_id' => $provider->main_category_id,
            'sub_category_id' => $request->sub_category_id,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'price_type' => $request->price_type,
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $isFirst = true;
            foreach ($request->file('images') as $image) {
                $path = $image->store('services/' . $service->id, 'public');
                ServiceImage::create([
                    'service_id' => $service->id,
                    'image_path' => $path,
                    'is_primary' => $isFirst,
                ]);
                $isFirst = false;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة الخدمة بنجاح',
            'data' => $service->load('images')
        ]);
    }

    /**
     * تحديث خدمة موجودة
     */
    public function updateService(Request $request, $id)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)->first();

        if (!$provider) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم العثور على حساب مزود خدمة'
            ], 404);
        }

        $service = Service::where('id', $id)
            ->where('service_provider_id', $provider->id)
            ->first();

        if (!$service) {
            return response()->json([
                'status' => false,
                'message' => 'الخدمة غير موجودة'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'price_type' => 'required|string|in:fixed,per_meter,per_point,per_unit,hour,day,range,custom',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $validator->errors()
            ], 422);
        }

        $service->update([
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'price_type' => $request->price_type,
        ]);

        // Handle image updates
        if ($request->has('current_images')) {
            $currentImagesRaw = $request->input('current_images', []);
            
            // If it's a string, try to parse it (in case it's sent as JSON)
            if (is_string($currentImagesRaw)) {
                $currentImagesRaw = json_decode($currentImagesRaw, true) ?? [$currentImagesRaw];
            }
            
            \Log::info('updateService current_images:', ['raw' => $currentImagesRaw]);
            
            $existingImages = $service->images;
            foreach ($existingImages as $img) {
                // Check against both the absolute URL and the relative path
                $isKept = false;
                foreach ($currentImagesRaw as $url) {
                    if ($img->image_url === $url || str_contains($url, $img->image_path)) {
                        $isKept = true;
                        break;
                    }
                }

                if (!$isKept) {
                    \Log::info('Deleting service image:', ['path' => $img->image_path]);
                    Storage::disk('public')->delete($img->image_path);
                    $img->delete();
                }
            }
        }

        // Upload new images
        if ($request->hasFile('images')) {
            $hasPrimary = $service->images()->where('is_primary', true)->exists();
            foreach ($request->file('images') as $image) {
                $path = $image->store('services/' . $service->id, 'public');
                ServiceImage::create([
                    'service_id' => $service->id,
                    'image_path' => $path,
                    'is_primary' => !$hasPrimary,
                ]);
                $hasPrimary = true;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الخدمة بنجاح',
            'data' => $service->load('images')
        ]);
    }

    /**
     * حذف خدمة
     */
    public function deleteService(Request $request, $id)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)->first();

        $service = Service::where('id', $id)
            ->where('service_provider_id', $provider->id)
            ->first();

        if (!$service) {
            return response()->json([
                'status' => false,
                'message' => 'الخدمة غير موجودة'
            ], 404);
        }

        // Delete associated images from storage
        foreach ($service->images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }

        $service->delete(); // cascade deletes service_images rows

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الخدمة بنجاح'
        ]);
    }

    /**
     * تغيير حالة الخدمة (نشط/متوقف)
     */
    public function toggleServiceStatus(Request $request, $id)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)->first();

        if (!$provider) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم العثور على حساب مزود خدمة'
            ], 404);
        }

        $service = Service::where('id', $id)
            ->where('service_provider_id', $provider->id)
            ->first();

        if (!$service) {
            return response()->json([
                'status' => false,
                'message' => 'الخدمة غير موجودة'
            ], 404);
        }

        $service->status = $service->status === 'active' ? 'paused' : 'active';
        $service->save();

        return response()->json([
            'status' => true,
            'message' => 'تم تغيير حالة الخدمة بنجاح',
            'data' => $service
        ]);
    }

    /**
     * إحصائيات سريعة لمزود الخدمة
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        $provider = ServiceProvider::where('user_id', $user->user_id)->first();

        if (!$provider) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم العثور على حساب مزود خدمة'
            ], 404);
        }

        $stats = [
            'total_orders' => MaintenanceRequest::where('provider_id', $provider->id)->count(),
            'pending_orders' => MaintenanceRequest::where('provider_id', $provider->id)->where('status', 'pending')->count(),
            'completed_orders' => MaintenanceRequest::where('provider_id', $provider->id)->where('status', 'completed')->count(),
            'rating' => (float) ($provider->rating_average ?? 0),
            'rating_average' => (float) ($provider->rating_average ?? 0),
            'review_count' => Review::where('rated_id', $user->user_id)->count(),
        ];

        return response()->json([
            'status' => true,
            'data' => $stats
        ]);
    }

    /**
     * جلب تقييمات مزود الخدمة
     */
    public function getReviews(Request $request)
    {
        $user = $request->user();
        
        $reviews = Review::with(['rater', 'maintenanceRequest.service'])
            ->where('rated_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $reviews->map(fn($review) => $this->formatReviewResponse($review)),
        ]);
    }

    /**
     * تنسيق بيانات التقييم للعرض في التطبيق
     */
    private function formatReviewResponse(Review $review): array
    {
        $service = $review->maintenanceRequest?->service;
        
        \Log::info('Review rater check:', [
            'review_id' => $review->id,
            'rater_id' => $review->rater_id,
            'rater_name' => $review->rater ? $review->rater->full_name : 'null rater',
            'profile_image_value' => $review->rater ? $review->rater->profile_image : 'none',
        ]);

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment ?? '',
            'clientImage' => $review->rater && $review->rater->profile_image 
                ? asset('media/' . $review->rater->profile_image) 
                : null,
            'location' => $review->rater ? $review->rater->address_description : '',
            'jobTitle' => $service?->title ?? 'عمل منجز',
            'completedDate' => $review->created_at?->format('Y-m-d'),
            'price' => $service?->price ?? 0,
            'icon' => null, 
        ];
    }
}
