<?php

namespace App\Http\Controllers;

use App\Models\PostalCode;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostalCodeController extends Controller
{
    public function index()
    {
        $postalCodes = PostalCode::with('city.province')->paginate(15);
        return view('admin.addresses.postal-codes.index', compact('postalCodes'));
    }

    public function create()
    {
        $cities = City::with('province')->get();
        return view('admin.addresses.postal-codes.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'postal_code' => 'required|string|max:20',
            'city_id' => 'required|exists:cities,city_id',
        ]);

        PostalCode::create($validated);

        return redirect()->route('postal-codes.index')
            ->with('success', 'Postal code created successfully.');
    }

    public function show(PostalCode $postalCode)
    {
        $postalCode->load('city.province', 'fullAddresses');
        return view('admin.addresses.postal-codes.show', compact('postalCode'));
    }

    public function edit(PostalCode $postalCode)
    {
        $cities = City::with('province')->get();
        return view('admin.addresses.postal-codes.edit', compact('postalCode', 'cities'));
    }

    public function update(Request $request, PostalCode $postalCode)
    {
        $validated = $request->validate([
            'postal_code' => 'required|string|max:20',
            'city_id' => 'required|exists:cities,city_id',
        ]);

        $postalCode->update($validated);

        return redirect()->route('postal-codes.index')
            ->with('success', 'Postal code updated successfully.');
    }

    public function destroy(PostalCode $postalCode)
    {
        if ($postalCode->fullAddresses()->count() > 0) {
            return back()->with('error', 'Cannot delete postal code with existing addresses.');
        }

        $postalCode->delete();

        return redirect()->route('postal-codes.index')
            ->with('success', 'Postal code deleted successfully.');
    }

    public function getByCity($cityId)
    {
        $postalCodes = PostalCode::where('city_id', $cityId)->get();
        return response()->json($postalCodes);
    }
}