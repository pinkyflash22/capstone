<?php

namespace App\Http\Controllers;

use App\Models\FullAddress;
use App\Models\Barangay;
use App\Models\PostalCode;
use Illuminate\Http\Request;

class FullAddressController extends Controller
{
    public function index()
    {
        $addresses = FullAddress::with('barangay.city.province', 'postalCode')
            ->paginate(15);
        return view('admin.addresses.full-addresses.index', compact('addresses'));
    }

    public function create()
    {
        $barangays = Barangay::with('city.province')->get();
        $postalCodes = PostalCode::with('city')->get();
        return view('admin.addresses.full-addresses.create', compact('barangays', 'postalCodes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'street' => 'required|string|max:255',
            'barangay_id' => 'required|exists:barangays,barangay_id',
            'postal_id' => 'nullable|exists:postal_codes,postal_id',
        ]);

        FullAddress::create($validated);

        return redirect()->route('full-addresses.index')
            ->with('success', 'Address created successfully.');
    }

    public function show(FullAddress $fullAddress)
    {
        $fullAddress->load('barangay.city.province', 'postalCode', 'users', 'stations');
        return view('admin.addresses.full-addresses.show', compact('fullAddress'));
    }

    public function edit(FullAddress $fullAddress)
    {
        $barangays = Barangay::with('city.province')->get();
        $postalCodes = PostalCode::with('city')->get();
        return view('admin.addresses.full-addresses.edit', compact('fullAddress', 'barangays', 'postalCodes'));
    }

    public function update(Request $request, FullAddress $fullAddress)
    {
        $validated = $request->validate([
            'street' => 'required|string|max:255',
            'barangay_id' => 'required|exists:barangays,barangay_id',
            'postal_id' => 'nullable|exists:postal_codes,postal_id',
        ]);

        $fullAddress->update($validated);

        return redirect()->route('full-addresses.index')
            ->with('success', 'Address updated successfully.');
    }

    public function destroy(FullAddress $fullAddress)
    {
        if ($fullAddress->users()->count() > 0 || $fullAddress->stations()->count() > 0) {
            return back()->with('error', 'Cannot delete address linked to users or stations.');
        }

        $fullAddress->delete();

        return redirect()->route('full-addresses.index')
            ->with('success', 'Address deleted successfully.');
    }
}