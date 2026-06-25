<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || $user->is_suspended || ! $user->isAnyRole($roles)) {
            abort(403, 'This action is not allowed for your role.');
        }

        return $next($request);
    }
}
