<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleWithMessage
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 5, int $decayMinutes = 5): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = (int) ceil($seconds / 60);

            return redirect()->route('account.procedure')
                ->withInput($request->only('email'))
                ->withErrors([
                    'throttle' => "Too many login attempts. Please try again in {$minutes} minute(s).",
                ]);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            'login|' . $request->ip()
        );
    }
}