# LPG Tamale System - Roles and Permissions

## Overview
The LPG Tamale system has two distinct user roles with different levels of access and responsibilities:
1. **Admin** - System administrators with full control
2. **Station Manager** - Individual station operators with limited permissions

---

## User Roles

### 1. ADMIN ROLE

**Description:** System administrators who manage the entire LPG distribution network across Tamale.

#### Permissions & Capabilities:

##### Station Management
- ✅ **View all stations** - See complete list of all LPG stations in the system
- ✅ **Add new stations** - Create new LPG station entries with:
  - Station name, address, contact details (phone, email)
  - Operating hours and pricing information
  - GPS location (latitude/longitude) using map picker
  - Station image
  - Initial availability status
- ✅ **Edit stations** - Modify any station's information including:
  - Basic details (name, address, contact)
  - Operating hours and price per kg
  - Location coordinates
  - Availability status
  - Station image
- ✅ **Delete stations** - Remove stations from the system
  - Note: Deleting a station also removes associated station manager accounts

##### User Management
- ✅ **View all station managers** - See all station manager accounts
- ✅ **Create station manager accounts** - Add new users with:
  - Full name and email
  - Login password
  - Assignment to a specific station (one manager per station)
- ✅ **Delete station managers** - Remove user accounts from the system
- ❌ **Cannot modify passwords** - Password changes not implemented yet

##### Monitoring & Analytics
- ✅ **Dashboard statistics** - View real-time metrics:
  - Total number of stations
  - Available stations count
  - Unavailable stations count
  - Total station manager accounts
- ✅ **Station availability tracking** - Monitor which stations have LPG in stock
- ✅ **Location viewing** - See GPS coordinates of all stations
- ✅ **Price monitoring** - View current price per kg at each station

##### System Access
- ✅ **Admin dashboard** - Access to `/admin` route
- ✅ **Full system visibility** - Can see and manage all resources
- ❌ **Cannot access station manager dashboard** - Role-restricted

---

### 2. STATION MANAGER ROLE

**Description:** Individual users assigned to manage a specific LPG station's availability status.

#### Permissions & Capabilities:

##### Station Status Management
- ✅ **Toggle LPG availability** - Mark station as Available/Unavailable
  - Primary function: Update when LPG is in stock or out of stock
  - Triggers automatic timestamp update
- ✅ **View own station details** - See information for assigned station only:
  - Station name and address
  - Contact information
  - Operating hours
  - Current availability status
  - Last updated timestamp

##### Monitoring
- ✅ **Dashboard access** - Limited dashboard showing:
  - Current station status (Available/Unavailable)
  - Last update time
  - Operating hours
  - Quick toggle button for availability
- ✅ **Status update confirmation** - Visual feedback when toggling availability

##### Restrictions
- ❌ **Cannot view other stations** - Access limited to assigned station only
- ❌ **Cannot modify station details** - No ability to change:
  - Name, address, contact information
  - Operating hours
  - Pricing
  - Location coordinates
  - Station image
- ❌ **Cannot create or delete stations** - No station management rights
- ❌ **Cannot manage users** - No access to user management
- ❌ **Cannot access admin dashboard** - Role-restricted to `/station` route
- ❌ **Cannot change own password** - Not implemented yet
- ❌ **Cannot view analytics** - No access to system-wide statistics

##### System Access
- ✅ **Station dashboard** - Access to `/station` route
- ❌ **No admin access** - Cannot access `/admin` route

---

## Role Comparison Matrix

| Feature | Admin | Station Manager |
|---------|-------|-----------------|
| **View all stations** | ✅ Yes | ❌ No (own only) |
| **Add stations** | ✅ Yes | ❌ No |
| **Edit station details** | ✅ Yes | ❌ No |
| **Delete stations** | ✅ Yes | ❌ No |
| **Toggle availability** | ✅ Yes (all) | ✅ Yes (own only) |
| **Create users** | ✅ Yes | ❌ No |
| **Delete users** | ✅ Yes | ❌ No |
| **View system stats** | ✅ Yes | ❌ No |
| **Access admin dashboard** | ✅ Yes | ❌ No |
| **Access station dashboard** | ❌ No | ✅ Yes |
| **View location coordinates** | ✅ Yes | ❌ No |
| **Update pricing** | ✅ Yes | ❌ No |
| **Change passwords** | ❌ Not implemented | ❌ Not implemented |

---

## Authentication & Access Control

