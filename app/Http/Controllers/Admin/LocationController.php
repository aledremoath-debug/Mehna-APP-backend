<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = Location::withCount('users')->get()->groupBy('governorate');
        return view('admin.locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.locations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'governorate' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        Location::create($request->all());

        return redirect()->route('admin.locations.index')->with('success', 'تم إضافة الموقع بنجاح.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location)
    {
        return view('admin.locations.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        $request->validate([
            'governorate' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $location->update($request->all());

        return redirect()->route('admin.locations.index')->with('success', 'تم تحديث الموقع بنجاح.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        // Check if location has users before deleting
        if ($location->users()->count() > 0) {
            return redirect()->back()->with('error', 'لا يمكن حذف هذا الموقع لأنه مرتبط بمستخدمين.');
        }

        $location->delete();
        return redirect()->route('admin.locations.index')->with('success', 'تم حذف الموقع بنجاح.');
    }
}
