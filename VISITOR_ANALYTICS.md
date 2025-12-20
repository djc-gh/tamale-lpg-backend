# Visitor Analytics System - Complete Implementation

## Overview
A comprehensive visitor tracking and analytics system that records every website visit, captures device/browser information, and provides detailed analytics dashboards for admins.

## Components Implemented

### 1. **Visitors Table** (Migration: `2025_12_20_104627_create_visitors_table`)
Stores detailed information about each visit:
- `ip_address` - Client IP address (supports IPv4 & IPv6)
- `url` - Request URL
- `method` - HTTP method (GET, POST, etc.)
- `user_agent` - Browser user agent string
- `device_type` - mobile, tablet, or desktop
- `browser` - Chrome, Firefox, Safari, Edge, etc.
- `os` - Windows, macOS, Linux, iOS, Android
- `user_id` - Authenticated user (nullable)
- `response_code` - HTTP status code
- `response_time_ms` - Response time in milliseconds
- Indexed for fast queries: `ip_address`, `created_at`, `[ip_address, created_at]`, `user_id`

### 2. **Visitor Model** (`app/Models/Visitor.php`)
- Mass assignable attributes
- Relationship to User model
- DateTime casting for timestamps

### 3. **TrackVisitor Middleware** (`app/Http/Middleware/TrackVisitor.php`)
Automatically captures visitor data for every request:
- Skips tracking for: health checks, auth routes, storage endpoints
- Parses user agent to extract device type, browser, and OS
- Captures response code and response time
- Stores authenticated user ID if logged in
- Handles errors gracefully without breaking requests
- Registered globally for both web and API middleware groups

### 4. **UserAgentParser Service** (`app/Services/UserAgentParser.php`)
Parses user agent strings to extract:
- **Device Type**: Mobile, Tablet, or Desktop
- **Browser**: Chrome, Safari, Firefox, Edge, Opera, IE
- **Operating System**: Windows, macOS, Linux, iOS, Android

### 5. **AnalyticsService** (`app/Services/AnalyticsService.php`)
Business logic for calculating analytics:
- `getTotalUniqueVisitors()` - Count distinct IP addresses
- `getTotalVisits()` - Count all visits (including repeats)
- `getVisitsByDay()` - Daily stats with date range
- `getVisitsByMonth()` - Monthly stats with date range
- `getTopPages()` - Most visited pages with unique visitor count
- `getDeviceTypeDistribution()` - Device type breakdown
- `getBrowserDistribution()` - Browser usage stats
- `getOsDistribution()` - Operating system stats
- `getReturningVsNewVisitors()` - Identify repeat visitors
- `getOverview()` - Combined dashboard data

### 6. **AnalyticsController** (`app/Http/Controllers/Api/AnalyticsController.php`)
API endpoints (all admin-only):

#### Endpoints:
```
GET  /api/analytics/overview              - Quick dashboard snapshot
GET  /api/analytics/daily                 - Daily visit stats by date range
GET  /api/analytics/monthly               - Monthly visit stats
GET  /api/analytics/top-pages             - Most visited pages
GET  /api/analytics/devices               - Device type distribution
GET  /api/analytics/browsers              - Browser distribution
GET  /api/analytics/operating-systems     - OS distribution
GET  /api/analytics/returning-vs-new      - New vs returning visitors
```

## Query Parameters

### Overview
- `days=30` - Last N days (default: 30)

### Daily/Monthly Stats
- `start_date=2025-12-20` - Start date filter
- `end_date=2025-12-21` - End date filter

### Top Pages
- `limit=10` - Number of pages (default: 10, max: 100)
- `start_date` - Optional date filter
- `end_date` - Optional date filter

### Device/Browser/OS
- `start_date` - Optional date filter
- `end_date` - Optional date filter

### Returning vs New
- `days=30` - Period in days (default: 30)

## Example API Responses

