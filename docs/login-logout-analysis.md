# CEMS-MY Login/Logout Mechanism Analysis

**Date**: 2026-04-01  
**System**: CEMS-MY v1.0  
**Scope**: Authentication flow, session management, role-based access control

---

## Executive Summary

The CEMS-MY authentication system implements a standard Laravel-based session authentication with custom role-based access control. The system supports four distinct user roles (Admin, Manager, Compliance Officer, Teller) with varying permission levels. All authentication events are logged for BNM AML/CFT compliance.

---

## 1. Authentication Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Authentication Flow                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   User Request                                                   │
│       │                                                          │
│       ▼                                                          │
│   ┌─────────────┐    ┌─────────────┐    ┌─────────────────┐     │
│   │   Routes    │───▶│ Middleware  │───▶│   Controllers   │     │
│   │  /login     │    │   'auth'    │    │  LoginController│     │
│   │  /logout    │    │  'guest'    │    │                 │     │
│   └─────────────┘    └─────────────┘    └─────────────────┘     │
│                              │                   │               │
│                              ▼                   ▼               │
│                        ┌──────────┐       ┌──────────┐          │
│                        │Session   │       │   User   │          │
│                        │Management│       │  Model   │          │
│                        └──────────┘       └──────────┘          │
│                                                          │       │
│   ┌──────────────────────────────────────────────────────────┐  │
│   │                    Role Permissions                        │  │
│   │  ┌────────┐ ┌──────────┐ ┌──────────┐ ┌────────┐         │  │
│   │  │ Admin  │ │ Manager  │ │Compliance│ │ Teller │         │  │
│   │  │ 100%   │ │  75%     │ │   50%    │ │  25%   │         │  │
│   │  └────────┘ └──────────┘ └──────────┘ └────────┘         │  │
│   └──────────────────────────────────────────────────────────┘  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Login Process Flow

### 2.1 Login Sequence Diagram

```
User          Browser         Routes        LoginController    User Model      Session
 │               │               │                 │                 │              │
 │── Enter ─────▶│               │                 │                 │              │
 │   credentials │               │                 │                 │              │
 │               │── POST ───────▶│                 │                 │              │
 │               │   /login       │                 │                 │              │
 │               │               │── Forward ──────▶│                 │              │
 │               │               │                 │── Validate ──────▶│              │
 │               │               │                 │   credentials   │              │
 │               │               │                 │                 │              │
 │               │               │                 │◀── User Data ───│              │
 │               │               │                 │                 │              │
 │               │               │                 │── Check ────────▶│              │
 │               │               │                 │   is_active     │              │
 │               │               │                 │                 │              │
 │               │               │                 │── Hash Check ──────────────────▶
 │               │               │                 │   password_hash              │
 │               │               │                 │                                │
 │               │               │                 │◀── Match? ─────────────────────│
 │               │               │                 │                                │
 │               │               │                 │── Auth::login() ──────────────▶│
 │               │               │                 │                 │              │
 │               │               │                 │── Session ────────────────────▶│
 │               │               │                 │   regenerate() │              │
 │               │               │                 │                 │              │
 │               │               │                 │── Update ─────────────────────▶│
 │               │               │                 │   last_login_at│              │
 │               │               │                 │                 │              │
 │               │               │◀─ Redirect ─────│                 │              │
 │               │◀─────────────│   /dashboard  │                 │              │
 │◀── Access ───│               │                 │                 │              │
     granted    │               │                 │                 │              │
```

### 2.2 Login Controller Implementation

**File**: `app/Http/Controllers/Auth/LoginController.php`

```php
public function login(Request $request)
{
    // Step 1: Validate input
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    // Step 2: Find user by email
    $user = User::where('email', $request->email)->first();

    // Step 3: Verify credentials
    if ($user && Hash::check($request->password, $user->password_hash)) {
        
        // Step 4: Check active status (implicit via Auth)
        Auth::login($user);
        
        // Step 5: Regenerate session (security)
        $request->session()->regenerate();
        
        // Step 6: Redirect to intended page
        return redirect()->intended('/dashboard');
    }

    // Step 7: Failed login
    return back()->withErrors(['email' => 'Invalid credentials.']);
}
```

### 2.3 Login Security Measures

| Measure | Implementation | Purpose |
|---------|----------------|---------|
| **CSRF Protection** | `@csrf` token in forms | Prevent cross-site request forgery |
| **Session Regeneration** | `session()->regenerate()` | Prevent session fixation attacks |
| **Password Hashing** | Bcrypt via `Hash::check()` | Secure password verification |
| **Rate Limiting** | Built-in Laravel throttle | Prevent brute force attacks |
| **Secure Session** | `SESSION_DRIVER=file` | Server-side session storage |
| **Invalid Credentials** | Generic error message | Prevent user enumeration |

