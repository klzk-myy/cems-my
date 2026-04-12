# Comprehensive Test Design Document
## CEMS-MY Laravel Application

### Current State Analysis
- **Total Test Files**: 107
- **Passing Tests**: 1,069
- **Services**: 49 services with 435+ public methods
- **Models**: 51 models with 420+ public methods
- **Middleware**: 19 middleware classes

---

## 1. EDGE CASE TESTING

### 1.1 Boundary Value Testing for Monetary Amounts
**Priority: Critical**
**Estimated Tests: 45**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Transaction at exactly RM 3,000 (CDD threshold) | amount_local = 3000.00 | CDD Level: Standard | Critical |
| Transaction at RM 2,999.99 (just below threshold) | amount_local = 2999.99 | CDD Level: Simplified | Critical |
| Transaction at exactly RM 50,000 (large transaction threshold) | amount_local = 50000.00 | Status: Pending, requires approval | Critical |
| Transaction at RM 49,999.99 (just below threshold) | amount_local = 49999.99 | Status: Completed | Critical |
| Transaction at RM 10,000 (CTOS threshold) | amount_local = 10000.00 | CTOS report required | Critical |
| Transaction at RM 9,999.99 (just below CTOS) | amount_local = 9999.99 | No CTOS required | Critical |
| Transaction with 6+ decimal places precision | rate = 4.723456 | Proper BCMath handling, no float conversion | High |
| Transaction with maximum allowed amount | amount = 999999999.99 | Success, no overflow | High |
| Transaction with zero amount | amount = 0 | Validation error | Critical |
| Transaction with negative amount | amount = -100 | Validation error | Critical |
| Transaction with very small amount (0.01) | amount = 0.01 | Success, precision maintained | Medium |
| Currency position at zero balance | balance = 0 | Cannot sell, proper error message | Critical |
| Currency position at exactly requested amount | balance = sell_amount | Success, balance becomes 0 | Critical |
| Average cost calculation with extreme values | Old: 0.000001 @ 1000000 | Proper weighted average calculation | High |
| Revaluation P&L with zero rate change | old_rate = new_rate | Zero P&L, no division issues | Medium |

**Key Assertions:**
- Assert CDD level determination is accurate at boundaries
- Assert transaction status changes at thresholds
- Assert BCMath precision maintained (no float casting)
- Assert proper validation errors for invalid amounts
- Assert currency position boundaries respected

### 1.2 Time-Based Edge Cases
**Priority: High**
**Estimated Tests: 35**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Transaction at 23:59:59 (end of day) | timestamp = 23:59:59 | Recorded on correct date, period open | Critical |
| Transaction spanning midnight | start before, end after | Proper date attribution | Critical |
| Transaction on last day of month | date = last day | Correct accounting period assignment | Critical |
| Transaction on first day of month | date = 1st | Correct period opening | High |
| Transaction on year boundary | Dec 31 → Jan 1 | Correct fiscal year handling | Critical |
| Transaction on fiscal year end | fiscal_year_end_date | Period closing restrictions enforced | Critical |
| Velocity check spanning midnight | transactions at 23:55 and 00:05 | 24h window calculated correctly | High |
| Structuring detection across hour boundary | 3 transactions in 59 minutes vs 61 minutes | Correct count within 1 hour window | High |
| Session timeout at exactly threshold | idle = timeout_minutes | Graceful logout, no partial operations | Critical |
| STR deadline calculation over weekends | suspicion_date = Friday | Deadline excludes weekends (next Tuesday) | Critical |
| Aggregate transaction lookback over month end | 7-day lookback spans month | Correct date range calculation | High |
| Till close at end of day | close_time = 23:59:59 | Proper reconciliation, no data loss | Critical |
| Rate API fetch with stale timestamp | cached_rate from yesterday | Fresh rates fetched, cache invalidated | Medium |
| Scheduled job during DST transition | 2:00 AM on transition day | Consistent scheduling behavior | Medium |

**Key Assertions:**
- Assert date/time boundaries handled correctly
- Assert no off-by-one errors in date calculations
- Assert accounting periods correctly determined
- Assert fiscal year boundaries enforced
- Assert time windows (velocity, structuring) calculate correctly
- Assert session timeout edge cases handled

### 1.3 Concurrent Transaction Handling
**Priority: Critical**
**Estimated Tests: 25**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Simultaneous buy transactions updating same position | 2 concurrent buys | Both succeed, position sum correct | Critical |
| Simultaneous sell transactions (stock sufficient) | 2 sells of 500 each (1000 available) | Both succeed, balance = 0 | Critical |
| Simultaneous sell transactions (insufficient stock) | 2 sells of 600 each (1000 available) | One succeeds, one fails with error | Critical |
| Concurrent buy and sell of same currency | Buy 1000 + Sell 500 simultaneously | Proper locking, no deadlock | Critical |
| Multiple concurrent large transactions requiring approval | 3 pending transactions | All queued properly, no race condition | Critical |
| Concurrent approval of same transaction | 2 managers approve simultaneously | One succeeds, one gets "already processed" | Critical |
| Concurrent till operations (open/close) | Open while closing | Proper locking, clear error message | Critical |
| Concurrent stock transfers | Send and receive simultaneously | Proper state management | High |
| Concurrent journal entry posting | 2 entries posting | Both succeed, ledger consistent | Critical |
| Concurrent rate updates | API fetch during transaction | Rate consistent throughout transaction | High |
| Optimistic locking with version mismatch | Transaction version outdated | Proper exception, no stale data | Critical |
| Database deadlock scenario | Circular lock dependency | Deadlock detected, transaction retries | High |

**Key Assertions:**
- Assert database locks prevent race conditions
- Assert optimistic locking works correctly
- Assert no deadlocks or lock timeouts
- Assert concurrent operations maintain data consistency
- Assert proper error handling for concurrent conflicts
- Assert transaction rollback on concurrent modification

