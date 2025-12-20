# Frontend Authentication & Authorization Guide

## User Roles

The LPG Tamale API supports two user roles:

### 1. **Admin (Administrator)**
- Full access to all API endpoints
- Can create, read, update, and delete stations
- Can manage all station availability and pricing
- Can view all analytics and reports
- Role value: `"admin"`

### 2. **Station Manager**
- Limited access to specific station operations
- Can only manage their assigned station
- Can update availability and pricing for their station
- Cannot create or delete stations
- Cannot view other stations' management features
- Role value: `"station"`

---

## Authentication Response Structure

When a user successfully logs in, the API returns a response with user information that includes the `role` and `is_admin` fields:

```json
{
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "admin",
      "station_id": null,
      "is_active": true,
      "created_at": "2025-12-20T10:00:00Z",
      "updated_at": "2025-12-20T10:00:00Z"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

---

## Frontend Routing Strategy

Use these fields from the authentication response to decide where to route users:

### Step 1: Check Authentication
```javascript
if (!token || !user) {
  // Redirect to login page
  navigateTo('/login');
}
```

### Step 2: Check Active Status
```javascript
if (!user.is_active) {
  // Show account disabled message
  // Redirect to login page
  navigateTo('/account-disabled');
}
```

### Step 3: Check User Role

#### For Admin Users:
```javascript
if (user.role === 'admin') {
  // Route to admin dashboard
  navigateTo('/admin/dashboard');
  // Can access:
  // - /admin/stations (view all)
  // - /admin/stations/create (create new)
  // - /admin/stations/:id/edit (edit)
  // - /admin/stations/:id (delete)
  // - /admin/analytics (reports)
}
```

#### For Station Manager Users:
```javascript
if (user.role === 'station') {
  // Route to station manager dashboard
  navigateTo('/manager/dashboard');
  // Provide station_id from user object
  const stationId = user.station_id;
  
  // Can access:
  // - /manager/station/:stationId (view only)
  // - /manager/station/:stationId/availability (update)
  // - /manager/station/:stationId/pricing (update)
}
```

---

## Example Frontend Implementation (JavaScript/Vue/React)

### Login Handler
```javascript
async function handleLogin(email, password) {
  try {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message);
    }

    // Store token and user info
    localStorage.setItem('token', data.data.token);
    localStorage.setItem('user', JSON.stringify(data.data.user));

    // Route based on role
    routeUserByRole(data.data.user);
  } catch (error) {
    console.error('Login failed:', error);
  }
}

function routeUserByRole(user) {
  // Check if account is active
  if (!user.is_active) {
    window.location.href = '/account-disabled';
    return;
  }

  // Route based on role
  switch (user.role) {
    case 'admin':
      window.location.href = '/admin/dashboard';
      break;
    case 'station':
      window.location.href = `/manager/dashboard?station=${user.station_id}`;
      break;
    default:
      window.location.href = '/login';
  }
}
```

### Permission Checking Utility
```javascript
// auth.js or similar utility file

export const userPermissions = {
  isAdmin: (user) => user?.role === 'admin',
  
  isStationManager: (user) => user?.role === 'station',
  
  canManageStation: (user, stationId) => {
    if (userPermissions.isAdmin(user)) return true;
    return user?.station_id === stationId;
  },
  
  isActive: (user) => user?.is_active === true,
  
  canCreateStation: (user) => userPermissions.isAdmin(user),
  
  canEditStation: (user, stationId) => {
    if (!userPermissions.isActive(user)) return false;
    return userPermissions.canManageStation(user, stationId);
  },
  
  canUpdateAvailability: (user, stationId) => {
    if (!userPermissions.isActive(user)) return false;
    return userPermissions.isStationManager(user) && 
           user.station_id === stationId;
  }
};

// Usage in components
if (userPermissions.isAdmin(currentUser)) {
  showAdminPanel();
}

if (userPermissions.canEditStation(currentUser, stationId)) {
  enableEditButton();
}
```

### Protected Route Component
```javascript
// ProtectedRoute.vue or ProtectedRoute.jsx

function ProtectedRoute({ user, requiredRole, children }) {
  if (!user) {
    return <Redirect to="/login" />;
  }

  if (!user.is_active) {
    return <Redirect to="/account-disabled" />;
  }

  if (requiredRole && user.role !== requiredRole) {
    return <Unauthorized />;
  }

  return children;
}

// Usage
<ProtectedRoute user={currentUser} requiredRole="admin">
  <AdminDashboard />
</ProtectedRoute>
```

---

## API Authorization Response

When an unauthorized request is made:

### Missing Authentication (401)
```json
{
  "message": "Unauthenticated"
}
```

### Insufficient Permissions (403)
```json
{
  "message": "Unauthorized - Admin access required"
}
```

or for station managers:

```json
{
  "message": "Unauthorized - Station manager access required"
}
```

---

## Role-Based Endpoint Access

| Endpoint | Admin | Station Manager | Description |
|----------|-------|-----------------|-------------|
| `GET /api/stations` | ✅ | ✅ | List all stations |
| `GET /api/stations/{id}` | ✅ | ✅ | View station details |
| `POST /api/stations` | ✅ | ❌ | Create new station |
| `PUT /api/stations/{id}` | ✅ | ❌ | Edit station |
| `DELETE /api/stations/{id}` | ✅ | ❌ | Delete station |
| `PATCH /api/stations/{id}/availability` | ✅ | ✅* | Update availability |
| `POST /api/stations/nearby` | ✅ | ✅ | Find nearby stations |

*Station managers can only update their assigned station's availability.

---

## Best Practices

1. **Always validate on both frontend and backend** - Never rely solely on frontend role checks
2. **Refresh token before expiration** - Call `/api/auth/refresh` periodically
3. **Handle inactive accounts** - Check `is_active` flag on every page load
4. **Store token securely** - Use HTTP-only cookies instead of localStorage when possible
5. **Logout on 401 responses** - Treat 401 errors as session expired and redirect to login
6. **Display appropriate UI** - Hide/disable buttons based on user role
7. **Log administrative actions** - Track who made what changes (important for auditing)

---

## Example User Objects

### Admin User
```json
{
  "id": 1,
  "name": "System Administrator",
  "email": "admin@tamalelpg.com",
  "role": "admin",
  "station_id": null,
  "is_active": true,
  "created_at": "2025-12-20T10:00:00Z",
  "updated_at": "2025-12-20T10:00:00Z"
}
```

### Station Manager User
```json
{
  "id": 2,
  "name": "Manager Name",
  "email": "manager@tamalelpg.com",
  "role": "station",
  "station_id": "019b396b-dd20-7120-bedb-858432d894c3",
  "is_active": true,
  "created_at": "2025-12-20T10:00:00Z",
  "updated_at": "2025-12-20T10:00:00Z"
}
```

### Inactive User (Should not be allowed to proceed)
```json
{
  "id": 3,
  "name": "Disabled User",
  "email": "disabled@tamalelpg.com",
  "role": "station",
  "station_id": "019b396b-dd20-7120-bedb-858432d894c3",
  "is_active": false,
  "created_at": "2025-12-20T10:00:00Z",
  "updated_at": "2025-12-20T10:00:00Z"
}
```
