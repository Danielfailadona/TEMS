<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZoneController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $query = Zone::with('team');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') $query->where('is_active', true);
            elseif ($status === 'inactive') $query->where('is_active', false);
        }

        if ($teamId = $request->input('team_id')) {
            $query->where('team_id', $teamId);
        }

        if ($assignment = $request->input('assignment')) {
            if ($assignment === 'assigned') $query->whereNotNull('team_id');
            elseif ($assignment === 'unassigned') $query->whereNull('team_id');
        }

        $zones = $query->latest()->get();

        $stats = [
            'total' => Zone::count(),
            'assigned' => Zone::whereNotNull('team_id')->count(),
            'unassigned' => Zone::whereNull('team_id')->count(),
            'teams' => Team::whereHas('zones')->count(),
        ];

        $teams = Team::orderBy('name')->get(['id', 'name']);

        $teamColors = [];
        $palette = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#db2777', '#65a30d'];
        foreach ($teams as $i => $team) {
            $teamColors[$team->id] = $palette[$i % count($palette)];
        }

        $mapData = $zones->map(fn ($z) => [
            'id' => $z->id,
            'name' => $z->name,
            'address' => $z->address,
            'lat' => $z->center_latitude,
            'lng' => $z->center_longitude,
            'radius' => (int) $z->radius_m,
            'team_id' => $z->team_id,
            'team_name' => $z->team?->name,
            'is_active' => $z->is_active,
            'color' => $teamColors[$z->team_id] ?? '#9ca3af',
        ]);

        return view('zones.index', compact('zones', 'stats', 'teams', 'teamColors', 'mapData'));
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        $zones = Zone::with('team:id,name')->get(['id', 'name', 'center_latitude', 'center_longitude', 'radius_m', 'team_id']);
        $teams = Team::orderBy('name')->get();

        return view('zones.create', compact('zones', 'teams'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:500'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'center_latitude' => ['required', 'numeric'],
            'center_longitude' => ['required', 'numeric'],
            'radius_m' => ['required', 'numeric'],
            'is_active' => ['boolean'],
        ]);

        Zone::create($data);

        return redirect()->route('zones.index')->with('success', 'Zone created successfully.');
    }

    public function edit(Zone $zone): View
    {
        $this->authorizeAdmin();

        $zones = Zone::with('team:id,name')->where('id', '!=', $zone->id)->get(['id', 'name', 'center_latitude', 'center_longitude', 'radius_m', 'team_id']);
        $teams = Team::orderBy('name')->get();

        return view('zones.edit', compact('zone', 'zones', 'teams'));
    }

    public function update(Request $request, Zone $zone): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:500'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'center_latitude' => ['required', 'numeric'],
            'center_longitude' => ['required', 'numeric'],
            'radius_m' => ['required', 'numeric'],
            'is_active' => ['boolean'],
        ]);

        $zone->update($data);

        return redirect()->route('zones.index')->with('success', 'Zone updated successfully.');
    }

    public function toggleActive(Zone $zone): RedirectResponse
    {
        $this->authorizeAdmin();

        $zone->is_active = !$zone->is_active;
        $zone->save();

        return back()->with('success', 'Zone '.
            ($zone->is_active ? 'activated' : 'deactivated').
            ' successfully.');
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        $this->authorizeAdmin();

        $zone->delete();

        return redirect()->route('zones.index')->with('success', 'Zone deleted successfully.');
    }

    public function myZone(): View
    {
        $user = auth()->user();
        $teams = $user->teams;
        $zones = Zone::with('team')->whereIn('team_id', $teams->pluck('id'))->where('is_active', true)->get();

        return view('zones.my-zone', compact('user', 'zones', 'teams'));
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
