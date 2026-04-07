<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\ChatBotControllerWithGemni2;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\MaintenanceRequestController;
use App\Http\Controllers\OrderController;


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');

/* |-------------------------------------------------------------------------- | طرق المصادقة للمستخدمين (User Auth Routes) |-------------------------------------------------------------------------- */
Route::post('/send-registration-code', [AuthController::class , 'sendRegistrationCode']);
Route::post('/check-registration-code', [AuthController::class , 'checkRegistrationCode']);
Route::post('/register', [AuthController::class , 'register']);
Route::post('/login', [AuthController::class , 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// عام – تحقق من إصدار التطبيق (بدون مصادقة)
Route::get('/app-version', [\App\Http\Controllers\Admin\AppSettingController::class , 'publicCheck']);

// طرق محمية بالتوكن (Protected Routes)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class , 'logout']);
    Route::get('/user', [AuthController::class , 'user']);
    Route::put('/user/update', [AuthController::class , 'updateProfile']);
    Route::post('/user/update-image', [AuthController::class , 'updateProfileImage']);
    Route::put('/user/change-password', [AuthController::class , 'changePassword']);
    Route::put('/user/update-tokens', [AuthController::class , 'updateTokens']);
    Route::delete('/user/delete', [AuthController::class , 'deleteAccount']);

    // طلبات العميل
    Route::get('/my-orders', [OrderController::class , 'myOrders']);
    Route::post('/orders/{id}/cancel', [OrderController::class , 'cancelOrder']);

    /*
     |----------------------------------------------------------------------
     | طرق التاجر (Vendor Routes)
     |----------------------------------------------------------------------
     */
    Route::prefix('vendor')->group(function () {
            // بيانات المتجر
            Route::get('/store', [VendorController::class , 'getStore']);
            Route::post('/store', [VendorController::class , 'updateStore']);

            // المنتجات
            Route::get('/products', [VendorController::class , 'getProducts']);
            Route::post('/products', [VendorController::class , 'addProduct']);
            Route::post('/products/{id}', [VendorController::class , 'updateProduct']);
            Route::delete('/products/{id}', [VendorController::class , 'deleteProduct']);

            // الطلبات
            Route::get('/orders', [VendorController::class , 'getOrders']);
            Route::put('/orders/{id}/status', [VendorController::class , 'updateOrderStatus']);
            Route::get('/reviews', [VendorController::class , 'getReviews']);

            // الإحصائيات
            Route::get('/stats', [VendorController::class , 'getStats']);
        }
        );

        /*
     |----------------------------------------------------------------------
     | طرق مزود الخدمة (Provider Routes)
     |----------------------------------------------------------------------
     */
        Route::prefix('provider')->group(function () {
            Route::get('/profile', [ProviderController::class , 'getProfile']);
            Route::post('/profile', [ProviderController::class , 'updateProfile']);
            Route::get('/orders', [ProviderController::class , 'getOrders']);
            Route::put('/orders/{id}/status', [ProviderController::class , 'updateOrderStatus']);
            Route::get('/stats', [ProviderController::class , 'getStats']);
            Route::get('/reviews', [ProviderController::class , 'getReviews']);
            Route::get('/categories', [ProviderController::class , 'getCategories']);
            Route::get('/categories/{id}/subcategories', [ProviderController::class , 'getSubCategories']);

            // إدارة الخدمات
            Route::get('/services', [ProviderController::class , 'getServices']);
            Route::post('/services', [ProviderController::class , 'addService']);
            Route::put('services/{id}', [ProviderController::class , 'updateService']);
            Route::post('services/{id}', [ProviderController::class , 'updateService']); // Added for multipart POST compatibility
            Route::put('services/{id}/status', [ProviderController::class , 'toggleServiceStatus']);
            Route::delete('services/{id}', [ProviderController::class , 'deleteService']);
        }
        );

        // طلبات الخدمة (Service Requests)
        Route::get('/maintenance-requests', [MaintenanceRequestController::class , 'index']);
        Route::get('/maintenance-requests/{id}', [MaintenanceRequestController::class , 'show']);
        Route::post('/maintenance-requests', [MaintenanceRequestController::class , 'store']);
        Route::put('/maintenance-requests/{id}/status', [MaintenanceRequestController::class , 'updateStatus']);
        Route::put('/maintenance-requests/{id}/workflow', [MaintenanceRequestController::class , 'updateWorkflow']);

        // التقييمات
        Route::post('/reviews', [\App\Http\Controllers\ReviewController::class , 'store']);

        // الإشعارات (Notifications)
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class , 'index']);
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class , 'markAllAsRead']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class , 'markAsRead']);    });