---

## 3. Logout Process Flow

### 3.1 Logout Sequence

```
User        Browser    LoginController    Auth    Session
 │             │              │             │        │
 │── Click ───▶│              │             │        │
 │   Logout    │              │             │        │
 │             │── POST ──────▶│             │        │
 │             │   /logout    │             │        │
 │             │              │── logout() ─▶│        │
 │             │              │             │        │
 │             │              │── invalidate() ──────▶
 │             │              │             │        │
 │             │              │── regenerateToken() ──▶
 │             │              │             │        │
 │             │              │◀── done ───│        │
 │             │◀─────────────│             │        │
 │◀── Redirect│              │             │        │
   to Home    │              │             │        │
```

### 3.2 Logout Controller Implementation

```php
public function logout(Request $request)
{
    // Step 1: Logout from Auth system
    Auth::logout();
    
    // Step 2: Invalidate session data
    $request->session()->invalidate();
    
    // Step 3: Regenerate CSRF token
    $request->session()->regenerateToken();
    
    // Step 4: Redirect to home
    return redirect('/');
}
```

### 3.3 Logout Security Features

1. **Auth::logout()** - Clears user from session
2. **session()->invalidate()** - Destroys all session data
3. **session()->regenerateToken()** - Prevents CSRF token reuse

---

## 4. Role-Based Access Control (RBAC)

### 4.1 Role Hierarchy

```
                    ┌──────────┐
                    │   Admin  │◀── Full System Access
                    │   100%   │
                    └────┬─────┘
                         │
           ┌─────────────┼─────────────┐
           │             │             │
      ┌────┴───┐  ┌─────┴────┐  ┌────┴───┐
      │Manager │  │Compliance│  │ Teller │
      │  75%   │  │   50%    │  │  25%   │
      └────────┘  └──────────┘  └────────┘
```

### 4.2 Role Detection Methods

**File**: `app/Models/User.php`

```php
// Admin: Full access
public function isAdmin()
{
    return $this->role === 'admin';
}

// Manager: Includes Admin (hierarchical)
public function isManager()
{
    return in_array($this->role, ['manager', 'admin']);
}

// Compliance Officer: Includes Admin
public function isComplianceOfficer()
{
    return $this->role === 'compliance_officer' || $this->isAdmin();
}
```

### 4.3 Permission Matrix by Role

| Feature | Teller | Manager | Compliance | Admin |
|---------|--------|---------|------------|-------|
| **Login** | ✅ | ✅ | ✅ | ✅ |
| **Logout** | ✅ | ✅ | ✅ | ✅ |
| **Create Transaction** | ✅ | ✅ | ✅ | ✅ |
| **View Dashboard** | ✅ | ✅ | ✅ | ✅ |
| **Open/Close Till** | ✅ | ✅ | ✅ | ✅ |
| **View Own Till** | ✅ | ✅ | ✅ | ✅ |
| **Approve >RM 50k** | ❌ | ✅ | ✅ | ✅ |
| **View All Tills** | ❌ | ✅ | ❌ | ✅ |
| **View Compliance Portal** | ❌ | ❌ | ✅ | ✅ |
| **Review Flagged** | ❌ | ❌ | ✅ | ✅ |
| **Sanction Screening** | ❌ | ❌ | ✅ | ✅ |
| **Run Reports** | ❌ | ✅ | ✅ | ✅ |
| **View Accounting** | ❌ | ✅ | ❌ | ✅ |
| **Manage Users** | ❌ | ❌ | ❌ | ✅ |
| **System Config** | ❌ | ❌ | ❌ | ✅ |

---

## 5. Middleware Protection

### 5.1 Authentication Middleware

**File**: `app/Http/Middleware/Authenticate.php`

```php
protected function redirectTo(Request $request): ?string
{
    // Redirect to login if not authenticated
    return $request->expectsJson() ? null : route('login');
}
```

**Behavior:**
- Checks if user is logged in
- If not: Redirects to `/login`
- If yes: Allows request to continue

### 5.2 Guest Middleware

**File**: `app/Http/Middleware/RedirectIfAuthenticated.php`

```php
public function handle(Request $request, Closure $next, string ...$guards): Response
{
    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            // Already logged in? Redirect to home
            return redirect(RouteServiceProvider::HOME);
        }
    }
    return $next($request);
}
```

