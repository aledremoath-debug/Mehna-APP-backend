<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get all products for the pieces view.
     * GET /api/products
     */
    public function index(Request $request)
    {
        $query = Product::with(['seller', 'images', 'productCategory'])
            ->withSum('orderDetails', 'quantity');

        // البحث بكلمة مفتاحية
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // تصفية حسب تصنيف المنتج
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('product_category_id', $request->category_id); 
        }

        if ($request->has('seller_id') && !empty($request->seller_id)) {
            $query->where('seller_id', $request->seller_id);
        }

        // تصفية حسب السعر
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // الترتيب
        $sort = $request->get('sort', 'popular');
        if ($sort == 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort == 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort == 'newest') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('order_details_sum_quantity', 'desc');
        }

        $products = $query->get();

        return response()->json([
            'status' => true,
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'description' => $product->description,
                    'additional_specs' => $product->additional_specs,
                    'price' => $product->price,
                    'stock_quantity' => $product->stock_quantity,
                    'main_category_id' => $product->main_category_id,
                    'product_category_id' => $product->product_category_id,
                    'product_category_name' => $product->productCategory ? $product->productCategory->name : null,
                    'seller_id' => $product->seller_id,
                    'image' => $product->images->where('is_primary', true)->first() 
                        ? asset('media/' . $product->images->where('is_primary', true)->first()->image_path)
                        : ($product->images->first() ? asset('media/' . $product->images->first()->image_path) : null),
                    'seller_user_id' => $product->seller ? $product->seller->user_id : null,
                    'store_name' => $product->seller ? $product->seller->shop_name : 'متجر غير معروف',
                    'store_icon' => $product->seller && $product->seller->shop_image ? asset('media/' . $product->seller->shop_image) : null,
                    'store_rating' => $product->seller ? $product->seller->rating_average : 0,
                    'store_rating_count' => $product->seller ? $product->seller->rating_count : 0,
                    'images' => $product->images->map(function ($img) {
                        return [
                            'id' => $img->id,
                            'url' => asset('media/' . $img->image_path),
                            'is_primary' => $img->is_primary,
                        ];
                    }),
                ];
            }),
        ]);
    }
}