### 1.4 Large Data Volume Scenarios
**Priority: High**
**Estimated Tests: 20**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Customer with 10,000+ transactions | 10000 transaction records | Pagination works, queries performant | High |
| Transaction import with 1,000 rows | CSV with 1000 rows | Batch processing, memory efficient | High |
| Transaction import with 10,000 rows | CSV with 10000 rows | Chunked processing, no memory overflow | High |
| Audit log with 100,000+ entries | Large system_logs table | Query performance acceptable (< 2s) | Medium |
| Velocity check with 500+ transactions in 24h | Customer with 500+ recent txns | Query optimized, uses indexes | High |
| Aggregate calculation with 1000+ related transactions | 7-day lookback with 1000 txns | Memory efficient calculation | High |
| Report generation for full year | 12 months of data | Chunked generation, streaming output | Medium |
| Customer search with 50,000+ customers | Full text search | Index usage, response < 1s | Medium |
| Compliance flags with 10,000+ open items | Large flagged_transactions table | Pagination, filtering performant | Medium |
| Currency position with 10,000+ movements | High transaction volume | Average cost calculation accurate | High |
| Sanctions screening with large lists | 10,000+ sanction entries | Batch processing, timeout handling | Medium |
| Revaluation with 50+ currency positions | Multiple currencies | Batch revaluation, proper errors | Medium |
| Dashboard with 1,000+ daily transactions | Heavy load | Cached data, async calculations | Low |
| Export of 50,000+ records | Large dataset export | Streaming response, memory management | Medium |

**Key Assertions:**
- Assert query performance within acceptable limits
- Assert memory usage stays within limits
- Assert pagination works correctly
- Assert no timeouts on large operations
- Assert batch processing handles large volumes
- Assert proper index usage for performance

---

## 2. ERROR HANDLING TESTS

### 2.1 Database Connection Failures
**Priority: Critical**
**Estimated Tests: 20**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Database connection lost during transaction | Kill connection mid-transaction | Graceful rollback, user-friendly error | Critical |
| Database timeout during long query | Query exceeds timeout | Timeout exception, operation fails safely | Critical |
| Deadlock detected during concurrent access | Simultaneous conflicting updates | Deadlock exception, automatic retry | Critical |
| Database connection pool exhausted | Max connections reached | Queue request, retry logic | High |
| Temporary database unavailability | DB restart | Health check failure, graceful degradation | High |
| Write failure to audit log | system_logs insert fails | Transaction still completes, log warning | Critical |
| Foreign key constraint violation | Invalid reference | Validation error, proper message | Critical |
| Unique constraint violation | Duplicate entry | Validation error, user-friendly message | Critical |
| Transaction rollback on error | Error during multi-step operation | All changes rolled back, no partial state | Critical |
| Connection restored after failure | DB comes back online | Automatic reconnection, operation resumes | High |

**Key Assertions:**
- Assert transactions rollback on database errors
- Assert user-friendly error messages (no SQL exposed)
- Assert audit trail captures error context
- Assert no data corruption on connection failure
- Assert proper exception hierarchy (DB exceptions wrapped)
- Assert connection retry logic works

### 2.2 External API Timeouts/Failures
**Priority: Critical**
**Estimated Tests: 18**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Exchange rate API timeout | Slow/no response | Fallback to cached rates, log warning | Critical |
| Exchange rate API returns 500 error | Server error | Graceful handling, use cached rates | Critical |
| Exchange rate API returns invalid JSON | Malformed response | Validation error, fallback handling | Critical |
| Exchange rate API rate limit exceeded | 429 response | Backoff and retry, queue for later | Critical |
| Exchange rate API unauthorized | 401/403 response | Log error, alert admin, use fallback | High |
| CTOS submission API timeout | Network timeout | Mark as pending retry, queue job | Critical |
| CTOS submission API failure | 5xx error | Retry with exponential backoff | Critical |
| Sanctions list API unavailable | External service down | Use cached list, log incident | High |
| STR submission to BNM timeout | Network issues | Save draft, retry later | Critical |
| Document verification API failure | Third-party service error | Queue for manual verification | Medium |
| Email service (SMTP) failure | Mail server down | Queue emails, retry later | Medium |
| SMS notification service failure | Provider error | Log failure, continue operation | Low |
| File storage service (S3) timeout | Upload timeout | Retry upload, exponential backoff | Medium |

**Key Assertions:**
- Assert graceful degradation on API failure
- Assert cached data used when available
- Assert retry logic with exponential backoff
- Assert queue jobs created for async retry
- Assert proper logging of API failures
- Assert no blocking on non-critical external calls
- Assert circuit breaker pattern (if implemented)

### 2.3 Invalid Input Scenarios
**Priority: Critical**
**Estimated Tests: 40**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Null required fields | customer_id = null | Validation error, field highlighted | Critical |
| Empty string required fields | purpose = "" | Validation error, proper message | Critical |
| Invalid email format | email = "not-an-email" | Validation error | Critical |
| Invalid phone number format | contact = "abc123" | Validation error | Critical |
| Invalid ID number format | MyKad = "invalid" | Validation error | Critical |
| Invalid currency code | currency = "XYZ" | Validation error, supported currencies listed | Critical |
| Invalid transaction type | type = "INVALID" | Enum validation error | Critical |
| Negative rate | rate = -4.5 | Validation error | Critical |
| Zero rate | rate = 0 | Validation error | Critical |
| Rate with too many decimals | rate = 4.723456789 | Precision validation or truncation | Medium |
| Amount exceeding max limit | amount = 1000000000 | Validation error | Critical |
| Invalid date format | date = "not-a-date" | Validation error | High |
| Future date for transaction | date = tomorrow | Validation error | High |
| Past date outside allowed range | date = 1 year ago | Validation error | Medium |
| SQL injection attempt | name = "'; DROP TABLE" | Sanitized, no injection | Critical |
| XSS attempt in text fields | purpose = "<script>alert(1)</script>" | Escaped/removed | Critical |
| Path traversal attempt | filename = "../../../etc/passwd" | Sanitized, validation error | Critical |
| Oversized file upload | file > max_upload_size | Validation error before processing | Critical |
| Wrong file type upload | Upload .exe as document | MIME type validation error | Critical |
| Invalid JSON in API request | Malformed JSON | 400 error, clear message | Critical |
| Missing CSRF token | Form without token | 419 error, session expired message | Critical |
| Invalid CSRF token | Wrong token | 419 error | Critical |
| Invalid UUID format | id = "not-a-uuid" | Validation error | High |
| Unicode/emoji in name field | name = "Test 😀" | Accepted or sanitized properly | Medium |
| Very long string (> 10,000 chars) | Description field overflow | Truncated or validation error | Medium |
| Binary data in text field | Non-UTF8 bytes | Sanitized or validation error | Medium |
| HTML entities injection | name = "&lt;script&gt;" | Proper encoding | Medium |
| Null bytes in string | "test\x00.txt" | Sanitized or rejected | Medium |
| Array where string expected | amount = ["100"] | Type validation error | High |
| String where numeric expected | amount = "abc" | Numeric validation error | Critical |

