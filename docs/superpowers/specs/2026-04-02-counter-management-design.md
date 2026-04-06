# Counter Management System Design Specification

**Version:** 1.0
**Date:** 2026-04-02
**Status:** Draft
**Author:** Design Team
**Target Implementation:** PHP 8.2+ / MySQL 8.0+ / Laravel 10+

---

## Executive Summary

The Counter Management System enables tracking of multiple identical counters within a single branch. Each counter operates as a distinct workstation with its own session lifecycle, cash float management, and formal handover procedures with supervisor verification and physical cash counts.

**Scope:** Single-branch implementation with 2-5 identical counters, expandable to multi-branch in future phases.

---

## Overview

### Key Components

- **Counters** - Physical workstations (2-5 identical units)
- **Counter Sessions** - Daily open/close cycles per counter
- **Counter Handovers** - Formal transfer between users with verification
- **Till Balances** - Cash float tracking per counter per currency
- **Audit Trail** - Complete logging of all counter operations

### Integration Points

- Links to existing User model (who operates which counter)
- Links to existing Transaction model (which counter processed which transaction)
- Links to existing TillBalance model (cash management)
- Extends existing SystemLog for audit trail

---

## Data Model

### New Tables

#### counters

Physical workstations within the branch.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| code | VARCHAR(10) | UNIQUE, NOT NULL | Counter code (e.g., "C01", "C02") |
| name | VARCHAR(50) | NOT NULL | Counter name (e.g., "Counter 1", "Main Counter") |
| status | ENUM('active','inactive') | NOT NULL, DEFAULT 'active' | Counter status |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (code)
- KEY (status)

---

#### counter_sessions

Daily open/close cycles for each counter.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| counter_id | BIGINT UNSIGNED | FK, NOT NULL | Counter reference |
| user_id | BIGINT UNSIGNED | FK, NOT NULL | User who opened the session |
| session_date | DATE | NOT NULL | Business date |
| opened_at | DATETIME | NOT NULL | Session opened timestamp |
| closed_at | DATETIME | NULL | Session closed timestamp |
| opened_by | BIGINT UNSIGNED | FK, NOT NULL | User who opened |
| closed_by | BIGINT UNSIGNED | FK, NULL | User who closed |
| status | ENUM('open','closed','handed_over') | NOT NULL, DEFAULT 'open' | Session status |
| notes | TEXT | NULL | Additional notes |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- KEY (counter_id, session_date)
- KEY (status)
- KEY (user_id)

**Foreign Keys:**
- FK_counter_sessions_counter_id → counters(id) ON DELETE RESTRICT
- FK_counter_sessions_user_id → users(id) ON DELETE RESTRICT
- FK_counter_sessions_opened_by → users(id) ON DELETE RESTRICT
- FK_counter_sessions_closed_by → users(id) ON DELETE SET NULL

---

#### counter_handovers

Formal handover records between users.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Primary key |
| counter_session_id | BIGINT UNSIGNED | FK, NOT NULL | Session reference |
| from_user_id | BIGINT UNSIGNED | FK, NOT NULL | User handing over |
| to_user_id | BIGINT UNSIGNED | FK, NOT NULL | User taking over |
| supervisor_id | BIGINT UNSIGNED | FK, NOT NULL | Supervisor who verified |
| handover_time | DATETIME | NOT NULL | Handover timestamp |
| physical_count_verified | BOOLEAN | DEFAULT TRUE | Physical count verified |
| variance_myr | DECIMAL(15,2) | DEFAULT 0.00 | Variance in MYR |
| variance_notes | TEXT | NULL | Variance explanation |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record created |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Record updated |

**Indexes:**
- PRIMARY KEY (id)
- KEY (counter_session_id)
- KEY (from_user_id)
- KEY (to_user_id)
- KEY (supervisor_id)
- KEY (handover_time)

