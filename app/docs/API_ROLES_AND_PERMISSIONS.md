# LPG Tamale API - Roles and Permissions

This document clarifies the roles and permissions in the LPG Tamale API based on the ROLES_AND_PERMISSIONS.md specification.

---

## User Roles

### ADMIN
- **Purpose:** System-wide management of the entire LPG network
- **Station Assignment:** None (NULL in database)
- **Account Creation:** Only created by other admins (NOT via public registration)
- **Primary Functions:**
  - Full station management (create, read, update, delete)
  - User account management
  - System monitoring and analytics

### STATION MANAGER (role: 'station')
- **Purpose:** Manage a single assigned LPG station
- **Station Assignment:** Must be assigned to exactly one station
- **Account Creation:** Via public registration (defaults to this role)
- **Primary Functions:**
  - Toggle their assigned station's availability status only
  - View their own station details
  - Cannot modify station information (name, address, pricing, etc.)

---

## API Endpoints by Role

### Public Endpoints (No Authentication Required)

#### Station Listing & Search
```
GET /api/stations
GET /api/stations/{id}
POST /api/stations/nearby
GET /api/stations/{id}/price-history
```
- Anyone can view station information and search nearby stations
- Returns only public station data

#### Authentication
```
POST /api/auth/register
POST /api/auth/login
```
- Public registration creates a new STATION MANAGER account
- Defaults to role: 'station' with no station assignment initially

---

### Protected Endpoints (Authentication Required)

#### For All Authenticated Users
```
GET /api/auth/me
POST /api/auth/logout
POST /api/auth/refresh
```
- Returns current user information including role and station_id

---

### Admin-Only Endpoints

**Middleware:** `admin`

#### Station Management (CRUD)
```
POST /api/stations
PUT /api/stations/{id}
DELETE /api/stations/{id}
```
- Create new stations
- Update any station (name, address, pricing, location, image, etc.)
- Delete stations from the system

#### Station Availability (Any Station)
```
PATCH /api/stations/{id}/availability
```
- Admins can toggle availability for ANY station in the system
- Request body: `{ "is_available": true|false }`

---

### Station Manager-Only Endpoints

**Middleware:** `station.manager`

#### Station Availability (Own Station Only)
```
PATCH /api/stations/{id}/availability
```
- Station managers can ONLY toggle availability for their assigned station
- Attempting to update another station returns 403 Forbidden
- Request body: `{ "is_available": true|false }`
- Controller validates: `canManageStation()` returns false → 403 error

---

## Authentication Flow

### Successful Login Response

Both admin and station manager logins return the same structure with role information:

```json
{
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Manager",
    "email": "john@tamalelpg.com",
    "role": "admin|station",
    "station_id": "550e8400-e29b-41d4-a716-446655440001",
    "is_active": true,
    "created_at": "2025-12-20T10:30:00Z",
    "updated_at": "2025-12-20T10:30:00Z"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Key Fields for Frontend:**
- `role` - Determines what features to show (admin vs station)
- `station_id` - For station managers, the ID of their assigned station
- `is_active` - Check before allowing access; redirect disabled accounts
- `token` - Use for Authorization header in subsequent requests

---

## Authorization Rules

### Route Access

| Endpoint | Public | Admin | Station Manager | Notes |
|----------|--------|-------|-----------------|-------|
| POST /api/auth/register | ✅ | ✅ | ✅ | Creates 'station' role account |
| POST /api/auth/login | ✅ | ✅ | ✅ | Returns user data with role |
| GET /api/stations | ✅ | ✅ | ✅ | All see same list |
| GET /api/stations/{id} | ✅ | ✅ | ✅ | All see same details |
| POST /api/stations | ❌ | ✅ | ❌ | Admin only |
| PUT /api/stations/{id} | ❌ | ✅ | ❌ | Admin only |
| DELETE /api/stations/{id} | ❌ | ✅ | ❌ | Admin only |
| PATCH /api/stations/{id}/availability | ❌ | ✅* | ✅** | *Admin any station, **Manager own only |
| GET /api/auth/me | ❌ | ✅ | ✅ | Returns current user info |
| POST /api/auth/logout | ❌ | ✅ | ✅ | Revokes token |
| POST /api/auth/refresh | ❌ | ✅ | ✅ | Issues new token |

---

## Frontend Implementation Guide

### 1. After Login - Check Role and Redirect

```javascript
async function handleLogin(email, password) {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  
  const { user, token } = await response.json();
  
  // Store token and user info
  localStorage.setItem('token', token);
  localStorage.setItem('user', JSON.stringify(user));
  
  // Check if account is active
  if (!user.is_active) {
    window.location.href = '/account-disabled';
    return;
  }
  
  // Route based on role
  if (user.role === 'admin') {
    window.location.href = '/admin/dashboard';
  } else if (user.role === 'station') {
    window.location.href = `/station/dashboard?station=${user.station_id}`;
  }
}
```

### 2. Permission Checking Utilities

```javascript
const user = JSON.parse(localStorage.getItem('user'));

