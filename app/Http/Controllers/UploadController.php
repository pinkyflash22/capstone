<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Marker;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request)
{
    // $request->validate([
    //     'image' => 'nullable|image|max:2048'
    // ]);

    // $imagePath = null;

    // if ($request->hasFile('image')) {
    //     $imagePath = $request->file('image')
    //         ->store('markers', 'public');
    // }

    // $marker = Marker::create([
    //     'title' => $request->title,
    //     'image' => $imagePath
    // ]);

    // return response()->json([
    //     'image' => asset('storage/'.$imagePath)
    // ]);
}
  
}