**Foreign Keys:**
- FK_counter_handovers_counter_session_id → counter_sessions(id) ON DELETE RESTRICT
- FK_counter_handovers_from_user_id → users(id) ON DELETE RESTRICT
- FK_counter_handovers_to_user_id → users(id) ON DELETE RESTRICT
- FK_counter_handovers_supervisor_id → users(id) ON DELETE RESTRICT

---

### Modified Tables

#### transactions

Add counter tracking to existing transactions.

| New Column | Type | Constraints | Description |
|------------|------|-------------|-------------|
| counter_id | BIGINT UNSIGNED | FK, NULL | Which counter processed this transaction |
| counter_session_id | BIGINT UNSIGNED | FK, NULL | Which session processed this transaction |

**Foreign Keys:**
- FK_transactions_counter_id → counters(id) ON DELETE SET NULL
- FK_transactions_counter_session_id → counter_sessions(id) ON DELETE SET NULL

---

#### till_balances

Link existing till balances to counters.

| New Column | Type | Constraints | Description |
|------------|------|-------------|-------------|
| counter_id | BIGINT UNSIGNED | FK, NULL | Which counter this till belongs to |

**Foreign Keys:**
- FK_till_balances_counter_id → counters(id) ON DELETE SET NULL

---

## Workflows & Business Logic

### Counter Session Lifecycle

#### Opening a Counter

**Prerequisites:**
- Counter is not already open today
- User is not already at another counter
- User has appropriate role (teller, manager, admin)

**Process:**
1. User selects counter to open
2. System validates prerequisites
3. User enters opening float amounts per currency
4. System creates counter_session record (status: open)
5. System creates/updates till_balances for each currency
6. Audit log entry created

**Validation Rules:**
- Only one open session per counter per date
- One user per counter at a time
- Opening float amounts must be positive

---

#### Closing a Counter

**Prerequisites:**
- Counter is open
- User is the one who opened it OR supervisor override
- All transactions for the session are complete

**Process:**
1. User selects counter to close
2. System validates prerequisites
3. User enters closing float amounts per currency
4. System calculates variance per currency
5. If variance > threshold, requires supervisor approval
6. System updates counter_session (status: closed, closed_by, closed_at)
7. System updates till_balances
8. Audit log entry created

**Variance Thresholds:**
- Green: Variance < RM 100 (auto-approved)
- Yellow: Variance RM 100-500 (requires note)
- Red: Variance > RM 500 (requires supervisor approval)

---

#### Counter Handover (Formal)

**Prerequisites:**
- Counter is open
- Both users (from and to) are present
- Supervisor is available for verification
- Physical cash count performed

**Process:**
1. User A (current) and User B (taking over) both present
2. Supervisor initiates handover
3. Physical cash count performed by both users
4. Variance calculated and documented
5. Supervisor verifies and approves
6. System creates counter_handover record
7. System updates counter_session (status: handed_over)
8. System creates new counter_session for User B
9. Audit log entry created

**Validation Rules:**
- Both users must be present
- Supervisor must have manager or admin role
- Physical count must be verified
- Variance must be documented

---

### Business Rules

1. **One user per counter** - A user can only be at one counter at a time
2. **One session per counter per day** - Counter can only have one open session per date
3. **Supervisor required for handovers** - Handovers must be verified by a supervisor (manager or admin role)
4. **Variance thresholds** - Small variances auto-approved, large variances require supervisor
5. **Audit everything** - All counter operations logged with user, timestamp, IP
6. **Session integrity** - Cannot close a counter with pending transactions
7. **Handover continuity** - New session inherits all balances from previous session

---

## API & Controller Design

### New Controllers

#### CounterController