**Key Assertions:**
- Assert all inputs validated before processing
- Assert validation errors are user-friendly
- Assert no SQL injection possible (prepared statements)
- Assert no XSS vulnerabilities (output encoding)
- Assert proper type checking
- Assert boundary validation on all inputs
- Assert file upload security (MIME, size, extension)

### 2.4 Race Condition Tests
**Priority: High**
**Estimated Tests: 15**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Double-submit transaction (rapid clicks) | Two requests within 100ms | One succeeds, one rejected as duplicate | Critical |
| Cancel already cancelled transaction | Concurrent cancel requests | One succeeds, second gets "already cancelled" | Critical |
| Approve already approved transaction | Two approval requests | One succeeds, second gets "already processed" | Critical |
| Update transaction during approval | Edit while approving | Conflict error, optimistic locking works | Critical |
| Close till during transaction creation | Till closes mid-transaction | Transaction fails with "till closed" | Critical |
| Update customer during transaction | Customer modified mid-transaction | Transaction uses snapshot or fails gracefully | High |
| Concurrent rate updates | Rate changes during calculation | Calculation uses rate at transaction start | High |
| Session expiration mid-transaction | Session times out | Current operation completes, next requires re-auth | Critical |
| Concurrent password reset attempts | Two reset requests | Both valid, both send emails | Medium |
| Lock escalation timeout | Waiting for lock too long | Timeout error, operation aborted | High |

**Key Assertions:**
- Assert optimistic locking prevents lost updates
- Assert idempotency keys prevent duplicates
- Assert no race conditions in balance updates
- Assert proper error messages for race conditions
- Assert transactions are atomic
- Assert locks released properly on errors

---

## 3. SECURITY TESTS

### 3.1 SQL Injection Attempts
**Priority: Critical**
**Estimated Tests: 25**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Classic SQL injection in search | "' OR '1'='1" | No injection, parameter binding works | Critical |
| Union-based SQL injection | "' UNION SELECT * FROM users--" | No data leakage | Critical |
| Blind SQL injection timing attack | "' AND (SELECT * FROM (SELECT(SLEEP(5)))a)" | No delay, query optimized | Critical |
| SQL injection in LIKE clause | "%' OR 1=1--" | Escaped properly, partial match only | Critical |
| SQL injection in ORDER BY | "id; DROP TABLE users--" | Whitelist validation, no injection | Critical |
| SQL injection in numeric parameter | "1 OR 1=1" | Type casting prevents injection | Critical |
| SQL injection in JSON field | '{"key": "value\' OR \'1\'=\'1"}' | JSON validation, no injection | Critical |
| SQL injection in batch operations | [1, 2, "3 OR 1=1"] | Type validation on each element | Critical |
| SQL injection via file upload | CSV with SQL in cell | Sanitized on import | High |
| Second-order SQL injection | Stored XSS executes as SQL later | Output encoding prevents | High |
| SQL injection in API parameters | ?search=' OR '1'='1 | Parameter binding | Critical |
| SQL injection in headers | X-Custom: ' OR '1'='1 | Headers sanitized, no query building | High |
| SQL injection in cookies | Cookie tampering | Cookies verified, not used in queries | Medium |
| Error-based SQL injection | Input causing error | Generic error messages, no SQL details | Critical |
| Stacked query attempt | "'; DELETE FROM users;--" | Only first query executed | Critical |
| Time-based blind injection | "' AND pg_sleep(5)--" | Query timeout, no delay exposed | High |
| Out-of-band SQL injection | "' AND LOAD_FILE(...)" | Network restricted, no callback | Medium |
| Comment injection | "admin'--" | Comment stripped or escaped | Critical |
| Boolean-based blind injection | "' AND 1=1--" vs "' AND 1=2--" | Same response time, no leakage | Critical |
| SQL injection in pivot/unpivot | Dynamic SQL building | Prepared statements used | High |

**Key Assertions:**
- Assert all queries use prepared statements
- Assert no dynamic SQL concatenation
- Assert ORM/Query Builder used exclusively
- Assert error messages don't expose SQL
- Assert input validation before query building
- Assert no user input in table/column names
- Assert parameterized queries in LIKE clauses

