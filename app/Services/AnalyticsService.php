<?php

namespace App\Services;

use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AnalyticsService
{
    /**
     * Get total unique visitors
     */
    public function getTotalUniqueVisitors(?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $query = Visitor::select('ip_address')->distinct();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->count();
    }

    /**
     * Get total visits (including repeats)
     */
    public function getTotalVisits(?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $query = Visitor::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->count();
    }

    /**
     * Get visits by day
     */
    public function getVisitsByDay(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $visits = Visitor::selectRaw('DATE(created_at) as date, COUNT(*) as total_visits, COUNT(DISTINCT ip_address) as unique_visitors')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $visits->map(function ($item) {
            return [
                'date' => $item->date,
                'total_visits' => $item->total_visits,
                'unique_visitors' => $item->unique_visitors,
            ];
        })->toArray();
    }

    /**
     * Get visits by month
     */
    public function getVisitsByMonth(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonths(12);
        $endDate = $endDate ?? now();

        $visits = Visitor::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total_visits, COUNT(DISTINCT ip_address) as unique_visitors")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get();

        return $visits->map(function ($item) {
            return [
                'month' => $item->month,
                'total_visits' => $item->total_visits,
                'unique_visitors' => $item->unique_visitors,
            ];
        })->toArray();
    }

    /**
     * Get top pages
     */
    public function getTopPages(?Carbon $startDate = null, ?Carbon $endDate = null, int $limit = 10): array
    {
        $query = Visitor::selectRaw('url, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $pages = $query->groupBy('url')
            ->orderByDesc('visits')
            ->limit($limit)
            ->get();

        return $pages->map(function ($item) {
            return [
                'url' => $item->url,
                'visits' => $item->visits,
                'unique_visitors' => $item->unique_visitors,
            ];
        })->toArray();
    }

    /**
     * Get device type distribution
     */
    public function getDeviceTypeDistribution(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Visitor::selectRaw('device_type, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $devices = $query->whereNotNull('device_type')
            ->groupBy('device_type')
            ->orderByDesc('visits')
            ->get();

        return $devices->map(function ($item) {
            return [
                'device_type' => $item->device_type,
                'visits' => $item->visits,
                'unique_visitors' => $item->unique_visitors,
            ];
        })->toArray();
    }

    /**
     * Get browser distribution
     */
    public function getBrowserDistribution(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Visitor::selectRaw('browser, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $browsers = $query->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('visits')
            ->get();

        return $browsers->map(function ($item) {
            return [
                'browser' => $item->browser,
                'visits' => $item->visits,
                'unique_visitors' => $item->unique_visitors,
            ];
        })->toArray();
    }

    /**
     * Get OS distribution
     */
    public function getOsDistribution(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Visitor::selectRaw('os, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $operatingSystems = $query->whereNotNull('os')
            ->groupBy('os')
            ->orderByDesc('visits')
            ->get();

        return $operatingSystems->map(function ($item) {
            return [
                'os' => $item->os,
                'visits' => $item->visits,
                'unique_visitors' => $item->unique_visitors,
            ];
        })->toArray();
    }

    /**
     * Get overview statistics
     */
    public function getOverview(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        return [
            'total_visits' => $this->getTotalVisits($startDate, $endDate),
            'total_unique_visitors' => $this->getTotalUniqueVisitors($startDate, $endDate),
            'avg_visits_per_day' => round($this->getTotalVisits($startDate, $endDate) / max(1, now()->diffInDays($startDate)), 2),
            'device_distribution' => $this->getDeviceTypeDistribution($startDate, $endDate),
            'top_pages' => $this->getTopPages($startDate, $endDate, 5),
        ];
    }

    /**
     * Get returning vs new visitors
     */
    public function getReturningVsNewVisitors(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        // Get all IPs that visited during the period
        $visitorIps = Visitor::selectRaw('ip_address, COUNT(*) as visit_count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('ip_address')
            ->get();

        $newVisitors = 0;
        $returningVisitors = 0;

        foreach ($visitorIps as $visitor) {
            if ($visitor->visit_count === 1) {
                $newVisitors++;
            } else {
                $returningVisitors++;
            }
        }

        return [
            'new_visitors' => $newVisitors,
            'returning_visitors' => $returningVisitors,
            'total_unique_visitors' => $newVisitors + $returningVisitors,
            'return_rate' => $newVisitors + $returningVisitors > 0 ? round(($returningVisitors / ($newVisitors + $returningVisitors)) * 100, 2) : 0,
        ];
    }
}