```php
// List all counters
GET /counters
Response: {
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "C01",
      "name": "Counter 1",
      "status": "active",
      "current_session": { ... }
    }
  ]
}

// Create new counter (admin only)
POST /counters
Request: { code: "C02", name: "Counter 2" }
Response: { "success": true, "data": { counter } }

// Open a counter session
POST /counters/{id}/open
Request: {
  opening_floats: [
    { currency_id: 1, amount: 10000.00 },
    { currency_id: 2, amount: 5000.00 }
  ]
}
Response: {
  "success": true,
  "data": {
    "counter": { ... },
    "session": { ... },
    "till_balances": [ ... ]
  }
}

// Close a counter session
POST /counters/{id}/close
Request: {
  closing_floats: [
    { currency_id: 1, amount: 10200.00 },
    { currency_id: 2, amount: 4980.00 }
  ],
  notes: "Small variance due to rounding"
}
Response: {
  "success": true,
  "data": {
    "session": { ... },
    "variances": [
      { currency_id: 1, variance: 200.00 },
      { currency_id: 2, variance: -20.00 }
    ]
  }
}

// Get counter status
GET /counters/{id}/status
Response: {
  "success": true,
  "data": {
    "counter": { ... },
    "status": "open",
    "current_user": { ... },
    "session": { ... },
    "till_balances": [ ... ]
  }
}

// Get counter history
GET /counters/{id}/history?from_date=2026-04-01&to_date=2026-04-30
Response: {
  "success": true,
  "data": [
    {
      "session_date": "2026-04-01",
      "opened_by": "John Doe",
      "opened_at": "2026-04-01 09:00:00",
      "closed_by": "John Doe",
      "closed_at": "2026-04-01 18:00:00",
      "status": "closed",
      "total_variance_myr": 50.00
    }
  ]
}
```

---

#### CounterHandoverController

```php
// Initiate handover
POST /counters/{id}/handover
Request: {
  from_user_id: 5,
  to_user_id: 6,
  supervisor_id: 3,
  physical_counts: [
    { currency_id: 1, amount: 10200.00 },
    { currency_id: 2, amount: 4980.00 }
  ],
  variance_notes: "No variance"
}
Response: {
  "success": true,
  "data": {
    "handover": { ... },
    "new_session": { ... }
  }
}

// Get pending handovers
GET /handovers/pending
Response: {
  "success": true,
  "data": [ ... ]
}

// Get handover history
GET /handovers/history?from_date=2026-04-01&to_date=2026-04-30
Response: {
  "success": true,
  "data": [ ... ]
}
```

---

### Modified Controllers

#### TransactionController

**Changes to create/store methods:**
- Require counter_id and counter_session_id in request
- Validate counter is open
- Validate user is assigned to that counter
- Link transaction to counter and session

**Validation:**
```php
'counter_id' => 'required|exists:counters,id',
'counter_session_id' => 'required|exists:counter_sessions,id,status,open'
```

---

#### StockCashController

**Changes to openTill/closeTill methods:**
- Link till operations to counter_id
- Validate counter session is open
- Update till_balances with counter_id

---

### API Response Format

**Success Response:**
```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Operation completed successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "field": "counter_id",
      "reason": "Counter is already open today"
    }
  }
}
```

---

## UI/Views Design

### New Views

#### counters/index.blade.php

**Layout:**
- Summary cards at top: Total counters, Open counters, Available counters
- Counter list table below

**Table Columns:**
- Code
- Name
- Status (badge: green for active, gray for inactive)
- Current User (if open)
- Session Time (opened_at)
- Actions (Open/Close/View History)

**Actions:**
- "Open Counter" button (if closed and user not at another counter)
- "Close Counter" button (if open and user is the one who opened it)
- "View History" link

---

#### counters/open.blade.php

**Form Fields:**
- Counter selection dropdown (filtered to available counters)
- Opening float entry form (per currency)
  - Currency dropdown with current balance display
  - Amount input field
  - Add/Remove currency buttons
- Notes field (optional)

**Validation Display:**
- Error messages for invalid inputs
- Warning if user is already at another counter

**Submit Button:**
- "Open Counter" button

---

#### counters/close.blade.php

**Display Section:**
- Counter info: code, name
- Session info: opened by, opened at
- Opening balances table (per currency)

**Form Section:**
- Closing float entry form (per currency)
  - Currency dropdown
  - Opening balance display
  - Amount input field
  - Variance calculation (auto-calculated)

**Variance Display:**
- Per currency variance (color-coded: green/yellow/red)
- Total variance in MYR
- Variance threshold indicators