### 3.2 XSS Prevention
**Priority: Critical**
**Estimated Tests: 20**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Reflected XSS in search results | Search: "<script>alert(1)</script>" | HTML escaped in output | Critical |
| Stored XSS in transaction purpose | Purpose: "<img src=x onerror=alert(1)>" | Sanitized on display | Critical |
| XSS in customer name | Name: "<script>alert('XSS')</script>" | Escaped in all views | Critical |
| XSS in audit log description | Description with script tags | HTML entities encoded | Critical |
| XSS via URL parameters | ?callback=<script>alert(1)</script> | URL validation, no execution | Critical |
| XSS in file upload filename | Filename: "<script>alert(1)</script>.pdf" | Sanitized filename | Critical |
| DOM-based XSS | Fragment: #<script>alert(1)</script> | Fragment sanitized | High |
| XSS in JSON response | API returns user input | JSON encoding prevents execution | Critical |
| XSS via CSS injection | Style: "expression(alert(1))" | CSS sanitized | Medium |
| XSS in markdown/rendered content | "[click me](javascript:alert(1))" | Link validation | Medium |
| XSS via data URI | "data:text/html,<script>alert(1)</script>" | URL scheme whitelist | Medium |
| XSS in SVG upload | SVG with embedded script | SVG sanitized or rejected | Medium |
| XSS in PDF metadata | PDF with malicious metadata | Metadata stripped | Low |
| XSS in Excel export | Formula injection: =cmd|' /C calc'!A0 | Formula detection, prefix added | Medium |
| XSS via HTTP headers | Header injection attempt | Headers sanitized | Medium |
| XSS in error messages | Error with user input | User input escaped | Critical |
| XSS in email templates | Template variables escaped | HTML entities in emails | Critical |
| XSS via template injection | Server-side template injection | Template sandboxing | High |
| Content Security Policy violations | Policy headers present | CSP headers enforced | High |
| XSS protection headers | X-XSS-Protection header | Headers present and correct | Medium |

**Key Assertions:**
- Assert Blade {{ }} escapes output by default
- Assert {!! !!} used only for trusted content
- Assert Content Security Policy headers set
- Assert XSS protection headers present
- Assert all user input escaped in views
- Assert sanitization libraries used where appropriate
- Assert context-aware encoding (HTML vs JS vs CSS)

### 3.3 CSRF Protection
**Priority: Critical**
**Estimated Tests: 15**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| CSRF token missing | POST without _token | 419 error, CSRF exception | Critical |
| CSRF token invalid | POST with wrong _token | 419 error | Critical |
| CSRF token expired | Old session token | 419 error, redirect to login | Critical |
| CSRF token reuse | Same token used twice | Second request succeeds (stateless) | Medium |
| CSRF via AJAX | fetch() without X-CSRF-TOKEN | 419 error | Critical |
| CSRF via form submission | External form posts to site | 419 error | Critical |
| CSRF via GET request | GET to state-changing endpoint | Route not defined or protected | Critical |
| CSRF token in meta tag | @csrf present in forms | Token rendered in all forms | Critical |
| CSRF protection bypass attempt | Header manipulation | Protection still enforced | Critical |
| Double-submit cookie | Cookie-based token | Token matches cookie | High |
| CSRF in file upload forms | multipart/form-data | CSRF token included | Critical |
| CSRF token in API requests | API endpoints | Token or JWT required | Critical |
| SameSite cookie attribute | Cookie settings | SameSite=Lax or Strict | High |
| Origin header validation | Cross-origin request | Validated against whitelist | High |
| CSRF protection on state-changing routes | All POST/PUT/DELETE | Middleware applied | Critical |

**Key Assertions:**
- Assert @csrf directive in all forms
- Assert 419 response for missing/invalid tokens
- Assert tokens are cryptographically secure
- Assert SameSite cookie attribute set
- Assert state-changing routes use POST/PUT/DELETE
- Assert API routes use token-based auth or CSRF
- Assert CSRF middleware applied to web routes

### 3.4 Authorization Bypass Attempts
**Priority: Critical**
**Estimated Tests: 30**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Access admin routes as teller | Teller user → /users | 403 Forbidden | Critical |
| Access compliance routes as manager | Manager → /compliance | 403 Forbidden | Critical |
| Access accounting as teller | Teller → /accounting | 403 Forbidden | Critical |
| Direct URL access to transaction approval | Teller → POST /approve | 403 Forbidden | Critical |
| Access other user's transactions | User A views User B's txns | 403 or filtered results | Critical |
| Access other branch data | Branch A user → Branch B data | 403 or no results | Critical |
| Modify URL parameter to access restricted record | Change ID in URL | 403 if unauthorized | Critical |
| Access cancelled/deleted transaction | View deleted record | 403 or not found | Critical |
| Access pending approval without role | Regular user → approval | 403 Forbidden | Critical |
| Access STR reports as teller | Teller → /str | 403 Forbidden | Critical |
| Access user management as manager | Manager → /users/create | 403 Forbidden | Critical |
| Access system settings as compliance | Compliance → /settings | 403 Forbidden | Critical |
| Bypass middleware via OPTIONS request | OPTIONS /admin | Still requires auth | High |
| Access with manipulated role in JWT | Tampered JWT payload | Signature validation fails | Critical |
| Access after session expiration | Expired session cookie | Redirect to login | Critical |
| Access with null session | No session | Redirect to login | Critical |
| Privilege escalation via mass assignment | Set role via request | Mass assignment protection | Critical |
| Access disabled user account | is_active = false | Login denied | Critical |
| Access MFA-protected route without MFA | User without MFA setup | Redirect to MFA setup | Critical |
| Access MFA-verified route without verification | MFA not verified | Prompt for MFA code | Critical |
| Access API without valid token | No Authorization header | 401 Unauthorized | Critical |
| Access with expired API token | Expired token | 401 Unauthorized | Critical |
| Access with revoked token | Revoked token | 401 Unauthorized | Critical |
| Access export functionality as teller | Teller → /export | 403 Forbidden | Critical |
| Access audit logs as non-admin | Regular user → /audit | 403 Forbidden | Critical |
| Access data breach alerts as teller | Teller → /data-breach-alerts | 403 Forbidden | Critical |
| Direct access to controller methods | Bypass route | Route definition required | High |
| Access via case-insensitive URL | /ADMIN vs /admin | Normalized, auth checked | Medium |
| Access with URL encoding bypass | Encoded characters | Decoded then checked | Medium |
| Access to .env file | GET /.env | 404, file protected | Critical |

**Key Assertions:**
- Assert role-based access control enforced
- Assert middleware applied to all sensitive routes
- Assert authorization checks in controllers
- Assert branch scoping applied correctly
- Assert resource ownership verified
- Assert API token validation correct
- Assert no authorization bypass via parameter tampering
- Assert enum-based role checking used

