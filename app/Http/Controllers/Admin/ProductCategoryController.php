<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::withCount('products')->latest()->paginate(20);

        if (request()->ajax()) {
            return response()->json([
                'html' => view('admin.product_categories.partials.rows', compact('categories'))->render(),
                'hasMore' => $categories->hasMorePages(),
            ]);
        }

        return view('admin.product_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.product_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
            'icon' => 'nullable|string|max:255',
        ], [
            'name.required' => 'اسم التصنيف مطلوب',
            'name.unique' => 'هذا التصنيف موجود مسبقاً',
        ]);

        ProductCategory::create($request->only('name', 'icon'));

        return redirect()->route('admin.product_categories.index')
            ->with('success', 'تم إضافة التصنيف بنجاح');
    }

    public function edit(ProductCategory $productCategory)
    {
        return view('admin.product_categories.edit', compact('productCategory'));
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name,' . $productCategory->id,
            'icon' => 'nullable|string|max:255',
        ], [
            'name.required' => 'اسم التصنيف مطلوب',
            'name.unique' => 'هذا التصنيف موجود مسبقاً',
        ]);

        $productCategory->update($request->only('name', 'icon'));

        return redirect()->route('admin.product_categories.index')
            ->with('success', 'تم تحديث التصنيف بنجاح');
    }

    public function destroy(ProductCategory $productCategory)
    {
        if ($productCategory->products()->count() > 0) {
            return redirect()->route('admin.product_categories.index')
                ->with('error', 'لا يمكن حذف التصنيف لأنه يحتوي على منتجات');
        }

        $productCategory->delete();

        return redirect()->route('admin.product_categories.index')
            ->with('success', 'تم حذف التصنيف بنجاح');
    }
}