**Notes Section:**
- Notes field (required if variance > RM 100)

**Submit Button:**
- "Close Counter" button

---

#### counters/history.blade.php

**Filters:**
- Date range picker (from_date, to_date)
- Counter dropdown
- User dropdown

**Table Columns:**
- Date
- Counter
- User
- Opened At
- Closed At
- Status
- Total Variance (MYR)
- Actions (View Details)

**Export Button:**
- Export to CSV/Excel

---

#### counters/handover.blade.php

**Display Section:**
- Counter info: code, name
- Current user (from_user)
- Current session info

**Form Fields:**
- To user selection dropdown (filtered to available users)
- Supervisor selection dropdown (filtered to managers/admins)
- Physical count entry form (per currency)
  - Currency dropdown
  - System balance display
  - Physical count input field
  - Variance calculation (auto-calculated)

**Variance Display:**
- Per currency variance
- Total variance in MYR
- Variance notes field (required if variance > 0)

**Submit Button:**
- "Complete Handover" button

---

### Modified Views

#### transactions/create.blade.php

**Additions:**
- Counter selection dropdown (filtered to open counters where user is assigned)
- Display counter session info (opened at, current user)
- Validation: counter must be open before allowing transaction

---

#### stock-cash/index.blade.php

**Additions:**
- Counter filter dropdown
- Show which counter each till belongs to
- Group till balances by counter

---

## Testing Strategy

### Unit Tests

#### CounterService

```php
class CounterServiceTest extends TestCase
{
    public function test_can_open_counter_session()
    {
        // Arrange
        $counter = Counter::factory()->create();
        $user = User::factory()->create(['role' => 'teller']);

        // Act
        $session = $this->service->openSession($counter, $user, $openingFloats);

        // Assert
        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'status' => 'open'
        ]);
    }

    public function test_cannot_open_if_already_open()
    {
        // Arrange
        $counter = Counter::factory()->create();
        $user = User::factory()->create(['role' => 'teller']);
        $this->service->openSession($counter, $user, []);

        // Act & Assert
        $this->expectException(BusinessException::class);
        $this->service->openSession($counter, $user, []);
    }

    public function test_cannot_open_if_user_at_another_counter()
    {
        // Arrange
        $counter1 = Counter::factory()->create();
        $counter2 = Counter::factory()->create();
        $user = User::factory()->create(['role' => 'teller']);
        $this->service->openSession($counter1, $user, []);

        // Act & Assert
        $this->expectException(BusinessException::class);
        $this->service->openSession($counter2, $user, []);
    }

    public function test_can_close_counter_session()
    {
        // Arrange
        $session = CounterSession::factory()->create(['status' => 'open']);

        // Act
        $this->service->closeSession($session, $closingFloats);

        // Assert
        $this->assertDatabaseHas('counter_sessions', [
            'id' => $session->id,
            'status' => 'closed'
        ]);
    }

    public function test_calculates_variance_correctly()
    {
        // Arrange
        $opening = 10000.00;
        $closing = 10200.00;

        // Act
        $variance = $this->service->calculateVariance($opening, $closing);

        // Assert
        $this->assertEquals(200.00, $variance);
    }

    public function test_requires_supervisor_for_large_variance()
    {
        // Arrange
        $session = CounterSession::factory()->create(['status' => 'open']);
        $closingFloats = [/* large variance */];

        // Act & Assert
        $this->expectException(BusinessException::class);
        $this->service->closeSession($session, $closingFloats);
    }
}
```

---

#### CounterHandoverService