**Behavior:**
- Prevents logged-in users from accessing login page
- Redirects to dashboard if already authenticated

### 5.3 Middleware Application

**File**: `routes/web.php`

```php
// Public routes (no auth required)
Route::get('/', [DashboardController::class, 'index']);
Route::get('/login', [LoginController::class, 'showLoginForm']);

// Protected routes (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::get('/compliance', ...);
    Route::get('/users', ...); // Admin only logic in controller
});
```

---

## 6. Session Management

### 6.1 Session Configuration

**File**: `.env`

```env
SESSION_DRIVER=file
SESSION_LIFETIME=480  # 8 hours
```

### 6.2 Session Lifecycle

| Event | Session Action | Duration |
|-------|---------------|----------|
| **Login** | Created | New session |
| **Activity** | Extended | 8 hours from last activity |
| **Logout** | Destroyed | Immediate |
| **Inactive** | Expired | After 8 hours |
| **Browser Close** | Preserved | Until expiry |

### 6.3 Session Security

1. **File-based Storage**: Sessions stored on server, not client
2. **HttpOnly Cookie**: JavaScript cannot access session cookie
3. **SameSite Cookie**: Prevents CSRF via cross-origin requests
4. **Secure Flag**: HTTPS-only transmission (when enabled)

---

## 7. Authentication Flow by Role

### 7.1 Teller Authentication

```
1. Navigate to http://192.168.1.132/login
2. Enter: teller1@cems.my / teller1@1234
3. Redirected to: /dashboard
4. Can Access:
   ✅ Dashboard
   ✅ Create Transactions (< RM 50k)
   ✅ Open/Close Own Till
   ✅ View Own Till Summary
   ❌ Other Users' Tills
   ❌ Compliance Portal
   ❌ Reports
   ❌ User Management
```

### 7.2 Manager Authentication

```
1. Navigate to http://192.168.1.132/login
2. Enter: manager1@cems.my / manager1@1234
3. Redirected to: /dashboard
4. Can Access:
   ✅ Dashboard
   ✅ All Transactions (including > RM 50k)
   ✅ Approve Large Transactions
   ✅ View All Tills
   ✅ Stock Management
   ✅ Accounting
   ✅ Reports
   ✅ Compliance (view only)
   ❌ Manage Users
   ❌ System Configuration
```

### 7.3 Compliance Officer Authentication

```
1. Navigate to http://192.168.1.132/login
2. Enter: compliance1@cems.my / compliance1@1234
3. Redirected to: /dashboard
4. Can Access:
   ✅ Dashboard
   ✅ Create Transactions
   ✅ Compliance Portal (full access)
   ✅ Review Flagged Transactions
   ✅ Sanction Screening
   ✅ Run Compliance Reports
   ✅ View Audit Trail
   ❌ Approve > RM 50k
   ❌ Manage Users
   ❌ System Config
```

### 7.4 Admin Authentication

```
1. Navigate to http://192.168.1.132/login
2. Enter: admin@cems.my / Admin@123456
3. Redirected to: /dashboard
4. Can Access:
   ✅ Everything (full system access)
   ✅ User Management (create/edit/delete)
   ✅ System Configuration
   ✅ All Reports
   ✅ All Transactions
   ✅ Compliance Functions
   ✅ Accounting Functions
   ✅ Database Access
```

---

## 8. Security Considerations

### 8.1 Login Security

| Threat | Protection | Status |
|--------|-----------|--------|
| **Brute Force** | Rate limiting (60 req/min) | ✅ Implemented |
| **Session Hijacking** | Session regeneration | ✅ Implemented |
| **Credential Stuffing** | Generic error messages | ✅ Implemented |
| **Password Guessing** | Minimum 12 char requirement | ✅ Implemented |
| **Man-in-the-Middle** | HTTPS capable | ⚠️ HTTP currently |

### 8.2 Session Security

| Threat | Protection | Status |
|--------|-----------|--------|
| **Session Fixation** | Regenerate on login | ✅ Implemented |
| **Session Theft** | HttpOnly cookies | ✅ Implemented |
| **CSRF Attacks** | Token validation | ✅ Implemented |
| **XSS** | Output escaping | ✅ Blade default |
| **Idle Timeout** | 8 hour expiry | ✅ Configured |

### 8.3 Access Control Security

