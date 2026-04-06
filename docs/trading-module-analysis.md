# CEMS-MY Trading Module Analysis

**Date**: 2026-04-01
**System**: CEMS-MY v1.0
**Standard**: Malaysian Institute of Accountants (MIA) - MFRS Compliance
**Classification**: Technical Documentation

---

## Executive Summary

This document provides a comprehensive analysis of the CEMS-MY trading modules, covering the complete workflow from initial stock/cash entry through teller trading transactions to MIA-compliant accounting entries. The system ensures accurate tracking of foreign currency positions, real-time cash management, and automated accounting entries following Malaysian accounting standards.

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Initial Setup Workflow](#2-initial-setup-workflow)
3. [Teller Trading Mechanism](#3-teller-trading-mechanism)
4. [Accounting Integration](#4-accounting-integration)
5. [MIA Compliance](#5-mia-compliance)
6. [Data Flow Diagrams](#6-data-flow-diagrams)
7. [Stock and Cash Balance Triggers](#7-stock-and-cash-balance-triggers)

---

## 1. System Overview

### 1.1 Trading Module Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    CEMS-MY TRADING MODULES                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │   Stock      │───▶│  Teller      │───▶│ Accounting   │      │
│  │   Opening    │    │  Trading     │    │   Entries    │      │
│  └──────────────┘    └──────────────┘    └──────────────┘      │
│         │                    │                    │            │
│         ▼                    ▼                    ▼            │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │ Till Balance │    │ Transaction  │    │ Journal      │      │
│  │   Records    │    │   Creation   │    │   Entries    │      │
│  └──────────────┘    └──────────────┘    └──────────────┘      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2 Key Components

| Component | Purpose | Data Flow |
|-----------|---------|-----------|
| **StockCashController** | Manages till opening/closing | Creates/updates TillBalance records |
| **CurrencyPositionService** | Tracks foreign currency inventory | Updates CurrencyPosition on transactions |
| **Transaction Model** | Records all buy/sell transactions | Triggers compliance monitoring |
| **RevaluationService** | Monthly P&L calculation | Creates RevaluationEntry records |
| **Chart of Accounts** | MIA-compliant account structure | Serves as foundation for journal entries |

---

## 2. Initial Setup Workflow

### 2.1 Day Opening Process

**Step 1: Access Stock/Cash Management**
```
Manager/Admin navigates to: http://local.host/stock-cash
```

**Step 2: Open Till for Each Currency**

The till opening process captures initial stock positions:

```php
// StockCashController::openTill()
$tillBalance = TillBalance::create([
    'till_id' => $validated['till_id'],      // e.g., 'TILL-001'
    'currency_code' => $validated['currency_code'], // e.g., 'USD'
    'opening_balance' => $validated['opening_balance'], // Physical count
    'date' => today(),
    'opened_by' => auth()->id(),
    'notes' => $validated['notes'],
]);
```

**Data Captured:**
- Till ID (unique identifier)
- Currency code
- Opening balance (physical count)
- Opening timestamp
- User who opened
- Optional notes

**Audit Trail:**
```php
SystemLog::create([
    'user_id' => auth()->id(),
    'action' => 'till_opened',
    'entity_type' => 'TillBalance',
    'entity_id' => $tillBalance->id,
    'new_values' => [
        'till_id' => $validated['till_id'],
        'currency_code' => $validated['currency_code'],
        'opening_balance' => $validated['opening_balance'],
    ],
]);
```

### 2.2 Initial Stock Position Creation

When the first transaction occurs for a currency, the system automatically creates a CurrencyPosition:

```php
// CurrencyPositionService::updatePosition()
$position = CurrencyPosition::firstOrCreate(
    ['currency_code' => $currencyCode, 'till_id' => $tillId],
    [
        'balance' => '0',
        'avg_cost_rate' => $rate,
        'last_valuation_rate' => $rate,
    ]
);
```

**Note:** Currency positions are created lazily (on first transaction) rather than during till opening to avoid zero-balance position records.

---

## 3. Teller Trading Mechanism

### 3.1 Transaction Types

| Type | Description | Impact on Position | Impact on Cash |
|------|-------------|-------------------|----------------|
| **Buy** | Purchase foreign currency from customer | Increase foreign currency balance | Decrease MYR cash |
| **Sell** | Sell foreign currency to customer | Decrease foreign currency balance | Increase MYR cash |

### 3.2 Transaction Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    TELLER TRANSACTION FLOW                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Step 1: Customer Registration                                   │
│  ├── Collect customer details                                  │
│  ├── Verify ID (MyKad/Passport)                                │
│  ├── Check sanction lists                                      │
│  └── CDD/EDD assessment                                        │
│                                                                  │
│  Step 2: Transaction Entry                                       │
│  ├── Select currency                                           │
│  ├── Enter amount (foreign or local)                           │
│  ├── System calculates counterpart                             │
│  └── Apply current rate                                        │
│                                                                  │
│  Step 3: Compliance Check                                        │
│  ├── Amount ≥ RM 50,000? → Manager approval required           │
│  ├── Sanction screening                                        │
│  ├── Velocity check (24h cumulative)                             │
│  └── Structuring detection                                     │
│                                                                  │
│  Step 4: Stock Validation                                        │
│  ├── For Sell: Check sufficient balance                        │
│  ├── For Buy: Always allowed (stock increases)                 │
│  └── Negative balance prevention                               │
│                                                                  │
│  Step 5: Execute Transaction                                     │
│  ├── Create Transaction record                                   │
│  ├── Update CurrencyPosition                                     │
│  ├── Update TillBalance (cash)                                 │
│  └── Generate receipt                                          │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.3 Transaction Model Structure

```php
// Key fields in Transaction model
'type' => 'Buy' or 'Sell',
'currency_code' => 'USD', 'EUR', etc.,
'amount_local' => MYR amount,
'amount_foreign' => Foreign currency amount,
'rate' => Exchange rate applied,
'status' => 'Completed', 'OnHold', 'Pending',
'hold_reason' => If status is OnHold,
'cdd_level' => 'Simplified', 'Standard', 'Enhanced',
```

### 3.4 Stock Update Logic

**Buy Transaction (Stock Increase):**
```php
// CurrencyPositionService::updatePosition() for Buy
$newBalance = $this->mathService->add($oldBalance, $amount);

if ($this->mathService->compare($oldBalance, '0') > 0) {
    // Calculate new weighted average cost
    $newAvgCost = $this->mathService->calculateAverageCost(
        $oldBalance, $oldAvgCost, $amount, $rate
    );
} else {
    // First purchase - cost = rate
    $newAvgCost = $rate;
}
```

**Sell Transaction (Stock Decrease):**
```php
// CurrencyPositionService::updatePosition() for Sell
// Validation: Prevent negative balance
if ($this->mathService->compare($oldBalance, $amount) < 0) {
    throw new \InvalidArgumentException(
        "Insufficient balance. Available: {$oldBalance}, Requested: {$amount}"
    );
}

if ($this->mathService->compare($oldBalance, '0') <= 0) {
    throw new \InvalidArgumentException(
        "Cannot sell: Position is empty or negative. Balance: {$oldBalance}"
    );
}

$newBalance = $this->mathService->subtract($oldBalance, $amount);
$newAvgCost = $oldAvgCost; // Cost basis unchanged on sale
```

### 3.5 Average Cost Calculation

The system uses weighted average cost method (MIA compliant):

```
Formula:
New Avg Cost = (Old Balance × Old Avg Cost + New Amount × New Rate) / Total Balance

Example:
- Initial: 1,000 USD @ 4.50 = 4,500 MYR cost
- Buy: 500 USD @ 4.70 = 2,350 MYR cost
- New Avg = (4,500 + 2,350) / 1,500 = 4.566667 MYR/USD
```

---

## 4. Accounting Integration

### 4.1 Chart of Accounts (MIA Structure)

```sql
-- Chart of Accounts Structure
1000 - Cash - MYR                    (Asset)
1100 - Cash - USD                    (Asset)
1200 - Cash - EUR                    (Asset)
1300 - Cash - GBP                    (Asset)
1400 - Cash - SGD                    (Asset)

2000 - Foreign Currency Inventory    (Asset)
2100 - Unrealized Forex Gains/Losses (Equity)

4000 - Revenue - Forex Trading       (Revenue)
4100 - Revenue - Revaluation Gain    (Revenue)

5000 - Expense - Revaluation Loss    (Expense)
5100 - Expense - Transaction Costs   (Expense)
```

### 4.2 Journal Entry Triggers

**Trigger 1: Transaction Creation**

When a transaction is completed, the following entries occur:

```
BUY Transaction (Purchase USD):
┌─────────────────────────────────────────────────┐
│ Dr Foreign Currency Inventory (1100)   1,000  │
│    Cr Cash - MYR (1000)                        4,750  │
│                                                 │
│ (Buy 1,000 USD @ 4.75 = 4,750 MYR)             │
└─────────────────────────────────────────────────┘

SELL Transaction (Sell USD):
┌─────────────────────────────────────────────────┐
│ Dr Cash - MYR (1000)                    4,700  │
│    Cr Foreign Currency Inventory (1100)         1,000  │
│    Cr Revenue - Forex Trading (4000)             (gain)│
│    OR                                              │
│ Dr Expense - Forex Trading (5000)        (loss) │
│                                                 │
│ (Sell 1,000 USD @ 4.70, avg cost 4.566667)     │
│ Gain = (4.70 - 4.566667) × 1,000 = 133.33      │
└─────────────────────────────────────────────────┘
```

**Trigger 2: Monthly Revaluation**

End-of-month revaluation calculates unrealized P&L:

```
Revaluation Entry (if rate increased):
┌─────────────────────────────────────────────────┐
│ Dr Foreign Currency Inventory           XXX   │
│    Cr Revenue - Revaluation Gain (4100)        XXX   │
│                                                 │
│ (Rate increased, stock value appreciated)      │
└─────────────────────────────────────────────────┘

Revaluation Entry (if rate decreased):
┌─────────────────────────────────────────────────┐
│ Dr Expense - Revaluation Loss (5000)      XXX   │
│    Cr Foreign Currency Inventory                 XXX   │
│                                                 │
│ (Rate decreased, stock value depreciated)      │
└─────────────────────────────────────────────────┘
```

### 4.3 Revaluation Service

```php
// RevaluationService::runRevaluation()
foreach ($positions as $position) {
    $gainLoss = $this->mathService->calculateRevaluationPnl(
        $position->balance,
        $oldRate,
        $newRate
    );
    
    RevaluationEntry::create([
        'currency_code' => $position->currency_code,
        'old_rate' => $oldRate,
        'new_rate' => $newRate,
        'gain_loss_amount' => $gainLoss,
        'revaluation_date' => $date,
    ]);
    
    // Update cumulative P&L
    $position->update([
        'unrealized_pnl' => $cumulativePnl,
        'last_valuation_rate' => $newRate,
    ]);
}
```

---

## 5. MIA Compliance

### 5.1 Malaysian Institute of Accountants Standards

The system follows MIA guidelines through:

| Requirement | Implementation | Status |
|-------------|----------------|--------|
| **Accrual Basis** | Transactions recorded when occurred | ✅ |
| **Historical Cost** | Weighted average cost method | ✅ |
| **Consistency** | Same accounting methods applied | ✅ |
| **Materiality** | All transactions recorded regardless of size | ✅ |
| **Going Concern** | System assumes continuous operation | ✅ |
| **Full Disclosure** | Audit logs for all transactions | ✅ |
| **Prudence** | Losses recognized immediately, gains on realization | ✅ |

### 5.2 MFRS 21 (Effects of Changes in Foreign Exchange Rates)

**Functional Currency:** MYR

**Transaction Recording:**
- Initial: Record at spot rate on transaction date
- Settlement: Difference recognized as forex gain/loss

**Translation at Reporting Date:**
- Monetary items: Translate at closing rate
- Non-monetary items: Keep at historical cost

**Implementation:**
```php
// Transaction date rate used for initial recording
$transactionRate = $rateApiService->getRateForCurrency($currencyCode);

// Month-end revaluation at closing rate
$closingRate = $rateApiService->getRateForCurrency($currencyCode);
$unrealizedPnl = ($closingRate - $avgCostRate) * $balance;
```

### 5.3 Audit Trail Requirements

All accounting entries include:
- User ID who initiated
- Timestamp (ISO 8601 format)
- IP address
- Before/after values (for updates)
- Entity type and ID

```php
SystemLog::create([
    'user_id' => auth()->id(),
    'action' => 'transaction_created',
    'entity_type' => 'Transaction',
    'entity_id' => $transaction->id,
    'new_values' => $transaction->toArray(),
    'ip_address' => $request->ip(),
]);
```

---

## 6. Data Flow Diagrams

### 6.1 Complete Transaction Flow

```
┌─────────┐     ┌──────────────┐     ┌──────────────┐
│ Teller  │────▶│ Transaction  │────▶│ Compliance   │
│ Screen  │     │   Entry      │     │   Check      │
└─────────┘     └──────────────┘     └───────┬──────┘
                                              │
                                              ▼
                                       ┌──────────────┐
                                       │  Approved?   │
                                       └───────┬──────┘
                                               │
                              ┌────────────────┴────────────┐
                              ▼                              ▼
                    ┌──────────────────┐          ┌──────────────┐
                    │ YES - Continue   │          │ NO - On Hold │
                    └────────┬─────────┘          └──────────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ Stock Validation │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ Execute Transaction│
                    └────────┬─────────┘
                             │
           ┌─────────────────┼─────────────────┐
           │                 │                 │
           ▼                 ▼                 ▼
┌──────────────────┐ ┌──────────────┐ ┌──────────────┐
│ Create Transaction│ │ Update       │ │ Update       │
│ Record            │ │ Currency     │ │ Till         │
│                   │ │ Position     │ │ Balance      │
└────────┬────────┘ └──────────────┘ └──────────────┘
         │
         ▼
┌──────────────────┐
│ Trigger Journal  │
│ Entries (MIA)    │
└────────┬────────┘
         │
         ▼
┌──────────────────┐
│ Log Audit Trail  │
└──────────────────┘
```

### 6.2 Stock Balance Update Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    STOCK BALANCE UPDATE FLOW                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Input: Transaction Type (Buy/Sell), Currency, Amount, Rate    │
│                                                                 │
│         ┌─────────────────────────────────────┐                  │
│         │         CurrencyPositionService    │                  │
│         │            ::updatePosition()      │                  │
│         └──────────────────┬──────────────────┘                  │
│                            │                                    │
│           ┌────────────────┴────────────────┐                   │
│           ▼                                  ▼                  │
│  ┌──────────────────┐             ┌──────────────────┐          │
│  │ Buy Transaction  │             │ Sell Transaction │          │
│  └────────┬─────────┘             └────────┬─────────┘          │
│           │                                │                    │
│           ▼                                ▼                    │
│  ┌──────────────────┐             ┌──────────────────┐          │
│  │ Check: Balance > 0 │             │ Check: Balance >=│          │
│  │ for avg calc?    │             │ Amount?          │          │
│  └────────┬─────────┘             └────────┬─────────┘          │
│           │                                │                    │
│           ▼                                ▼                    │
│  ┌──────────────────┐             ┌──────────────────┐          │
│  │ Calculate New    │             │ Throw Exception │          │
│  │ Weighted Avg     │             │ if Insufficient │          │
│  └────────┬─────────┘             └────────┬─────────┘          │
│           │                                │                    │
│           │                                ▼                    │
│           │                       ┌──────────────────┐          │
│           │                       │ Decrease Balance │          │
│           │                       └────────┬─────────┘          │
│           │                                │                    │
│           ▼                                │                    │
│  ┌──────────────────┐                      │                    │
│  │ Increase Balance │◀─────────────────────┘                    │
│  └────────┬─────────┘                                           │
│           │                                                      │
│           ▼                                                      │
│  ┌──────────────────┐                                            │
│  │ Update Database  │                                            │
│  └──────────────────┘                                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. Stock and Cash Balance Triggers

### 7.1 Trigger Matrix

| Event | Triggers | Updates | Accounting Entry |
|-------|----------|---------|------------------|
| **Till Open** | Manual (Manager) | TillBalance.opening_balance | None (physical count) |
| **Buy Transaction** | Teller action | CurrencyPosition↑, TillBalance↓ | Dr Inventory, Cr Cash |
| **Sell Transaction** | Teller action | CurrencyPosition↓, TillBalance↑ | Dr Cash, Cr Inventory, Cr Revenue |
| **Till Close** | Manual (Manager) | TillBalance.closing_balance, variance | Variance to suspense account |
| **Month End** | Automated | RevaluationEntry, CurrencyPosition.unrealized_pnl | Dr/Cr Inventory, Cr/Dr Revenue/Expense |

### 7.2 Real-Time Balance Tracking

**Currency Position (Foreign Stock):**
```php
// Current balance calculation
$position = CurrencyPosition::where('currency_code', $currencyCode)
    ->where('till_id', $tillId)
    ->first();

$currentBalance = $position->balance;
$avgCostRate = $position->avg_cost_rate;
$unrealizedPnl = $position->unrealized_pnl;
```

**Till Balance (Cash):**
```php
// Current till status
$tillBalance = TillBalance::where('till_id', $tillId)
    ->where('currency_code', $currencyCode)
    ->whereDate('date', today())
    ->first();

$openingBalance = $tillBalance->opening_balance;
$closingBalance = $tillBalance->closing_balance;
$variance = $tillBalance->variance;
```

### 7.3 Variance Analysis

When till is closed, variance is calculated:

```php
$variance = $closingBalance - $openingBalance;

// Expected based on transactions
$expectedBalance = $openingBalance + $totalBuys - $totalSells;

// Physical vs System variance
$physicalVariance = $closingBalance - $expectedBalance;

if ($physicalVariance != 0) {
    // Flag for investigation
    // Potential causes:
    // - Counting error
    // - Theft
    // - Transaction recording error
    // - System error
}
```

---

## 8. Summary

### 8.1 Key Workflows

| Workflow | Entry Point | Key Tables | Output |
|----------|-------------|------------|--------|
| **Day Opening** | /stock-cash | till_balances | Opening positions recorded |
| **Transaction** | /transactions/create | transactions, currency_positions | Transaction record, stock update |
| **Day Closing** | /stock-cash | till_balances | Variance report |
| **Month End** | Automated | revaluation_entries | P&L adjustment entries |
| **Audit Report** | /reports | system_logs | Compliance documentation |

### 8.2 MIA Compliance Checklist

- ✅ Double-entry bookkeeping
- ✅ Accrual basis accounting
- ✅ Historical cost principle
- ✅ Weighted average cost method
- ✅ Full audit trail
- ✅ Monthly revaluation
- ✅ Foreign exchange rate handling (MFRS 21)
- ✅ Revenue recognition at transaction date
- ✅ Expense matching principle

### 8.3 Data Integrity Controls

- Transaction-level database transactions (rollback on failure)
- Negative balance prevention
- Till variance tracking
- Audit logging for all changes
- Approval workflows for large transactions
- Sanction screening integration

---

**Document Information**
- **Author**: CEMS-MY Technical Team
- **Version**: 1.0
- **Last Updated**: 2026-04-01
- **Next Review**: Quarterly
- **Compliance**: MIA, BNM AML/CFT Policy (Revised 2025)
