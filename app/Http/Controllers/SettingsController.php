<?php

namespace App\Http\Controllers;

use App\Models\EnforcerLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $sessions = Schema::hasTable('sessions')
            ? DB::table('sessions')->where('user_id', auth()->id())->orderBy('last_activity', 'desc')->get()
            : collect();

        $user = auth()->user();
        $gpsLocation = EnforcerLocation::where('user_id', $user->id)->first();

        return view('settings.index', compact('sessions', 'user', 'gpsLocation'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'notify_email' => 'nullable|boolean',
            'notify_in_app' => 'nullable|boolean',
            'notify_citations' => 'nullable|boolean',
            'notify_payments' => 'nullable|boolean',
            'notify_appeals' => 'nullable|boolean',
            'notify_clamping' => 'nullable|boolean',
            'pagination_size' => 'nullable|integer|in:10,25,50',
            'gps_enabled' => 'nullable|boolean',
        ]);

        $preferences = $user->preferences ?? [];
        $preferences['notifications'] = [
            'email' => $validated['notify_email'] ?? true,
            'in_app' => $validated['notify_in_app'] ?? true,
            'citations' => $validated['notify_citations'] ?? true,
            'payments' => $validated['notify_payments'] ?? true,
            'appeals' => $validated['notify_appeals'] ?? true,
            'clamping' => $validated['notify_clamping'] ?? true,
        ];
        $preferences['pagination_size'] = $validated['pagination_size'] ?? 10;
        $preferences['gps_enabled'] = $validated['gps_enabled'] ?? false;

        $user->preferences = $preferences;
        $user->save();

        if ($user->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer)) {
            $gpsLocation = EnforcerLocation::firstOrNew(['user_id' => $user->id]);
            $gpsLocation->status = ($validated['gps_enabled'] ?? false) ? 'active' : 'inactive';
            if (!($validated['gps_enabled'] ?? false)) {
                $gpsLocation->latitude = $gpsLocation->latitude ?? 0;
                $gpsLocation->longitude = $gpsLocation->longitude ?? 0;
            }
            $gpsLocation->save();
        }

        return redirect()->route('settings.index')->with('success', 'Settings updated successfully.');
    }
}
