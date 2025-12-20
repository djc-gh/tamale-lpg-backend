# Find Nearest Available LPG Station - Backend Implementation

## Overview
Enhanced endpoint that finds the nearest **active and available** LPG stations to a user's location with intelligent sorting and availability filtering.

### Key Distinction
- **`is_active`** - Station operational status (open/closed permanently). Only active stations appear in search results.
- **`is_available`** - Stock availability status (has stock/no stock). All active stations shown, but available ones listed first.

## How It Works

### Sorting Logic
Stations are returned in this order:
1. **Available stations first** - Sorted by distance (closest first)
2. **Unavailable stations** - Sorted by distance (closest first)

**Only active stations are returned** - Deactivated stations never appear in results.

This ensures:
- ✅ Only operating stations are shown
- ✅ Closest available station is always first
- ✅ User can see unavailable alternatives if needed
- ✅ If furthest is the only available, it's still returned as the "closest available"

### Response Scenarios

#### Scenario 1: Available stations found
```json
{
  "message": "Nearby stations retrieved successfully",
  "data": [
    {
      "id": "...",
      "name": "Tamale Central Gas Station",
      "is_available": true,
      "distance_km": 0,
      ...
    },
    {
      "id": "...",
      "name": "Vittin Gas Point", 
      "is_available": true,
      "distance_km": 1.9,
      ...
    }
  ],
  "available_count": 2,
  "unavailable_count": 4,
  "radius_km": 10
}
```

#### Scenario 2: No available stations
```json
{
  "message": "No available LPG station near you is available",
  "data": [
    // All stations shown but all have is_available=false
  ],
  "available_count": 0,
  "unavailable_count": 6,
  "radius_km": 10,
  "note": "All nearby stations are currently unavailable"
}
```

#### Scenario 3: No stations in radius
```json
{
  "message": "No LPG stations found in the specified radius",
  "data": [],
  "available_count": 0,
  "unavailable_count": 0,
  "radius_km": 10
}
```

## API Endpoint

**Endpoint:** `POST /api/stations/nearby`

**URL:** `http://localhost:8001/api/stations/nearby`

**Method:** POST (or GET with query parameters)

### Request Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `latitude` | float | ✅ Yes | - | User's latitude |
| `longitude` | float | ✅ Yes | - | User's longitude |
| `radius` | integer | ❌ No | 5 | Search radius in kilometers (1-100) |
| `available_only` | boolean | ❌ No | false | Return only available stations |

### Request Example

```bash
curl -X POST "http://localhost:8001/api/stations/nearby" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": 9.4034,
    "longitude": -0.8424,
    "radius": 10,
    "available_only": false
  }'
```

### Response Fields

Each station object includes:
```json
{
  "id": "019b396b-dd20-7120-bedb-858432d894c3",
  "name": "Tamale Central Gas Station",
  "address": "123 Main Road, Tamale Central",
  "phone": "+233 24 123 4567",
  "email": "central@tamalelpg.com",
  "is_available": true,                    // ← Availability status
  "price_per_kg": "12.50",
  "operating_hours": "6:00 AM - 8:00 PM",
  "image": "https://...",
  "latitude": 9.4034,
  "longitude": -0.8424,
  "distance_km": 0,                        // ← Distance from user
  "created_at": "2025-12-20T01:42:08Z",
  "updated_at": "2025-12-20T10:41:46Z"
}
```

## Implementation Details

### Database Query
Uses Haversine formula to calculate great-circle distance:
```sql
(6371 * acos(
  cos(radians(latitude)) * cos(radians(user_latitude)) *
  cos(radians(user_longitude) - radians(longitude)) +
  sin(radians(latitude)) * sin(radians(user_latitude))
)) AS distance_km
```

### Code Flow

1. **StationController::nearbyStations()**
   - Validates latitude, longitude, radius, available_only
   - Calls StationService

2. **StationService::getNearbyStations()**
   - Uses `Station::withinRadius()` scope to calculate distances
   - Separates available and unavailable stations
   - Concatenates them (available first)
   - Returns sorted collection

3. **Station Model Scope::withinRadius()**
   - Calculates Haversine distance for all stations
   - Filters by radius
   - Orders by distance ascending

4. **StationResource**
   - Transforms Station model to JSON
   - Includes distance_km if available

## Frontend Integration

The Next.js frontend needs to:

1. **Get user's geolocation:**
   ```javascript
   navigator.geolocation.getCurrentPosition((position) => {
     const { latitude, longitude } = position.coords;
   });
   ```

2. **Call the API:**
   ```javascript
   fetch('/api/stations/nearby', {
     method: 'POST',
     headers: { 'Content-Type': 'application/json' },
     body: JSON.stringify({
       latitude: 9.4034,
       longitude: -0.8424,
       radius: 10
     })
   })
   ```

