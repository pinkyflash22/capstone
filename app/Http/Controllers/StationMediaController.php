<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\StationMedia;
use App\Models\StationOfficer;
use App\Support\MediaPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StationMediaController extends Controller
{
    protected function canManageStation(Station $station): bool
    {
        $user = Auth::user();
        $role = strtolower($user?->role->role ?? '');

        if ($role === 'admin') {
            return true;
        }

        if (!in_array($role, ['chief', 'station admin'], true)) {
            return false;
        }

        $assignedStationId = StationOfficer::where('user_id', $user?->user_id)->value('station_id');

        return (int) $assignedStationId === (int) $station->station_id;
    }

    public function storeProfile(Request $request, Station $station)
    {
        abort_unless($this->canManageStation($station), 403);

        $validated = $request->validate([
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $existingProfile = $station->profileMedia()->first();
        if ($existingProfile) {
            MediaPath::delete($existingProfile->path);
            $existingProfile->delete();
        }

        $path = MediaPath::storeForDisk(
            $validated['profile_image']->store('station-media/profile', 'supabase'),
            'supabase'
        );

        StationMedia::create([
            'station_id' => $station->station_id,
            'path' => $path,
            'kind' => 'profile',
            'uploaded_by_user_id' => Auth::user()?->user_id,
        ]);

        return back()->with('success', 'Station profile image updated successfully.');
    }

    public function storeGallery(Request $request, Station $station)
    {
        abort_unless($this->canManageStation($station), 403);

        $validated = $request->validate([
            'gallery_images' => 'required|array|min:1|max:6',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'caption' => 'nullable|string|max:255',
        ]);

        $baseSort = (int) StationMedia::where('station_id', $station->station_id)
            ->where('kind', 'gallery')
            ->max('sort_order');

        foreach ($validated['gallery_images'] as $index => $image) {
            $path = MediaPath::storeForDisk(
                $image->store('station-media/gallery', 'supabase'),
                'supabase'
            );

            StationMedia::create([
                'station_id' => $station->station_id,
                'path' => $path,
                'kind' => 'gallery',
                'caption' => $validated['caption'] ?? null,
                'sort_order' => $baseSort + $index + 1,
                'uploaded_by_user_id' => Auth::user()?->user_id,
            ]);
        }

        return back()->with('success', 'Station gallery updated successfully.');
    }

    public function destroy(StationMedia $stationMedia)
    {
        $station = $stationMedia->station;
        abort_unless($station && $this->canManageStation($station), 403);

        MediaPath::delete($stationMedia->path);
        $stationMedia->delete();

        return back()->with('success', 'Station image removed successfully.');
    }

    public function update(Request $request, StationMedia $stationMedia)
    {
        $station = $stationMedia->station;
        abort_unless($station && $this->canManageStation($station), 403);

        $validated = $request->validate([
            'caption' => 'nullable|string|max:255',
        ]);

        $stationMedia->update([
            'caption' => $validated['caption'] ?? null,
        ]);

        return back()->with('success', 'Station image details updated successfully.');
    }
}
