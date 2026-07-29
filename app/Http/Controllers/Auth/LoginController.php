<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function accountProcedure(SupabaseAuthService $authService): View|RedirectResponse
    {
        if (auth()->check()) {
            if (auth()->user()->isPending()) {
                auth()->user()->update(['is_online' => false]);
                $authService->logout();

                $showRegister = old('_action') === 'register' || request()->query('form') === 'register';

                return view('auth.account-procedure', compact('showRegister'));
            }

            return redirect()->route('dashboard');
        }

        $showRegister = old('_action') === 'register' || request()->query('form') === 'register';

        return view('auth.account-procedure', compact('showRegister'));
    }

    public function create(): RedirectResponse
    {
        if (auth()->check()) {
            if (auth()->user()->isPending()) {
                return redirect()->route('account.pending');
            }

            return redirect()->route('dashboard');
        }

        return redirect()->route('account.procedure');
    }

    public function store(Request $request, SupabaseAuthService $authService): RedirectResponse
    {
        if ($request->input('_action') === 'register') {
            return $this->storeRegister($request, $authService);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = $authService->attempt($credentials['email'], $credentials['password']);
        $authService->login($user, $request->boolean('remember'));

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'is_online' => true,
        ]);

        \App\Models\DeviceManager::create([
            'user_id' => $user->id,
            'device_name' => $request->header('User-Agent'),
            'device_type' => $this->detectDeviceType($request->header('User-Agent')),
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'session_id' => session()->getId(),
            'last_activity' => now(),
        ]);

        if ($user->isPending()) {
            return redirect()->route('account.pending');
        }

        if ($user->isRejected()) {
            auth()->logout();

            return redirect()->route('account.procedure')->withErrors(['email' => 'Your account has been rejected.']);
        }

        if ($user->isSuspended()) {
            auth()->logout();

            return redirect()->route('account.procedure')->withErrors(['email' => 'Your account has been suspended.']);
        }

        return redirect()->intended(route('dashboard'));
    }

    protected function detectDeviceType(?string $ua): string
    {
        if (! $ua) {
            return 'unknown';
        }
        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $ua)) {
            return 'mobile';
        }
        if (preg_match('/tablet|ipad/i', $ua)) {
            return 'tablet';
        }

        return 'desktop';
    }

    public function showRegister(): RedirectResponse
    {
        if (auth()->check()) {
            if (auth()->user()->isPending()) {
                return redirect()->route('account.pending');
            }

            return redirect()->route('dashboard');
        }

        return redirect()->route('account.procedure', ['form' => 'register']);
    }

    public function pending(): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('citizen.citation.lookup');
        }

        $user = auth()->user();

        if ($user->account_status === 'pending') {
            return view('auth.pending');
        }

        if (in_array($user->account_status, ['rejected', 'suspended'], true)) {
            auth()->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been '.$user->account_status.'.',
            ]);
        }

        return redirect()->route('login');
    }

    public function storeRegister(Request $request, SupabaseAuthService $authService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ]);

        $supabaseId = $authService->signupWithVerification(
            $validated['name'],
            $validated['email'],
            $validated['password'],
            $request->getSchemeAndHttpHost()
        );

        $user = User::create([
            'supabase_id' => $supabaseId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => Role::Enforcer,
            'is_active' => true,
            'account_status' => 'pending',
        ]);

        auth()->login($user);

        return redirect()->route('account.pending');
    }

    public function destroy(SupabaseAuthService $authService): RedirectResponse
    {
        $wasPending = auth()->check() && auth()->user()->isPending();

        if (auth()->check()) {
            auth()->user()->update(['is_online' => false]);
        }

        $authService->logout();

        $redirect = redirect()->route('account.procedure');

        if ($wasPending) {
            $redirect->withErrors([
                'email' => 'Your account is pending admin approval. You will be notified once approved.',
            ]);
        }

        return $redirect;
    }
}
