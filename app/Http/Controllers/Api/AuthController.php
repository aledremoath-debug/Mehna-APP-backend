<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\UserPassword;
use App\Models\Seller;
use App\Models\PasswordResetCode;
use App\Models\RegistrationVerificationCode;
use App\Mail\ResetPasswordMail;
use App\Mail\VerificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * إرسال كود التحقق لإنشاء حساب جديد
     * POST /api/send-registration-code
     */
    public function sendRegistrationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة أو النطاق غير حقيقي',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // إنشاء كود التحقق
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // حفظ الكود
            RegistrationVerificationCode::updateOrCreate(
                ['email' => $request->email],
                [
                    'code' => $code,
                    'expires_at' => Carbon::now()->addHour(),
                ]
            );

            // إرسال البريد - إذا فشل سيلقي استثناء
            Mail::to($request->email)->send(new VerificationMail($code));

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني',
            ]);
        } catch (\Exception $e) {
            \Log::error("Email sending failed for registration: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => config('app.debug') ? "Error: " . $e->getMessage() : 'البريد الإلكتروني غير حقيقي أو لا يمكن الوصول إليه',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        }
    }

    /**
     * التحقق من كود التسجيل (بدون إنشاء الحساب بعد)
     * POST /api/check-registration-code
     */
    public function checkRegistrationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns|unique:users,email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $verification = RegistrationVerificationCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$verification) {
            return response()->json([
                'status' => false,
                'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'كود التحقق صحيح',
        ]);
    }

    /**
     * تسجيل مستخدم جديد (Register - Final Stage)
     * POST /api/register
     */
    public function register(Request $request)
    {
        // التحقق من صحة كل البيانات
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8',
            'code' => 'required|string|size:6',
            'location_id' => 'required|integer|exists:locations,id',
            'address_description' => 'nullable|string|max:500',
            'fcm_token' => 'nullable|string|max:500',
            'user_token' => 'nullable|string|max:500',
        ], [
            'full_name.required' => 'الاسم الكامل مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً',
            'password.required' => 'كلمة المرور مطلوبة',
            'code.required' => 'كود التحقق مطلوب',
            'location_id.required' => 'تحديد المحافظة والمديرية مطلوب',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors(),
            ], 422);
        }

        // التحقق من الكود مرة أخرى قبل إنشاء الحساب
        $verification = RegistrationVerificationCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$verification) {
            return response()->json([
                'status' => false,
                'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية الرجاء المحاولة مرة أخرى',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $userType = User::TYPE_CUSTOMER;

            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'user_type' => $userType,
                'is_active' => 1, // نشط فوراً لأنه تم التحقق منه
                'email_verified_at' => Carbon::now(),
                'location_id' => $request->location_id,
                'address_description' => $request->address_description,
                'fcm_token' => $request->fcm_token,
                'user_token' => $request->user_token,
            ]);

            // إنشاء كلمة المرور في الجدول المنفصل
            UserPassword::create([
                'user_id' => $user->user_id,
                'password_hash' => Hash::make($request->password),
            ]);

            // حذف الكود بعد الاستخدام الناجح
            $verification->delete();

            DB::commit();

            // إنشاء توكن للمستخدم ليدخل مباشرة
            $token = $user->createToken('auth_token')->plainTextToken;

            // تحميل العلاقات
            $user->load(['location', 'seller', 'provider']);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء الحساب بنجاح',
                'data' => [
                    'user' => $this->formatUserResponse($user),
                    'token' => $token,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Final Registration error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء الحساب',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * تسجيل الدخول (Login)
     * POST /api/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors(),
            ], 422);
        }

        // البحث عن المستخدم
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'البريد الإلكتروني غير مسجل',
            ], 401);
        }

        // التحقق من كلمة المرور من الجدول المنفصل
        $userPassword = UserPassword::where('user_id', $user->user_id)->first();

        if (!$userPassword || !Hash::check($request->password, $userPassword->password_hash)) {
            return response()->json([
                'status' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 401);
        }

        // التحقق من حالة الحساب
        if (!$user->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'حسابك غير مفعل. يرجى الانتظار حتى تتم الموافقة.',
            ], 403);
        }

        // تحديث fcm_token و user_token إذا أُرسلا
        $updateData = [];
        if ($request->filled('fcm_token')) {
            $updateData['fcm_token'] = $request->fcm_token;
        }
        if ($request->filled('user_token')) {
            $updateData['user_token'] = $request->user_token;
        }
        if (!empty($updateData)) {
            $user->update($updateData);
        }

        // حذف التوكنات القديمة (لتسجيل دخول واحد فقط)
        $user->tokens()->delete();

        // إنشاء token جديد
        $token = $user->createToken('auth_token')->plainTextToken;

        // تحميل العلاقات
        $user->load(['location', 'seller', 'provider']);

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => $this->formatUserResponse($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * تسجيل الخروج (Logout)
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        // حذف التوكن الحالي
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    /**
     * جلب بيانات المستخدم الحالي (Profile)
     * GET /api/user
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['location', 'seller', 'provider']);

        return response()->json([
            'status' => true,
            'data' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * تحديث بيانات المستخدم (Update Profile)
     * PUT /api/user/update
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . $user->user_id . ',user_id',
            'location_id' => 'sometimes|nullable|integer|exists:locations,id',
            'address_description' => 'sometimes|nullable|string|max:500',
            'fcm_token' => 'sometimes|nullable|string|max:500',
            'user_token' => 'sometimes|nullable|string|max:500',
        ], [
            'phone.unique' => 'رقم الهاتف مسجل لمستخدم آخر',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($request->only([
            'full_name',
            'phone',
            'location_id',
            'address_description',
            'fcm_token',
            'user_token'
        ]));

        $user->refresh();
        $user->load(['location', 'seller', 'provider']);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث البيانات بنجاح',
            'data' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * تحديث صورة الملف الشخصي
     * POST /api/user/update-image
     */
    public function updateProfileImage(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'profile_image.required' => 'الصورة مطلوبة',
            'profile_image.image' => 'يجب أن يكون الملف صورة',
            'profile_image.mimes' => 'الصيغ المسموحة: jpeg, png, jpg, webp',
            'profile_image.max' => 'حجم الصورة يجب أن لا يتجاوز 2 ميجابايت',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في التحقق من الصورة',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // حذف الصورة القديمة إذا وجدت
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // تخزين الصورة الجديدة
            $path = $request->file('profile_image')->store('profiles', 'public');

            $user->update([
                'profile_image' => $path
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الصورة الشخصية بنجاح',
                'data' => [
                    'profile_image' => asset('media/' . $path)
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء رفع الصورة',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تغيير كلمة المرور (Change Password)
     * PUT /api/user/change-password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed|different:current_password',
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة',
            'new_password.required' => 'كلمة المرور الجديدة مطلوبة',
            'new_password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'new_password.confirmed' => 'كلمة المرور الجديدة غير متطابقة',
            'new_password.different' => 'كلمة المرور الجديدة يجب أن تكون مختلفة عن الكلمة الحالية',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        \Log::info("Attempting to change password for user ID: " . $user->user_id);

        $userPassword = UserPassword::where('user_id', $user->user_id)->first();

        // التحقق من كلمة المرور الحالية
        if (!$userPassword || !Hash::check($request->current_password, $userPassword->password_hash)) {
            \Log::warning("Password change failed: Current password incorrect for user ID: " . $user->user_id);
            return response()->json([
                'status' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة',
            ], 422);
        }

        // تحديث كلمة المرور بشكل أكثر قوة
        $updated = UserPassword::updateOrCreate(
            ['user_id' => $user->user_id],
            ['password_hash' => Hash::make($request->new_password)]
        );

        if ($updated) {
            \Log::info("Password successfully updated for user ID: " . $user->user_id);
            return response()->json([
                'status' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح',
            ]);
        }

        \Log::error("Failed to update password in database for user ID: " . $user->user_id);
        return response()->json([
            'status' => false,
            'message' => 'فشل تحديث كلمة المرور في قاعدة البيانات',
        ], 500);
    }

    /**
     * تحديث FCM Token و User Token
     * PUT /api/user/update-tokens
     */
    public function updateTokens(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'nullable|string|max:500',
            'user_token' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $updateData = [];
        if ($request->has('fcm_token')) {
            $updateData['fcm_token'] = $request->fcm_token;
        }
        if ($request->has('user_token')) {
            $updateData['user_token'] = $request->user_token;
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث التوكنات بنجاح',
        ]);
    }

    /**
     * حذف الحساب (Delete Account)
     * DELETE /api/user/delete
     */
    public function deleteAccount(Request $request)
    {
        try {
            $user = $request->user();

            // Delete user password
            UserPassword::where('user_id', $user->user_id)->delete();

            // Note: Depending on your database foreign key setup (ON DELETE CASCADE),
            // you might need to manually delete related records in providers, sellers, etc.
            // Assuming ON DELETE CASCADE is set, or if we need to do it manually:
            \App\Models\ServiceProvider::where('user_id', $user->user_id)->delete();
            \App\Models\Seller::where('user_id', $user->user_id)->delete();

            // Revoke all tokens
            $user->tokens()->delete();

            // Delete the user record
            $user->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف الحساب بنجاح',
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to delete account for user ID: " . ($user->user_id ?? 'unknown') . " Error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الحساب',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * طلب استعادة كلمة المرور (Forgot Password)
     * POST /api/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'email.exists' => 'هذا البريد الإلكتروني غير مسجل لدينا',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في التحقق',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // توليد كود من 6 أرقام
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // حذف الأكواد القديمة لهذا البريد
            PasswordResetCode::where('email', $request->email)->delete();

            // حفظ الكود الجديد (صالح لمدة 15 دقيقة)
            PasswordResetCode::create([
                'email' => $request->email,
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(15),
            ]);

            // إرسال الكود عبر البريد الحقيقي
            Mail::to($request->email)->send(new ResetPasswordMail($code));

            \Log::info("Password reset code for {$request->email}: {$code}");

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إرسال الكود',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * التحقق من كود الاستعادة (Verify Reset Code)
     * POST /api/verify-reset-code
     */
    public function verifyResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'code.required' => 'كود التحقق مطلوب',
            'code.size' => 'كود التحقق يجب أن يكون 6 أرقام',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resetCode = PasswordResetCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$resetCode) {
            return response()->json([
                'status' => false,
                'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'كود التحقق صحيح',
        ]);
    }

    /**
     * تعيين كلمة مرور جديدة (Reset Password)
     * POST /api/reset-password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'كلمة المرور الجديدة مطلوبة',
            'password.min' => 'يجب أن لا تقل كلمة المرور عن 8 أحرف',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في التحقق',
                'errors' => $validator->errors(),
            ], 422);
        }

        // التحقق مرة أخيرة من الكود
        $resetCode = PasswordResetCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$resetCode) {
            return response()->json([
                'status' => false,
                'message' => 'فشلت العملية، كود التحقق منتهي الصلاحية أو غير صحيح',
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();
            $userPassword = UserPassword::where('user_id', $user->user_id)->first();

            // التحقق من أن كلمة المرور الجديدة ليست نفس القديمة
            if ($userPassword && Hash::check($request->password, $userPassword->password_hash)) {
                return response()->json([
                    'status' => false,
                    'message' => 'كلمة المرور الجديدة يجب أن تكون مختلفة عن كلمة المرور السابقة',
                ], 422);
            }

            // تحديث كلمة المرور في الجدول المنفصل
            UserPassword::updateOrCreate(
                ['user_id' => $user->user_id],
                ['password_hash' => Hash::make($request->password)]
            );

            // حذف الكود بعد الاستخدام
            $resetCode->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح، يمكنك الآن تسجيل الدخول',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث كلمة المرور',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تنسيق بيانات المستخدم للاستجابة
     */
    private function formatUserResponse(User $user): array
    {
        $isApproved = $user->approval_status == User::STATUS_APPROVED;
        $hasSeller = (bool) $user->seller;
        $hasProvider = (bool) ($user->provider ?? $user->serviceProvider);

        return [
            'user_id' => $user->user_id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'user_type' => $user->user_type,
            'user_type_label' => $user->user_type_label,
            'is_active' => $user->is_active,
            'approval_status' => $user->approval_status,
            'store_status' => $hasSeller ? $user->approval_status : null,
            'provider_status' => $hasProvider ? $user->approval_status : null,
            'rejection_reason' => $user->rejection_reason,
            'location_id' => $user->location_id,
            'address_description' => $user->address_description,
            'location' => $user->location ? $user->location->governorate . ' - ' . $user->location->district : null,
            'has_store' => ($hasSeller && $isApproved) ? true : false,
            'is_provider' => ($hasProvider && $isApproved) ? true : false,
            'fcm_token' => $user->fcm_token,
            'user_token' => $user->user_token,
            'profile_image' => $user->profile_image ? asset('media/' . $user->profile_image) : null,
            'created_at' => $user->created_at ? $user->created_at->toDateTimeString() : null,
        ];
    }

    /**
     * جلب قائمة المواقع (المحافظات)
     * GET /api/locations
     */
    public function getLocations()
    {
        // نريد عرض المحافظة - المديرية
        $locations = \App\Models\Location::select('id', 'governorate', 'district')->get();
        return response()->json([
            'status' => true,
            'data' => $locations
        ]);
    }
}
