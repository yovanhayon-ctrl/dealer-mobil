<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Hanya role yang disebut yang boleh lewat, mis. "role:owner" atau "role:mechanic"; selain itu 403.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($request->user()?->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
