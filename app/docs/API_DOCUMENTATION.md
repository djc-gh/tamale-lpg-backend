# LPG Tamale API Documentation

## Overview
RESTful API for managing LPG gas stations in Tamale. The API provides endpoints for authentication, station management, geolocation services, and analytics.

**Status:** Production Ready ✅  
**Last Updated:** December 20, 2025

---

## Base URL
```
http://localhost:8001/api
```

---

## Authentication

All protected endpoints require Bearer token authentication via Laravel Sanctum.

### Token Format
```
{user_id}|{random_base64_string}
Example: 019b39d5-ff0e-7288-9503-208e353dfda3|gM5xK2pL9qR8wE1tY3uI4oP5sA6dF7gH8jK9lM0nB1cV2xZ3y
```

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

## User Roles & Permissions

| Role | Description | Permissions |
|------|-------------|------------|
| `user` | Regular user | View public data, find nearby stations |
| `admin` | Administrator | Manage all stations, users, managers, analytics |
| `station_manager` | Station manager | Manage only assigned station (availability, status) |

---

## Endpoints

### Authentication

#### 1. Register User
- **POST** `/auth/register`
- **Access**: Public ✅
- **Request Body**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```
- **Response** (200):
```json
{
  "message": "User registered successfully",
  "data": {
    "id": "019b39d5-ff0e-7288-9503-208e353dfda3",
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user",
    "is_active": true,
    "created_at": "2025-12-20T10:00:00.000000Z",
    "updated_at": "2025-12-20T10:00:00.000000Z"
  },
  "token": "019b39d5-ff0e-7288-9503-208e353dfda3|gM5xK2pL9qR8wE1tY3uI4oP5sA6dF7gH8jK9lM0nB1cV2xZ3y",
  "token_type": "Bearer"
}
```
- **Validation Rules**:
  - `name`: required, string, max 255
  - `email`: required, email, unique
  - `password`: required, min 8, confirmed

#### 2. Login User
- **POST** `/auth/login`
- **Access**: Public ✅
- **Request Body**:
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```
- **Response** (200):
```json
{
  "message": "Login successful",
  "data": {
    "id": "019b39d5-ff0e-7288-9503-208e353dfda3",
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user",
    "is_active": true,
    "created_at": "2025-12-20T10:00:00.000000Z",
    "updated_at": "2025-12-20T10:00:00.000000Z"
  },
  "token": "019b39d5-ff0e-7288-9503-208e353dfda3|gM5xK2pL9qR8wE1tY3uI4oP5sA6dF7gH8jK9lM0nB1cV2xZ3y",
  "token_type": "Bearer"
}
```
- **Error Response** (401):
```json
{
  "message": "Invalid credentials"
}
```

#### 3. Get Current User
- **GET** `/auth/me`
- **Access**: Protected 🔒 (any authenticated user)
- **Response** (200):
```json
{
  "data": {
    "id": "019b39d5-ff0e-7288-9503-208e353dfda3",
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user",
    "is_active": true,
    "created_at": "2025-12-20T10:00:00.000000Z",
    "updated_at": "2025-12-20T10:00:00.000000Z"
  }
}
```

#### 4. Logout User
- **POST** `/auth/logout`
- **Access**: Protected 🔒 (any authenticated user)
- **Response** (200):
```json
{
  "message": "Logged out successfully"
}
```

#### 5. Refresh Token
- **POST** `/auth/refresh`
- **Access**: Protected 🔒 (any authenticated user)
- **Response** (200):
```json
{
  "message": "Token refreshed successfully",
  "token": "019b39d5-ff0e-7288-9503-208e353dfda3|nE7qW2pL9qR8wE1tY3uI4oP5sA6dF7gH8jK9lM0nB1cV2xZ3y",
  "token_type": "Bearer"
}
```

---

### Stations

#### 1. List All Stations
- **GET** `/stations`
- **Access**: Public ✅
- **Query Parameters**:
  - `per_page` (int, default: 15): Items per page (1-100)
  - `available` (boolean): Filter by availability status
  - `assigned` (boolean): Filter by assignment status (manager only)
  - `latitude` (float): User latitude for distance calculation
  - `longitude` (float): User longitude for distance calculation
  - `radius` (int): Search radius in kilometers
  - `sort_by` (string): Sort by 'name', 'price_per_kg', 'distance'

