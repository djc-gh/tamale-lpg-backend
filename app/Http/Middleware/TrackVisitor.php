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

        // Track the visitor asynchronously (don't block the response)
        try {
            $userAgent = $request->userAgent() ?? '';
            $parser = new UserAgentParser($userAgent);

            Visitor::create([
                'ip_address' => $this->getClientIp($request),
                'url' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'user_agent' => $userAgent,
                'device_type' => $parser->getDeviceType(),
                'browser' => $parser->getBrowser(),
                'os' => $parser->getOs(),
                'user_id' => $request->user()?->id,
                'response_code' => $response->getStatusCode(),
                'response_time_ms' => $responseTime,
            ]);
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
        $skipPaths = [
            'health',
            'up',
            'api/auth',
            'storage',
        ];

        foreach ($skipPaths as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return true;
            }
        }

        return false;
    }
}