function canCreateStation() {
  return user?.role === 'admin';
}

function canUpdateStation(stationId) {
  if (user?.role === 'admin') return true;
  if (user?.role === 'station') return user.station_id === stationId;
  return false;
}

function canToggleAvailability(stationId) {
  if (user?.role === 'admin') return true;
  if (user?.role === 'station') return user.station_id === stationId;
  return false;
}
```

### 3. Protected Routes - Show/Hide Based on Role

```javascript
// Show admin section only to admins
{user?.role === 'admin' && (
  <button onClick={openAddStationModal}>+ Add Station</button>
)}

// Show availability toggle only if user can manage this station
{canToggleAvailability(station.id) && (
  <AvailabilityToggle station={station} />
)}

// Disable edit/delete buttons for non-admin
<button disabled={user?.role !== 'admin'}>Edit</button>
```

### 4. API Request Headers

```javascript
const token = localStorage.getItem('token');
const headers = {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
};

// Example: Update availability
fetch(`/api/stations/${stationId}/availability`, {
  method: 'PATCH',
  headers,
  body: JSON.stringify({ is_available: true })
});
```

---

## Error Responses

### 401 Unauthorized (No Token)
```json
{
  "message": "Unauthenticated"
}
```
- User is not logged in or token expired
- Redirect to login page

### 403 Forbidden (Wrong Role)
```json
{
  "message": "Unauthorized - Admin access required"
}
```
or
```json
{
  "message": "Unauthorized - Station manager access required"
}
```
or
```json
{
  "message": "Unauthorized - You can only manage your assigned station"
}
```
- User logged in but doesn't have permission for this action
- Show appropriate error message

### 422 Validation Error
```json
{
  "errors": {
    "is_available": ["The is_available field is required."]
  }
}
```
- Request data validation failed
- Display field-specific errors to user

---

## Common Workflows

### Admin Creating a Station Manager Account

1. Admin creates station (POST /api/stations)
2. Frontend later allows admin to create manager account:
   - Can be done via admin dashboard UI (not via public registration)
   - Backend would need separate admin-only endpoint for this
   - User assigned to specific station_id

### Station Manager Toggling Availability

1. Manager logs in → Redirected to station dashboard
2. Dashboard shows current station status
3. Manager clicks "Mark Unavailable" button
4. Frontend: PATCH /api/stations/{station_id}/availability
5. Backend validates manager owns the station
6. Status updates and confirmed to user

### Admin Checking System Status

1. Admin logs in → Redirected to admin dashboard
2. Dashboard shows all stations with availability status
3. Admin can click any station to see details
4. Admin can toggle availability for any station
5. Admin can edit/delete stations as needed

---

## Security Notes

### Password & Token Management
- All passwords hashed server-side (bcrypt/argon2)
- Tokens provided by Laravel Sanctum
- Tokens stored in localStorage (or HttpOnly cookie for better security)

### Station Manager Limitations
- Database constraints ensure one manager per station
- API validates manager can only modify their own station
- Frontend should disable UI elements for unauthorized actions

### Admin Account Protection
- Admins created by other admins only (no public registration)
- No public role in registration endpoint
- Frontend should never let non-admin users create admin accounts

### Inactive Account Handling
- `is_active` flag prevents login access (if implemented backend-side)
- Frontend checks `is_active` in response and redirects if false
- Admin can deactivate accounts from dashboard

---

## Testing Authorization

### Test Cases for API

```bash
# Admin creating a station ✅
curl -X POST http://localhost:8000/api/stations \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","address":"Addr","phone":"+233...","email":"test@test.com","latitude":9.4,"longitude":-0.8,"operating_hours":"6-8pm","price_per_kg":3.5}'

# Station manager creating a station ❌ 403
curl -X POST http://localhost:8000/api/stations \
  -H "Authorization: Bearer {manager_token}" \
  -H "Content-Type: application/json" \
  -d '{...}'

# Station manager toggling their own station ✅
curl -X PATCH http://localhost:8000/api/stations/{own_station_id}/availability \
  -H "Authorization: Bearer {manager_token}" \
  -H "Content-Type: application/json" \
  -d '{"is_available":false}'

# Station manager toggling other station ❌ 403
curl -X PATCH http://localhost:8000/api/stations/{other_station_id}/availability \
  -H "Authorization: Bearer {manager_token}" \
  -H "Content-Type: application/json" \
  -d '{"is_available":false}'

# Admin toggling any station ✅
curl -X PATCH http://localhost:8000/api/stations/{any_station_id}/availability \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"is_available":false}'
```

---

## Summary

| Role | Capabilities | Restrictions |
|------|--------------|--------------|
| **Admin** | Create/Update/Delete stations, Manage users, Toggle any station availability | Cannot limit to single station |
| **Station Manager** | Toggle own station availability, View own station details | Cannot create/edit/delete stations, Cannot access other stations |
| **Guest/Public** | View all stations, Search nearby, Browse prices | Cannot perform any modifications |

The authorization is enforced both at the middleware level (route access) and in the controller (resource-level access for station managers).
