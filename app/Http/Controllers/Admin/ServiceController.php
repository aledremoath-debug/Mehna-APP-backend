<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

use App\Models\MainCategory;
use App\Models\SubCategory;
use App\Models\ServiceProvider;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $services = Service::with(['mainCategory', 'subCategory', 'provider.user'])->latest()->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.services.partials.service_rows', compact('services'))->render(),
                'hasMore' => $services->hasMorePages()
            ]);
        }

        return view('admin.services.index', compact('services'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $mainCategories = MainCategory::all();
        $subCategories = SubCategory::all();
        $providers = ServiceProvider::with('user')->get();
        return view('admin.services.create', compact('mainCategories', 'subCategories', 'providers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'main_category_id' => 'required|exists:main_categories,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'service_provider_id' => 'required|exists:service_providers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        Service::create($request->all());

        return redirect()->route('admin.services.index')->with('success', 'تم إضافة عرض الخدمة بنجاح.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        $mainCategories = MainCategory::all();
        $subCategories = SubCategory::all();
        $providers = ServiceProvider::with('user')->get();
        return view('admin.services.edit', compact('service', 'mainCategories', 'subCategories', 'providers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'main_category_id' => 'required|exists:main_categories,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'service_provider_id' => 'required|exists:service_providers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $service->update($request->all());

        return redirect()->route('admin.services.index')->with('success', 'تم تحديث عرض الخدمة بنجاح.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->route('admin.services.index')->with('success', 'تم حذف عرض الخدمة بنجاح.');
    }
}
