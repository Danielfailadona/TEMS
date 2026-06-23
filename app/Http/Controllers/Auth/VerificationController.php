<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function showNotice(): View
    {
        return view('auth.verify-email');
    }

    public function verify(Request $request, SupabaseAuthService $authService): RedirectResponse
    {
        $request->validate(['access_token' => 'required|string']);

        $supabaseId = $authService->verifyEmailViaSupabase($request->access_token);

        if (! $supabaseId) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Invalid or expired verification link.']);
        }

        $user = User::where('supabase_id', $supabaseId)->first();

        if ($user) {
            $user->update(['email_verified_at' => now()]);
        }

        return redirect()->route('login')
            ->with('status', 'Your email has been verified. You can now sign in.');
    }

    public function resend(Request $request, SupabaseAuthService $authService): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $authService->resendVerificationEmail($request->email);

        return back()->with('status', 'A new verification link has been sent to your email.');
    }
}
