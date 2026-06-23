<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        $teams = Team::with(['leader', 'members'])->latest()->get();

        return view('teams.index', compact('teams'));
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        $enforcers = User::query()->where('role', Role::Enforcer->value)->orderBy('name')->get();
        $zones = Zone::with('team:id,name')->get(['id', 'name', 'center_latitude', 'center_longitude', 'radius_m', 'team_id']);

        return view('teams.create', compact('enforcers', 'zones'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'leader_id' => ['nullable', 'exists:users,id'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        $team = Team::create($data);
        $team->members()->sync($data['members'] ?? []);

        return redirect()->route('teams.index')->with('success', 'Team created successfully.');
    }

    public function edit(Team $team): View
    {
        $this->authorizeAdmin();

        $enforcers = User::query()->where('role', Role::Enforcer->value)->orderBy('name')->get();
        $zones = Zone::with('team:id,name')->get(['id', 'name', 'center_latitude', 'center_longitude', 'radius_m', 'team_id']);

        return view('teams.edit', [
            'team' => $team->load(['members']),
            'enforcers' => $enforcers,
            'zones' => $zones,
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'leader_id' => ['nullable', 'exists:users,id'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        $team->update($data);
        $team->members()->sync($data['members'] ?? []);

        return redirect()->route('teams.index')->with('success', 'Team updated successfully.');
    }

    public function toggleZone(Request $request, Team $team): JsonResponse
    {
        $this->authorizeAdmin();

        $zone = Zone::findOrFail($request->integer('zone_id'));

        $assigned = $request->boolean('assigned');
        if ($assigned) {
            $zone->update(['team_id' => $team->id]);
        } else {
            $zone->update(['team_id' => null]);
        }

        return response()->json([
            'assigned' => $assigned,
            'zone_id' => $zone->id,
            'team_id' => $assigned ? $team->id : null,
            'assigned_count' => $team->zones()->count(),
        ]);
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