### Overview Response
```json
{
  "message": "Analytics overview retrieved successfully",
  "period": {
    "start_date": "2025-11-20",
    "end_date": "2025-12-20",
    "days": "30"
  },
  "data": {
    "total_visits": 9,
    "total_unique_visitors": 1,
    "avg_visits_per_day": 0.3,
    "device_distribution": [
      {
        "device_type": "desktop",
        "visits": 9,
        "unique_visitors": 1
      }
    ],
    "top_pages": [
      {
        "url": "/api/stations",
        "visits": 8,
        "unique_visitors": 1
      }
    ]
  }
}
```

### Top Pages Response
```json
{
  "message": "Top pages retrieved successfully",
  "limit": 10,
  "data": [
    {
      "url": "/api/stations",
      "visits": 8,
      "unique_visitors": 1
    },
    {
      "url": "/api/stations/019b396b-dd20-7120-bedb-858432d894c3",
      "visits": 1,
      "unique_visitors": 1
    }
  ]
}
```

### Returning vs New Response
```json
{
  "message": "Returning vs new visitors retrieved successfully",
  "period": {
    "start_date": "2025-11-20",
    "end_date": "2025-12-20",
    "days": "30"
  },
  "data": {
    "new_visitors": 0,
    "returning_visitors": 1,
    "total_unique_visitors": 1,
    "return_rate": 100
  }
}
```

## Key Features

✅ **Comprehensive Tracking**
- Every request is automatically tracked
- IP address, device type, browser, OS captured
- Response time and HTTP status codes recorded
- Authenticated user tracking

✅ **Privacy Considerations**
- IP addresses stored (can be hashed if needed)
- User agent parsing doesn't require external API
- No cookies or tracking pixels needed
- Transparent logging

✅ **Performance**
- Indexed queries for fast analytics
- Configurable date ranges
- Middleware doesn't block requests (async-safe)
- Can handle high traffic

✅ **Analytics Capabilities**
- Unique vs total visitor distinction
- Device/browser/OS breakdown
- Time-based aggregation (daily, monthly)
- Top pages ranking
- Returning visitor identification
- Traffic trends

## Security

- ✅ All endpoints require admin authentication
- ✅ Uses Laravel Sanctum token validation
- ✅ No sensitive user data exposed
- ✅ Error handling doesn't expose system paths

## Usage Examples

### Get Dashboard Overview
```bash
curl -X GET "http://localhost:8001/api/analytics/overview?days=30" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Get Daily Stats for Date Range
```bash
curl -X GET "http://localhost:8001/api/analytics/daily?start_date=2025-12-15&end_date=2025-12-20" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Get Top 5 Pages
```bash
curl -X GET "http://localhost:8001/api/analytics/top-pages?limit=5" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Get Device Distribution
```bash
curl -X GET "http://localhost:8001/api/analytics/devices" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

## Database Queries

### Check Visitor Count
```php
\App\Models\Visitor::count(); // Total visits
\App\Models\Visitor::distinct('ip_address')->count(); // Unique IPs
```

### Get Visitors from Last 24 Hours
```php
\App\Models\Visitor::where('created_at', '>=', now()->subDay())->get();
```

### Get Most Popular Page
```php
\App\Models\Visitor::selectRaw('url, COUNT(*) as visits')
  ->groupBy('url')
  ->orderByDesc('visits')
  ->limit(1)
  ->get();
```

## Future Enhancements

- [ ] IP Geolocation (country, city) using MaxMind GeoIP2
- [ ] Session tracking (group multiple visits by IP)
- [ ] Referrer tracking (from which URL visitors came)
- [ ] Click heatmap integration
- [ ] Automated report generation
- [ ] Data export to CSV/PDF
- [ ] Real-time visitor dashboard with WebSockets
- [ ] Visitor flow/funnel analysis
- [ ] Bounce rate calculation
- [ ] Data retention policies (auto-delete old data)
- [ ] IP anonymization for GDPR compliance
- [ ] Custom date range reports

## Notes

- Middleware is registered for both `web` and `api` middleware groups
- Database indexes ensure O(log n) query performance
- All datetime values use UTC timezone
- Response tracking is optional and gracefully fails
- Visitor data is separate from authentication/user data
