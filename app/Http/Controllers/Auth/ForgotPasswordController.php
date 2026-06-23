<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request, SupabaseAuthService $authService): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $authService->sendPasswordResetLink($request->email);

        return back()->with('status', 'If that email exists in our system, a password reset link has been sent.');
    }
}
