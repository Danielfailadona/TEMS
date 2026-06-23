<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function showResetForm(): View
    {
        return view('auth.reset-password');
    }

    public function reset(Request $request, SupabaseAuthService $authService): RedirectResponse
    {
        $request->validate([
            'access_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $authService->updatePasswordViaSupabase($request->access_token, $request->password);

        return redirect()->route('login')->with('status', 'Your password has been reset. Please sign in.');
    }
}
