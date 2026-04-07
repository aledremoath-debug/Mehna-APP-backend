<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Seller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\Notification;
use App\Services\FcmService;

class VendorController extends Controller
{
    // ══════════════════════════════════════════════
    //  بيانات المتجر (Store Data)
    // ══════════════════════════════════════════════

    /**
     * جلب بيانات المتجر الحالي
     * GET /api/vendor/store
     *
     * إذا لم يكن هناك سجل للمتجر، ننشئ واحداً فارغاً تلقائياً
     */
    public function getStore(Request $request)
    {
        $user = $request->user();

        // جلب سجل المتجر إذا كان موجوداً
        $seller = Seller::where('user_id', $user->user_id)->first();

        if (!$seller) {
            return response()->json([
                'status' => true,
                'data'   => null,
                'message'=> 'لم يتم إنشاء بيانات المتجر بعد'
            ]);
        }

        return response()->json([
            'status' => true,
            'data'   => $this->formatSellerResponse($seller),
        ]);
    }

    /**
     * إنشاء أو تحديث بيانات المتجر
     * POST /api/vendor/store
     */
    public function updateStore(Request $request)
    {
        if ($request->hasFile('shop_image')) {
            $file = $request->file('shop_image');
            \Log::info('File Details:', [
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
        \Log::info('UpdateStore Request All:', $request->all());
        
        $user = $request->user();
        $seller = Seller::where('user_id', $user->user_id)->first();
        $isFirstTime = !$seller || empty($seller->shop_image);

        // إذا كانت الصورة مرسلة كنص وليست ملفاً، نحذفها لتجنب فشل التحقق
        if ($request->has('shop_image') && !$request->hasFile('shop_image')) {
            $request->request->remove('shop_image');
        }

        $rules = [
            'shop_name'           => 'required|string|max:255',
            'email'               => 'required|email|max:255',
            'phone'               => 'required|string|max:20',
            'location'            => 'required|string|max:255',
            'shop_description'    => 'required|string|max:1000',
            'commercial_register' => 'nullable|string|max:255',
        ];

        // التحقق من الصورة: إجبارية فقط في المرة الأولى
        $imageRule = [
            $isFirstTime ? 'required' : 'nullable',
            'file',
            'max:2048',
            function ($attribute, $value, $fail) {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (empty($ext)) {
                        $ext = strtolower(pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));
                    }
                    \Log::info("Validation Debug - Ext: $ext, Mime: " . $value->getClientMimeType() . ", Name: " . $value->getClientOriginalName());
                    $allowed = ['jpeg', 'png', 'jpg', 'webp', 'ico', 'gif'];
                    if (!in_array($ext, $allowed)) {
                        $fail('الصيغ المسموحة هي: jpeg, png, jpg, webp, ico, gif');
                    }
                }
            }
        ];
        $rules['shop_image'] = $imageRule;
        
        $validator = Validator::make($request->all(), $rules, [
            'shop_name.required'        => 'اسم المتجر مطلوب',
            'email.required'            => 'البريد الإلكتروني للمتجر مطلوب',
            'phone.required'            => 'رقم هاتف المتجر مطلوب',
            'location.required'         => 'موقع المتجر مطلوب',
            'shop_description.required' => 'وصف المتجر مطلوب',
            'shop_image.required'       => 'صورة المتجر مطلوبة',
            'shop_image.file'           => 'الملف المرفوع يجب أن يكون صورة صحيحة',
            'shop_image.mimes'          => 'الصيغ المسموحة هي: jpeg, png, jpg, webp, ico, gif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // استخدام السجل الموجود أو إنشاء واحد جديد
            if (!$seller) {
                $seller = new Seller();
                $seller->user_id = $user->user_id;
            }
            
            $seller->shop_name = $request->shop_name;
            $seller->email = $request->email;
            $seller->phone = $request->phone;
            $seller->location = $request->location;
            $seller->shop_description = $request->shop_description;
            $seller->commercial_register = $request->commercial_register;

            // رفع صورة المتجر إذا وجدت
            if ($request->hasFile('shop_image')) {
                // حذف الصورة القديمة
                if ($seller->shop_image) {
                    Storage::disk('public')->delete($seller->shop_image);
                }
                $seller->shop_image = $request->file('shop_image')->store('sellers', 'public');
            }

            $seller->save();

            // تحديث حالة الطلب ليكون قيد الانتظار بدلاً من الموافقة الفورية
            $user->update([
                'approval_status' => User::STATUS_PENDING,
            ]);

            // إرسال إشعار للادمن
            if ($isFirstTime) {
                \App\Models\Notification::create([
                    'title'   => 'طلب انضمام جديد - تاجر',
                    'message' => 'قام ' . $user->full_name . ' بتقديم طلب انضمام كتاجر.',
                    'is_read' => false,
                    'user_id' => null, // للادمن
                ]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم حفظ بيانات المتجر بنجاح',
                'data'    => $this->formatSellerResponse($seller),
            ]);
        } catch (\Exception $e) {
            \Log::error('Seller save error: ' . $e->getMessage(), [
                'user_id' => $user->user_id,
                'request' => $request->except('shop_image'),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء حفظ البيانات',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ══════════════════════════════════════════════
    //  المنتجات (Products)
    // ══════════════════════════════════════════════

    /**
     * جلب منتجات التاجر
     * GET /api/vendor/products
     */
    public function getProducts(Request $request)
    {
        $user = $request->user();
        $seller = Seller::where('user_id', $user->user_id)->first();

        if (!$seller) {
            return response()->json([
                'status' => true,
                'data'   => [],
            ]);
        }

        $products = Product::where('seller_id', $seller->id)
            ->with('images')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $products->map(fn($p) => $this->formatProductResponse($p)),
        ]);
    }

    /**
     * إضافة منتج جديد
     * POST /api/vendor/products
     */
    public function addProduct(Request $request)
    {
        $user = $request->user();
        $seller = Seller::where('user_id', $user->user_id)->first();

        if (!$seller) {
            return response()->json([
                'status'  => false,
                'message' => 'يجب إنشاء بيانات المتجر أولاً',
            ], 400);
        }


        $validator = Validator::make($request->all(), [
            'product_name'   => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'product_category_id' => 'required|exists:product_categories,id',
            'additional_specs' => 'nullable|string',
            'images'         => 'nullable|array',
            'images.*'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'product_name.required'   => 'اسم المنتج مطلوب',
            'price.required'          => 'السعر مطلوب',
            'stock_quantity.required' => 'الكمية مطلوبة',
            'images.*.image'          => 'يجب أن يكون الملف صورة',
            'images.*.mimes'          => 'الصيغ المسموحة: jpeg, png, jpg, webp',
            'images.*.max'            => 'حجم الصورة يجب أن لا يتجاوز 5 ميجابايت',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'خطأ في البيانات',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $product = Product::create([
            'seller_id'      => $seller->id,
            'product_category_id' => $request->product_category_id,
            'product_name'   => $request->product_name,
            'description'    => $request->description,
            'price'          => $request->price,
            'stock_quantity' => $request->stock_quantity,
            'additional_specs' => $request->additional_specs,
        ]);

        // رفع صور المنتج
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        $product->load('images');

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة المنتج بنجاح',
            'data'    => $this->formatProductResponse($product),
        ], 201);
    }

    /**
     * تحديث منتج
     * POST /api/vendor/products/{id}
     */
    public function updateProduct(Request $request, $id)
    {
        $user = $request->user();
        $seller = Seller::where('user_id', $user->user_id)->first();

        if (!$seller) {
            return response()->json(['status' => false, 'message' => 'متجر غير موجود'], 404);
        }

        $product = Product::where('id', $id)->where('seller_id', $seller->id)->first();

        if (!$product) {
            return response()->json(['status' => false, 'message' => 'المنتج غير موجود'], 404);
        }

        \Log::info('UpdateProduct Request Data:', $request->except(['images']));

        $validator = Validator::make($request->all(), [
            'product_name'   => 'sometimes|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'sometimes|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'additional_specs' => 'nullable|string',
            'images'         => 'nullable|array',
            'images.*'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'images.*.image' => 'يجب أن يكون الملف صورة',
            'images.*.max'   => 'حجم الصورة يجب أن لا يتجاوز 5 ميجابايت',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $product->update($request->only(['product_name', 'description', 'price', 'stock_quantity', 'additional_specs', 'product_category_id']));

        // رفع صور جديدة (بدون حذف القديمة لضمان بقائها)
        if ($request->hasFile('images')) {
            // إضافة الصور الجديدة فقط
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => false, // لا نغير الصورة الرئيسية أثناء التحديث البسيط إلا بطلب
                ]);
            }
        }

        $product->load('images');

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث المنتج بنجاح',
            'data'    => $this->formatProductResponse($product),
        ]);
    }

    /**
     * حذف منتج
     * DELETE /api/vendor/products/{id}
     */
    public function deleteProduct(Request $request, $id)
    {
        $user = $request->user();
        $seller = Seller::where('user_id', $user->user_id)->first();

        if (!$seller) {
            return response()->json(['status' => false, 'message' => 'متجر غير موجود'], 404);
        }

        $product = Product::where('id', $id)->where('seller_id', $seller->id)->first();

        if (!$product) {
            return response()->json(['status' => false, 'message' => 'المنتج غير موجود'], 404);
        }

        // حذف صور المنتج من التخزين
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->images()->delete();
        $product->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف المنتج بنجاح',
        ]);
    }

    // ══════════════════════════════════════════════
    //  طلبات التاجر (Vendor Orders)
    // ══════════════════════════════════════════════

    /**
     * جلب طلبات التاجر
     * GET /api/vendor/orders
     */
    public function getOrders(Request $request)
    {
        $user = $request->user();
        $seller = Seller::where('user_id', $user->user_id)->first();

        if (!$seller) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $query = Order::where('seller_id', $seller->id)
            ->with(['user', 'orderDetails.product.images'])
            ->latest();

        // تصفية حسب الحالة
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->with('reviews')->get();

        return response()->json([
            'status' => true,
            'data'   => $orders->map(fn($o) => $this->formatOrderResponse($o)),
        ]);
    }

    /**
     * تحديث حالة الطلب
     * PUT /api/vendor/orders/{id}/status
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $user = $request->user();
        $seller = Seller::where('user_id', $user->user_id)->first();

        $order = Order::where('id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,completed,cancelled',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $oldStatus = $order->status;
        $order->status = $request->status;
        if ($request->status === 'cancelled') {
            $order->cancel_reason = $request->input('reason');
            
            // إعادة الكميات للمخزون إذا تم الإلغاء ولم يكن ملغياً من قبل
            if ($oldStatus !== 'cancelled') {
                foreach ($order->orderDetails as $detail) {
                    if ($detail->product) {
                        $detail->product->increment('stock_quantity', $detail->quantity);
                    }
                }
            }
        }
        $order->save();

        // جلب بيانات العميل لإرسال إشعار
        $customer = $order->user;
        if ($customer) {
            $statusAr = [
                'pending' => 'قيد الانتظار',
                'processing' => 'قيد التنفيذ',
                'completed' => 'مكتمل',
                'cancelled' => 'ملغي',
            ][$request->status] ?? $request->status;

            $title = "تحديث حالة طلب المنتج";
            $message = "تم تغيير حالة طلبك رقم #{$order->id} إلى {$statusAr}";

            // حفظ في جدول الإشعارات
            Notification::create([
                'user_id' => $customer->user_id,
                'order_id' => $order->id,
                'title' => $title,
                'message' => $message,
                'is_read' => false,
                'notifiable_type' => Order::class,
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
                        'type' => 'product_order_status',
                        'target_role' => 'customer',
                        'order_id' => (string) $order->id,
                        'status' => $request->status
                    ]
                );
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث حالة الطلب',
            'data'    => $this->formatOrderResponse($order),
        ]);
    }

    /**
     * جلب تقييمات التاجر
     * GET /api/vendor/reviews
     */
    public function getReviews(Request $request)
    {
        $user = $request->user();
        
        // جلب التقييمات حيث يكون rated_id هو معرف المستخدم التاجر
        $reviews = Review::with(['rater'])
            ->where('rated_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $reviews->map(fn($review) => $this->formatReviewResponse($review)),
        ]);
    }

    // ══════════════════════════════════════════════
    //  إحصائيات لوحة التحكم (Dashboard Stats)
    // ══════════════════════════════════════════════

    /**
     * جلب إحصائيات التاجر
     * GET /api/vendor/stats
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        $seller = Seller::where('user_id', $user->user_id)->first();

        if (!$seller) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'product_count'    => 0,
                    'new_orders'       => 0,
                    'completed_orders' => 0,
                    'total_sales'      => 0,
                ],
            ]);
        }

        $productCount = Product::where('seller_id', $seller->id)->count();
        $newOrders = Order::where('seller_id', $seller->id)->where('status', 'pending')->count();
        $completedOrders = Order::where('seller_id', $seller->id)->where('status', 'completed')->count();

        // حساب إجمالي المبيعات (المجموع المالي للطلبات المكتملة)
        $totalSales = Order::where('seller_id', $seller->id)
            ->where('status', 'completed')
            ->sum('total_price');

        // مبيعات اليوم
        $todaySales = Order::where('seller_id', $seller->id)
            ->where('status', 'completed')
            ->whereDate('created_at', now()->today())
            ->sum('total_price');

        // مبيعات الشهر الحالي
        $monthSales = Order::where('seller_id', $seller->id)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_price');

        return response()->json([
            'status' => true,
            'data'   => [
                'product_count'    => $productCount,
                'new_orders'       => $newOrders,
                'completed_orders' => $completedOrders,
                'total_sales'      => number_format($totalSales, 2, '.', ''),
                'today_sales'      => number_format($todaySales, 2, '.', ''),
                'month_sales'      => number_format($monthSales, 2, '.', ''),
                'rating_average'   => $seller->rating_average,
                'rating_count'     => $seller->rating_count,
            ],
        ]);
    }

    // ══════════════════════════════════════════════
    //  دوال التنسيق (Format Helpers)
    // ══════════════════════════════════════════════

    private function formatSellerResponse(Seller $seller): array
    {
        return [
            'id'                  => $seller->id,
            'user_id'             => $seller->user_id,
            'shop_name'           => $seller->shop_name,
            'email'               => $seller->email,
            'phone'               => $seller->phone,
            'location'            => $seller->location,
            'shop_description'    => $seller->shop_description,
            'commercial_register' => $seller->commercial_register,
            'shop_image'          => $seller->shop_image ? asset('media/' . $seller->shop_image) : null,
            'rating_average'      => $seller->rating_average,
            'rating_count'        => $seller->rating_count,
        ];
    }

    private function formatProductResponse(Product $product): array
    {
        return [
            'id'             => $product->id,
            'product_name'   => $product->product_name,
            'description'    => $product->description,
            'additional_specs' => $product->additional_specs,
            'price'          => number_format($product->price, 2, '.', ''),
            'stock_quantity' => $product->stock_quantity,
            'product_category_id' => $product->product_category_id,
            'main_category_id' => $product->main_category_id,
            'image'          => $product->images->where('is_primary', true)->first() 
                ? asset('media/' . $product->images->where('is_primary', true)->first()->image_path)
                : ($product->images->first() ? asset('media/' . $product->images->first()->image_path) : null),
            'images'         => $product->images->map(fn($img) => [
                'id'         => $img->id,
                'url'        => asset('media/' . $img->image_path),
                'is_primary' => $img->is_primary,
            ])->toArray(),
            'created_at'     => $product->created_at?->toDateTimeString(),
        ];
    }

    protected function formatOrderResponse(Order $order): array
    {
        return [
            'id'            => $order->id,
            'customer_name' => $order->user?->full_name ?? 'عميل',
            'status'        => $order->status,
            'total_price'   => number_format($order->total_price, 2, '.', ''),
            'description'   => $order->description ?? '',
            'location'      => $order->location,
            'date'          => $order->created_at?->toDateTimeString(),
            'pending_at'    => $order->pending_at?->toDateTimeString(),
            'processing_at' => $order->processing_at?->toDateTimeString(),
            'completed_at'  => $order->completed_at?->toDateTimeString(),
            'cancelled_at'  => $order->cancelled_at?->toDateTimeString(),
            'cancel_reason' => $order->cancel_reason,
            'is_reviewed'   => $order->reviews()->where('rater_id', $order->user_id)->exists(),
            'items'         => $order->orderDetails->map(function ($detail) {
                $product = $detail->product;
                $imageUrl = null;
                if ($product && $product->images->isNotEmpty()) {
                    $imageUrl = asset('media/' . $product->images->first()->image_path);
                }
                return [
                    'product_name' => $product ? $product->product_name : 'منتج محذوف',
                    'quantity'     => $detail->quantity,
                    'unit_price'   => number_format($detail->unit_price, 2, '.', ''),
                    'image'        => $imageUrl,
                ];
            }),
        ];
    }

    private function formatReviewResponse(Review $review): array
    {
        return [
            'id'         => $review->id,
            'rating'     => $review->rating,
            'comment'    => $review->comment,
            'client_name'=> $review->rater ? $review->rater->full_name : 'عميل مجهول',
            'client_image' => $review->rater && $review->rater->profile_image 
                ? asset('media/' . $review->rater->profile_image) 
                : null,
            'date'       => $review->created_at?->toDateTimeString(),
        ];
    }
}
