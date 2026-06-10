<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     * Only allow users with 'admin' role to proceed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'error' => 'Accès refusé. Seuls les administrateurs peuvent accéder à cette ressource.'
            ], 403);
        }

        return $next($request);
    }
}