```php
class CounterHandoverServiceTest extends TestCase
{
    public function test_can_initiate_handover()
    {
        // Arrange
        $session = CounterSession::factory()->create(['status' => 'open']);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $supervisor = User::factory()->create(['role' => 'manager']);

        // Act
        $handover = $this->service->initiateHandover(
            $session,
            $fromUser,
            $toUser,
            $supervisor,
            $physicalCounts
        );

        // Assert
        $this->assertDatabaseHas('counter_handovers', [
            'counter_session_id' => $session->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id
        ]);
    }

    public function test_requires_supervisor_verification()
    {
        // Arrange
        $session = CounterSession::factory()->create(['status' => 'open']);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $supervisor = User::factory()->create(['role' => 'teller']); // Not a supervisor

        // Act & Assert
        $this->expectException(BusinessException::class);
        $this->service->initiateHandover(
            $session,
            $fromUser,
            $toUser,
            $supervisor,
            $physicalCounts
        );
    }

    public function test_creates_new_session_after_handover()
    {
        // Arrange
        $oldSession = CounterSession::factory()->create(['status' => 'open']);

        // Act
        $this->service->initiateHandover(/* ... */);

        // Assert
        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $oldSession->counter_id,
            'status' => 'open'
        ]);
        $this->assertDatabaseHas('counter_sessions', [
            'id' => $oldSession->id,
            'status' => 'handed_over'
        ]);
    }

    public function test_logs_physical_count_variance()
    {
        // Arrange
        $physicalCounts = [
            ['currency_id' => 1, 'amount' => 10200.00]
        ];

        // Act
        $handover = $this->service->initiateHandover(/* ... */);

        // Assert
        $this->assertEquals(200.00, $handover->variance_myr);
    }
}
```

---

### Feature Tests

#### CounterController

```php
class CounterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_counters()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'teller']);
        Counter::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($user)->get('/counters');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Counter 1');
    }

    public function test_user_can_open_counter()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'teller']);
        $counter = Counter::factory()->create();

        // Act
        $response = $this->actingAs($user)->post("/counters/{$counter->id}/open", [
            'opening_floats' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ]
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $counter->id,
            'user_id' => $user->id,
            'status' => 'open'
        ]);
    }

    public function test_user_can_close_counter()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'teller']);
        $session = CounterSession::factory()->create([
            'user_id' => $user->id,
            'status' => 'open'
        ]);

        // Act
        $response = $this->actingAs($user)->post("/counters/{$session->counter_id}/close", [
            'closing_floats' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ]
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('counter_sessions', [
            'id' => $session->id,
            'status' => 'closed'
        ]);
    }

    public function test_supervisor_can_override_close()
    {
        // Arrange
        $supervisor = User::factory()->create(['role' => 'manager']);
        $session = CounterSession::factory()->create([
            'user_id' => 999, // Different user
            'status' => 'open'
        ]);

        // Act
        $response = $this->actingAs($supervisor)->post("/counters/{$session->counter_id}/close", [
            'closing_floats' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ]
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('counter_sessions', [
            'id' => $session->id,
            'closed_by' => $supervisor->id,
            'status' => 'closed'
        ]);
    }

    public function test_cannot_open_without_permission()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'viewer']); // No permission
        $counter = Counter::factory()->create();

        // Act
        $response = $this->actingAs($user)->post("/counters/{$counter->id}/open", [
            'opening_floats' => []
        ]);

        // Assert
        $response->assertStatus(403);
    }
}
```

---

#### CounterHandoverController

```php
class CounterHandoverControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_initiate_handover()
    {
        // Arrange
        $supervisor = User::factory()->create(['role' => 'manager']);
        $session = CounterSession::factory()->create(['status' => 'open']);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        // Act
        $response = $this->actingAs($supervisor)->post("/counters/{$session->counter_id}/handover", [
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'physical_counts' => [
                ['currency_id' => 1, 'amount' => 10000.00]
            ]
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('counter_handovers', [
            'counter_session_id' => $session->id,
            'supervisor_id' => $supervisor->id
        ]);
    }

    public function test_requires_both_users_present()
    {
        // Arrange
        $supervisor = User::factory()->create(['role' => 'manager']);
        $session = CounterSession::factory()->create(['status' => 'open']);
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        // Act & Assert
        $response = $this->actingAs($supervisor)->post("/counters/{$session->counter_id}/handover", [
            'from_user_id' => 999, // Invalid user
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'physical_counts' => []
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_creates_audit_log()
    {
        // Arrange
        $supervisor = User::factory()->create(['role' => 'manager']);
        $session = CounterSession::factory()->create(['status' => 'open']);

        // Act
        $this->actingAs($supervisor)->post("/counters/{$session->counter_id}/handover", [
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'supervisor_id' => $supervisor->id,
            'physical_counts' => []
        ]);

        // Assert
        $this->assertDatabaseHas('system_logs', [
            'action' => 'counter_handover',
            'entity_type' => 'counter_handover'
        ]);
    }
}
```