/* |-------------------------------------------------------------------------- | طرق الإدارة (Admin Routes) |-------------------------------------------------------------------------- */
Route::post('/admin/login', [AdminAuthController::class , 'login']);
Route::middleware(['auth:sanctum', 'is_admin'])->prefix('admin')->group(function () {
    Route::post('/logout', [AdminAuthController::class , 'logout']);

    // Join Requests API
    Route::get('/join-requests', [\App\Http\Controllers\Admin\OrderController::class , 'joinRequests']);
    Route::post('/join-requests/approve', [\App\Http\Controllers\Admin\OrderController::class , 'approve']);
    Route::post('/join-requests/reject', [\App\Http\Controllers\Admin\OrderController::class , 'reject']);
});

/* |-------------------------------------------------------------------------- | طرق عامة أخرى (Public Routes) |-------------------------------------------------------------------------- */
// Route::get('/mehna-bot', [ChatBotController::class , 'chat']);
// Route::get('/chat', [ChatBotControllerWithGemni2::class , 'chat']);

Route::post('/chat', [ChatBotControllerWithGemni2::class , 'chat']);
Route::get('/products', [\App\Http\Controllers\Api\ProductController::class , 'index']);
Route::post('/orders', [\App\Http\Controllers\OrderController::class , 'store']);
Route::post('/complaints', [\App\Http\Controllers\Admin\ComplaintController::class , 'store']);
Route::get('/my-complaints', [\App\Http\Controllers\Admin\ComplaintController::class , 'getUserComplaints'])->middleware('auth:sanctum');
Route::get('/app-settings', [\App\Http\Controllers\Admin\AppSettingController::class , 'index']);
Route::get('/product-categories', function () {
    $categories = \App\Models\ProductCategory::orderBy('name')->get(['id', 'name', 'icon']);
    return response()->json(['status' => true, 'data' => $categories]);
});
Route::get('/locations', [\App\Http\Controllers\Api\AuthController::class , 'getLocations']);

// طرق الخدمات والتصنيفات (Services & Categories)
Route::get('/categories', [ServiceController::class , 'categories']);
Route::get('/categories/{id}/sub-categories', [ServiceController::class , 'subCategories']);
Route::get('/services', [ServiceController::class , 'index']);
Route::get('/providers', [ServiceController::class , 'providers']);
Route::get('/sellers', [ServiceController::class , 'sellers']);

// مسار لاختبار الإشعارات (للاختبار فقط)
Route::get('/test-fcm/{user_id}', function ($user_id) {
    $user = \App\Models\User::where('user_id', $user_id)->first();
    if (!$user || !$user->fcm_token)
        return response()->json(['error' => 'User not found or has no FCM token'], 404);

    $fcm = new \App\Services\FcmService();
    $success = $fcm->sendNotification($user->fcm_token, 'اختبار الإشعارات', 'هذا تنبيه تجريبي من نظام مهنة!', ['test' => true]);

    return response()->json([
    'status' => $success,
    'message' => $success ? 'تم إرسال الإشعار بنجاح' : 'فشل إرسال الإشعار، تأكد من مفتاح الـ API',
    'token' => $user->fcm_token
    ]);
});