3. **Handle responses:**
   - If `available_count > 0`: Show available stations first
   - If `available_count === 0`: Show message "No available LPG station near you"
   - If `data.length === 0`: Show message "No stations found in radius"

## Test Cases

### Test 1: Stations Available
```bash
curl -X POST "http://localhost:8001/api/stations/nearby" \
  -H "Content-Type: application/json" \
  -d '{"latitude": 9.4034, "longitude": -0.8424, "radius": 10}'
```
✅ Returns available stations first

### Test 2: No Available Stations
```bash
# Set all to unavailable first
docker exec lpg_app php artisan tinker \
  --execute="\App\Models\Station::query()->update(['is_available' => false]);"

curl -X POST "http://localhost:8001/api/stations/nearby" \
  -H "Content-Type: application/json" \
  -d '{"latitude": 9.4034, "longitude": -0.8424, "radius": 10}'
```
✅ Returns message "No available LPG station near you is available"

### Test 3: Available Only Filter
```bash
curl -X POST "http://localhost:8001/api/stations/nearby" \
  -H "Content-Type: application/json" \
  -d '{"latitude": 9.4034, "longitude": -0.8424, "radius": 10, "available_only": true}'
```
✅ Returns only available stations

### Test 4: Small Radius
```bash
curl -X POST "http://localhost:8001/api/stations/nearby" \
  -H "Content-Type: application/json" \
  -d '{"latitude": 9.4034, "longitude": -0.8424, "radius": 1}'
```
✅ Returns only stations within 1km

## Performance Considerations

- ✅ Database indexes on `latitude` and `longitude` improve query speed
- ✅ Index on `is_active` for fast filtering
- ✅ Calculation happens in MySQL (not PHP)
- ✅ Collection sorting in memory (minimal impact)
- ✅ Typically < 100ms response time for 10km radius

## Manage Station Status (Admin Only)

### Toggle Station Active Status

**Endpoint:** `PATCH /api/stations/{id}/status`

**Purpose:** Activate or deactivate a station (e.g., when a station permanently closes or reopens)

**URL:** `http://localhost:8001/api/stations/{id}/status`

**Authentication:** Required (Admin only)

### Request

```bash
curl -X PATCH "http://localhost:8001/api/stations/019b396b-dd4d-70cc-be56-d2a4445b4ea5/status" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin_token}" \
  -d '{"is_active": false}'
```

**Request Body:**
```json
{
  "is_active": false  // or true to reactivate
}
```

**Response (Deactivated):**
```json
{
  "message": "Station deactivated successfully",
  "data": {
    "id": "019b396b-dd4d-70cc-be56-d2a4445b4ea5",
    "name": "Vittin Gas Point",
    "is_active": false,
    "is_available": true,
    ...
  }
}
```

**Response (Reactivated):**
```json
{
  "message": "Station activated successfully",
  "data": {
    "id": "019b396b-dd4d-70cc-be56-d2a4445b4ea5",
    "name": "Vittin Gas Point",
    "is_active": true,
    "is_available": true,
    ...
  }
}
```

### Authorization

Only admins can toggle station status:
- Returns 403 Forbidden if non-admin user attempts this

### Key Points

- **Deactivated stations are completely hidden** from the nearby stations search
- **All station data is preserved** - just hidden from public view
- **Can be reactivated at any time** - no data loss
- **Useful for**: Permanent closure, renovation, bankruptcy, relocation, etc.

## Files Modified

1. **database/migrations/2025_12_20_120000_add_is_active_to_stations_table.php**
   - New migration adding `is_active` boolean column
   - Adds index on `is_active` for performance

2. **app/Models/Station.php**
   - Added `is_active` to fillable attributes
   - Added `is_active` to casts (boolean)
   - Added `scopeActive()` scope
   - Updated `scopeWithinRadius()` to filter by `is_active = true`

3. **app/Http/Controllers/Api/StationController.php**
   - Added `toggleStatus()` method (admin only)
   - Validates `is_active` parameter

4. **app/Http/Resources/StationResource.php**
   - Added `is_active` field to response

5. **routes/api.php**
   - Added route: `PATCH /api/stations/{id}/status` (admin only)

## Future Enhancements

- [ ] Add price filtering (show cheapest nearby)
- [ ] Add opening hours filtering (show open now)
- [ ] Add manager name (who's managing the station)
- [ ] Add last availability update timestamp
- [ ] Add user reviews/ratings per station
- [ ] Cache results for 5 minutes
- [ ] Add estimated arrival time using Google Maps API

## Notes

- All distances are in kilometers
- Coordinates use WGS84 format (standard GPS)
- Radius is limited to 100km max
- `POST /api/stations/nearby` requires no authentication (public endpoint)
- `PATCH /api/stations/{id}/status` requires admin authentication
- Distance calculation uses great-circle distance (Haversine formula)
