<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MainCategory;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    public function index(Request $request)
    {
        $subCategories = SubCategory::with('mainCategory')->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.sub_categories.partials.subcategory_rows', compact('subCategories'))->render(),
                'hasMore' => $subCategories->hasMorePages()
            ]);
        }

        return view('admin.sub_categories.index', compact('subCategories'));
    }

    public function create()
    {
        $mainCategories = MainCategory::all();
        return view('admin.sub_categories.create', compact('mainCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'main_category_id' => 'required|exists:main_categories,id',
        ]);

        SubCategory::create($request->all());

        return redirect()->route('admin.sub_categories.index')->with('success', 'تم إضافة التصنيف الفرعي بنجاح.');
    }

    public function edit(SubCategory $subCategory)
    {
        $mainCategories = MainCategory::all();
        return view('admin.sub_categories.edit', compact('subCategory', 'mainCategories'));
    }

    public function update(Request $request, SubCategory $subCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'main_category_id' => 'required|exists:main_categories,id',
        ]);

        $subCategory->update($request->all());

        return redirect()->route('admin.sub_categories.index')->with('success', 'تم تحديث التصنيف الفرعي بنجاح.');
    }

    public function destroy(SubCategory $subCategory)
    {
        $subCategory->delete();
        return redirect()->route('admin.sub_categories.index')->with('success', 'تم حذف التصنيف الفرعي بنجاح.');
    }
}
