<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Multimedia;
use App\Models\Role;
use App\Support\MediaPath;
use Illuminate\Validation\ValidationException;

class MultimediaController extends Controller
{
        
    public function store(Request $request)
    {
        $this->assertAuthorized($request);
        $validated = $request->validate([
            'path' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,mp4,mov,webm'],
            'type' => ['required', 'in:icon,proof,report,photo,video'],
        ]);

        $file = $request->file('path');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $allowedExtensions = [
            'icon' => ['jpg', 'jpeg', 'png', 'webp'],
            'proof' => ['jpg', 'jpeg', 'png', 'webp'],
            'photo' => ['jpg', 'jpeg', 'png', 'webp'],
            'report' => ['pdf', 'doc', 'docx'],
            'video' => ['mp4', 'mov', 'webm'],
        ];
        if (! in_array($extension, $allowedExtensions[$validated['type']], true)) {
            throw ValidationException::withMessages([
                'path' => 'The uploaded file does not match the selected multimedia type.',
            ]);
        }

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
            'type' => $validated['type'],
        ]);

        // return back()->with('success', 'File uploaded successfully');
        return response()->json([
            'success' => true,
            'multimedia' => $multimedia, // optional: send back the saved file info
        ]);
    }
    public function index(Request $request)
    {
        $this->assertAuthorized($request);
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

    private function assertAuthorized(Request $request): void
    {
        $role = Role::roleNameForId((int) ($request->user()?->role_id ?? 0));
        abort_unless(in_array($role, ['admin', 'provincial admin', 'station admin', 'chief', 'firefighter'], true), 403);
    }
}