### 3.5 Session Fixation
**Priority: Critical**
**Estimated Tests: 12**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Session fixation on login | Attacker sets session ID | New session ID generated | Critical |
| Session fixation on role change | Role upgraded | Session regenerated | Critical |
| Session ID in URL | ?PHPSESSID=xxx | Session ID not in URL | Critical |
| Predictable session IDs | Sequential IDs | Cryptographically random IDs | Critical |
| Session ID exposure in logs | Session in error logs | Session IDs masked in logs | Medium |
| Session fixation via XSS | document.cookie manipulation | HttpOnly flag prevents | Critical |
| Session hijacking via stolen cookie | Cookie theft | IP binding or device fingerprinting | Medium |
| Concurrent session handling | Multiple logins | Sessions managed correctly | Medium |
| Session invalidation on logout | Logout action | Session destroyed server-side | Critical |
| Session timeout handling | Inactivity timeout | Session invalidated properly | Critical |
| Session fixation after MFA | Post-MFA login | Session regenerated | Critical |
| Session security headers | Cookie attributes | Secure, HttpOnly, SameSite set | Critical |

**Key Assertions:**
- Assert session_regenerate_id(true) on login
- Assert session_destroy() on logout
- Assert session.cookie_secure = true
- Assert session.cookie_httponly = true
- Assert session.cookie_samesite set
- Assert no session ID in URL
- Assert random, unpredictable session IDs
- Assert session timeout enforced

---

## 4. PERFORMANCE TESTS

### 4.1 Load Testing for Transaction Creation
**Priority: High**
**Estimated Tests: 15**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Sustained load: 100 concurrent transactions | 100 users, 60 seconds | All succeed, < 2s response time | High |
| Spike load: 500 transactions in 10 seconds | Sudden spike | Queue handles, no failures | High |
| Load with database connection pool | Max connections | Connection pooling works | High |
| Load with cache enabled | Cache hits | Performance improvement measurable | Medium |
| Load with cache disabled | Cache misses | Graceful degradation | Medium |
| Load during compliance monitoring | Background jobs | Async processing, no blocking | High |
| Load with position updates | High frequency | Lock contention minimal | High |
| Load with audit logging | All actions logged | Async logging, no performance hit | Medium |
| Load with rate limiting | Exceed rate limit | 429 errors returned properly | Medium |
| Load on approval workflow | Multiple approvals | Optimistic locking handles | High |
| Load on cancellation | Multiple cancellations | Proper locking, no deadlocks | High |
| Load on reporting endpoints | Concurrent reports | Query optimization, caching | Medium |
| Load on customer search | Full-text search | Index usage, < 1s response | Medium |
| Load on currency rate fetch | Cache expiry | Stale-while-revalidate pattern | Medium |
| Load on export functionality | Large exports | Streaming, chunked responses | Medium |

**Key Assertions:**
- Assert response times under load meet SLAs
- Assert no errors or failures under load
- Assert resource utilization within limits
- Assert graceful degradation under extreme load
- Assert rate limiting effective
- Assert connection pooling prevents exhaustion

### 4.2 Query Optimization Verification
**Priority: High**
**Estimated Tests: 18**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| N+1 query detection | List with relations | Eager loading used | Critical |
| Missing index query | Unindexed column search | Query analyzer suggests index | High |
| Full table scan detection | Large table query | Index usage verified | High |
| Slow query logging | Queries > 1s | Logged for review | Medium |
| Query execution plan review | EXPLAIN on critical queries | Index seek, not scan | High |
| Pagination with large offsets | Page 1000 of results | Cursor-based or optimized | Medium |
| Transaction list with filters | Multiple filter criteria | Composite index usage | High |
| Audit log queries | Large system_logs table | Partitioned or archived | Medium |
| Report generation queries | Aggregations over large data | Materialized views or caching | Medium |
| Search query performance | Full-text search | Full-text index usage | Medium |
| Count query optimization | Large table count | Approximate or cached count | Medium |
| Join order optimization | Multiple table joins | Optimizer chooses correct order | Medium |
| Subquery vs join performance | Equivalent queries | Join preferred | Low |
| Query cache effectiveness | Repeated queries | Cache hit ratio > 80% | Medium |
| Index fragmentation check | Highly updated tables | Maintenance scheduled | Low |
| Explain plan for velocity check | 24h aggregation query | Optimized execution plan | High |
| Explain plan for structuring check | 1-hour window query | Proper index usage | High |
| Explain plan for aggregate transactions | 7-day lookback | Efficient date range scan | High |

**Key Assertions:**
- Assert no N+1 queries in critical paths
- Assert indexes used for WHERE clauses
- Assert query execution time < 100ms for critical queries
- Assert EXPLAIN plans show index usage
- Assert no full table scans on large tables
- Assert query cache configured correctly

### 4.3 Cache Behavior Tests
**Priority: Medium**
**Estimated Tests: 15**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Cache hit for exchange rates | Cached rate | Returns cached value, no API call | Medium |
| Cache miss for exchange rates | Expired cache | Fetches fresh, updates cache | Medium |
| Cache invalidation on rate update | Rate updated | Cache cleared, fresh on next request | Medium |
| Cache stampede prevention | Cache expiry | Staggered or locked refresh | Medium |
| Cache for user permissions | Role permissions | Cached per user, invalidated on change | Low |
| Cache for customer data | Customer profile | TTL respected, stale data handled | Low |
| Cache for compliance rules | AML rules | Rules cached, invalidated on update | Medium |
| Cache warm-up | Application start | Critical data pre-cached | Low |
| Cache eviction under memory pressure | Memory full | LRU eviction works | Low |
| Distributed cache consistency | Multiple instances | Cache invalidation broadcast | Low |
| Cache for dashboard data | Dashboard stats | Cached, background refresh | Medium |
| Cache for currency positions | Position data | Real-time vs cached trade-off | High |
| Cache for sanctions lists | Sanctions data | Cached, periodic refresh | Medium |
| Cache bypass for critical operations | Force refresh | Bypass cache flag works | Medium |
| Cache failure handling | Redis unavailable | Fallback to database | Medium |

