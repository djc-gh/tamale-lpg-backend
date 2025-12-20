<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analyticsService)
    {
    }

    /**
     * Get analytics overview
     */
    public function overview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $days = $validated['days'] ?? 30;
        $startDate = now()->subDays($days);

        $data = $this->analyticsService->getOverview($startDate, now());

        return response()->json([
            'message' => 'Analytics overview retrieved successfully',
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => now()->toDateString(),
                'days' => $days,
            ],
            'data' => $data,
        ]);
    }

    /**
     * Get daily visits statistics
     */
    public function dailyStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : now()->subDays(30);
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : now();

        $data = $this->analyticsService->getVisitsByDay($startDate, $endDate);

        return response()->json([
            'message' => 'Daily statistics retrieved successfully',
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'data' => $data,
        ]);
    }

    /**
     * Get monthly visits statistics
     */
    public function monthlyStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : now()->subMonths(12);
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : now();

        $data = $this->analyticsService->getVisitsByMonth($startDate, $endDate);

        return response()->json([
            'message' => 'Monthly statistics retrieved successfully',
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'data' => $data,
        ]);
    }

    /**
     * Get top pages
     */
    public function topPages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $limit = $validated['limit'] ?? 10;
        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null;
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null;

        $data = $this->analyticsService->getTopPages($startDate, $endDate, $limit);

        return response()->json([
            'message' => 'Top pages retrieved successfully',
            'limit' => $limit,
            'data' => $data,
        ]);
    }

    /**
     * Get device type distribution
     */
    public function deviceDistribution(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null;
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null;

        $data = $this->analyticsService->getDeviceTypeDistribution($startDate, $endDate);

        return response()->json([
            'message' => 'Device distribution retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * Get browser distribution
     */
    public function browserDistribution(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null;
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null;

        $data = $this->analyticsService->getBrowserDistribution($startDate, $endDate);

        return response()->json([
            'message' => 'Browser distribution retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * Get OS distribution
     */
    public function osDistribution(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null;
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null;

        $data = $this->analyticsService->getOsDistribution($startDate, $endDate);

        return response()->json([
            'message' => 'OS distribution retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * Get returning vs new visitors
     */
    public function returningVsNew(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $days = $validated['days'] ?? 30;
        $startDate = now()->subDays($days);

        $data = $this->analyticsService->getReturningVsNewVisitors($startDate, now());

        return response()->json([
            'message' => 'Returning vs new visitors retrieved successfully',
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => now()->toDateString(),
                'days' => $days,
            ],
            'data' => $data,
        ]);
    }
}
