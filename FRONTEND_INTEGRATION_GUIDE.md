# Frontend Integration Guide

## Quick Start

### 1. Authentication Flow

```
User Login
    ↓
POST /api/auth/login
    ↓
Receive Token + User Data
    ↓
Store Token in Storage
    ↓
Use Token in All Requests
    ↓
GET /api/auth/me (verify on app load)
    ↓
Access Protected Endpoints
```

### 2. API Response Format

All endpoints return this structure:

```json
{
  "message": "Success message",
  "data": { /* Response data */ },
  "token": "..." // Only for auth endpoints
}
```

### 3. Error Response Format

```json
{
  "message": "Error description",
  "errors": { /* Field validation errors */ }
}
```

---

## Data Models

### User Model

```typescript
interface User {
  id: string;           // UUID
  name: string;
  email: string;
  role: 'user' | 'admin' | 'station_manager';
  is_active: boolean;
  created_at: string;   // ISO 8601 datetime
  updated_at: string;   // ISO 8601 datetime
}
```

**Example:**
```json
{
  "id": "019b39d5-ff0e-7288-9503-208e353dfda3",
  "name": "John Doe",
  "email": "john@example.com",
  "role": "user",
  "is_active": true,
  "created_at": "2025-12-20T12:34:56.000000Z",
  "updated_at": "2025-12-20T12:34:56.000000Z"
}
```

### Station Model

```typescript
interface Station {
  id: string;                    // UUID
  name: string;
  address: string;
  phone: string;
  email: string;
  is_available: boolean;         // Has stock
  is_active: boolean;            // Is operating
  price_per_kg: string;          // Decimal
  operating_hours: string;
  image: string | null;          // URL or null
  latitude: number;
  longitude: number;
  distance_km?: number;          // Only in nearby search
  created_at: string;            // ISO 8601 datetime
  updated_at: string;            // ISO 8601 datetime
}
```

**Example:**
```json
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
  "distance_km": 0,
  "created_at": "2025-12-20T01:42:08.000000Z",
  "updated_at": "2025-12-20T10:41:46.000000Z"
}
```

### Authentication Response

```typescript
interface AuthResponse {
  message: string;
  data: User;
  token: string;         // Format: {user_id}|{random_string}
  token_type: 'Bearer';
}
```

### Nearby Stations Response

```typescript
interface NearbyStationsResponse {
  message: string;
  data: Station[];
  available_count: number;
  unavailable_count: number;
  radius_km: number;
  note?: string;         // Only if no available stations
}
```

---

## API Endpoints Summary

### Authentication

| Method | Endpoint | Public | Body | Returns |
|--------|----------|--------|------|---------|
| POST | `/auth/register` | ✅ | name, email, password | User + Token |
| POST | `/auth/login` | ✅ | email, password | User + Token |
| GET | `/auth/me` | ❌ | - | User |
| POST | `/auth/refresh` | ❌ | - | Token |
| POST | `/auth/logout` | ❌ | - | Message |

### Stations

| Method | Endpoint | Public | Body/Query | Returns |
|--------|----------|--------|-----------|---------|
| GET | `/stations` | ✅ | filters, pagination | Station[] |
| GET | `/stations/{id}` | ✅ | - | Station |
| POST | `/stations/nearby` | ✅ | latitude, longitude, radius | NearbyStationsResponse |
| PATCH | `/stations/{id}/availability` | ❌ | is_available | Station |
| PATCH | `/stations/{id}/status` | ❌ | is_active | Station |
| GET | `/stations/{id}/price-history` | ✅ | limit, offset | PriceHistory[] |

---

## Frontend Request Examples

### Setup Request Interceptor

```typescript
// interceptor.ts
export function createApiClient(token?: string) {
  return {
    async request<T>(
      url: string,
      options: RequestInit = {}
    ): Promise<T> {
      const headers: HeadersInit = {
        'Content-Type': 'application/json',
        ...options.headers,
      };

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const response = await fetch(
        `http://localhost:8001/api${url}`,
        { ...options, headers }
      );

      if (response.status === 401) {
        // Token invalid, redirect to login
        window.location.href = '/login';
      }

      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message);
      }

      return data;
    },
  };
}
```

### Login Example

```typescript
// auth.service.ts
export async function login(
  email: string,
  password: string
): Promise<{ user: User; token: string }> {
  const response = await fetch(
    'http://localhost:8001/api/auth/login',
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    }
  );

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  const data = await response.json();
  
  // Store token
  localStorage.setItem('token', data.token);
  localStorage.setItem('user', JSON.stringify(data.data));
  
  return {
    user: data.data,
    token: data.token,
  };
}
```

### Find Nearby Stations Example

```typescript
// stations.service.ts
export async function findNearbyStations(
  latitude: number,
  longitude: number,
  radius: number = 5,
  availableOnly: boolean = false
): Promise<NearbyStationsResponse> {
  const response = await fetch(
    'http://localhost:8001/api/stations/nearby',
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        latitude,
        longitude,
        radius,
        available_only: availableOnly,
      }),
    }
  );

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  return response.json();
}
```

### Toggle Availability Example (Manager)

```typescript
// stations.service.ts
export async function toggleAvailability(
  stationId: string,
  isAvailable: boolean,
  token: string
): Promise<Station> {
  const response = await fetch(
    `http://localhost:8001/api/stations/${stationId}/availability`,
    {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({ is_available: isAvailable }),
    }
  );

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  return (await response.json()).data;
}
```

---

## React Integration Examples

### useAuth Hook

```typescript
// hooks/useAuth.ts
import { useState, useEffect } from 'react';

