<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Allow only active users with one of the given roles.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $hasRequiredRole = $roles === [] || in_array($user->role, $roles, true);

        if (! $user->isActive() || ! $hasRequiredRole) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
