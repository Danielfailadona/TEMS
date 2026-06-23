<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SupabaseAuthService
{
    public function attempt(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        if ($user && $user->password) {
            $localAttempt = $this->attemptViaLocal($email, $password, $user);

            if ($localAttempt) {
                return $localAttempt;
            }
        }

        if (config('supabase.url')) {
            return $this->attemptViaSupabase($email, $password, $user);
        }

        $localResult = $this->attemptViaLocal($email, $password, $user);

        if ($localResult) {
            return $localResult;
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    protected function attemptViaSupabase(string $email, string $password, ?User $user): User
    {
        try {
            $response = Http::withHeaders([
                'apikey' => config('supabase.anon_key'),
                'Content-Type' => 'application/json',
            ])->post(config('supabase.url').'/auth/v1/token?grant_type=password', [
                'email' => $email,
                'password' => $password,
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'email' => ['Unable to connect to authentication service. Please try again.'],
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        $data = $response->json();
        $supabaseId = $data['user']['id'] ?? null;

        if (! $user) {
            $user = User::create([
                'supabase_id' => $supabaseId,
                'name' => $data['user']['user_metadata']['name'] ?? $email,
                'email' => $email,
                'role' => 'enforcer',
                'is_active' => true,
                'account_status' => 'pending',
            ]);
        } else {
            $user->update([
                'supabase_id' => $supabaseId,
                'password' => Hash::make($password),
            ]);
        }

        return $user;
    }

    protected function attemptViaLocal(string $email, string $password, ?User $user): ?User
    {
        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function login(User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);
        request()->session()->regenerate();
    }

    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    public function sendPasswordResetLink(string $email): void
    {
        $redirectTo = route('password.reset', [], true);

        try {
            $response = Http::withHeaders([
                'apikey' => config('supabase.anon_key'),
                'Content-Type' => 'application/json',
            ])->post(config('supabase.url').'/auth/v1/recover', [
                'email' => $email,
                'redirect_to' => $redirectTo,
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'email' => ['Unable to connect to authentication service. Please try again.'],
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'email' => ['Failed to send password reset email. Please try again.'],
            ]);
        }
    }

    public function signupWithVerification(string $name, string $email, string $password, ?string $baseUrl = null): ?string
    {
        if (! config('supabase.url')) {
            return null;
        }

        $redirectTo = ($baseUrl ?? config('app.url')).'/email/verify/callback';

        try {
            $response = Http::withHeaders([
                'apikey' => config('supabase.anon_key'),
                'Content-Type' => 'application/json',
            ])->post(config('supabase.url').'/auth/v1/signup', [
                'email' => $email,
                'password' => $password,
                'data' => ['name' => $name],
                'redirect_to' => $redirectTo,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            Log::warning('Supabase signup failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'email' => $email,
            ]);

            return null;
        }

        return $response->json('id');
    }

    public function verifyEmailViaSupabase(string $accessToken): ?string
    {
        try {
            $response = Http::withHeaders([
                'apikey' => config('supabase.anon_key'),
                'Authorization' => 'Bearer '.$accessToken,
            ])->get(config('supabase.url').'/auth/v1/user');
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->json('id');
    }

    public function resendVerificationEmail(string $email): void
    {
        try {
            $response = Http::withHeaders([
                'apikey' => config('supabase.anon_key'),
                'Content-Type' => 'application/json',
            ])->post(config('supabase.url').'/auth/v1/resend', [
                'email' => $email,
                'type' => 'signup',
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'email' => ['Unable to connect to authentication service. Please try again.'],
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'email' => ['Failed to resend verification email. Please try again.'],
            ]);
        }
    }

    public function updatePasswordViaSupabase(string $accessToken, string $newPassword): void
    {
        try {
            $response = Http::withHeaders([
                'apikey' => config('supabase.anon_key'),
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->put(config('supabase.url').'/auth/v1/user', [
                'password' => $newPassword,
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'password' => ['Unable to connect to authentication service. Please try again.'],
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'password' => ['Failed to reset password. The link may have expired.'],
            ]);
        }
    }
}
