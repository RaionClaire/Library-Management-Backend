<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $user = $request->user();

        if (!$user->role) {
            return response()->json([
                'message' => 'User has no role assigned.'
            ], 403);
        }

        if (!in_array($user->role->name, $roles)) {
            return response()->json([
                'message' => 'Unauthorized. Insufficient permissions.'
            ], 403);
        }

        return $next($request);
    }
}