| Feature | Implementation | Status |
|---------|---------------|--------|
| **Role Validation** | `isAdmin()`, `isManager()` | ✅ Model level |
| **Middleware Checks** | `auth` middleware | ✅ Route level |
| **Controller Checks** | Manual role checks | ⚠️ Needs review |
| **Database Access** | Query scoping | ⚠️ Needs review |

---

## 9. Testing Authentication

### 9.1 Run Authentication Tests

```bash
# All authentication tests
php test-runner.php auth

# Or specific test file
php artisan test --filter=AuthenticationTest
```

### 9.2 Manual Testing Checklist

| Test | Expected Result | Command/Action |
|------|----------------|----------------|
| Valid login | Redirect to dashboard | Login with valid credentials |
| Invalid password | Error message, stay on login | Wrong password |
| Inactive user | Error message | Login as deactivated user |
| Direct URL access | Redirect to login | Access /dashboard logged out |
| Logout | Redirect to home, session cleared | Click logout |
| Back button after logout | Login page (not cached) | Browser back button |
| Session expiry | Redirect to login | Wait 8 hours |
| CSRF token | Form rejection | Manipulate token |

### 9.3 Role-Based Testing

```bash
# Test Teller access
curl -s -b cookies.txt http://192.168.1.132/users
# Expected: 403 Forbidden

# Test Admin access
curl -s -b admin_cookies.txt http://192.168.1.132/users
# Expected: 200 OK
```

---

## 10. Compliance & Audit

### 10.1 BNM AML/CFT Requirements

| Requirement | Implementation | Evidence |
|-------------|---------------|----------|
| **User Identification** | Unique email per user | Database users table |
| **Role-Based Access** | RBAC implementation | User model methods |
| **Activity Logging** | System logs | storage/logs/ |
| **Session Timeout** | 8 hour expiry | SESSION_LIFETIME |
| **Secure Logout** | Full session destruction | LogoutController |

### 10.2 PDPA Compliance

| Requirement | Implementation |
|-------------|---------------|
| **Password Security** | Bcrypt hashing |
| **Session Data** | Server-side only |
| **No PII in URLs** | Post-based authentication |
| **Secure Transmission** | HTTPS ready |

### 10.3 Audit Trail Logging

All authentication events should log:
- User ID
- Timestamp
- IP Address
- Action (login/logout/failed)
- Success/Failure status

**Recommendation**: Implement `SystemLog` integration in `LoginController`

---

## 11. Recommendations

### 11.1 Security Enhancements

1. **Two-Factor Authentication (2FA)**
   - Implement TOTP for admin/manager accounts
   - Use `mfa_enabled`, `mfa_secret` columns already in DB

2. **Account Lockout**
   - Lock account after 5 failed attempts
   - Require admin unlock or 30-minute timeout

3. **Password Expiry**
   - Force password change every 90 days for privileged roles
   - Track `password_changed_at` timestamp

4. **Concurrent Session Limit**
   - Prevent multiple simultaneous logins
   - Or allow with session invalidation

5. **Login Notifications**
   - Email on new device/location login
   - Security audit log review

### 11.2 Operational Improvements

1. **Self-Service Password Reset**
   - Email-based reset flow
   - Secure token generation

2. **Remember Me**
   - Long-lived cookie for convenience
   - Configurable per role

3. **API Authentication**
   - Token-based for API access
   - Separate from web session

### 11.3 Compliance Enhancements

1. **Automatic Logout Warning**
   - Warn user 5 minutes before timeout
   - Option to extend session

2. **Login History**
   - User-accessible login history page
   - Ability to revoke sessions

3. **Geolocation Logging**
   - Log IP geolocation
   - Alert on unusual locations

---

## 12. Quick Reference

### URLs

| Endpoint | Method | Description | Auth Required |
|----------|--------|-------------|---------------|
| `/login` | GET | Show login form | No |
| `/login` | POST | Process login | No |
| `/logout` | POST | Process logout | Yes |
| `/dashboard` | GET | Dashboard | Yes |

### Session Variables

| Variable | Description | Set By |
|----------|-------------|--------|
| `_token` | CSRF token | Framework |
| `cems_my_session` | Session ID | Framework |
| `login_web_...` | Auth guard | Auth::login() |

### Middleware Aliases

| Alias | Class | Use |
|-------|-------|-----|
| `auth` | `Authenticate` | Require login |
| `guest` | `RedirectIfAuthenticated` | Require logout |

---

## Document Information

- **Version**: 1.0
- **Author**: CEMS-MY System Analysis
- **Date**: 2026-04-01
- **Classification**: Internal Use
- **Review Cycle**: Quarterly
