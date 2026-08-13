<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Citation;
use App\Models\Team;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $query = Team::with(['leader', 'members']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->boolean('has_zones')) {
            $query->has('zones');
        }

        $teams = $query->latest()->get();

        // Citation counts (this month) per team — via current members
        $startOfMonth = now()->startOfMonth();
        $memberIds = $teams->flatMap(fn ($t) => $t->members->pluck('id'))->unique()->values();

        $citationsByMember = $memberIds->isEmpty()
            ? collect()
            : Citation::whereIn('issued_by', $memberIds)
                ->where('issued_at', '>=', $startOfMonth)
                ->selectRaw('issued_by, COUNT(*) AS cnt')
                ->groupBy('issued_by')
                ->pluck('cnt', 'issued_by');

        $citationByTeam = [];
        foreach ($teams as $team) {
            $count = 0;
            foreach ($team->members as $member) {
                $count += (int) ($citationsByMember[$member->id] ?? 0);
            }
            $citationByTeam[$team->id] = $count;
        }

        // Zone counts per team
        $zoneCounts = Zone::whereIn('team_id', $teams->pluck('id'))
            ->selectRaw('team_id, COUNT(*) AS cnt')
            ->groupBy('team_id')
            ->pluck('cnt', 'team_id');

        // Fleet-wide stats
        $stats = [
            'total' => Team::count(),
            'active' => Team::where('is_active', true)->count(),
            'members' => (int) DB::table('team_user')->distinct()->count('user_id'),
            'citations_this_month' => (int) $citationsByMember->sum(),
        ];

        $hasFilters = $request->filled('search')
            || $request->filled('status')
            || $request->boolean('has_zones');

        return view('teams.index', compact(
            'teams',
            'stats',
            'zoneCounts',
            'citationByTeam',
            'hasFilters',
        ));
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
            'zones' => ['nullable', 'array'],
            'zones.*' => ['exists:zones,id'],
        ]);

        $team->update($data);
        $team->members()->sync($data['members'] ?? []);
        $team->zones()->sync($data['zones'] ?? []);

        return redirect()->route('teams.index')->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorizeAdmin();

        $name = $team->name;

        DB::transaction(function () use ($team) {
            // Unassign zones so they aren't orphaned.
            $team->zones()->update(['team_id' => null]);
            // Detach members so the pivot rows go with the team.
            $team->members()->detach();
            $team->delete();
        });

        return redirect()->route('teams.index')->with('success', "Team \"{$name}\" deleted. Assigned zones were released.");
    }

    public function toggleActive(Team $team): RedirectResponse
    {
        $this->authorizeAdmin();

        $team->is_active = ! $team->is_active;
        $team->save();

        return back()->with('success', 'Team '.($team->is_active ? 'activated' : 'deactivated').' successfully.');
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
