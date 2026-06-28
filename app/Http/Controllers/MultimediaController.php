<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Multimedia;
use App\Support\MediaPath;
use Illuminate\Support\Str;

class MultimediaController extends Controller
{
        
    public function store(Request $request)
    {
        $request->validate([
            'path' => 'required|file|max:10240', // 10MB
            'type' => 'required|in:icon,proof,report,photo,video'
        ]);

        $file = $request->file('path');

        // ✅ generate custom ID
        // $multimediaId = 'MM-' . Str::uuid();

        // ✅ store file
        $path = MediaPath::storeForDisk(
            $file->store('multimedia', 'supabase'),
            'supabase'
        );

        // ✅ save to database
       $multimedia = Multimedia::create([
            // 'multimedia_id' => $multimediaId,
            'path' => $path,
            'created_at' => now(),
            'type' => $request->type
        ]);

        // return back()->with('success', 'File uploaded successfully');
        return response()->json([
            'success' => true,
            'multimedia' => $multimedia, // optional: send back the saved file info
        ]);
    }
    public function index()
    {
        $files = Multimedia::latest()->get()->map(function ($file) {
            return [
                'multimedia_id' => $file->multimedia_id,
                'type' => $file->type,
                'created_at' => $file->created_at,
                // convert path → public URL
                'url' => MediaPath::url($file->path),
            ];
        });

        return response()->json($files);
    }
}
