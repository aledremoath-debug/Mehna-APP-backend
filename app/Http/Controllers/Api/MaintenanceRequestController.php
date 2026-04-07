<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use App\Models\ServiceProvider;
use App\Services\FcmService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MaintenanceRequestController extends Controller
{
    /**
     * جلب طلبات الصيانة الخاصة بالمستخدم
     * GET /api/maintenance-requests
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $requests = MaintenanceRequest::with(['provider.user', 'service', 'product.seller', 'product.images', 'customer'])
            ->withExists('reviews')
            ->where('customer_id', $user->user_id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $requests
        ]);
    }

    /**
     * إنشاء طلب صيانة جديد
     * POST /api/maintenance-requests
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|exists:service_providers,id',
            'service_id' => 'nullable|exists:services,id',
            'problem_description' => 'required|string',
            'attachment_images' => 'nullable|array',
            'attachment_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB limit
            'scheduled_at' => 'nullable|date',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'product_id' => 'nullable|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // التحقق لمنع مزود الخدمة من طلب خدمة من نفسه
        if ($request->provider_id) {
            $provider = ServiceProvider::find($request->provider_id);
            if ($provider && $provider->user_id == $user->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'عذراً، لا يمكنك طلب خدمة صيانة من حسابك الخاص.',
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            $user = $request->user();
            $data = $request->only([
                'provider_id', 
                'service_id', 
                'problem_description', 
                'scheduled_at',
                'address',
                'latitude',
                'longitude',
                'product_id'
            ]);
            
            $data['customer_id'] = $user->user_id;
            $data['status'] = 'pending';

            // معالجة الصور المتعددة إذا وجدت
            if ($request->hasFile('attachment_images')) {
                $imagePaths = [];
                foreach ($request->file('attachment_images') as $image) {
                    $path = $image->store('maintenance/requests', 'public');
                    $imagePaths[] = $path;
                }
                $data['attachment_images'] = $imagePaths;
            }

            // إنشاء الطلب
            $maintenanceRequest = MaintenanceRequest::create($data);

            // جلب بيانات مزود الخدمة لإرسال إشعار
            $maintenanceRequest->load(['product.seller', 'product.images']);
            $provider = ServiceProvider::with('user')->find($request->provider_id);
            
            if ($provider && $provider->user) {
                $product = $maintenanceRequest->product;
                $service = \App\Models\Service::find($request->service_id);
                $customerAddress = $maintenanceRequest->address ?? 'غير محدد';
                
                if ($maintenanceRequest->product_id) {
                    $shopName = $product && $product->seller ? $product->seller->shop_name : 'غير معروف';
                    $productName = $product ? $product->product_name : 'غير محدد';
                    $title = 'طلب استشارة جديد';
                    $message = "لديك طلب استشارة من العميل {$user->full_name} بخصوص المنتج ({$productName}) من متجر ({$shopName}).\nالعنوان: {$customerAddress}";
                    $notifType = 'consultation_request';
                } else {
                    $serviceName = $service ? $service->title : 'صيانة عامة';
                    $title = 'طلب صيانة جديد';
                    $message = "لديك طلب صيانة جديد من العميل {$user->full_name} لخدمة ({$serviceName}).\nالعنوان: {$customerAddress}";
                    $notifType = 'maintenance_request';
                }

                Notification::create([
                    'user_id' => $provider->user->user_id,
                    'maintenance_request_id' => $maintenanceRequest->id,
                    'title' => $title,
                    'message' => $message,
                    'is_read' => false,
                    'notifiable_type' => MaintenanceRequest::class,
                    'notifiable_id' => $maintenanceRequest->id,
                    'target_role' => 'provider'
                ]);
                
                $fcm = new FcmService();
                if ($provider->user->fcm_token) {
                    $productImage = null;
                    if ($product && $product->images->count() > 0) {
                        $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first();
                        $productImage = url('media/' . $primaryImage->image_path);
                    }

                    $fcm->sendNotification(
                        $provider->user->fcm_token,
                        $title,
                        $message,
                        [
                            'type' => $notifType,
                            'target_role' => 'provider',
                            'request_id' => (string) $maintenanceRequest->id,
                            'customer_address' => $customerAddress,
                            'product_image' => $productImage
                        ],
                        $productImage
                    );
                }
            }

            // إشعار العميل بتأكيد طلب الصيانة
            $customerTitle = $maintenanceRequest->product_id ? 'تم إرسال طلب الاستشارة' : 'تم إرسال طلب الصيانة';
            $customerMessage = $maintenanceRequest->product_id 
                ? 'تم إرسال طلب الاستشارة الخاص بك بنجاح وهو في انتظار موافقة مقدم الخدمة'
                : 'تم إرسال طلب الصيانة الخاص بك بنجاح وهو في انتظار موافقة مقدم الخدمة';

            Notification::create([
                'user_id' => $user->user_id,
                'maintenance_request_id' => $maintenanceRequest->id,
                'title' => $customerTitle,
                'message' => $customerMessage,
                'is_read' => false,
                'notifiable_type' => MaintenanceRequest::class,
                'notifiable_id' => $maintenanceRequest->id,
                'target_role' => 'customer'
            ]);

            if ($user->fcm_token) {
                $fcm->sendNotification(
                    $user->fcm_token,
                    $customerTitle,
                    $customerMessage,
                    [
                        'type' => 'status_update',
                        'target_role' => 'customer',
                        'status' => 'pending',
                        'request_id' => (string) $maintenanceRequest->id
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال طلب الصيانة بنجاح',
                'data' => $maintenanceRequest
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إرسال الطلب',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * جلب تفاصيل طلب صيانة محدد
     * GET /api/maintenance-requests/{id}
     */
    public function show($id)
    {
        $maintenanceRequest = MaintenanceRequest::with([
            'customer',
            'provider.user',
            'service',
            'product.seller',
            'product.images'
        ])->find($id);

        if (!$maintenanceRequest) {
            return response()->json([
                'status' => false,
                'message' => 'الطلب غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $maintenanceRequest
        ]);
    }

    /**
     * تحديث حالة طلب الصيانة (قبول / رفض / إكمال)
     * PUT /api/maintenance-requests/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $request = request();
        $validator = Validator::make($request->all(), [
            'status'        => 'required|in:accepted,cancelled,completed,in_progress',
            'cancel_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $maintenanceRequest = MaintenanceRequest::find($id);

        if (!$maintenanceRequest) {
            return response()->json(['status' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        $maintenanceRequest->status = $request->status;

        if ($request->status === 'cancelled' && $request->filled('cancel_reason')) {
            $maintenanceRequest->cancel_reason = $request->cancel_reason;
        }

        $maintenanceRequest->save();

        // إشعار العميل
        $fcm = new FcmService();
        $customer = \App\Models\User::find($maintenanceRequest->customer_id);
        if ($customer) {
            $statusMessages = [
                'accepted'    => ['title' => 'تم قبول طلبك', 'body' => 'قبل مزود الخدمة طلبك وسيتواصل معك قريباً'],
                'cancelled'   => ['title' => 'تم رفض طلبك',  'body' => $request->cancel_reason ?? 'تم رفض طلبك من قبل مزود الخدمة'],
                'in_progress' => ['title' => 'جاري العمل على طلبك', 'body' => 'بدأ مزود الخدمة العمل على طلبك'],
                'completed'   => ['title' => 'تم إنجاز طلبك', 'body' => 'تم إنجاز الخدمة بنجاح'],
            ];
            $msg = $statusMessages[$request->status] ?? null;
            if ($msg) {
                Notification::create([
                    'user_id'               => $customer->user_id,
                    'maintenance_request_id'=> $maintenanceRequest->id,
                    'title'                 => $msg['title'],
                    'message'               => $msg['body'],
                    'is_read'               => false,
                    'notifiable_type'       => MaintenanceRequest::class,
                    'notifiable_id'         => $maintenanceRequest->id,
                    'target_role'           => 'customer',
                ]);
                if ($customer->fcm_token) {
                    $fcm->sendNotification($customer->fcm_token, $msg['title'], $msg['body'], [
                        'type' => 'status_update',
                        'target_role' => 'customer',
                        'status' => $request->status,
                        'request_id' => (string) $maintenanceRequest->id
                    ]);
                }
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data'    => $maintenanceRequest,
        ]);
    }

    /**
     * تحديث ملاحظات وتكاليف الفني
     * PUT /api/maintenance-requests/{id}/workflow
     */
    public function updateWorkflow(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'provider_notes'   => 'nullable|string',
            'added_cost'       => 'nullable|numeric|min:0',
            'cost_description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $maintenanceRequest = MaintenanceRequest::find($id);
        if (!$maintenanceRequest) {
            return response()->json(['status' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        if ($request->filled('provider_notes'))   $maintenanceRequest->provider_notes   = $request->provider_notes;
        if ($request->filled('added_cost'))        $maintenanceRequest->added_cost        = $request->added_cost;
        if ($request->filled('cost_description'))  $maintenanceRequest->cost_description  = $request->cost_description;
        $maintenanceRequest->save();

        // إشعار العميل بالتكلفة المضافة
        if ($request->filled('added_cost') && $request->added_cost > 0) {
            $fcm = new FcmService();
            $customer = \App\Models\User::find($maintenanceRequest->customer_id);
            if ($customer) {
                $costMsg = 'أضاف الفني تكلفة إضافية: ' . $request->added_cost . ' ريال';
                if ($request->filled('cost_description')) {
                    $costMsg .= ' - ' . $request->cost_description;
                }
                Notification::create([
                    'user_id'               => $customer->user_id,
                    'maintenance_request_id'=> $maintenanceRequest->id,
                    'title'                 => 'تكلفة إضافية',
                    'message'               => $costMsg,
                    'is_read'               => false,
                    'notifiable_type'       => MaintenanceRequest::class,
                    'notifiable_id'         => $maintenanceRequest->id,
                    'target_role'           => 'customer',
                ]);
                if ($customer->fcm_token) {
                    $fcm->sendNotification($customer->fcm_token, 'تكلفة إضافية', $costMsg, [
                        'type' => 'cost_update', 'request_id' => $maintenanceRequest->id
                    ]);
                }
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم حفظ البيانات بنجاح',
            'data'    => $maintenanceRequest,
        ]);
    }
}
