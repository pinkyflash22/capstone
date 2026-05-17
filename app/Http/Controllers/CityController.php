<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::with('province')->paginate(15);
        return view('admin.addresses.cities.index', compact('cities'));
    }

    public function create()
    {
        $provinces = Province::all();
        return view('admin.addresses.cities.create', compact('provinces'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'city_name' => 'required|string|max:255',
            'province_id' => 'required|exists:provinces,province_id',
        ]);

        City::create($validated);

        return redirect()->route('cities.index')
            ->with('success', 'City created successfully.');
    }

    public function show(City $city)
    {
        $city->load('province', 'barangays', 'postalCodes');
        return view('admin.addresses.cities.show', compact('city'));
    }

    public function edit(City $city)
    {
        $provinces = Province::all();
        return view('admin.addresses.cities.edit', compact('city', 'provinces'));
    }

    public function update(Request $request, City $city)
    {
        $validated = $request->validate([
            'city_name' => 'required|string|max:255',
            'province_id' => 'required|exists:provinces,province_id',
        ]);

        $city->update($validated);

        return redirect()->route('cities.index')
            ->with('success', 'City updated successfully.');
    }

    public function destroy(City $city)
    {
        if ($city->barangays()->count() > 0 || $city->postalCodes()->count() > 0) {
            return back()->with('error', 'Cannot delete city with existing barangays or postal codes.');
        }

        $city->delete();

        return redirect()->route('cities.index')
            ->with('success', 'City deleted successfully.');
    }

    public function getByProvince($provinceId)
    {
        $cities = City::where('province_id', $provinceId)->get();
        return response()->json($cities);
    }
}