<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! $user->roles()->exists()) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            if (! $user->hasPermissionTo(trim($permission))) {
                abort(403);
            }
        }

        return $next($request);
    }
}
