<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user();
        $schedules = collect();
        $weeklySchedule = [];

        if ($user->isRole(Role::Enforcer)) {
            $schedules = $user->enforcerSchedules()->get();

            for ($day = 0; $day <= 6; $day++) {
                $weeklySchedule[$day] = $schedules
                    ->filter(fn ($schedule) => $schedule->isDayActive($day))
                    ->map(fn ($schedule) => [
                        'start' => $schedule->start_time_short,
                        'end' => $schedule->end_time_short,
                    ])
                    ->values()
                    ->all();
            }
        }

        return view('profile.edit', [
            'user' => $user,
            'schedules' => $schedules,
            'weeklySchedule' => $weeklySchedule,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'current_password' => ['required_with:new_password', 'nullable', 'string'],
            'new_password' => ['nullable', 'string', Password::min(8)->letters()->mixedCase()->numbers(), 'confirmed'],
        ]);

        if ($request->filled('new_password')) {
            if (! Hash::check($request->input('current_password'), $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
            }
            $user->password = Hash::make($request->input('new_password'));
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        if ($request->hasFile('profile_photo')) {
            try {
                $path = \App\Services\SupabaseStorage::put('avatars/'.$request->file('profile_photo')->hashName(), $request->file('profile_photo'));
            } catch (\Throwable $e) {
                report($e);
                return back()->withErrors(['profile_photo' => 'Failed to upload profile photo. Please try again.'])->withInput();
            }
            $user->profile_photo_path = $path;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }
}
