<?php

namespace App\Http\Middleware;

use App\Models\Visitor;
use App\Services\UserAgentParser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tracking for certain routes
        if ($this->shouldSkipTracking($request)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $response = $next($request);
        $responseTime = (int) ((microtime(true) - $startTime) * 1000); // Convert to ms

        // Check if this session has already been tracked
        $sessionKey = 'visitor_tracked';
        if ($request->session()->has($sessionKey)) {
            // Already tracked this session, skip
            return $response;
        }

        // Track the visitor (first time in this session)
        try {
            $userAgent = $request->userAgent() ?? '';
            $parser = new UserAgentParser($userAgent);

            // Get authenticated user ID (must be integer or null)
            $userId = null;
            if ($request->user() && is_numeric($request->user()->id)) {
                $userId = (int) $request->user()->id;
            }

            Visitor::create([
                'ip_address' => $this->getClientIp($request),
                'url' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'user_agent' => $userAgent,
                'device_type' => $parser->getDeviceType(),
                'browser' => $parser->getBrowser(),
                'os' => $parser->getOs(),
                'user_id' => $userId,
                'response_code' => $response->getStatusCode(),
                'response_time_ms' => $responseTime,
            ]);

            // Mark this session as tracked
            $request->session()->put($sessionKey, true);
        } catch (\Exception $e) {
            // Silently fail - don't break the request
            \Log::warning('Visitor tracking failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Get the client IP address
     */
    private function getClientIp(Request $request): string
    {
        // Check for IP from shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            // Handle multiple IPs (take the first)
            if (strpos($ip, ',') !== false) {
                $ips = array_map('trim', explode(',', $ip));
                $ip = $ips[0];
            }
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        return $ip;
    }

    /**
     * Determine if the request should be tracked
     */
    private function shouldSkipTracking(Request $request): bool
    {
        // Skip internal/utility endpoints
        $skipPaths = [
            'health',
            'up',
            'storage',
            'sanctum/csrf-cookie',
        ];

        foreach ($skipPaths as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return true;
            }
        }

        return false;
    }
}