### Login Process
1. Users log in with email and password
2. System validates credentials
3. User role is determined from account
4. Redirected to appropriate dashboard:
   - `admin` role → `/admin` dashboard
   - `station` role → `/station` dashboard

### Route Protection
- **Admin routes** (`/admin`): Only accessible by users with `role: 'admin'`
- **Station routes** (`/station`): Only accessible by users with `role: 'station'`
- Unauthorized access attempts redirect to `/login`

### Session Management
- Authentication state persisted using Zustand
- Users remain logged in across page refreshes
- Logout clears authentication state and redirects to home

---

## Workflows by Role

### Admin Workflow

#### Adding a New Station
1. Navigate to Admin Dashboard
2. Click "Add Station" button
3. Fill in station details:
   - Name, address, phone, email
   - Operating hours and price per kg
   - Click "Get Location" or manually enter coordinates
   - Select location on map
   - Optionally add station image URL
   - Set initial availability status
4. Submit form
5. Station appears in stations list

#### Creating a Station Manager
1. Navigate to "Users" tab in Admin Dashboard
2. Click "Add User" button
3. Enter manager details:
   - Full name and email
   - Create password
   - Assign to specific station
4. Submit form
5. Manager can now log in and manage their station

#### Monitoring Station Availability
1. View dashboard statistics (available/unavailable counts)
2. Browse stations table
3. See real-time status badges
4. Click location links to view on map
5. Edit or delete stations as needed

---

### Station Manager Workflow

#### Daily Operations
1. Log in to Station Dashboard
2. View current availability status
3. When LPG stock changes:
   - Click "Mark as Unavailable" (if LPG runs out)
   - Click "Mark as Available" (when LPG is restocked)
4. System automatically updates timestamp
5. Change is immediately visible to customers on public site

#### Monitoring
- Check "Last Updated" time to see when status was last changed
- Verify operating hours displayed correctly
- View current availability badge (green = available, red = unavailable)

---

## Database Role Mapping

### Users Table
```sql
CREATE TABLE users (
    id UUID PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(20) CHECK (role IN ('admin', 'station')),
    station_id UUID REFERENCES stations(id),  -- NULL for admins
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

**Constraints:**
- `role` must be either `'admin'` or `'station'`
- `station_id` must be NULL for admin users
- `station_id` must be set for station manager users
- One unique station manager per station (enforced by unique index)

---

## Security Considerations

### Current Implementation
- ✅ Role-based route protection
- ✅ Frontend role validation
- ✅ Password storage (basic, needs backend hashing)
- ✅ Session persistence

### Recommended Enhancements for Backend
1. **Password Security**
   - Hash passwords with bcrypt/argon2
   - Implement password reset functionality
   - Enforce strong password policies

2. **JWT Authentication**
   - Use JWT tokens for API requests
   - Include role in token claims
   - Implement token refresh mechanism

3. **API Authorization**
   - Validate user role on every backend request
   - Prevent station managers from accessing other stations' data
   - Implement rate limiting

4. **Audit Logging**
   - Track who makes availability changes
   - Log station modifications
   - Monitor user creation/deletion

5. **Multi-factor Authentication (MFA)**
   - Optional for admin accounts
   - SMS/Email verification

---

## Future Role Enhancements

### Potential Additional Roles
1. **Super Admin** - Manage admin accounts and system settings
2. **Customer Support** - Read-only access to all data, can update contact info
3. **Analyst** - Access to reports and analytics only
4. **Regional Manager** - Manage stations within a specific region

### Potential Permission Additions
1. **Station Managers:**
   - Update own station's operating hours
   - Update pricing (with admin approval)
   - View basic analytics (customer views, status change history)
   - Upload station photos
   - Respond to customer reviews/feedback

2. **Admins:**
   - Bulk import/export stations
   - Generate reports (availability trends, pricing analysis)
   - Configure system settings
   - Manage user permissions granularly
   - View audit logs
   - Send notifications to station managers

---

## Default Accounts

### Sample Admin Account
- **Email:** `admin@tamalelpg.com`
- **Name:** System Administrator
- **Role:** `admin`
- **Station:** None (NULL)

### Sample Station Manager
- **Email:** `central@tamalelpg.com`
- **Name:** Central Station Manager
- **Role:** `station`
- **Station:** Tamale Central Gas Station

---

## Support & Documentation

For technical questions about roles and permissions:
- Review this document
- Check database schema in `database-schema.sql`
- Review frontend code in `src/app/admin/` and `src/app/station/`
- Contact system administrator

Last Updated: December 20, 2025
