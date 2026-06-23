<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->account_status === 'pending') {
            return redirect()->route('account.pending');
        }

        if ($user && in_array($user->account_status, ['rejected', 'suspended'], true)) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'Your account has been '.$user->account_status.'.']);
        }

        return $next($request);
    }
}
