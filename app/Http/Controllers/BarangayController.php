<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangayController extends Controller
{
    public function index()
    {
        $barangays = Barangay::with('city.province')->paginate(15);
        return view('admin.addresses.barangays.index', compact('barangays'));
    }

    public function create()
    {
        $cities = City::with('province')->get();
        return view('admin.addresses.barangays.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'barangay_name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,city_id',
        ]);

        Barangay::create($validated);

        return redirect()->route('barangays.index')
            ->with('success', 'Barangay created successfully.');
    }

    public function show(Barangay $barangay)
    {
        $barangay->load('city.province', 'fullAddresses');
        return view('admin.addresses.barangays.show', compact('barangay'));
    }

    public function edit(Barangay $barangay)
    {
        $cities = City::with('province')->get();
        return view('admin.addresses.barangays.edit', compact('barangay', 'cities'));
    }

    public function update(Request $request, Barangay $barangay)
    {
        $validated = $request->validate([
            'barangay_name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,city_id',
        ]);

        $barangay->update($validated);

        return redirect()->route('barangays.index')
            ->with('success', 'Barangay updated successfully.');
    }

    public function destroy(Barangay $barangay)
    {
        if ($barangay->fullAddresses()->count() > 0) {
            return back()->with('error', 'Cannot delete barangay with existing addresses.');
        }

        $barangay->delete();

        return redirect()->route('barangays.index')
            ->with('success', 'Barangay deleted successfully.');
    }

    public function getByCity($cityId)
    {
        $barangays = Barangay::where('city_id', $cityId)->get();
        return response()->json($barangays);
    }
}