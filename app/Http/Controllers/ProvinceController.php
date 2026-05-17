<?php

namespace App\Http\Controllers;

use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProvinceController extends Controller
{
    public function index()
    {
        $provinces = Province::with('cities')->paginate(15);
        return view('admin.addresses.provinces.index', compact('provinces'));
    }

    public function create()
    {
        return view('admin.addresses.provinces.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'province_name' => 'required|string|unique:provinces,province_name|max:255',
        ]);

        Province::create($validated);

        return redirect()->route('provinces.index')
            ->with('success', 'Province created successfully.');
    }

    public function show(Province $province)
    {
        $province->load('cities.barangays');
        return view('admin.addresses.provinces.show', compact('province'));
    }

    public function edit(Province $province)
    {
        return view('admin.addresses.provinces.edit', compact('province'));
    }

    public function update(Request $request, Province $province)
    {
        $validated = $request->validate([
            'province_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('provinces', 'province_name')->ignore($province->province_id, 'province_id'),
            ],
        ]);

        $province->update($validated);

        return redirect()->route('provinces.index')
            ->with('success', 'Province updated successfully.');
    }

    public function destroy(Province $province)
    {
        // Check if province has cities
        if ($province->cities()->count() > 0) {
            return back()->with('error', 'Cannot delete province with existing cities.');
        }

        $province->delete();

        return redirect()->route('provinces.index')
            ->with('success', 'Province deleted successfully.');
    }
}