**Key Assertions:**
- Assert cache hit improves performance
- Assert cache TTL respected
- Assert cache invalidation works correctly
- Assert graceful fallback on cache failure
- Assert no stale data served
- Assert cache stampede prevented

---

## 5. INTEGRATION TESTS

### 5.1 Multi-Service Interactions
**Priority: High**
**Estimated Tests: 25**

| Test Scenario | Services Involved | Expected Output | Priority |
|--------------|-------------------|-----------------|----------|
| Complete transaction lifecycle | Transaction + Compliance + Accounting + Position | All services coordinated | Critical |
| Transaction with compliance hold | Transaction + Compliance + Alert | Alert created, transaction held | Critical |
| Transaction approval workflow | Transaction + Approval + Accounting | Journal entries created on approval | Critical |
| Transaction cancellation | Transaction + Cancellation + Accounting | Reversal entries created | Critical |
| Large transaction with EDD | Transaction + Compliance + EDD + Alert | EDD record created | Critical |
| Sanctions match workflow | Transaction + Sanctions + Alert + Case | Case auto-created | Critical |
| STR report generation | Compliance + STR + Audit | Report generated, audit logged | Critical |
| Currency revaluation | Revaluation + Accounting + Position | Entries created, positions updated | Critical |
| Stock transfer workflow | StockTransfer + Position + Accounting + Audit | All updates coordinated | Critical |
| Counter open/close | Counter + Position + Till | Balances reconciled | Critical |
| Counter handover | Counter + Position + User + Audit | Custody transferred | Critical |
| Fiscal year closing | FiscalYear + Accounting + Period | All periods closed, entries created | Critical |
| Budget vs actual reporting | Budget + Accounting + Ledger | Reports accurate | Medium |
| Multi-branch transaction | Transaction + BranchScope + Position | Data scoped correctly | Critical |
| Customer risk update | Customer + RiskScoring + Compliance | Risk updated, alerts triggered | High |
| Transaction import with errors | Import + Transaction + ErrorHandler | Errors captured, partial success | High |
| Transaction retry from DLQ | Recovery + Transaction + ErrorHandler | Retry successful | High |
| CTOS report submission | CTOS + Transaction + ExternalAPI | Submitted, tracked | Critical |
| Data breach detection | DataBreach + Audit + Email | Alert created, notification sent | High |
| Rate API integration | RateApi + Cache + ExternalAPI | Rates fetched, cached, logged | Medium |
| Sanctions rescreening job | Sanctions + Customer + Compliance | Batch processing, alerts created | High |
| Velocity monitoring | Monitoring + Transaction + Alert | Threshold exceeded, alert created | Critical |
| Structuring detection | Monitoring + Transaction + Alert | Pattern detected, flag created | Critical |
| Compliance dashboard aggregation | Compliance + Reporting + Multiple | Data accurate, performant | Medium |
| Audit log chain verification | Audit + Multiple services | Hash chain intact | Critical |

**Key Assertions:**
- Assert all services called in correct order
- Assert data consistency across services
- Assert rollback on any service failure
- Assert events dispatched and handled
- Assert no circular dependencies
- Assert proper transaction boundaries

### 5.2 Event/Listener Testing
**Priority: High**
**Estimated Tests: 20**

| Test Scenario | Event/Listener | Expected Output | Priority |
|--------------|----------------|-----------------|----------|
| TransactionCreated event dispatch | TransactionCreated → TransactionCreatedListener | Listener executed | Critical |
| CounterSessionOpened event | CounterSessionOpened → Audit logging | Log entry created | Medium |
| TransactionApproved event | TransactionApproved → Compliance recheck | Compliance re-evaluated | High |
| TransactionCancelled event | TransactionCancelled → Position reversal | Position restored | Critical |
| ComplianceFlagCreated event | FlagCreated → AlertTriage | Alert created and assigned | High |
| CustomerRiskChanged event | RiskChanged → Monitoring | Monitoring updated | Medium |
| AuditLogWritten event | LogWritten → Hash chain update | Hash chain intact | Critical |
| DataBreachDetected event | BreachDetected → Notification | Admin notified | High |
| StrSubmitted event | StrSubmitted → BNM tracking | Submission tracked | Critical |
| RateUpdated event | RateUpdated → Cache clear | Cache invalidated | Medium |
| SessionExpired event | SessionExpired → Cleanup | Session cleaned up | Medium |
| LoginFailed event | LoginFailed → Rate limiting | Rate limit incremented | High |
| LoginSuccess event | LoginSuccess → Audit log | Log entry created | Medium |
| StockTransferCreated event | TransferCreated → Notifications | Notifications sent | Low |
| EddSubmitted event | EddSubmitted → Compliance review | Added to queue | High |
| Concurrent event handling | Multiple events | Queue processed in order | Medium |
| Event listener failure | Listener throws exception | Event logged, retry scheduled | Medium |
| Event serialization | Event with complex data | Serializable, no errors | Medium |
| Event broadcast (if applicable) | Broadcast event | Websocket clients notified | Low |
| Event queue dispatch | Dispatched to queue | Job queued, processed async | High |

**Key Assertions:**
- Assert events dispatched correctly
- Assert listeners receive correct data
- Assert listeners executed in correct order
- Assert async events processed by queue
- Assert event failures logged and retried
- Assert no memory leaks in event handling

### 5.3 Queue Job Testing
**Priority: High**
**Estimated Tests: 20**

