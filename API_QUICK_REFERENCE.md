# API Quick Reference

## Base URL
```
http://localhost:8001/api
```

## Authentication

### Register
```
POST /auth/register
Body: { name, email, password, password_confirmation }
Returns: { message, data: User, token }
```

### Login
```
POST /auth/login
Body: { email, password }
Returns: { message, data: User, token, token_type }
```

### Get Current User
```
GET /auth/me
Headers: Authorization: Bearer {token}
Returns: { data: User }
```

### Logout
```
POST /auth/logout
Headers: Authorization: Bearer {token}
Returns: { message }
```

### Refresh Token
```
POST /auth/refresh
Headers: Authorization: Bearer {token}
Returns: { message, token, token_type }
```

---

## Stations

### Get All Stations
```
GET /stations
Query: per_page, page, available (true/false)
Returns: { data: [Station], ... pagination }
```

### Get Station by ID
```
GET /stations/{id}
Returns: { data: Station }
```

### Find Nearby Stations ⭐ (Main endpoint for frontend)
```
POST /stations/nearby
Body: { latitude, longitude, radius (default 5), available_only (optional) }
Returns: {
  message,
  data: [Station],
  available_count,
  unavailable_count,
  radius_km
}
```

### Toggle Station Availability (Admin + Manager)
```
PATCH /stations/{id}/availability
Headers: Authorization: Bearer {token}
Body: { is_available: true/false }
Returns: { data: Station }
```

### Toggle Station Active Status (Admin + Manager)
```
PATCH /stations/{id}/status
Headers: Authorization: Bearer {token}
Body: { is_active: true/false }
Returns: { message, data: Station }
```

### Get Price History
```
GET /stations/{stationId}/price-history
Query: limit, offset
Returns: [PriceHistory]
```

---

## Authorization

### Token Format
```
{user_id}|{random_base64_string}
Example: 019b39d5-ff0e-7288-9503-208e353dfda3|gM5xK2pL9qR8wE1tY3uI4oP5sA6dF7gH8jK9lM0nB1cV2xZ3y
```

### Using Token
```
Authorization: Bearer {token}
```

---

## User Roles

- **user** - Regular user (no special permissions)
- **admin** - Can manage everything
- **station_manager** - Can manage only assigned station

---

## HTTP Status Codes

- 200 - Success
- 201 - Created
- 400 - Bad Request
- 401 - Unauthorized (no token or invalid)
- 403 - Forbidden (no permission)
- 404 - Not Found
- 422 - Validation Error
- 500 - Server Error

---

## Test Credentials

```
Admin:
  Email: admin@example.com
  Password: password

Manager (Infin - assigned to Tamale Central):
  Email: infin@lpgtamale.com
  Password: password
```

---

## Common Headers

```
Content-Type: application/json
Authorization: Bearer {token}
```

---

## Response Format

### Success (200)
```json
{
  "message": "Success message",
  "data": { ... }
}
```

### Error (4xx, 5xx)
```json
{
  "message": "Error description",
  "errors": { "field": ["error message"] }
}
```

---

## Frontend Checklist

- [ ] Setup authentication flow (login/register)
- [ ] Store token in localStorage
- [ ] Add Authorization header to all requests
- [ ] Implement nearby stations search with geolocation
- [ ] Show stations sorted by availability + distance
- [ ] Allow station managers to toggle availability
- [ ] Logout functionality
- [ ] Handle 401 responses (redirect to login)

---

## Notes for Frontend Dev

1. **Geolocation**: Use `navigator.geolocation.getCurrentPosition()` to get user's coordinates
2. **Nearby Stations**: Call `POST /stations/nearby` with user's GPS coordinates
3. **Smart Sorting**: Available stations shown first, then unavailable (both by distance)
4. **Token Storage**: Use localStorage for now, HttpOnly cookies in production
5. **Error Handling**: Check response status and `message` field for user-friendly errors
6. **Permissions**: Admin can toggle any station, managers only their own

---

## Example: Login Flow

```javascript
// 1. User clicks login
const response = await fetch('http://localhost:8001/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, password })
});

// 2. Save token
const data = await response.json();
localStorage.setItem('token', data.token);

// 3. Use token in future requests
const stations = await fetch('http://localhost:8001/api/stations', {
  headers: { 'Authorization': `Bearer ${data.token}` }
});
```

---

## Example: Find Nearby Stations

```javascript
// 1. Get user's coordinates
navigator.geolocation.getCurrentPosition((position) => {
  const { latitude, longitude } = position.coords;

  // 2. Find nearby stations
  fetch('http://localhost:8001/api/stations/nearby', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      latitude,
      longitude,
      radius: 10  // 10km radius
    })
  })
  .then(res => res.json())
  .then(data => {
    // data.data = array of stations
    // data.available_count = number of available stations
    // data.unavailable_count = number of unavailable stations
    console.log(data.data);
  });
});
```

---

## Environment Variables

```
NEXT_PUBLIC_API_URL=http://localhost:8001/api
```

Use in code:
```javascript
fetch(`${process.env.NEXT_PUBLIC_API_URL}/stations/nearby`, { ... })
```