---

### Integration Tests

```php
class CounterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_validates_counter_session_is_open()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'teller']);
        $counter = Counter::factory()->create();
        $session = CounterSession::factory()->create([
            'counter_id' => $counter->id,
            'status' => 'open'
        ]);

        // Act
        $response = $this->actingAs($user)->post('/transactions', [
            'counter_id' => $counter->id,
            'counter_session_id' => $session->id,
            // ... other transaction fields
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', [
            'counter_id' => $counter->id,
            'counter_session_id' => $session->id
        ]);
    }

    public function test_till_balance_updates_linked_to_counter()
    {
        // Arrange
        $counter = Counter::factory()->create();
        $session = CounterSession::factory()->create([
            'counter_id' => $counter->id,
            'status' => 'open'
        ]);

        // Act
        $this->service->openSession($counter, $user, $openingFloats);

        // Assert
        $this->assertDatabaseHas('till_balances', [
            'counter_id' => $counter->id
        ]);
    }

    public function test_audit_log_entries_created_for_all_operations()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'teller']);
        $counter = Counter::factory()->create();

        // Act
        $this->service->openSession($counter, $user, $openingFloats);

        // Assert
        $this->assertDatabaseHas('system_logs', [
            'action' => 'counter_opened',
            'user_id' => $user->id
        ]);
    }

    public function test_counter_handover_properly_transitions_sessions()
    {
        // Arrange
        $oldSession = CounterSession::factory()->create(['status' => 'open']);

        // Act
        $this->handoverService->initiateHandover(/* ... */);

        // Assert
        $this->assertDatabaseHas('counter_sessions', [
            'id' => $oldSession->id,
            'status' => 'handed_over'
        ]);
        $this->assertDatabaseHas('counter_sessions', [
            'counter_id' => $oldSession->counter_id,
            'status' => 'open'
        ]);
    }
}
```

---

## Implementation Phases

### Phase 1: Core Counter Management (Week 1)

**Deliverables:**
- Create counters table and migration
- Create Counter model
- Create CounterController with basic CRUD
- Create counters/index.blade.php view
- Create counters/open.blade.php view
- Create counters/close.blade.php view
- Implement open/close session logic
- Add audit logging

**Testing:**
- Unit tests for CounterService
- Feature tests for CounterController
- Integration tests for session lifecycle

---

### Phase 2: Counter Handovers (Week 2)

**Deliverables:**
- Create counter_handovers table and migration
- Create CounterHandover model
- Create CounterHandoverService
- Create CounterHandoverController
- Create counters/handover.blade.php view
- Implement handover workflow with supervisor verification
- Add physical count variance tracking

**Testing:**
- Unit tests for CounterHandoverService
- Feature tests for CounterHandoverController
- Integration tests for handover session transition

---

### Phase 3: Transaction Integration (Week 3)

**Deliverables:**
- Add counter_id and counter_session_id to transactions table
- Update TransactionController to validate counter session
- Update transactions/create.blade.php to include counter selection
- Update StockCashController to link to counters
- Update stock-cash/index.blade.php to show counter info

**Testing:**
- Integration tests for transaction-counter linkage
- Feature tests for transaction validation
- End-to-end workflow tests

---

### Phase 4: Reporting & History (Week 4)

**Deliverables:**
- Create counters/history.blade.php view
- Implement history filtering and export
- Add counter performance metrics
- Create counter utilization reports
- Add variance analysis reports