- **Example Request**:
```
GET /stations?per_page=20&available=true&sort_by=price_per_kg
```

- **Response** (200):
```json
{
  "data": [
    {
      "id": "019b396b-dd20-7120-bedb-858432d894c3",
      "name": "Tamale Central Gas Station",
      "address": "123 Main Road, Tamale Central",
      "phone": "+233 24 123 4567",
      "email": "central@tamalelpg.com",
      "is_available": true,
      "is_active": true,
      "price_per_kg": "12.50",
      "operating_hours": "6:00 AM - 8:00 PM",
      "image": "https://...",
      "latitude": 9.4034,
      "longitude": -0.8424,
      "distance_km": null,
      "created_at": "2025-12-20T01:42:08.000000Z",
      "updated_at": "2025-12-20T10:41:46.000000Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

#### 2. Get Single Station
- **GET** `/stations/{id}`
- **Access**: Public ✅
- **Response** (200): Single station object
- **Error** (404): Station not found

#### 3. Find Nearby Stations ⭐ (Main Feature)
- **POST** `/stations/nearby`
- **Access**: Public ✅
- **Description**: Find active stations near user's location with smart sorting
- **Request Body**:
```json
{
  "latitude": 9.4034,
  "longitude": -0.8424,
  "radius": 10,
  "available_only": false
}
```
- **Response** (200):
```json
{
  "message": "Nearby stations retrieved successfully",
  "data": [
    {
      "id": "019b396b-dd20-7120-bedb-858432d894c3",
      "name": "Tamale Central Gas Station",
      "is_available": true,
      "is_active": true,
      "distance_km": 0,
      ...
    },
    {
      "id": "019b396b-dd4d-70cc-be56-d2a4445b4ea5",
      "name": "Vittin Gas Point",
      "is_available": false,
      "is_active": true,
      "distance_km": 1.89,
      ...
    }
  ],
  "available_count": 1,
  "unavailable_count": 1,
  "radius_km": 10
}
```
- **Note**: Only active stations (is_active=true) are returned. Available stations are listed first, then unavailable (both sorted by distance).

#### 4. Create Station
- **POST** `/stations`
- **Access**: Protected 🔒 (Admin only)
- **Request Body**:
```json
{
  "name": "New Gas Station",
  "address": "456 Gas Avenue, Tamale",
  "phone": "+233 24 999 8888",
  "email": "newstation@tamalelpg.com",
  "latitude": 9.4100,
  "longitude": -0.8500,
  "operating_hours": "6:00 AM - 9:00 PM",
  "price_per_kg": "12.75",
  "image": "https://...",
  "is_available": true,
  "is_active": true
}
```
- **Response** (201): Created station object
- **Validation Rules**:
  - `name`: required, string, max 255
  - `email`: required, email, unique
  - `latitude`: required, numeric
  - `longitude`: required, numeric

#### 5. Update Station
- **PUT** `/stations/{id}`
- **Access**: Protected 🔒 (Admin only)
- **Request Body**: Same as create (all fields optional except ID)
- **Response** (200): Updated station object

#### 6. Delete Station
- **DELETE** `/stations/{id}`
- **Access**: Protected 🔒 (Admin only)
- **Response** (204): No content

#### 7. Update Station Availability
- **PATCH** `/stations/{id}/availability`
- **Access**: Protected 🔒 (Admin + Station Manager)
- **Description**: Toggle if station has stock
- **Request Body**:
```json
{
  "is_available": false
}
```
- **Response** (200):
```json
{
  "id": "019b396b-dd20-7120-bedb-858432d894c3",
  "name": "Tamale Central Gas Station",
  "is_available": false,
  "is_active": true,
  ...
}
```
- **Authorization**:
  - ✅ Admin can toggle any station
  - ✅ Manager can toggle only their assigned station
  - ❌ Other managers get 403 Forbidden

#### 8. Toggle Station Active Status
- **PATCH** `/stations/{id}/status`
- **Access**: Protected 🔒 (Admin + Station Manager)
- **Description**: Toggle if station is operating (permanent closure/reopening)
- **Request Body**:
```json
{
  "is_active": false
}
```
- **Response** (200):
```json
{
  "message": "Station deactivated successfully",
  "data": {
    "id": "019b396b-dd20-7120-bedb-858432d894c3",
    "name": "Tamale Central Gas Station",
    "is_active": false,
    ...
  }
}
```
- **Authorization**:
  - ✅ Admin can toggle any station
  - ✅ Manager can toggle only their assigned station
  - ❌ Other managers get 403 Forbidden
- **Impact**: Deactivated stations are hidden from all nearby searches

#### 9. Get Station Price History
- **GET** `/stations/{stationId}/price-history`
- **Access**: Public ✅
- **Query Parameters**:
  - `limit` (int): Number of records
  - `offset` (int): Pagination offset
- **Response** (200):
```json
[
  {
    "id": "019b396c-dd53-11f0-a85a-727d03ec5208",
    "station_id": "019b396b-dd20-7120-bedb-858432d894c3",
    "price_per_kg": "12.50",
    "effective_from": "2025-12-20T10:00:00.000000Z",
    "updated_by": "Admin User",
    "created_at": "2025-12-20T10:00:00.000000Z"
  }
]
```

---

### Station Manager Assignment

#### 1. Assign Manager to Station
- **POST** `/stations/{stationId}/assign-manager`
- **Access**: Protected 🔒 (Admin only)
- **Request Body**:
```json
{
  "manager_id": "306e93c1-dd53-11f0-a85a-727d03ec5208"
}
```
- **Response** (200): Assignment details

#### 2. Get Current Manager
- **GET** `/stations/{stationId}/manager`
- **Access**: Protected 🔒 (Admin only)
- **Response** (200): Current manager or null

#### 3. Remove Manager from Station
- **DELETE** `/stations/{stationId}/remove-manager`
- **Access**: Protected 🔒 (Admin only)
- **Response** (200): Success message

#### 4. Get Manager Assignment History
- **GET** `/stations/{stationId}/manager-history`
- **Access**: Protected 🔒 (Admin only)
- **Response** (200): Array of past assignments

---

### Station Managers (CRUD)

#### 1. List All Managers
- **GET** `/managers`
- **Access**: Protected 🔒 (Admin only)
- **Query Parameters**: `per_page`, `page`
- **Response** (200):
```json
{
  "message": "Station managers retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "306e93c1-dd53-11f0-a85a-727d03ec5208",
        "name": "Infin LGP Station",
        "email": "infin@lpgtamale.com",
        "is_active": true,
        "created_at": "2025-12-20T02:37:05.000000Z",
        "assignment_status": {
          "is_assigned": true,
          "station_id": "019b396b-dd20-7120-bedb-858432d894c3",
          "station_name": "Tamale Central Gas Station",
          "assigned_at": "2025-12-20T10:31:45.000000Z",
          "assigned_by": "Admin User"
        }
      }
    ],
    "meta": { ... }
  }
}
```

#### 2. Get Active Managers Only
- **GET** `/managers/active`
- **Access**: Protected 🔒 (Admin only)
- **Response** (200): Same as list but only active managers

#### 3. Create Manager
- **POST** `/managers`
- **Access**: Protected 🔒 (Admin only)
- **Request Body**:
```json
{
  "name": "New Manager",
  "email": "newmanager@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### 4. Get Single Manager
- **GET** `/managers/{id}`
- **Access**: Protected 🔒 (Admin only)

#### 5. Update Manager
- **PUT** `/managers/{id}`
- **Access**: Protected 🔒 (Admin only)

#### 6. Delete Manager
- **DELETE** `/managers/{id}`
- **Access**: Protected 🔒 (Admin only)

---

### Analytics (Admin Only)

#### 1. Analytics Overview
- **GET** `/analytics/overview`
- **Access**: Protected 🔒 (Admin only)
- **Response** (200):
```json
{
  "total_visits": 150,
  "unique_visitors": 42,
  "page_views": 245,
  "average_response_time": 85,
  "device_breakdown": {
    "mobile": 95,
    "tablet": 20,
    "desktop": 35
  },
  "top_page": "/api/stations/nearby",
  "top_page_views": 65
}
```

#### 2. Daily Statistics
- **GET** `/analytics/daily`
- **Query Parameters**: `start_date`, `end_date` (optional)
- **Response** (200):
```json
{
  "data": [
    {
      "date": "2025-12-20",
      "visits": 45,
      "unique_visitors": 12
    }
  ]
}
```

#### 3. Monthly Statistics
- **GET** `/analytics/monthly`
- **Query Parameters**: `start_month`, `end_month` (optional)
- **Response** (200): Visits aggregated by month

#### 4. Top Pages
- **GET** `/analytics/top-pages`
- **Response** (200): Most visited pages

#### 5. Device Distribution
- **GET** `/analytics/devices`
- **Response** (200): Breakdown by device type

#### 6. Browser Distribution
- **GET** `/analytics/browsers`
- **Response** (200): Breakdown by browser

#### 7. Operating System Distribution
- **GET** `/analytics/operating-systems`
- **Response** (200): Breakdown by OS

#### 8. Returning vs New Users
- **GET** `/analytics/returning-vs-new`
- **Response** (200):
```json
{
  "total_visits": 150,
  "unique_visitors": 42,
  "return_rate": "28.57%"
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated"
}
```

### Forbidden (403)
```json
{
  "message": "Unauthorized - You can only manage your assigned station"
}
```

### Not Found (404)
```json
{
  "message": "Not found"
}
```

### Server Error (500)
```json
{
  "message": "Internal Server Error"
}
```

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Success |
| 201 | Created - Resource created |
| 204 | No Content - Success, no response body |
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Missing or invalid token |
| 403 | Forbidden - No permission |
| 404 | Not Found - Resource doesn't exist |
| 422 | Validation Error - Invalid data |
| 500 | Server Error - Internal error |

---

## Key Features

✅ **UUID Primary Keys** - All IDs are UUIDs for better security  
✅ **Token-Based Auth** - Laravel Sanctum with secure tokens  
✅ **Smart Station Search** - Haversine formula for accurate distance  
✅ **Availability Sorting** - Available stations shown first  
✅ **Role-Based Access** - Admin, Manager, User roles  
✅ **Analytics** - Track visitors, page views, devices  
✅ **Manager Assignment** - Assign managers to stations  
✅ **Price History** - Track station price changes  
✅ **Visitor Tracking** - IP, device, browser, OS tracking  

---

## Test Credentials

```
Admin:
  Email: admin@example.com
  Password: password

Station Manager (Tamale Central):
  Email: infin@lpgtamale.com
  Password: password
```

---

## Field Definitions

### is_available vs is_active

| Field | Meaning | Impact | Use Case |
|-------|---------|--------|----------|
| `is_available` | Has stock | Shown in search | Temporarily out of stock |
| `is_active` | Is operating | Hidden from search | Permanent closure/relocation |

---

## Best Practices

1. **Always include token** in Authorization header for protected endpoints
2. **Use POST for nearby stations** even though it's read-only (to pass coordinates in body)
3. **Handle 401 responses** by redirecting to login
4. **Store tokens securely** in HttpOnly cookies (production)
5. **Implement token refresh** before expiration
6. **Validate on frontend** before sending to API
7. **Use HTTPS** in production
8. **Include proper error handling** for all requests

---

## Pagination

List endpoints return paginated results:
```json
{
  "data": [ ... ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "...",
    "per_page": 15,
    "to": 15,
    "total": 42
  }
}
```

---

## Rate Limiting

No rate limiting currently enforced. In production, consider:
- 5 login attempts per IP per 15 minutes
- 100 API requests per minute per token

---

## CORS Configuration

Currently configured for `localhost:3000` (Next.js default).  
Update in `.env` for production:
```
FRONTEND_URL=https://yourdomain.com
```

---

## Database

- **Engine**: MySQL 8.0
- **ORM**: Eloquent (Laravel)
- **Migrations**: All tables auto-migrated on deploy
- **Indexes**: Optimized for location and availability queries

---

## Related Documentation

- [Authentication API](AUTHENTICATION_API.md)
- [Nearest Station API](NEAREST_STATION_API.md)
- [Frontend Integration Guide](FRONTEND_INTEGRATION_GUIDE.md)
- [API Quick Reference](API_QUICK_REFERENCE.md)

---

## Support

For API issues:
1. Check the error response message
2. Verify request format and parameters
3. Check authentication token is valid
4. Review endpoint documentation above
