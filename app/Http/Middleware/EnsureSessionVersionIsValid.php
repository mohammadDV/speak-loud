<?php

namespace App\Http\Middleware;

use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionVersionIsValid
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $sessionVersion = AuthSession::currentVersion();
        $userVersion = (int) $user->session_version;

        // Legacy sessions created before session_version sync get adopted once.
        if ($sessionVersion === null) {
            AuthSession::syncVersion($user);

            return $next($request);
        }

        if ($sessionVersion !== $userVersion) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
