<?php

namespace Webkul\ManagerApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * Requires:
     *  1. Valid Sanctum token (guard: sanctum)
     *  2. Admin has the manager.app.access ACL permission
     *  3. Admin has at least one inventory source assigned
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('sanctum');

        if (! $admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $admin->status) {
            return response()->json(['message' => 'Account is disabled.'], 403);
        }

        if (
            $admin->role->permission_type !== 'all'
            && ! $admin->hasPermission('manager.app.access')
        ) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $admin->isInventorySourceRestricted()) {
            return response()->json(['message' => 'No warehouse assigned.'], 403);
        }

        return $next($request);
    }
}