| Test Scenario | Job | Expected Output | Priority |
|--------------|-----|-----------------|----------|
| VelocityMonitorJob execution | VelocityMonitorJob | Velocity check, alerts created | Critical |
| StructuringMonitorJob execution | StructuringMonitorJob | Pattern detection, flags created | Critical |
| SanctionsRescreeningJob execution | SanctionsRescreeningJob | Batch screening completed | Critical |
| StrDeadlineMonitorJob execution | StrDeadlineMonitorJob | Deadline alerts generated | Critical |
| CurrencyFlowJob execution | CurrencyFlowJob | Flow analysis completed | Medium |
| CustomerLocationAnomalyJob | LocationAnomalyJob | Anomalies detected | Medium |
| CounterfeitAlertJob execution | CounterfeitAlertJob | Alerts generated | Medium |
| ProcessTransactionRetry job | ProcessTransactionRetry | Transaction retried successfully | High |
| Job failure and retry | Failed job | Retried with backoff, then to DLQ | High |
| Job timeout handling | Long-running job | Timeout detected, cancelled | Medium |
| Job queue priority | High priority job | Processed before low priority | Medium |
| Job batch processing | Multiple jobs | Processed in batch efficiently | Low |
| Job rate limiting | Rate limited job | Respected, no overwhelm | Low |
| Job unique constraints | Duplicate job | Only one executed | Medium |
| Job middleware | Job with middleware | Middleware executed | Medium |
| Job chaining | Chained jobs | Executed in sequence | Low |
| Job batch cancellation | Batch cancel | All jobs cancelled | Low |
| Job release back to queue | Release job | Delayed retry | Medium |
| Job progress tracking | Long job | Progress updated | Low |
| Job batch success callback | Batch complete | Callback executed | Low |

**Key Assertions:**
- Assert jobs dispatched correctly
- Assert jobs processed by queue worker
- Assert job failures retried appropriately
- Assert dead letter queue for persistent failures
- Assert job middleware applied
- Assert job batching works correctly
- Assert job rate limiting effective

### 5.4 Email/Notification Testing
**Priority: Medium**
**Estimated Tests: 12**

| Test Scenario | Notification | Expected Output | Priority |
|--------------|--------------|-----------------|----------|
| Transaction confirmation email | Transaction created | Email sent with details | Medium |
| Large transaction approval request | Pending approval | Manager notified | Medium |
| Compliance alert notification | Flag created | Compliance officer notified | High |
| STR submission confirmation | STR submitted | Confirmation email | Medium |
| Password reset email | Reset requested | Email with secure link | Critical |
| Data breach alert email | Breach detected | Admin alerted immediately | Critical |
| MFA setup confirmation | MFA enabled | Confirmation email | Medium |
| Account locked notification | Failed attempts | User notified | Medium |
| Daily summary report | Scheduled | Email with summary | Low |
| Weekly compliance report | Scheduled | Compliance team notified | Low |
| Email queue failure | SMTP down | Queued for retry | Medium |
| Email template rendering | Dynamic content | Correctly rendered | Medium |

**Key Assertions:**
- Assert emails sent to correct recipients
- Assert email content accurate and secure
- Assert email queue processed asynchronously
- Assert email failures retried
- Assert no email in spam (DKIM, SPF)
- Assert unsubscribe links where applicable

---

## 6. MIDDLEWARE TESTING

### 6.1 Security Middleware
**Priority: Critical**
**Estimated Tests: 15**

| Test Scenario | Middleware | Expected Output | Priority |
|--------------|------------|-----------------|----------|
| Data breach threshold exceeded | DataBreachDetection | Alert created, admin notified | Critical |
| Mass export detected | DataBreachDetection | Export_Anomaly alert created | Critical |
| Session timeout after idle | SessionTimeout | Logout, redirect to login | Critical |
| Session timeout excluded paths | SessionTimeout | Paths excluded from timeout | Medium |
| Branch access unauthorized | CheckBranchAccess | 403 Forbidden | Critical |
| Branch access with wrong branch | CheckBranchAccess | 403 Forbidden | Critical |
| Branch access admin bypass | CheckBranchAccess | Allowed for admin | Critical |
| MFA required but not enabled | EnsureMfaEnabled | Redirect to MFA setup | Critical |
| MFA not verified | EnsureMfaVerified | Prompt for MFA code | Critical |
| Role check single role | CheckRole | Access granted/denied correctly | Critical |
| Role check any role | CheckRoleAny | Access if any role matches | Critical |
| Security headers present | SecurityHeaders | CSP, HSTS, X-Frame-Options set | High |
| CSRF token validation | VerifyCsrfToken | 419 on invalid token | Critical |
| Query performance monitoring | QueryPerformanceMonitor | Slow queries logged | Medium |
| Request logging | LogRequests | Audit log entry created | Medium |

**Key Assertions:**
- Assert middleware applied to correct routes
- Assert middleware behavior correct for each scenario
- Assert proper HTTP status codes returned
- Assert middleware chain order correct
- Assert middleware can be bypassed where intended
- Assert middleware performance acceptable

---

## 7. SPECIALIZED DOMAIN TESTING

### 7.1 AML/Compliance Specific Tests
**Priority: Critical**
**Estimated Tests: 25**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Velocity monitoring threshold at boundary | 24h total = 49999 vs 50001 | Alert only at 50001 | Critical |
| Structuring pattern: 3 transactions in 55 minutes | 3 txns < 3000 each in 55 min | Structuring detected | Critical |
| Structuring pattern: 3 transactions in 65 minutes | 3 txns < 3000 each in 65 min | No structuring | Critical |
| Aggregate transactions over 7 days | Sum = 50001 over 7 days | Aggregate alert created | Critical |
| Sanctions exact match | Name exactly matches entry | Match detected | Critical |
| Sanctions fuzzy match | Name similar but not exact | Match detected with score | High |
| Sanctions false positive | Common name "John Smith" | Review queue, not auto-block | High |
| STR deadline calculation across holidays | Suspicion on Friday before holiday | Deadline excludes holidays | Critical |
| Risk score calculation | Multiple factors | Score calculated correctly | High |
| Risk score threshold alerts | Score crosses threshold | Alert created | High |
| EDD questionnaire completion | All questions answered | Status updated to completed | Critical |
| EDD approval workflow | EDD submitted | Review queue, approval flow | Critical |
| PEP status flagging | PEP customer transaction | PEP flag created | Critical |
| High-risk customer transaction | High risk rating | Enhanced monitoring | Critical |
| Compliance case creation | Multiple related alerts | Case created, linked | High |
| Case aging calculation | Case open 5 days | Aging metrics correct | Medium |
| AML rule evaluation | Transaction triggers rule | Rule hit logged | High |
| Alert triage assignment | Unassigned alert | Auto-assigned to officer | Medium |
| Alert escalation | Alert unresolved 48h | Escalated to supervisor | Medium |
| CTOS report generation | Cash transaction >= 10000 | CTOS report created | Critical |
| CTOS submission retry | Submission fails | Retry scheduled | Critical |
| BNM report formatting | Report generated | Correct XML/JSON format | Critical |
| Regulatory calendar compliance | Upcoming deadlines | Calendar accurate, alerts sent | Medium |
| Compliance finding creation | Automated detection | Finding created, severity set | High |
| Compliance audit trail | Compliance actions | All actions logged | Critical |

