<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MainCategory;
use App\Models\SubCategory;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\Seller;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * جلب جميع التصنيفات الرئيسية (سباكة، كهرباء، إلخ)
     */
    public function categories()
    {
        try {
            $categories = MainCategory::all();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب التصنيفات بنجاح',
                'data'    => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب التصنيفات',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب الفئات الفرعية لتصنيف رئيسي معين
     */
    public function subCategories($mainCategoryId)
    {
        try {
            $subCategories = SubCategory::where('main_category_id', $mainCategoryId)
                ->withCount(['services' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الفئات الفرعية بنجاح',
                'data'    => $subCategories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب الفئات الفرعية',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب الخدمات مع إمكانية التصفية حسب التصنيف
     */
    public function index(Request $request)
    {
        try {
            $query = Service::with(['mainCategory', 'subCategory', 'provider.user', 'images', 'provider.services.images']);

            // تصفية حسب التصنيف الرئيسي
            if ($request->has('main_category_id') && !empty($request->main_category_id)) {
                $query->where('main_category_id', $request->main_category_id);
            }

            // البحث بكلمة مفتاحية
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // تصفية حسب السعر
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            $services = $query->get()->unique('title')->values();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الخدمات بنجاح',
                'data'    => $services
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب الخدمات',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب مقدمي الخدمة مع البحث والتصفية
     */
    public function providers(Request $request)
    {
        try {
            $query = ServiceProvider::with(['user', 'mainCategory', 'services.images', 'reviews.rater']);

            if ($request->has('main_category_id') && !empty($request->main_category_id)) {
                $query->where('main_category_id', $request->main_category_id);
            }

            // تصفية حسب الفئة الفرعية
            if ($request->has('sub_category_id') && !empty($request->sub_category_id)) {
                $query->whereHas('services', function($q) use ($request) {
                    $q->where('sub_category_id', $request->sub_category_id);
                });
            }

            // البحث بكلمة مفتاحية (في الاسم أو النبذة أو اسم الخدمة)
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('user', function($qu) use ($search) {
                        $qu->where('full_name', 'like', '%' . $search . '%');
                    })
                    ->orWhere('bio', 'like', '%' . $search . '%')
                    ->orWhereHas('services', function($qs) use ($search) {
                        $qs->where('title', 'like', '%' . $search . '%');
                    });
                });
            }
            
            // البحث بعنوان الخدمة (كما كان سابقاً)
            if ($request->has('service_title')) {
                $serviceTitle = $request->service_title;
                $query->whereHas('services', function ($q) use ($serviceTitle) {
                    $q->where('title', 'like', '%' . $serviceTitle . '%');
                });
            }

            // تصفية حسب التقييم
            if ($request->has('min_rating')) {
                $query->where('rating_average', '>=', $request->min_rating);
            }

            // تصفية حسب الموقع
            if ($request->has('location_id')) {
                $locationId = $request->location_id;
                $query->whereHas('user', function($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            $providers = $query->withCount([
                'maintenanceRequests as completed_orders_count' => function ($q) {
                    $q->where('status', 'completed');
                }
            ])
            ->orderBy('rating_average', 'desc')
            ->get();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب مقدمي الخدمة بنجاح',
                'data'    => $providers->map(function ($provider) {
                    return array_merge($provider->toArray(), [
                        'rating' => (float)($provider->rating_average ?? 0),
                        'rating_average' => (float)($provider->rating_average ?? 0),
                        'completed_orders_count' => $provider->completed_orders_count ?? 0,
                    ]);
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب مقدمي الخدمة',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب جميع المتاجر مع البحث
     */
    public function sellers(Request $request)
    {
        try {
            $query = Seller::query();

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('shop_name', 'like', '%' . $search . '%')
                      ->orWhere('shop_description', 'like', '%' . $search . '%');
                });
            }

            $sellers = $query->get();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب المتاجر بنجاح',
                'data'    => $sellers->map(function ($seller) {
                    return [
                        'id' => $seller->id,
                        'name' => $seller->shop_name,
                        'description' => $seller->shop_description,
                        'icon' => $seller->shop_image ? asset('media/' . $seller->shop_image) : null,
                        'rating' => $seller->rating_average ?? 0,
                        'ratingCount' => $seller->rating_count ?? 0,
                        'phone' => $seller->phone,
                        'email' => $seller->email,
                        'location' => $seller->location,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب المتاجر',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
