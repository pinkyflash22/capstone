<?php

namespace App\Support;

use App\Models\AiActionPlanChecklist;
use App\Models\BarangayHall;
use App\Models\BarangayPredictionChecklist;
use App\Models\Incident;
use App\Models\RiskArea;
use App\Models\User;
use App\Notifications\BarangayPortalNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class BarangayNotificationService
{
    public static function notifyChecklistPublished(BarangayPredictionChecklist $checklist): void
    {
        $checklist->loadMissing('barangay');

        $users = static::resolveBarangayUsers((int) $checklist->barangay_id);
        if ($users->isEmpty()) {
            return;
        }

        $barangayName = $checklist->barangay?->barangay_name ?: 'your barangay';

        NotificationFacade::send($users, new BarangayPortalNotification(
            'checklist',
            'New checklist published',
            'A new preparedness checklist for ' . $barangayName . ' is ready for review.',
            route('barangay.checklists'),
            'bi bi-clipboard2-check-fill',
            [
                'barangay_prediction_checklist_id' => $checklist->barangay_prediction_checklist_id,
                'barangay_id' => $checklist->barangay_id,
                'priority_level' => $checklist->priority_level,
                'status' => $checklist->status,
            ]
        ));
    }

    public static function notifyActionPlanChecklistPublished(AiActionPlanChecklist $checklist): void
    {
        if (! $checklist->barangay_id) {
            return;
        }

        $users = static::resolveBarangayUsers((int) $checklist->barangay_id);
        if ($users->isEmpty()) {
            return;
        }

        NotificationFacade::send($users, new BarangayPortalNotification(
            'checklist',
            'New checklist is up',
            'A new action-plan checklist has been generated for your barangay.',
            route('barangay.checklists'),
            'bi bi-clipboard2-check-fill',
            [
                'analysis_id' => $checklist->analysis_id,
                'barangay_id' => $checklist->barangay_id,
                'priority_level' => $checklist->priority_level,
                'status' => $checklist->status,
            ]
        ));
    }

    public static function notifyRiskAreaSubmitted(RiskArea $riskArea): void
    {
        $riskArea->loadMissing('barangayHall.barangay', 'reporter');

        $barangayId = (int) ($riskArea->barangayHall?->barangay_id ?? 0);
        if (! $barangayId) {
            return;
        }

        $users = static::resolveBarangayUsers($barangayId);
        if ($users->isEmpty()) {
            return;
        }

        $entryLabel = static::entryLabel($riskArea);
        $barangayName = $riskArea->barangayHall?->barangay?->barangay_name ?: 'your barangay';

        NotificationFacade::send($users, new BarangayPortalNotification(
            'hazard-risk',
            'New ' . strtolower($entryLabel) . ' report submitted',
            $entryLabel . ' record submitted in ' . $barangayName . ' and is waiting for review.',
            route('barangay.hazards'),
            $riskArea->entry_type === 'hazard' ? 'bi bi-exclamation-triangle-fill' : 'bi bi-shield-fill-exclamation',
            [
                'risk_area_id' => $riskArea->risk_area_id,
                'entry_type' => $riskArea->entry_type,
                'review_status' => $riskArea->review_status,
                'review_type' => $riskArea->review_type,
            ]
        ));
    }

    public static function notifyRiskAreaReviewed(RiskArea $riskArea, string $decision): void
    {
        $riskArea->loadMissing('barangayHall.barangay');

        $barangayId = (int) ($riskArea->barangayHall?->barangay_id ?? 0);
        if (! $barangayId) {
            return;
        }

        $users = static::resolveBarangayUsers($barangayId);
        if ($users->isEmpty()) {
            return;
        }

        $entryLabel = static::entryLabel($riskArea);
        $decisionLabel = strtolower($decision) === 'approved' ? 'approved' : 'rejected';

        NotificationFacade::send($users, new BarangayPortalNotification(
            'hazard-risk-review',
            $entryLabel . ' ' . ucfirst($decisionLabel),
            'A ' . strtolower($entryLabel) . ' record for your barangay was ' . $decisionLabel . ' by the reviewing office.',
            route('barangay.hazards'),
            strtolower($decision) === 'approved' ? 'bi bi-patch-check-fill' : 'bi bi-x-octagon-fill',
            [
                'risk_area_id' => $riskArea->risk_area_id,
                'entry_type' => $riskArea->entry_type,
                'review_status' => $decisionLabel,
            ]
        ));
    }

    public static function notifyNearbyFireIncident(Incident $incident): void
    {
        $incident = $incident->loadMissing('marker');

        if (
            ! $incident->marker ||
            strtolower(trim((string) ($incident->marker->category ?? ''))) !== 'fire' ||
            $incident->marker->lat === null ||
            $incident->marker->lng === null
        ) {
            return;
        }

        $radiusKm = (float) env('BARANGAY_FIRE_ALERT_RADIUS_KM', 0.5);
        $incidentLat = (float) $incident->marker->lat;
        $incidentLng = (float) $incident->marker->lng;

        $barangayHalls = BarangayHall::query()
            ->with(['barangay', 'marker'])
            ->whereNotNull('marker_id')
            ->get();

        foreach ($barangayHalls as $hall) {
            if (! $hall->marker || $hall->marker->lat === null || $hall->marker->lng === null) {
                continue;
            }

            $distanceKm = static::distanceKm(
                $incidentLat,
                $incidentLng,
                (float) $hall->marker->lat,
                (float) $hall->marker->lng
            );

            if ($distanceKm > $radiusKm) {
                continue;
            }

            $users = static::resolveBarangayUsers((int) $hall->barangay_id);
            if ($users->isEmpty()) {
                continue;
            }

            $barangayName = $hall->barangay?->barangay_name ?: 'your barangay';
            $distanceLabel = $distanceKm < 1
                ? round($distanceKm * 1000) . ' m'
                : number_format($distanceKm, 1) . ' km';

            NotificationFacade::send($users, new BarangayPortalNotification(
                'fire-nearby',
                'Fire reported near ' . $barangayName,
                'A fire incident was reported about ' . $distanceLabel . ' from the barangay area.',
                route('barangay.reports'),
                'bi bi-fire',
                [
                    'incident_id' => $incident->incident_id,
                    'marker_id' => $incident->marker_id,
                    'barangay_id' => $hall->barangay_id,
                    'distance_km' => round($distanceKm, 3),
                ]
            ));
        }
    }

    protected static function resolveBarangayUsers(int $barangayId): Collection
    {
        return User::query()
            ->with('role')
            ->where(function ($query) use ($barangayId) {
                $query
                    ->whereHas('barangayOfficer', function ($officerQuery) use ($barangayId) {
                        $officerQuery->where('barangay_id', $barangayId);
                    })
                    ->orWhere(function ($roleAddressQuery) use ($barangayId) {
                        $roleAddressQuery
                            ->whereHas('role', function ($roleQuery) {
                                $roleQuery->whereIn('role', ['barangay', 'barangay captain']);
                            })
                            ->whereHas('address', function ($addressQuery) use ($barangayId) {
                                $addressQuery->where('barangay_id', $barangayId);
                            });
                    });
            })
            ->get()
            ->unique('user_id')
            ->values();
    }

    protected static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * (2 * asin(min(1, sqrt($a))));
    }

    protected static function entryLabel(RiskArea $riskArea): string
    {
        return $riskArea->entry_type === 'hazard' ? 'Hazard' : 'Risk';
    }
}