interface AuthState {
  user: User | null;
  token: string | null;
  loading: boolean;
  error: string | null;
}

export function useAuth() {
  const [state, setState] = useState<AuthState>({
    user: null,
    token: localStorage.getItem('token'),
    loading: true,
    error: null,
  });

  // Verify token on mount
  useEffect(() => {
    if (state.token) {
      fetch('http://localhost:8001/api/auth/me', {
        headers: { 'Authorization': `Bearer ${state.token}` },
      })
        .then(res => res.json())
        .then(data => {
          if (data.data) {
            setState(prev => ({
              ...prev,
              user: data.data,
              loading: false,
            }));
          }
        })
        .catch(() => {
          localStorage.removeItem('token');
          setState(prev => ({
            ...prev,
            token: null,
            loading: false,
          }));
        });
    } else {
      setState(prev => ({ ...prev, loading: false }));
    }
  }, []);

  const login = async (email: string, password: string) => {
    setState(prev => ({ ...prev, loading: true }));
    try {
      const res = await fetch('http://localhost:8001/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.message);

      localStorage.setItem('token', data.token);
      setState(prev => ({
        ...prev,
        user: data.data,
        token: data.token,
        error: null,
      }));
      return data.data;
    } catch (err) {
      setState(prev => ({
        ...prev,
        error: err.message,
      }));
      throw err;
    } finally {
      setState(prev => ({ ...prev, loading: false }));
    }
  };

  const logout = async () => {
    if (state.token) {
      await fetch('http://localhost:8001/api/auth/logout', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${state.token}` },
      });
    }
    localStorage.removeItem('token');
    setState({
      user: null,
      token: null,
      loading: false,
      error: null,
    });
  };

  return { ...state, login, logout };
}
```

### Usage in Component

```typescript
// pages/login.tsx
import { useAuth } from '@/hooks/useAuth';
import { useRouter } from 'next/router';

export default function LoginPage() {
  const { login, loading, error } = useAuth();
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await login(email, password);
      router.push('/dashboard');
    } catch (err) {
      // Error already in state
    }
  };

  return (
    <div>
      <form onSubmit={handleLogin}>
        <input
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="Email"
        />
        <input
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          placeholder="Password"
        />
        <button type="submit" disabled={loading}>
          {loading ? 'Logging in...' : 'Login'}
        </button>
      </form>
      {error && <p style={{ color: 'red' }}>{error}</p>}
    </div>
  );
}
```

### Protected Route

```typescript
// components/ProtectedRoute.tsx
import { useAuth } from '@/hooks/useAuth';
import { useRouter } from 'next/router';

export function ProtectedRoute({
  children,
  requiredRole,
}: {
  children: React.ReactNode;
  requiredRole?: 'admin' | 'station_manager';
}) {
  const { user, loading } = useAuth();
  const router = useRouter();

  if (loading) return <div>Loading...</div>;

  if (!user) {
    router.push('/login');
    return null;
  }

  if (requiredRole && user.role !== requiredRole) {
    router.push('/unauthorized');
    return null;
  }

  return <>{children}</>;
}
```

---

## Environment Setup

### .env.local (Next.js)

```bash
NEXT_PUBLIC_API_URL=http://localhost:8001/api
NEXT_PUBLIC_APP_NAME=LPG Tamale
```

### Usage in Code

```typescript
const API_URL = process.env.NEXT_PUBLIC_API_URL;

fetch(`${API_URL}/stations/nearby`, { ... })
```

---

## Testing with Postman

1. Import the provided Postman collections
2. Set base URL variable: `http://localhost:8001/api`
3. Login to get token
4. Copy token and set in `{{token}}` variable
5. Test protected endpoints

---

## Common Issues

**CORS Error:**
```
Access to XMLHttpRequest has been blocked by CORS policy
```
- Backend is configured for localhost
- In production, update `.env` CORS settings
- Check request headers don't have extra spaces

**401 Unauthorized:**
```json
{ "message": "Unauthenticated" }
```
- Token missing or invalid format
- Check Authorization header: `Bearer {token}`
- Token may have expired, refresh or re-login

**422 Validation Error:**
```json
{
  "message": "The email has already been taken.",
  "errors": { "email": [...] }
}
```
- Check all required fields present
- Validate email format
- Check for unique constraint violations

---

## Development Checklist

- [ ] Setup API client/interceptor
- [ ] Implement login/register pages
- [ ] Store token in localStorage/cookies
- [ ] Add Authorization header to requests
- [ ] Implement useAuth hook
- [ ] Create ProtectedRoute component
- [ ] Test login/logout flow
- [ ] Implement nearby stations search
- [ ] Add geolocation permission handling
- [ ] Handle token expiration/refresh
- [ ] Test with different user roles (admin, manager, user)

---

## Production Checklist

- [ ] Switch to HTTPS in environment variables
- [ ] Use HttpOnly cookies for token storage
- [ ] Implement token refresh strategy
- [ ] Add error logging/monitoring
- [ ] Configure proper CORS headers
- [ ] Test with production backend URL
- [ ] Implement proper error boundaries
- [ ] Add rate limiting handling
- [ ] Test on different devices/browsers

---

## Support Resources

- Authentication API: `AUTHENTICATION_API.md`
- Nearest Station API: `NEAREST_STATION_API.md`
- Postman Collections: `postman/` directory
- GitHub Repository: [Your repo]
