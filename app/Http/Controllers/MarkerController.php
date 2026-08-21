<?php

namespace App\Http\Controllers;

use App\Models\Marker;

use App\Models\Status;
use App\Models\Multimedia;
use App\Support\MarkerFeedBuilder;
use App\Support\MediaPath;
use App\Support\UserActivityLogger;
use App\Services\BoundaryMatcherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MarkerController extends Controller
{
    private const MARKER_CATEGORIES = ['fire', 'station', 'hydrant', 'school', 'barangay', 'bridge', 'risk', 'hazard'];
    private const MARKER_STATUSES = ['active', 'inactive', 'completed'];

    private function mapCategoryNameToEnum(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match (true) {
            $normalized === 'fire',
            $normalized === 'incident',
            str_contains($normalized, 'incident'),
            $normalized === 'fire incident' => 'fire',
            $normalized === 'station',
            str_contains($normalized, 'station') => 'station',
            $normalized === 'hydrant',
            str_contains($normalized, 'hydrant') => 'hydrant',
            $normalized === 'school',
            str_contains($normalized, 'school') => 'school',
            $normalized === 'barangay',
            str_contains($normalized, 'barangay') => 'barangay',
            $normalized === 'bridge',
            str_contains($normalized, 'bridge') => 'bridge',
            $normalized === 'risk',
            str_contains($normalized, 'risk') => 'risk',
            $normalized === 'hazard',
            str_contains($normalized, 'hazard') => 'hazard',
            default => in_array($normalized, self::MARKER_CATEGORIES, true) ? $normalized : null,
        };
    }

    private function resolveCategory(?string $category, mixed $legacyCategoryId = null): ?string
    {
        $resolved = $this->mapCategoryNameToEnum($category);
        if ($resolved) {
            return $resolved;
        }

        if ($legacyCategoryId) {
            return match ((int) $legacyCategoryId) {
                1 => 'station',
                2, 3 => 'fire',
                4 => 'hydrant',
                5 => 'school',
                6 => 'barangay',
                7 => 'bridge',
                default => null,
            };
        }

        return null;
    }

    private function resolveStatus(?string $status, mixed $legacyStatusId = null): string
    {
        $normalized = strtolower(trim((string) $status));
        if (in_array($normalized, self::MARKER_STATUSES, true)) {
            return $normalized;
        }

        if ($legacyStatusId) {
            $legacyStatus = Status::find($legacyStatusId);
            $legacyName = strtolower(trim((string) ($legacyStatus?->status_name ?? '')));
            if (in_array($legacyName, self::MARKER_STATUSES, true)) {
                return $legacyName;
            }
        }

        return 'active';
    }

    private function publicValue(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function formatFullAddress($address): ?string
    {
        if (! $address) {
            return null;
        }

        $parts = array_filter([
            $this->publicValue($address->street ?? null),
            $this->publicValue($address->barangay?->barangay_name ?? null),
            $this->publicValue($address->barangay?->city?->city_name ?? null),
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    private function publicDisplayName($user): ?string
    {
        if (! $user) {
            return null;
        }

        $name = trim(implode(' ', array_filter([
            $this->publicValue($user->first_name ?? null),
            $this->publicValue($user->last_name ?? null),
        ])));

        return $name !== '' ? $name : $this->publicValue($user->name ?? null);
    }

    private function resolveStationChiefName($station): ?string
    {
        if (! $station?->stationOfficers) {
            return null;
        }

        $chiefOfficer = $station->stationOfficers
            ->filter(function ($officer) {
                $role = strtolower((string) ($officer->user?->role?->role ?? ''));
                $rank = strtolower((string) ($officer->rank ?? ''));
                $status = strtolower((string) ($officer->status ?? ''));

                $isChief = $role === 'chief' || str_contains($rank, 'chief');
                $isActive = $status === '' || in_array($status, ['active', 'available', 'on duty', 'on-duty'], true);

                return $isChief && $isActive;
            })
            ->sortByDesc(function ($officer) {
                return strtolower((string) ($officer->user?->role?->role ?? '')) === 'chief' ? 1 : 0;
            })
            ->first();

        return $this->publicDisplayName($chiefOfficer?->user);
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => MarkerFeedBuilder::buildForViewer(Auth::user())->values()->all(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

   

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'status' => ['nullable', Rule::in(self::MARKER_STATUSES)],
            'category' => ['nullable', Rule::in(self::MARKER_CATEGORIES)],
            'status_id' => 'nullable',
            'markerCategory_id' => 'nullable',
            'image' => 'nullable|file|image|max:5120',
        ]);

        $resolvedStatus = $this->resolveStatus(
            $validated['status'] ?? null,
            $request->input('status_id')
        );

        $resolvedCategory = $this->resolveCategory(
            $validated['category'] ?? null,
            $request->input('markerCategory_id')
        );

        $data = Marker::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'status' => $resolvedStatus,
            'category' => $resolvedCategory,
            'image' => $validated['image'] ?? null
        ]);

        $data->refresh();
        $data->load([
            'stations.address.barangay.city',
            'stations.stationOfficers.user.role',
            'barangayHall.address.barangay.city',
            'barangayHall.barangay.city',
            'hydrants.station',
            'incidents',
            'riskAreas',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marker saved successfully.',
            'data' => $data,
            'marker' => $data,
        ], 201);
    }

 
    public function getCount(Request $request)
    {
        try {
            $count = Marker::count();
            
            return response()->json([
                'success' => true,
                'count' => $count,
                'total' => $count
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function latest()
    {
        $latestMarker = Marker::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('marker_id')
            ->first();

        return response()->json([
            'marker_id' => $latestMarker?->marker_id,
            'updated_at' => $latestMarker?->updated_at?->toISOString(),
            'count' => Marker::count(),
        ]);
    }

public function update(Request $request, $markerId)
{
    try {
        $marker = Marker::findOrFail($markerId);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['nullable', Rule::in(self::MARKER_CATEGORIES)],
            'status' => ['nullable', Rule::in(self::MARKER_STATUSES)],
            
        ]);

        $updateData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ];

        $resolvedCategory = $this->resolveCategory(
            $validated['category'] ?? null,
         
        );
        if ($resolvedCategory) {
            $updateData['category'] = $resolvedCategory;
        }

        if (array_key_exists('status', $validated) || array_key_exists('status_id', $validated)) {
            $updateData['status'] = $this->resolveStatus(
                $validated['status'] ?? null,
             
            );
        }

        $marker->update($updateData);
        
        // Refresh the marker to get updated data
        $marker->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Marker updated successfully',
            'data' => $marker
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error updating marker: ' . $e->getMessage()
        ], 500);
    }
}

    public function updateLocation(Request $request, int $markerId, BoundaryMatcherService $boundaryMatcherService)
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $marker = Marker::query()->findOrFail($markerId);
        $lat = round((float) $validated['lat'], 7);
        $lng = round((float) $validated['lng'], 7);
        $isRiskOrHazard = in_array(strtolower((string) $marker->category), ['risk', 'hazard'], true)
            || $marker->riskAreas()->exists();
        $matchedHall = $isRiskOrHazard
            ? $boundaryMatcherService->locateBarangayHall($lat, $lng)
            : null;

        if ($isRiskOrHazard && ! $matchedHall) {
            throw ValidationException::withMessages([
                'lat' => 'Place the risk or hazard marker inside a saved barangay boundary with a linked barangay hall.',
            ]);
        }

        DB::transaction(function () use ($marker, $lat, $lng, $matchedHall) {
            $marker->update(['lat' => $lat, 'lng' => $lng]);

            if ($matchedHall) {
                $marker->riskAreas()->update([
                    'barangay_hall_id' => $matchedHall->barangay_hall_id,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Marker location updated successfully',
            'data' => $marker->fresh(),
        ]);
    }

    public function destroy($id)
    {
        try {
            \Log::info('Attempting to delete marker', ['marker_id' => $id]);
            
            $marker = Marker::find($id);
            
            if (!$marker) {
                \Log::warning('Marker not found', ['id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Marker not found'
                ], 404);
            }
            
            \Log::info('Found marker', [
                'marker_id' => $marker->marker_id,
                'title' => $marker->title
            ]);
            
            // Check for related records
            $stationCount = $marker->stations()->count();
            $hydrantCount = $marker->hydrants()->count();
            $incidentCount = $marker->incidents()->count();
            
            \Log::info('Related records count', [
                'stations' => $stationCount,
                'hydrants' => $hydrantCount,
                'incidents' => $incidentCount
            ]);
            
            // Delete related records
            if ($stationCount > 0) {
                $marker->stations()->delete();
            }
            
            if ($hydrantCount > 0) {
                $marker->hydrants()->delete();
            }
            
            if ($incidentCount > 0) {
                $marker->incidents()->delete();
            }
            
            // Delete multimedia if exists
            if ($marker->multimedia) {
                try {
                    MediaPath::delete($marker->multimedia->path);
                    $marker->multimedia->delete();
                } catch (\Exception $e) {
                    \Log::error('Error deleting multimedia', ['error' => $e->getMessage()]);
                }
            }
            
            // Delete the marker
            $deleted = $marker->delete();
            
            if ($deleted) {
                if (auth()->check() && $incidentCount > 0) {
                    UserActivityLogger::log('deleted incident report', auth()->id(), request());
                }

                \Log::info('Marker deleted successfully');
                return response()->json([
                    'success' => true,
                    'message' => 'Marker deleted successfully.'
                ], 200);
            } else {
                \Log::error('Marker deletion failed - delete() returned false');
                return response()->json([
                    'success' => false,
                    'message' => 'Marker could not be deleted.'
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error('Exception in marker deletion', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting marker: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update marker status (soft delete or mark as inactive)
     */
    public function updateStatus(Request $request, $markerId)
    {
        try {
            $validated = $request->validate([
                'status' => ['nullable', Rule::in(self::MARKER_STATUSES)],
                'status_id' => 'nullable|exists:statuses,status_id'
            ]);
            
            $marker = Marker::find($markerId);
            
            if (!$marker) {
                return response()->json([
                    'success' => false,
                    'message' => 'Marker not found'
                ], 404);
            }
            
            $marker->status = $this->resolveStatus(
                $validated['status'] ?? null,
                $validated['status_id'] ?? null
            );
            $marker->save();
            
            \Log::info('Marker status updated', [
                'marker_id' => $markerId,
                'new_status' => $marker->status
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Marker status updated successfully',
                'data' => $marker
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error updating marker status', [
                'marker_id' => $markerId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating marker status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getMapData()
    {
        $markers = Marker::with('category', 'status')->get();
        return response()->json($markers);
    }
}
