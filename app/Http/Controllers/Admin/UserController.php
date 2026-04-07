<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceProvider;
use App\Models\Seller as Vendor; // Alias Seller as Vendor to match previous code usage
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\UserPassword;

class UserController extends Controller
{
    /**
     * عرض قائمة المستخدمين شاملة البحث والتصفية.
     */
    public function index(Request $request)
    {
        // تهيئة استعلام الموديل لجلب بيانات المستخدمين
        $query = User::query();

        // 1. تصفية نتائج البحث حسب نوع المستخدم (عميل، تاجر، مهني، إلخ) إذا تم تحديده
        if ($request->filled('type')) {
            $query->where('user_type', $request->type);
        }

        // 2. ميزة البحث النصي (مربع البحث): للبحث بالاسم، البريد الإلكتروني، أو رقم الهاتف
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        // 3. جلب المستخدمين مع بيانات الموقع والتاجر المرتبطة بهم، 
        // وترتيبهم من الأحدث للأقدم مع تقسيم النتائج (15 لكل صفحة) Pagination
        $users = $query->with(['location', 'seller'])->orderBy('user_id', 'desc')->paginate(15);

        // 4. إذا كان الطلب من نوع AJAX (مثل التمرير المستمر أو التحديث التلقائي للصفحة)،
        // نعيد فقط جزء HTML الجاهز بدلاً من الصفحة كاملة (تحسين للأداء)
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.users.partials.user_rows', compact('users'))->render(),
                'hasMore' => $users->hasMorePages()
            ]);
        }

        // العرض الافتراضي لصفحة قائمة المستخدمين مع تمرير المتغير users
        return view('admin.users.index', compact('users'));
    }

    /**
     * عرض الصفحة الخاصة بنموذج إضافة مستخدم جديد من لوحة التحكم.
     */
    public function create()
    {
        // جلب جميع الأقسام الرئيسية والمواقع لعرضها في القوائم المنسدلة (Dropdown lists)
        $mainCategories = \App\Models\MainCategory::all();
        $locations = \App\Models\Location::all();
        return view('admin.users.create', compact('mainCategories', 'locations'));
    }

    /**
     * استقبال وحفظ بيانات المستخدم الجديد في قاعدة البيانات.
     */
    public function store(Request $request)
    {
        // 1. التحقق من صحة المدخلات الأساسية (Validation) والحقول المطلوبة
        $request->validate([
            'full_name' => 'required|string|max:255', 
            'email' => 'required|string|email|max:255|unique:users', 
            'phone' => 'required|string|max:20|unique:users', 
            'password' => 'required|string|min:8', 
            'user_type' => 'required|in:admin,customer,provider,vendor',
            
            'location_id' => 'required_unless:user_type,admin|nullable|exists:locations,id',
            'address_description' => 'required_unless:user_type,admin|nullable|string',

            'main_category_id' => 'required_if:user_type,provider|nullable|exists:main_categories,id',
            'experience_years' => 'nullable|integer|min:0',
            
            'shop_name' => 'required_if:user_type,vendor|nullable|string|max:255',
            'shop_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'full_name.required' => 'حقل الاسم الكامل مطلوب.',
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح.',
            'email.unique' => 'البريد الإلكتروني هذا مستخدم مسبقاً.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.unique' => 'رقم الهاتف هذا مسجل مسبقاً لمستخدم آخر.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'يجب أن لا تقل كلمة المرور عن 8 أحرف.',
            'user_type.required' => 'نوع المستخدم مطلوب.',
            'location_id.required_unless' => 'يرجى اختيار الموقع.',
            'address_description.required_unless' => 'يرجى إدخال وصف العنوان.',
            'main_category_id.required_if' => 'يرجى اختيار القسم الرئيسي لمقدم الخدمة.',
            'shop_name.required_if' => 'يرجى إدخال اسم المتجر.',
            'shop_image.image' => 'يجب أن يكون الملف المرفوع صورة.',
        ]);

        // تعيين قيم رقمية لنوع المستخدم بناءً على الحالات المعرفة في كلاس User
        $typeMap = [
            'admin' => User::TYPE_ADMIN,
            'customer' => User::TYPE_CUSTOMER,
            'provider' => User::TYPE_PROVIDER,
            'vendor' => User::TYPE_SELLER,
        ];
        
        $userType = $typeMap[$request->user_type] ?? User::TYPE_CUSTOMER;

        // 2. بدء معاملة قاعدة البيانات (Transaction) لضمان حفظ كل البيانات مرة واحدة
        // (إذا فشل استعلام واحد، يتم إلغاء كل العمليات السابقة لضمان صحة البيانات)
        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // التأكد من توفر موقع افتراضي. إذا لم يجد، سنقوم بإنشاء موقع افتراضي.
            $defaultLocation = \App\Models\Location::first();
            if (!$defaultLocation) {
                 $defaultLocation = \App\Models\Location::create([
                     'governorate' => 'صنعاء',
                     'district' => 'الامانة'
                 ]);
            }

            // تحديد الموقع: إذا لم يتم إرساله (حالة المدير)، نستخدم الموقع الافتراضي
            $locationId = $request->location_id ?: $defaultLocation->id;

            // 3. إنشاء سجل المستخدم الأساسي في جدول users
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'user_type' => $userType,
                'location_id' => $locationId,
                'address_description' => $request->address_description ?? '',
                'approval_status' => User::STATUS_APPROVED, // كونه من إضافة الأدمن يُعتبر مقبول تلقائياً
                'is_active' => true,
            ]);

            // 4. إنشاء وتشفير كلمة المرور وتشفيرها داخل جدول كلمات المرور المنفصل (user_passwords) 
            UserPassword::create([
                'user_id' => $user->user_id,
                'password_hash' => Hash::make($request->password), // استخدام نظام الـ Hash لتشفير الرقم السري
            ]);

            // 5. بناءً على نوع المستخدم، نقوم بإضافة بيانات خاصة في جداول أخرى (Role Specific Data)
            if ($userType === User::TYPE_PROVIDER) {
                // إضافة بيانات مقدم الخدمة في جدول service_providers
                ServiceProvider::create([
                    'user_id' => $user->user_id,
                    'main_category_id' => $request->main_category_id,
                    'bio' => $request->bio,
                    'experience_years' => $request->experience_years ?? 0,
                    'is_available' => true,
                    'rating_average' => 0,
                ]);
            } elseif ($userType === User::TYPE_SELLER) {
                // إذا تم رفع صورة متجر للتاجر، سيتم حفظها بمجلد shops في الـ public path
                $shopImagePath = null;
                if ($request->hasFile('shop_image')) {
                    $shopImagePath = $request->file('shop_image')->store('shops', 'public');
                }

                // إضافة بيانات التاجر في جدول sellers
                Vendor::create([
                    'user_id' => $user->user_id,
                    'shop_name' => $request->shop_name ?? ($request->full_name . ' Shop'),
                    'shop_description' => $request->shop_description,
                    'commercial_register' => $request->commercial_register,
                    'shop_image' => $shopImagePath
                ]);
            }

            // تأکید وحفظ (Commit) جميع العمليات السابقة بقاعدة البيانات لتصبح دائمة
            \Illuminate\Support\Facades\DB::commit();

            // العودة لقائمة المستخدمين مع إعلام النجاح
            return redirect()->route('admin.users.index')->with('success', 'تم إنشاء المستخدم بنجاح مع البيانات المرتبطة.');

        } catch (\Exception $e) {
            // تراجع كامل للمعاملات (Rollback) في حالة حدوث أخطاء مثل تعطل السيرفر أو خطأ في البيانات
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء إنشاء المستخدم: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * عرض الصفحة الخاصة بتعديل بيانات مستخدم موجود مسبقاً.
     */
    public function edit($id)
    {
        // الحصول على المستخدم المطلوب بناءً على الـ ID والتأكد من إرفاق بيانات الموقع، وبياناته كتاجر/مهني.
        $user = User::with(['location', 'serviceProvider', 'seller'])->findOrFail($id);
        
        $mainCategories = \App\Models\MainCategory::all(); // إحضار الأقسام لنموذج التحويل أو التعديل
        $locations = \App\Models\Location::all();
        
        return view('admin.users.edit', compact('user', 'mainCategories', 'locations'));
    }

    /**
     * استقبال الحقول المحدثّة من نموذج التعديل وحفظها في قاعدة البيانات.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id); // البحث عن المستخدم او اعادة 404 خطا 

        // 1. التحقق من المدخلات الأساسية
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id . ',user_id',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $id . ',user_id',
            'user_type' => 'required|in:admin,customer,provider,vendor',
        ], [
            'full_name.required' => 'حقل الاسم الكامل مطلوب.',
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح.',
            'email.unique' => 'البريد الإلكتروني هذا مستخدم مسبقاً لدى مستخدم آخر.',
            'phone.unique' => 'رقم الهاتف هذا مسجل مسبقاً لمستخدم آخر.',
            'user_type.required' => 'نوع المستخدم مطلوب.',
        ]);

        $typeMap = [
            'admin' => User::TYPE_ADMIN,
            'customer' => User::TYPE_CUSTOMER,
            'provider' => User::TYPE_PROVIDER,
            'vendor' => User::TYPE_SELLER,
            'seller' => User::TYPE_SELLER,
        ];
        
        $userType = $typeMap[$request->user_type] ?? $user->user_type;

        // 2. تحديث الحقول الرئيسية للمستخدم
        $user->update([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'user_type' => $userType,
            'is_active' => $request->has('is_active') ? 1 : 0, // تفعيل أو إيقاف الحساب
        ]);

        // 3. تحديث كلمة المرور (فقط إذا قام المسؤول بكتابة كلمة مرور جديدة)
        if ($request->filled('password')) {
            UserPassword::updateOrCreate(
                ['user_id' => $user->user_id],
                ['password_hash' => Hash::make($request->password)]
            );
        }

        // 4. تحديث البيانات الاضافية المرتبطة بدور المستخدم (مقدم خدمة أو تاجر)
        if ($userType === User::TYPE_PROVIDER) {
            // تحديث أو إنشاء سجل في (service_providers)
            ServiceProvider::updateOrCreate(
                ['user_id' => $user->user_id],
                [
                    'main_category_id' => $request->main_category_id,
                    'bio' => $request->bio,
                    'experience_years' => $request->experience_years ?? 0,
                ]
            );
        } elseif ($userType === User::TYPE_SELLER) {
            $data = [
                'shop_name' => $request->shop_name ?? ($request->full_name . ' Shop'),
                'shop_description' => $request->shop_description,
                'commercial_register' => $request->commercial_register,
            ];

            // لو رفع المشرف صورة جديدة لمتجر هذا المستخدم، احفظها و اضف المسار للملف
            if ($request->hasFile('shop_image')) {
                $data['shop_image'] = $request->file('shop_image')->store('shops', 'public');
            }

            // تحديث سجل التاجر (sellers)
            Vendor::updateOrCreate(
                ['user_id' => $user->user_id],
                $data
            );
        }

        return redirect()->route('admin.users.index')->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    /**
     * عرض صفحة الملف الشخصي / التفاصيل الشاملة للمستخدم.
     */
    public function show($id)
    {
        // جلب المستخدم مع كل علاقاته المعقدة (الموقع، بياناته كمزود، أقسامه، منتجاته كتاجر، وتقييماته)
        // يتم استخدام with() لمنع مشكلة الاستعلام المتكرر للقاعدة (N+1 Query Problem)
        $user = User::with([
            'location', 
            'serviceProvider.mainCategory', 
            'serviceProvider.services.subCategory',
            'seller.products', 
            'reviewsReceived.rater'
        ])->findOrFail($id);

        // تحميل طلبات الصيانة بشكل ديناميكي بناءً على دور المستخدم
        if ($user->user_type == 1) { 
            // 1 = مقدم خدمة (Provider): جلب الطلبات الواردة إليه مع بيانات العميل والخدمة
            $user->load(['serviceProvider.maintenanceRequests.service.subCategory', 'serviceProvider.maintenanceRequests.customer']);
        } elseif ($user->user_type == 0) { 
            // 0 = عميل (Customer): جلب طلبات الخدمات التي طلبها هو، مع بيانات الخدمة ومقدمها
            $user->load(['maintenanceRequests.service.subCategory', 'maintenanceRequests.provider']);
        }
            
        return view('admin.users.show', compact('user'));
    }

    /**
     * التبديل السريع (Toggle) لحالة تنشيط حساب المستخدم 
     * (تُستخدم عادة من زر أو سويتش في واجهة الموقع باستخدام AJAX).
     */
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->is_active = $request->status; // إما 1 (نشط) أو 0 (موقوف)
        $user->save();

        // الرد بصيغة JSON لأن الاستدعاء يتم بدون تحديث كامل للصفحة (AJAX)
        return response()->json([
            'success' => true, 
            'message' => 'تم تحديث حالة الحساب بنجاح.',
            'status' => $user->is_active
        ]);
    }

    /**
     * حذف مستخدم معين من النظام بالكامل.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        // ملاحظة: بفضل إعدادات (Cascade) في جداول الترحيل بقاعدة البيانات (Migrations)، 
        // سيتم تلقائياً حذف بيانات التاجر أو مقدم الخدمة التابعة لهذا الـ User

        return redirect()->route('admin.users.index')->with('success', 'تم حذف المستخدم بنجاح.');
    }
}