**Testing:**
- Feature tests for history views
- Integration tests for reporting
- Performance tests for large datasets

---

## Security Considerations

### Authentication & Authorization

- **Role-based access control:**
  - Teller: Can open/close counters they are assigned to
  - Manager: Can open/close any counter, supervise handovers
  - Admin: Full access to all counter operations

- **Permission checks:**
  - Validate user role before allowing operations
  - Check counter ownership before allowing close
  - Require supervisor verification for handovers

### Data Validation

- **Input validation:**
  - All monetary amounts must be positive
  - Currency codes must be valid
  - User IDs must exist and be active
  - Counter IDs must exist and be active

- **Business rule validation:**
  - One user per counter at a time
  - One session per counter per day
  - Cannot close counter with pending transactions
  - Variance thresholds enforced

### Audit Trail

- **Logging requirements:**
  - All counter operations logged
  - Include user, timestamp, IP address
  - Log old and new values for state changes
  - Log variance details for handovers

- **Log retention:**
  - Minimum 7 years retention (BNM requirement)
  - Immutable audit log (append-only)
  - Regular audit log review

---

## Performance Considerations

### Database Optimization

- **Indexes:**
  - counter_sessions: (counter_id, session_date), (status), (user_id)
  - counter_handovers: (counter_session_id), (from_user_id), (to_user_id), (supervisor_id)
  - transactions: (counter_id), (counter_session_id)
  - till_balances: (counter_id)

- **Query optimization:**
  - Use eager loading for relationships
  - Cache counter status for frequent access
  - Use database views for complex queries

### Caching Strategy

- **Cache counter status:**
  - Cache open counters list (5-minute TTL)
  - Cache user's current counter (session-based)
  - Cache counter availability (1-minute TTL)

- **Cache invalidation:**
  - Invalidate on counter open/close
  - Invalidate on handover
  - Invalidate on user logout

---

## Migration Strategy

### Data Migration

**Existing Data:**
- No existing counter data (new feature)
- Existing transactions will have NULL counter_id and counter_session_id
- Existing till_balances will have NULL counter_id

**Migration Steps:**
1. Create new tables (counters, counter_sessions, counter_handovers)
2. Add columns to existing tables (transactions, till_balances)
3. Seed default counters (C01, C02, C03, etc.)
4. Backfill NULL values where appropriate
5. Update application code to use new fields

**Rollback Plan:**
- Drop new tables
- Remove new columns from existing tables
- Restore previous application code

---

## Future Enhancements

### Multi-Branch Support

- Add branch_id to counters table
- Add branch filtering to all queries
- Implement inter-branch counter transfers
- Add branch-level reporting

### Advanced Features

- Counter performance analytics
- Automated variance alerts
- Counter scheduling and assignment
- Mobile counter support
- Counter inventory management

### Integration Points

- Integrate with existing Transaction module
- Integrate with existing Stock/Cash module
- Integrate with existing Reporting module
- Integrate with existing Audit module

---

## Appendix A: Glossary

| Term | Definition |
|------|------------|
| Counter | Physical workstation where transactions are processed |
| Counter Session | Daily open/close cycle for a counter |
| Handover | Formal transfer of counter control between users |
| Till Balance | Cash float at a counter for a specific currency |
| Variance | Difference between expected and actual cash count |
| Supervisor | Manager or admin who verifies handovers |

---

## Appendix B: Error Codes

| Code | Description |
|------|-------------|
| COUNTER_ALREADY_OPEN | Counter is already open today |
| USER_AT_ANOTHER_COUNTER | User is already at another counter |
| INVALID_COUNTER | Counter does not exist or is inactive |
| INVALID_SESSION | Session does not exist or is not open |
| SUPERVISOR_REQUIRED | Supervisor verification required |
| VARIANCE_TOO_LARGE | Variance exceeds threshold, requires supervisor approval |
| PENDING_TRANSACTIONS | Cannot close counter with pending transactions |

---

**Document End**

*This specification is subject to refinement during implementation. All design decisions should be validated against the latest BNM guidelines and operational requirements.*
