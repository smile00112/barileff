<?php

namespace Webkul\ManagerApp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Authenticate manager and return Sanctum token.
     *
     * POST /manager/api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::guard('admin')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        /** @var \Webkul\User\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (! $admin->status) {
            Auth::guard('admin')->logout();

            return response()->json(['message' => 'Account is disabled.'], 403);
        }

        if (
            $admin->role->permission_type !== 'all'
            && ! $admin->hasPermission('manager.app.access')
        ) {
            Auth::guard('admin')->logout();

            return response()->json(['message' => 'Access denied: manager permission required.'], 403);
        }

        if (! $admin->isInventorySourceRestricted()) {
            Auth::guard('admin')->logout();

            return response()->json(['message' => 'Access denied: no warehouse assigned.'], 403);
        }

        // Revoke previous manager-app tokens to avoid token accumulation.
        $admin->tokens()->where('name', 'manager-app')->delete();

        $token = $admin->createToken('manager-app')->plainTextToken;

        Auth::guard('admin')->logout();

        return response()->json([
            'token' => $token,
            'admin' => [
                'id'    => $admin->id,
                'name'  => $admin->name,
                'email' => $admin->email,
            ],
        ]);
    }

    /**
     * Return authenticated manager info.
     *
     * GET /manager/api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $admin = $request->user('sanctum');

        $sources = $admin->inventorySources()
            ->select(['inventory_sources.id', 'inventory_sources.name', 'inventory_sources.code'])
            ->get();

        return response()->json([
            'id'                => $admin->id,
            'name'              => $admin->name,
            'email'             => $admin->email,
            'inventory_sources' => $sources,
        ]);
    }

    /**
     * Revoke the current token.
     *
     * POST /manager/api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