**Key Assertions:**
- Assert AML rules trigger correctly
- Assert false positive rate acceptable
- Assert alerts created at correct thresholds
- Assert STR deadlines calculated accurately
- Assert compliance workflows function end-to-end
- Assert audit trail complete for compliance

### 7.2 Accounting Specific Tests
**Priority: Critical**
**Estimated Tests: 20**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Double-entry balance | Journal entry | Debits = Credits | Critical |
| Period close with open entries | Period close attempted | Validation error | Critical |
| Period close with imbalances | Trial balance unbalanced | Close prevented | Critical |
| Revaluation with rate increase | Rate goes up | Gain recognized | Critical |
| Revaluation with rate decrease | Rate goes down | Loss recognized | Critical |
| Revaluation at month end | Month-end revaluation | Accrual entries created | Critical |
| Budget variance calculation | Actual vs budget | Variance calculated correctly | Medium |
| Budget over/under alert | Exceeds threshold | Alert generated | Low |
| Fiscal year closing | Year-end close | Income summary entries created | Critical |
| Fiscal year with open periods | Open periods exist | Close prevented | Critical |
| Ledger entry balance | Account ledger | Running balance correct | Critical |
| Trial balance aggregation | All accounts | Debits = Credits | Critical |
| P&L calculation | Revenue - Expenses | Correct period profit/loss | Critical |
| Balance sheet equation | Assets = Liabilities + Equity | Equation holds | Critical |
| Cash flow categorization | Transactions | Correctly categorized | High |
| Reconciliation matching | Bank statement vs ledger | Matches identified | High |
| Reconciliation discrepancy | Unmatched items | Outstanding items tracked | High |
| Journal entry workflow | Draft → Pending → Posted | Status transitions correct | Critical |
| Journal entry reversal | Reversal requested | Reversing entry created | Critical |
| Financial ratio calculation | Ledger data | Ratios calculated correctly | Medium |

**Key Assertions:**
- Assert accounting equation always balanced
- Assert period closing validations work
- Assert revaluation entries accurate
- Assert financial statements accurate
- Assert audit trail for all accounting changes
- Assert no orphaned ledger entries

### 7.3 Currency/Position Specific Tests
**Priority: Critical**
**Estimated Tests: 15**

| Test Scenario | Input | Expected Output | Priority |
|--------------|-------|-----------------|----------|
| Position average cost with multiple buys | Buy 100@4.5, 200@4.7 | Average = 4.6333... | Critical |
| Position average cost with FIFO | Sell portion | Cost basis correct | Critical |
| Position balance never negative | Attempt oversell | Exception thrown | Critical |
| Position with zero quantity | Sell all | Balance = 0, avg_cost = 0 | Critical |
| Currency rate precision | 6 decimal places | Precision maintained | Critical |
| Cross-rate calculation | USD/MYR via USD/SGD | Cross-rate accurate | Medium |
| Rate spread calculation | Buy vs sell rate | Spread maintained | Medium |
| Position revaluation | Month-end | Unrealized P&L calculated | Critical |
| Multi-currency position | Multiple currencies | Each tracked separately | Critical |
| Till balance reconciliation | End of day | Till = Position + Cash | Critical |
| Inter-branch transfer | Stock transfer | Balances updated both sides | Critical |
| Currency position reporting | Position report | Accurate totals | Medium |
| Rate history tracking | Rate changes | History maintained | Medium |
| Position audit trail | All movements | Logged with hash chain | Critical |
| Position corruption detection | Invalid state | Validation catches | High |

**Key Assertions:**
- Assert average cost calculation accurate
- Assert position balance never negative
- Assert BCMath used for all calculations
- Assert currency conversions accurate
- Assert audit trail for all position changes
- Assert reconciliation balances

---

## SUMMARY

### Test Priority Breakdown
| Priority | Estimated Tests | Focus Areas |
|----------|-----------------|-------------|
| Critical | 320 | Security, core business logic, data integrity |
| High | 220 | Performance, compliance, error handling |
| Medium | 160 | Edge cases, integration, notifications |
| Low | 80 | Nice-to-have, reporting, optimizations |
| **Total** | **780** | |

### Test Category Breakdown
| Category | Estimated Tests | Current Coverage | Gap |
|----------|-----------------|------------------|-----|
| Edge Case Testing | 155 | 20% | Large gap |
| Error Handling | 93 | 30% | Medium gap |
| Security Tests | 102 | 40% | Medium gap |
| Performance Tests | 48 | 10% | Large gap |
| Integration Tests | 77 | 50% | Medium gap |
| Middleware Tests | 15 | 20% | Large gap |
| Domain-Specific | 290 | 60% | Small gap |

### Implementation Recommendations
1. **Start with Critical**: Focus on security, authorization, and core transaction logic
2. **Use Parameterized Tests**: For boundary value testing to reduce code duplication
3. **Mock External Services**: Use mocks for APIs, email, etc.
4. **Database Transactions**: Wrap tests in transactions for isolation
5. **Test Data Factories**: Create comprehensive factories for test data
6. **CI Integration**: Add these tests to CI pipeline with code coverage reporting
7. **Parallel Execution**: Structure tests for parallel execution to reduce runtime
