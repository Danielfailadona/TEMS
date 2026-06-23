<?php

namespace App\Http\Controllers;

use App\Models\EnforcerLocation;
use App\Models\Team;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        $teams = Team::with('members')->get();
        $zones = Zone::with('team')->get();

        return view('tracking.index', compact('teams', 'zones'));
    }

    public function locations(Request $request): JsonResponse
    {
        $locations = EnforcerLocation::with('user')->latest('last_seen_at')->get();
        $zones = Zone::with('team')->get();

        $enforcers = $locations->map(function ($location) use ($zones) {
            $closestZone = null;
            $shortestDistance = null;

            foreach ($zones as $zone) {
                $distance = $this->distanceKm(
                    (float) $location->latitude,
                    (float) $location->longitude,
                    (float) $zone->center_latitude,
                    (float) $zone->center_longitude
                );

                if ($shortestDistance === null || $distance < $shortestDistance) {
                    $shortestDistance = $distance;
                    $closestZone = $zone;
                }
            }

            $user = $location->user;

            return [
                'id' => $location->id,
                'user_id' => $user?->id,
                'name' => $user?->name ?? 'Unknown',
                'initials' => $user ? collect(explode(' ', $user->name))->map(fn ($p) => $p[0])->take(2)->join('') : 'E',
                'status' => $location->status ?? 'offline',
                'lat' => (float) $location->latitude,
                'lng' => (float) $location->longitude,
                'accuracy_m' => (float) ($location->accuracy_m ?? 0),
                'team' => $closestZone?->team?->name ?? '—',
                'zone_name' => $closestZone?->name ?? 'No zone',
                'distance_km' => round($shortestDistance ?? 0, 2),
                'inside_zone' => $closestZone && (($shortestDistance ?? 999) * 1000) <= ($closestZone->radius_m ?? 0),
                'last_seen_label' => $location->last_seen_at?->diffForHumans() ?? 'Never',
            ];
        });

        $zoneData = $zones->map(fn ($z) => [
            'id' => $z->id,
            'name' => $z->name,
            'center_lat' => (float) $z->center_latitude,
            'center_lng' => (float) $z->center_longitude,
            'radius_m' => (float) $z->radius_m,
            'team' => $z->team?->name,
        ]);

        return response()->json([
            'enforcers' => $enforcers,
            'zones' => $zoneData,
        ]);
    }

    protected function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
