<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsStationManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if user is station manager
        if (! $request->user()->isStationManager()) {
            return response()->json([
                'message' => 'Unauthorized - Station manager access required',
            ], 403);
        }

        // For station-specific operations, verify the manager owns the station
        $stationId = $request->route('id');
        if ($stationId && ! $request->user()->canManageStation($stationId)) {
            return response()->json([
                'message' => 'Unauthorized - You can only manage your assigned station',
            ], 403);
        }

        return $next($request);
    }
}
