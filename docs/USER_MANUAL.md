# CEMS-MY User Manual

**Currency Exchange Management System - Malaysia**

**Version**: 3.0
**Last Updated**: April 2026
**Document Type**: User Guide - New Employee Edition
**Target Audience**: New employees, tellers, and operational staff

---

## Quick Start Guide (Read This First!)

### Day 1 Checklist

Before your first transaction, complete these steps:

- [ ] Log in to CEMS-MY at `/login`
- [ ] Set up Multi-Factor Authentication (MFA)
- [ ] Understand your role and permissions
- [ ] Know where to find the counter
- [ ] Understand Buy vs Sell transactions

### Your First Transaction (5-Minute Overview)

**BUY Transaction Example**:
> Customer wants to SELL you USD and get MYR in return
> - You BUY USD from customer
> - Customer receives MYR
> - Your USD inventory increases

**SELL Transaction Example**:
> Customer wants to BUY USD from you, paying MYR
> - You SELL USD to customer
> - Customer pays MYR
> - Your USD inventory decreases

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [Daily Operations](#3-daily-operations)
4. [Transaction Management](#4-transaction-management)
5. [Counter/Till Management](#5-countertill-management)
6. [Customer Management](#6-customer-management)
7. [Compliance & AML](#7-compliance--aml)
8. [Accounting Basics](#8-accounting-basics)
9. [Reports & Regulatory Filing](#9-reports--regulatory-filing)
10. [Troubleshooting](#10-troubleshooting)
11. [FAQ - Frequently Asked Questions](#11-faq--frequently-asked-questions)
12. [Quick Reference Cards](#12-quick-reference-cards)

---

## 1. Introduction

### 1.1 What is CEMS-MY?

CEMS-MY (Currency Exchange Management System - Malaysia) is the software used to:

- **Process currency exchange transactions** (Buy and Sell)
- **Track cash balances** at each counter/till
- **Comply with Bank Negara Malaysia (BNM) regulations**
- **Generate required reports** for regulatory filing
- **Maintain complete audit trails** of all operations

### 1.2 Key Terms You Must Know

| Term | Simple Definition | Example |
|------|-------------------|---------|
| **BUY** | We purchase foreign currency FROM customer | Customer sells us USD, we give them MYR |
| **SELL** | We sell foreign currency TO customer | Customer buys USD from us, pays with MYR |
| **MYR** | Malaysian Ringgit (RM) - our base currency | Local currency |
| **Counter/Till** | The physical cash drawer/workstation | Where transactions happen |
| **CDD** | Customer Due Diligence - verifying who the customer is | KYC (Know Your Customer) |
| **STR** | Suspicious Transaction Report - BNM filing | Required for suspicious activity |
| **CTOS** | Cash Transaction Report - BNM filing | Required for cash >= RM 10,000 |
| **LCTR** | Large Cash Transaction Report - BNM filing | Required for >= RM 50,000 |

### 1.3 System Access

| Item | Details |
|------|---------|
| **Login URL** | `/login` |
| **Session Timeout** | 15 minutes of inactivity |
| **MFA Required** | YES - Must use authenticator app |
| **Supported Browsers** | Chrome, Firefox, Safari, Edge (latest versions) |

---

## 2. Getting Started

### 2.1 Setting Up MFA (First Login)

Multi-Factor Authentication (MFA) adds a second layer of security. You'll need a smartphone with an authenticator app.

**Step-by-Step MFA Setup**:

1. Go to `/login`
2. Enter your **Email** and **Password**
3. You'll be redirected to MFA setup page
4. **Download an authenticator app** on your phone:
   - Google Authenticator (Android/iOS)
   - Authy (Android/iOS)
5. **Open the app** and tap **"Add Account"** or **"+"**
6. **Scan the QR code** shown on CEMS-MY screen
7. **Enter the 6-digit code** from the app
8. **Save your backup codes** in a secure location (you'll need these if you lose your phone!)
9. Click **"Verify and Activate"**

**IMPORTANT**: Store your backup codes safely. If you lose your phone, you'll need these to access your account.

### 2.2 Understanding Your Role

Your role determines what you can do in the system:

| Role | Who Are They? | What Can They Do? |
|------|---------------|-------------------|
| **Teller** | Front-line staff | Create transactions, open/close own counter |
| **Manager** | Shift supervisor | Approve large transactions, open/close any counter, view reports |
| **Compliance Officer** | AML specialist | Review flags, manage STR reports, view audit logs |
| **Admin** | System administrator | Everything + user management + system config |

### 2.3 Dashboard Overview

After logging in, you'll see the Dashboard with key information:

```
┌─────────────────────────────────────────────────────────────┐
│  TODAY'S SUMMARY                                             │
├─────────────────────────────────────────────────────────────┤
│  Revenue: RM 45,230.00          Active Counters: 3         │
│  Pending Approvals: 2            Flagged Items: 1          │
└─────────────────────────────────────────────────────────────┘
```

**What each metric means**:

| Metric | What It Shows | Who Sees It |
|--------|---------------|-------------|
| **Today's Revenue** | Total MYR value of completed transactions today | Everyone |
| **Active Counters** | How many counters are currently open | Everyone |
| **Pending Approvals** | Transactions >= RM 50,000 waiting for manager | Managers |
| **Flagged Items** | Compliance flags needing review | Compliance/Managers |

---

## 3. Daily Operations

### 3.0 Section Introduction

This section guides you through your daily work as a teller. Think of this as your **playbook for the day** - it tells you everything you need to do from the moment you arrive until you leave.

**Why This Section Matters**:
Every day follows a similar pattern: open your counter, conduct transactions, handle shift changes, and close up. Understanding this flow ensures you never miss a step and every transaction is properly recorded.

**What You Will Learn**:
- The exact sequence of daily tasks
- How to open your counter properly
- What to do during your shift
- How to hand over to the next person
- How to close your counter at day's end

**Real-World Context**:
Imagine you arrive at 8:30 AM for your shift. This section tells you: log in at 8:35, open counter C01 at 8:45, process transactions until 5:00 PM, hand over at 5:15, close at 5:30. Each step builds on the previous one.

### 3.1 Your Typical Day Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   START     │───▶│   OPEN      │───▶│  TRANSACT   │───▶│   CLOSE     │
│   SHIFT     │    │   COUNTER   │    │   (BUY/SELL)│    │   COUNTER   │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
      │                  │                  │                  │
      │                  │                  │                  │
      ▼                  ▼                  ▼                  ▼
  Checklist          Steps 3.2         Steps 4.1-4.3        Steps 5.4
```

### 3.2 Opening a Counter (Before Your First Transaction)

Before you can process any transactions, you must open your counter/till.

**Why do I need to open a counter?**
- The counter tracks ALL cash movements
- Without opening, the system doesn't know you have cash to sell/buy
- It's required for accountability and audit trails

**Step-by-Step Counter Opening**:

1. **Navigate**: Click **"Counter Management"** → **"Counters"**
2. **Click**: **"Open Counter"** button (top right)
3. **Select Counter**: Choose your assigned counter (e.g., "C01 - Counter 1")
4. **Add Currencies**: For each currency you'll accept today:
   - Click **"Add Currency"**
   - Select currency (e.g., USD, EUR, GBP)
   - Enter **Opening Balance** = the physical cash amount you have
5. **Review**: Check all amounts are correct
6. **Click**: **"Open Counter"**

**Example - Opening with USD and EUR**:

```
Counter: C01 - Counter 1

Opening Floats:
┌──────────────┬─────────────────┐
│ Currency     │ Opening Balance │
├──────────────┼─────────────────┤
│ USD          │ 50,000.00       │
│ EUR          │ 30,000.00       │
│ GBP          │ 20,000.00       │
│ SGD          │ 40,000.00       │
└──────────────┴─────────────────┘

[Cancel]                    [Open Counter]
```

**Tips**:
- Count your physical cash BEFORE entering amounts
- Opening balance = actual physical cash you have
- The system will track transactions against this amount

---

## 4. Transaction Management

### 4.0 Section Introduction

This section is the **heart of your job** as a teller. Transactions are the core business activity - without transactions, there's no currency exchange business. Every time a customer wants to buy or sell foreign currency, you create a transaction.

**Why This Section Matters**:
- Transactions are how the company earns revenue
- Every transaction must be recorded for legal compliance
- Incorrect transactions can result in financial loss
- BNM requires all transactions to be properly documented
- Your job security depends on getting transactions right

**What You Will Learn**:
- How to process a BUY transaction (customer sells us currency)
- How to process a SELL transaction (customer buys currency from us)
- What happens when transactions are large (>= RM 50,000)
- How to handle pending transactions waiting for approval
- How to refund a completed transaction
- How to cancel a transaction that cannot be completed

**The Two Types of Transactions Explained Simply**:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    BUY vs SELL - What's the Difference?              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   BUY TRANSACTION                                                     │
│   ─────────────────                                                   │
│   Customer HAS foreign currency → Customer WANTS MYR                   │
│   Example: Customer has USD 1,000, wants Malaysian Ringgit          │
│   You: Give them RM, Take their USD                                  │
│   Your USD inventory: INCREASES                                      │
│                                                                      │
│   SELL TRANSACTION                                                    │
│   ──────────────────                                                 │
│   Customer WANTS foreign currency → Customer HAS MYR                  │
│   Example: Customer wants USD 1,000, pays with RM                     │
│   You: Give them USD, Take their RM                                   │
│   Your USD inventory: DECREASES                                       │
│                                                                      │
│   REMEMBER: BUY = We take currency IN, SELL = We give currency OUT   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Transaction Flow Overview**:

```
Customer Arrives
      │
      ▼
┌─────────────────┐
│ Identify Customer │
│ (Check ID, KYC) │
└─────────────────┘
      │
      ▼
┌─────────────────┐
│ BUY or SELL?    │──── BUY ────► Customer sells us foreign currency
│                 │              (We give them MYR, take their foreign)
└─────────────────┘
      │
      │ SELL
      ▼──── Customer buys from us
┌─────────────────┐
│ Check Stock     │──── Not enough ────► Cannot proceed, ask to return
│ Available?      │                    later or transfer stock
└─────────────────┘
      │
      │ Enough stock
      ▼
┌─────────────────┐
│ Enter in System │
└─────────────────┘
      │
      ▼
┌─────────────────┐
│ Amount >=       │──── YES ────► Transaction goes to PENDING APPROVAL
│ RM 50,000?     │              (Manager must approve)
└─────────────────┘
      │
      │ NO
      ▼
┌─────────────────┐
│ Transaction     │
│ COMPLETED       │
└─────────────────┘
      │
      ▼
┌─────────────────┐
│ Give customer   │
│ their money    │
│ + Receipt      │
└─────────────────┘
```

### 4.1 Buy Transaction (Customer Sells You Foreign Currency)

**Scenario**: A customer walks in with USD 1,000 and wants to exchange it for MYR.

**What happens in a BUY**:
- Customer gives you foreign currency (USD)
- You give customer MYR
- Your foreign currency inventory INCREASES

**Step-by-Step**:

1. **Navigate**: Click **"Operations"** → **"New Transaction"**
2. **Select Type**: Choose **"Buy"** (first option)
3. **Search Customer**:
   - Enter customer name, ID, or phone
   - If new customer, click **"+ New Customer"**
4. **Select Currency**: Choose **"USD"**
5. **Enter Amount**: Customer is selling **USD 1,000**
6. **Check Rate**: System shows current rate (e.g., 4.7500)
7. **System Calculates**: Shows customer receives: **RM 4,750.00**
8. **Select Purpose**: Choose from dropdown (e.g., "Travel")
9. **Enter Source of Funds**: Choose (e.g., "Salary")
10. **Click**: **"Create Transaction"**

**Transaction Preview**:

```
Transaction Type: BUY (Customer sells us foreign currency)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Customer:        Ahmad bin Ibrahim
ID Type:         MyKad
ID Number:       123456-78-9012

Currency:        USD
Amount:          1,000.00
Exchange Rate:   4.7500
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Customer Receives:  RM 4,750.00
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Purpose:          Travel
Source of Funds:  Salary

[Cancel]                              [Create Transaction]
```

**After Transaction**:
- System updates your USD position (increases by USD 1,000)
- System updates MYR cash (decreases by RM 4,750)
- Transaction receipt is generated

### 4.2 Sell Transaction (Customer Buys Foreign Currency From You)

**Scenario**: A customer wants to buy USD 500, paying with MYR.

**What happens in a SELL**:
- Customer gives you MYR
- You give customer foreign currency (USD)
- Your foreign currency inventory DECREASES

**Step-by-Step**:

1. **Navigate**: Click **"Operations"** → **"New Transaction"**
2. **Select Type**: Choose **"Sell"** (second option)
3. **Search Customer**: Find existing or create new
4. **Select Currency**: Choose **"USD"**
5. **Enter Amount**: Customer wants **USD 500.00**
6. **Check Rate**: System shows rate (e.g., 4.7600)
7. **System Calculates**: Customer pays: **RM 2,380.00**
8. **Fill Purpose and Source of Funds**
9. **Click**: **"Create Transaction"**

**IMPORTANT**: Sell transactions require sufficient stock. If you don't have enough USD in your counter, the transaction will be rejected.

### 4.3 Transaction Amount Thresholds

Understanding thresholds is CRITICAL for compliance:

| Amount (MYR) | What Happens | Who Approves |
|--------------|--------------|--------------|
| **< RM 3,000** | Simplified CDD, auto-complete | None needed |
| **RM 3,000 - 49,999** | Standard CDD, auto-complete | None needed |
| **>= RM 50,000** | Enhanced CDD, **MANAGER APPROVAL REQUIRED** | Manager |
| **>= RM 10,000** (cash) | CTOS Report auto-generated | System |

**Example Scenarios**:

| Scenario | Amount | What You See |
|----------|--------|--------------|
| Customer sells USD 500 @ 4.75 | RM 2,375 | "Transaction Completed" (auto) |
| Customer sells USD 5,000 @ 4.75 | RM 23,750 | "Transaction Completed" (auto) |
| Customer sells USD 11,000 @ 4.75 | RM 52,250 | "Pending Approval" - wait for manager |
| Customer sells USD 100,000 @ 4.75 | RM 475,000 | "Pending Approval" + STR triggered |

### 4.4 Large Transaction Flow (>= RM 50,000)

When you create a transaction >= RM 50,000:

1. **You create** the transaction
2. **System sets status** to "Pending Approval"
3. **Manager sees** it in their approval queue
4. **Manager reviews**:
   - Customer details and risk rating
   - Transaction purpose
   - CDD documentation
5. **Manager approves or rejects**
6. **If approved**: Transaction completes
7. **If rejected**: Transaction cancelled, customer notified

**As a Teller - What You Need to Do**:

```
1. Create transaction normally
2. Tell customer: "This transaction requires manager approval. Please wait."
3. Wait for manager to approve/reject
4. If approved: Give customer their money
5. If rejected: Explain and offer alternatives
```

### 4.5 Completing a Transaction

**For small transactions (auto-complete)**:

1. Transaction completes instantly
2. System updates positions
3. Receipt is generated
4. Give customer their money/receipt

**For large transactions (manager approval)**:

1. Wait for approval notification
2. When approved, complete the cash exchange
3. Give customer receipt
4. File any required documents

### 4.6 Transaction Receipt

Every completed transaction generates a receipt containing:

- Transaction ID (quote this for any queries)
- Date and time
- Customer information
- Currency and amount
- Exchange rate used
- MYR equivalent
- Compliance information (CDD level applied)

**Keep receipts for customer disputes and audit purposes.**

---

## 5. Counter/Till Management

### 5.0 Section Introduction

This section covers **Counter/Till Management** - arguably the second most important aspect of your daily work (after transactions). The counter is where all the action happens. Think of it as your **cash drawer** that must be properly opened, monitored, and closed.

**Why This Section Matters**:
- The counter tracks ALL money coming in and going out
- If the counter isn't opened, you cannot process transactions
- Every currency you hold must be accounted for
- Discrepancies (variance) can result in disciplinary action
- Proper counter management protects both you and the company

**What You Will Learn**:
- What a counter is and why it exists
- How to open your counter with the correct opening balance
- How to monitor your counter during the day
- How to perform a counter handover during shift changes
- How to close your counter at the end of the day
- How to handle variances (differences between expected and actual cash)

**The Counter is Your Responsibility**:
When you open a counter, you become responsible for:
- All cash in the drawer
- All transactions processed from that counter
- The accuracy of closing balances
- Any variances that cannot be explained

**Real-World Analogy**:
Think of a vending machine. When you load it with items and cash, you're responsible for:
- Tracking what's loaded (opening balance)
- Recording every sale (transactions)
- Knowing how much should be left (expected balance)
- Accounting for any missing items or cash (variance)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    COUNTER LIFECYCLE                                   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   ┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐       │
│   │  OPEN   │────▶│ ACTIVE  │────▶│ HANDOVER│────▶│  CLOSE  │       │
│   │ START   │     │ TRANSC- │     │  SHIFT  │     │   END   │       │
│   │  SHIFT  │     │ TIONS   │     │ CHANGE  │     │   DAY   │       │
│   └─────────┘     └─────────┘     └─────────┘     └─────────┘       │
│        │               │               │               │             │
│        ▼               ▼               ▼               ▼             │
│   Count cash     Monitor all      Count cash      Count cash           │
│   Set opening    transactions     Verify with     Verify with           │
│   balance        Track variance   next person     expected              │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 5.1 What is a Counter/Till?

A **Counter** (or Till) is your workstation where you:

- Store foreign currency inventory
- Conduct buy/sell transactions
- Track cash flows

**Key Concept**: Each counter has its own inventory. Counter C01's USD is separate from Counter C02's USD.

### 5.2 Viewing Your Counter Status

**Navigate**: **Counter Management** → **Counters** → **Status**

You'll see:

```
┌─────────────────────────────────────────────────────────────┐
│  YOUR COUNTER STATUS                                         │
├─────────────────────────────────────────────────────────────┤
│  Counter:        C01 - Counter 1                             │
│  Status:         OPEN (since 9:00 AM)                        │
│  Session Holder: You (teller1@cems.my)                      │
├─────────────────────────────────────────────────────────────┤
│  CURRENCY BALANCES                                           │
│  ┌──────────┬─────────────────┬─────────────────┐          │
│  │ Currency  │ Your Balance    │ In Transit       │          │
│  ├──────────┼─────────────────┼─────────────────┤          │
│  │ USD       │ 51,000.00       │ 0.00            │          │
│  │ EUR       │ 30,000.00       │ 0.00            │          │
│  │ GBP       │ 18,500.00       │ 0.00            │          │
│  └──────────┴─────────────────┴─────────────────┘          │
│                                                              │
│  EXPECTED CLOSING: Based on today's transactions              │
│  Variance: +RM 50.00 (positive = more cash than expected)   │
└─────────────────────────────────────────────────────────────┘
```

### 5.3 Counter Handover (Shift Change)

When your shift ends, you transfer the counter to the next person.

**Who Can Do Handover**: Manager or Admin (not tellers)

**Handover Process**:

```
┌─────────────────────────────────────────────────────────────┐
│  COUNTER HANDOVER                                           │
├─────────────────────────────────────────────────────────────┤
│  Step 1: Manager initiates handover                          │
│  Step 2: Select outgoing user (current holder)               │
│  Step 3: Select incoming user (next teller)                  │
│  Step 4: Count physical cash together                         │
│  Step 5: Enter physical counts in system                     │
│  Step 6: System calculates variance                           │
│  Step 7: Both parties verify and sign off                    │
└─────────────────────────────────────────────────────────────┘
```

**Step-by-Step Handover**:

1. **Manager navigates**: Counter Management → Counters → Handover
2. **Select Counter**: Choose the counter being handed over
3. **Select Outgoing User**: Person leaving (auto-filled if only one open)
4. **Select Incoming User**: Person taking over
5. **Enter Physical Counts**: Both tellers count cash together
6. **System calculates variance**: Compares expected vs actual
7. **Both verify**: Manager approves the handover

**Handover Validation Rules** (System Enforced):

| Rule | What It Means |
|------|---------------|
| Outgoing must be session holder | Only the person with the open session can hand over |
| Incoming can't be at another counter | Person must be free |
| Session must be open | Can't handover a closed counter |
| Supervisor must be Manager/Admin | Teller cannot self-approve handover |

### 5.4 Closing a Counter (End of Day)

When your counter closes for the day:

1. **Navigate**: Counter Management → Counters → Close
2. **Select Counter**: Choose your counter
3. **Enter Closing Balance**: Count physical cash for each currency
4. **System Calculates**:
   - Expected balance (based on opening + transactions)
   - Variance = Actual - Expected
5. **If Variance > RM 100**: You must enter a reason/notes
6. **If Variance > RM 500**: Manager approval required
7. **Close Counter**: Session ends

**Variance Types**:

| Variance | Color | Meaning | Action Required |
|----------|-------|---------|-----------------|
| RM 0 - 100 | Green | Normal | None |
| RM 101 - 500 | Yellow | Small discrepancy | Notes required |
| > RM 500 | Red | Large discrepancy | Manager + investigation |

**Why Variance Matters**:
- Positive variance (more cash): Could indicate counterfeit notes or counting errors
- Negative variance (less cash): Could indicate robbery, theft, or counting errors
- All variances are logged for audit

---

## 6. Customer Management

### 6.0 Section Introduction

This section is about **Customer Management** - understanding who your customers are and how to properly verify their identity. In the money services business, knowing your customer isn't just good practice - it's the **law**.

**Why This Section Matters**:
- BNM (Bank Negara Malaysia) requires all customers to be identified
- Failing to verify customers can result in massive fines
- You personally can be penalized for non-compliance
- Proper KYC (Know Your Customer) protects the company from criminals
- It's your first line of defense against money laundering

**What You Will Learn**:
- What information to collect from every customer
- How to verify customer identity documents
- How to determine customer risk levels
- How to handle different types of customers (local vs foreign)
- How to search for existing customers in the system
- What to do when a customer seems suspicious

**Know Your Customer (KYC) - The Legal Requirement**:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    KYC - KNOW YOUR CUSTOMER                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   WHY IT MATTERS:                                                   │
│   ───────────────                                                   │
│   Money laundering = Cleaning "dirty" money from crimes              │
│   Terrorist financing = Moving money for terrorist activities         │
│                                                                      │
│   YOUR ROLE:                                                        │
│   ──────────                                                        │
│   Every customer MUST be identified before any transaction            │
│   No ID = No Transaction (it's the law!)                            │
│   Document everything accurately                                      │
│   Report suspicious activity immediately                             │
│                                                                      │
│   PENALTIES FOR NON-COMPLIANCE:                                     │
│   ────────────────────────────                                       │
│   • Individual fines up to RM 1 million                              │
│   • Imprisonment up to 5 years                                      │
│   • Company fines up to RM 5 million                                 │
│   • Business license revocation                                      │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Customer Categories**:

| Customer Type | Who They Are | ID Required | Special Handling |
|--------------|---------------|-------------|------------------|
| **Individual Local** | Malaysian citizens | MyKad | Standard |
| **Individual Foreign** | Tourists, expats | Passport + Entry stamp | Must verify entry date |
| **Corporate** | Companies, businesses | Company docs + Director ID | Enhanced due diligence |
| **PEP** | Politically Exposed Person | Standard + Declaration | Enhanced + Senior approval |

### 6.1 Customer Data Requirements

Every customer must be verified before transactions.

**Required for All Customers**:

| Field | Description | Example |
|-------|-------------|---------|
| Full Name | As per ID document | Ahmad bin Ibrahim |
| ID Type | Malaysian (MyKad) or Passport | MyKad |
| ID Number | From MyKad/Passport | 123456-78-9012 |
| Nationality | Country of citizenship | Malaysian |
| Date of Birth | For age verification | 15/03/1985 |
| Phone | Contact number | 012-345-6789 |
| Address | Full address | 123 Jalan Utama, 50450 KL |

**Additional for Risk Customers**:
- PEP (Politically Exposed Person) declaration
- Source of wealth documentation
- Employer details
- Annual transaction estimate

### 6.2 Customer Risk Levels

The system automatically rates customers:

| Risk Level | Who Gets This | CDD Required |
|------------|---------------|---------------|
| **Low** | Regular customers, small amounts | Simplified |
| **Medium** | Moderate volume customers | Standard |
| **High** | PEPs, large transactions, flagged | Enhanced |

### 6.3 CDD Levels Explained

| CDD Level | When Applied | What It Means |
|-----------|--------------|---------------|
| **Simplified** | Amount < RM 3,000 AND low risk | Basic ID check |
| **Standard** | RM 3,000 - 49,999 | Full ID verification + purpose |
| **Enhanced** | >= RM 50,000 OR PEP OR High Risk | Extra documentation + approval |

### 6.4 Searching for Customers

**Navigate**: Operations → Customers

**Search by**:
- Name (partial match works)
- ID number
- Phone number
- Risk rating

**Creating a New Customer**:

1. Click **"+ New Customer"**
2. Fill in all required fields
3. Upload ID document (if required)
4. Set risk level (system may auto-assign)
5. Save customer

---

## 7. Compliance & AML

### 7.0 Section Introduction

This section covers **Compliance & AML (Anti-Money Laundering)**. This is one of the most critical areas of your job - possibly the most important. Why? Because AML compliance isn't just about following rules; it's about **preventing criminals from using our business to clean their money**.

**Why This Section Matters**:
- Non-compliance can result in massive fines (up to RM 5 million for the company)
- Individual tellers can face fines up to RM 1 million and imprisonment
- Our company can lose its license to operate
- Money laundering enables terrorism, drug trafficking, and other crimes
- You are the **first line of defense** against financial crime

**What You Will Learn**:
- What AML means and why it exists
- How to recognize suspicious transactions
- What compliance flags mean and how to respond
- How the STR (Suspicious Transaction Report) process works
- When and how to file CTOS reports
- Your legal obligations as a teller

**The Serious Reality of AML**:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    AML - IT'S NOT JUST PAPERWORK                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   WHAT IS MONEY LAUNDERING?                                         │
│   ─────────────────────────                                         │
│   Taking "dirty" money from crimes and making it look "clean"        │
│                                                                      │
│   STAGES OF MONEY LAUNDERING:                                      │
│   1. PLACEMENT - Getting cash into the system                       │
│   2. LAYERING - Moving money around to hide origin                  │
│   3. INTEGRATION - Bringing money back into legitimate business      │
│                                                                      │
│   YOUR COUNTER IS OFTEN THE "PLACEMENT" TARGET                      │
│   ───────────────────────────────────────────────                     │
│   Criminals try to exchange small amounts many times ("structuring") │
│   They use different people ("money mules")                           │
│   They give false reasons for transactions                           │
│   They avoid ID verification whenever possible                        │
│                                                                      │
│   RED FLAGS TO WATCH FOR:                                           │
│   • Customer seems nervous, avoiding eye contact                     │
│   • Different people picking up money for same customer              │
│   • Transactions just below reporting thresholds                      │
│   • Customer doesn't know basic details about transaction            │
│   • Unusual patterns for that customer's history                     │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Who Does What in Compliance**:

| Role | What They Do | Who This Is |
|------|--------------|-------------|
| **You (Teller)** | Initial identification, flag suspicious activity | Everyone |
| **Compliance Officer** | Review flags, manage STR reports | Specialized staff |
| **Money Laundering Reporting Officer (MLRO)** | File STRs to BNM | Senior compliance |
| **Management** | Oversee compliance program, approve policies | Managers/ Directors |

**The Three Lines of Defense**:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    THREE LINES OF DEFENSE                                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   FIRST LINE: YOU (The Teller)                                     │
│   ─────────────────────────────────                                │
│   • Verify customer identity                                          │
│   • Recognize suspicious patterns                                     │
│   • Ask questions when something seems wrong                         │
│   • Refuse transactions that seem suspicious                         │
│   • Report concerns to your manager                                 │
│                                                                      │
│   SECOND LINE: Compliance Team                                       │
│   ───────────────────────────────────                               │
│   • Review flagged transactions                                      │
│   • Monitor for unusual patterns                                     │
│   • Manage sanctions lists                                          │
│   • Conduct AML training                                             │
│   • File STR reports to BNM                                          │
│                                                                      │
│   THIRD LINE: External Auditors / BNM                               │
│   ────────────────────────────────────                              │
│   • Audit our AML procedures                                         │
│   • Check our compliance                                            │
│   • Impose fines for violations                                      │
│   • License and supervise MSB businesses                             │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 7.1 What is AML?

AML = Anti-Money Laundering

BNM requires us to:
- Verify customer identity
- Report suspicious transactions
- Keep records for 7 years
- Train staff on AML procedures

### 7.2 Compliance Flags

The system automatically flags transactions for review:

| Flag Type | What It Means | Severity |
|-----------|---------------|----------|
| **LargeAmount** | Transaction >= RM 50,000 | Medium |
| **SanctionsHit** | Possible match with sanctions list | Critical |
| **Velocity** | Too many transactions in 24 hours | Medium |
| **Structuring** | Multiple transactions near threshold | High |
| **EddRequired** | Enhanced Due Diligence needed | Medium |
| **PepStatus** | Customer is Politically Exposed Person | Medium |
| **HighRiskCustomer** | Customer has High risk rating | Medium |

### 7.3 What Happens When a Transaction is Flagged?

1. Transaction status changes to **"OnHold"**
2. Transaction does NOT complete automatically
3. Compliance Officer reviews the flag
4. Officer can:
   - **Clear**: Remove flag, proceed with transaction
   - **Escalate**: Pass to senior compliance
   - **Generate STR**: Create Suspicious Transaction Report
   - **Reject**: Cancel the transaction

### 7.4 STR (Suspicious Transaction Report)

An STR is a formal report to BNM about suspicious activity.

**When STRs are Generated**:
- Compliance officer determines transaction is suspicious
- Transaction cannot be explained by normal activity
- Amount or pattern is unusual for the customer

**STR Workflow**:

```
Draft → Pending Review → Pending Approval → Submitted → Acknowledged
  │           │                │              │            │
  ▼           ▼                ▼              ▼            ▼
Compliance  Senior         Manager        BNM          BNM
Officer     Compliance     approves       receives     confirms
creates     reviews        sends
```

**As a Teller - What You Do**:
- You don't create STRs (Compliance does)
- You may be asked for additional information
- Be honest and accurate in your responses

### 7.5 CTOS Reporting (>= RM 10,000 Cash)

**What**: Cash Transaction Report - Required by BNM for all cash transactions >= RM 10,000

**Who Does It**: System auto-generates when you complete the transaction

**What You Do**:
1. Complete transaction normally
2. System auto-generates CTOS
3. Ensure customer signs any required forms
4. File copies as required

---

## 8. Accounting Basics

### 8.0 Section Introduction

This section introduces **Accounting Basics** - understanding how every transaction affects the company's books. You might think "I'm a teller, not an accountant" but understanding accounting helps you see the bigger picture of your work.

**Why This Section Matters**:
- Every transaction creates accounting entries
- Your transactions affect company profits
- Understanding accounting makes you better at your job
- Management uses accounting data to make decisions
- BNM requires proper accounting records

**What You Will Learn**:
- What double-entry accounting means (debits and credits)
- The four account types: Assets, Liabilities, Revenue, Expenses
- How BUY and SELL transactions create journal entries
- What the chart of accounts means
- How to read basic financial reports

**Double-Entry Accounting Explained Simply**:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    DOUBLE-ENTRY ACCOUNTING - THE BASIC IDEA                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   EVERY TRANSACTION AFFECTS TWO THINGS                               │
│   ──────────────────────────────────────                            │
│                                                                      │
│   Think of it like a SCALE:                                         │
│                                                                      │
│        LEFT SIDE              RIGHT SIDE                             │
│        (DEBIT)               (CREDIT)                               │
│                                                                      │
│   • Something comes in → DEBIT (increase)                          │
│   • Something goes out → CREDIT (decrease)                         │
│   • The scale must ALWAYS balance!                                  │
│                                                                      │
│   EXAMPLE - You buy USD 1,000:                                      │
│   ────────────────────────────────────────                           │
│   You GIVE RM 4,750 to customer → Cash (MYR) decreases → CREDIT    │
│   You GET USD 1,000 → Foreign Currency Inventory increases → DEBIT   │
│                                                                      │
│   The two entries always balance!                                   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**The Four Account Types**:

| Type | What It Is | Example | DEBIT means | CREDIT means |
|------|-----------|---------|-------------|--------------|
| **ASSETS** | Things you own | Cash, Currency inventory | Increases | Decreases |
| **LIABILITIES** | Things you owe | Accounts payable | Decreases | Increases |
| **REVENUE** | Money coming in | Forex trading profit | Decreases | Increases |
| **EXPENSES** | Money going out | Forex loss, operating costs | Increases | Decreases |

### 8.1 Double-Entry Accounting (Don't Panic!)

Every transaction affects TWO accounts:

**BUY Example** (Customer sells us USD 1,000 @ 4.75):

```
┌─────────────────────────────────────────┐
│  Journal Entry for BUY                  │
├─────────────────────────────────────────┤
│  DEBIT:   Foreign Currency Inventory     │
│           (USD)           RM 4,750.00    │
│                                         │
│  CREDIT:  Cash (MYR)       RM 4,750.00   │
│                                         │
│  Meaning: We got USD, we gave MYR       │
└─────────────────────────────────────────┘
```

**SELL Example** (Customer buys USD 1,000 @ 4.80):

```
┌─────────────────────────────────────────┐
│  Journal Entry for SELL                 │
├─────────────────────────────────────────┤
│  DEBIT:   Cash (MYR)       RM 4,800.00 │
│                                         │
│  CREDIT:  Foreign Currency Inventory     │
│           (USD)           RM 4,750.00   │
│                                         │
│  CREDIT:  Forex Trading Revenue         │
│                              RM 50.00   │
│                                         │
│  Meaning: We got MYR, we gave USD      │
│          + profit on the deal           │
└─────────────────────────────────────────┘
```

### 8.2 Key Accounts You Should Know

| Account Code | Name | What It Tracks |
|--------------|------|----------------|
| 1000 | Cash (MYR) | Malaysian Ringgit cash |
| 2000 | Foreign Currency Inventory | All foreign currencies we hold |
| 5000 | Forex Trading Revenue | Profit from currency exchanges |
| 6000 | Forex Loss | Loss from currency exchanges |

### 8.3 Understanding Position Limits

Each currency has a maximum position limit (set by management).

**Position Limit Report** shows:
- Current inventory for each currency
- Maximum allowed
- Utilization percentage

**Example - USD Position**:

```
┌─────────────────────────────────────────┐
│  USD POSITION                           │
├─────────────────────────────────────────┤
│  Opening Balance:    USD 50,000.00       │
│  Today's Buys:      USD 10,000.00       │
│  Today's Sells:      USD  8,000.00       │
│  ──────────────────────────────────────  │
│  Current Position:   USD 52,000.00       │
│  Maximum Limit:      USD 75,000.00       │
│  Utilization:         69.3%               │
└─────────────────────────────────────────┘
```

---

## 9. Reports & Regulatory Filing

### 9.0 Section Introduction

This section covers **Reports & Regulatory Filing**. As a licensed Money Services Business (MSB), we are required by Bank Negara Malaysia (BNM) to submit various reports. These aren't optional - **failure to report can result in massive fines and loss of our operating license**.

**Why This Section Matters**:
- BNM requires specific reports at specific times
- Late or incorrect reports can result in fines
- Reports prove we're following AML/CFT regulations
- Proper reporting protects our license to operate
- Some reports are automatic, some require action from you

**What You Will Learn**:
- Which reports BNM requires from us
- How to generate daily reports (MSB2)
- What CTOS reports are and when they're filed
- The difference between LCTR, LMCA, and QLVR
- How to view previously filed reports

**The Regulatory Framework**:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    WHO REQUIRES WHAT?                                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   BANK NEGARA MALAYSIA (BNM) - Our Primary Regulator               │
│   ───────────────────────────────────────────────────────          │
│   • Issues our MSB license                                         │
│   • Sets AML/CFT requirements                                      │
│   • Conducts inspections                                           │
│   • Imposes fines for non-compliance                               │
│                                                                      │
│   REQUIRED REPORTS FROM US:                                        │
│   ────────────────────────────                                      │
│   • MSB2 - Daily transaction summary                              │
│   • LCTR - Large Cash Transaction Report (Monthly)                │
│   • LMCA - Monthly Large Cash Aggregate                            │
│   • QLVR - Quarterly Large Value Report                           │
│   • CTOS - Cash Transaction Report (Per transaction >= RM 10k)   │
│   • STR - Suspicious Transaction Report (As needed)              │
│                                                                      │
│   FILING DEADLINES:                                               │
│   ─────────────────                                               │
│   • MSB2: By 12:00 noon daily (previous day)                     │
│   • LCTR: By 10th of following month                              │
│   • LMCA: By 10th of following month                              │
│   • QLVR: By 30th of following quarter                            │
│   • CTOS: Within 24 hours of transaction                           │
│   • STR: Within 1 working day of suspicion                         │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Reports You Might Generate vs. Reports Filed Automatically**:

| Report | Who Generates It | When | Your Involvement |
|--------|-----------------|------|------------------|
| **MSB2** | You (Manager) | Daily | May need to generate if system auto-fails |
| **LCTR** | System | Monthly | Review for accuracy |
| **LMCA** | System | Monthly | Review for accuracy |
| **QLVR** | System | Quarterly | Review for accuracy |
| **CTOS** | System | Per transaction >= RM 10k | Ensure customer signs forms |
| **STR** | Compliance | When suspicious activity | Provide information if asked |

### 9.1 BNM Required Reports

| Report | Frequency | What It Contains |
|--------|----------|------------------|
| **MSB2** | Daily | All transactions summary |
| **LCTR** | Monthly | Large Cash Transaction Report (>= RM 50k) |
| **LMCA** | Monthly | Monthly Large Cash Aggregate |
| **QLVR** | Quarterly | Quarterly Large Value Report |
| **CTOS** | Per transaction | Cash Transaction Report (>= RM 10k) |
| **STR** | As needed | Suspicious Transaction Report |

### 9.2 Generating MSB2 Report

**Navigate**: Reports → MSB2

1. Select **Date** (usually "yesterday" or "today")
2. Click **"Generate Report"**
3. Preview the report
4. Export to PDF for filing

**MSB2 Contains**:
- Total buy/sell volumes
- Number of transactions
- Currency breakdown
- Counter performance

### 9.3 View Report History

**Navigate**: Reports → Report History

Shows all previously generated reports for audit purposes.

---

## 10. Troubleshooting

### 10.0 Section Introduction

This section helps you when things don't go as expected. Even the best systems have issues, and knowing how to handle problems quickly keeps the business running smoothly. This section is your **problem-solver guide**.

**Why This Section Matters**:
- Problems will happen - knowing how to handle them reduces stress
- Quick problem resolution keeps customers happy
- Proper troubleshooting prevents small issues from becoming big problems
- Wrong handling of issues can create compliance problems
- Your response to problems affects customer trust

**What You Will Learn**:
- Common problems and their solutions
- What error messages really mean
- When to escalate issues to your manager
- How to get help when you need it
- How to document issues properly

**The Troubleshooting Mindset**:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    WHEN SOMETHING GOES WRONG                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   STEP 1: STAY CALM                                                  │
│   ───────────────────                                                │
│   Don't panic. Most problems have simple solutions.                    │
│   Take a breath and approach the problem logically.                   │
│                                                                      │
│   STEP 2: UNDERSTAND THE ERROR                                        │
│   ───────────────────────────────────                                │
│   What is the system telling you? Read the error message.             │
│   What does it mean in plain language?                               │
│                                                                      │
│   STEP 3: TRY THE SIMPLE FIX FIRST                                    │
│   ──────────────────────────────                                     │
│   • Refresh the page                                                  │
│   • Log out and log back in                                          │
│   • Check your internet connection                                    │
│   • Verify your inputs are correct                                    │
│                                                                      │
│   STEP 4: ESCALATE IF NEEDED                                         │
│   ──────────────────────                                             │
│   If you've tried the simple fixes and it still doesn't work:         │
│   • Contact your manager                                             │
│   • Document what happened                                           │
│   • Take note of any error codes                                     │
│   • Don't try to hack or bypass the system                           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**Escalation Guide - Who to Contact**:

| Problem Type | First Contact | If Not Resolved |
|-------------|---------------|------------------|
| Can't log in | IT Support | Your Manager |
| Transaction error | Your Manager | IT Support |
| Counter issue | Your Manager | Senior Manager |
| Compliance question | Compliance Officer | MLRO |
| Customer complaint | Your Manager | Customer Service Head |
| System down | IT Support | IT Manager |

### 10.1 Common Issues and Solutions

| Problem | Likely Cause | Solution |
|---------|--------------|----------|
| "Insufficient balance" | Not enough foreign currency for SELL | Check counter position, source more stock |
| "Counter already open" | Session wasn't closed properly | Manager closes orphaned session |
| "Transaction pending approval" | Amount >= RM 50,000 | Wait for manager approval |
| "Customer not found" | Typo in search | Try partial name or ID number |
| "Rate expired" | Rate is too old | Refresh rates or contact manager |
| "Session timeout" | Inactive too long | Log in again |

### 10.2 Error Messages Explained

| Error Message | What It Really Means | What To Do |
|--------------|---------------------|------------|
| "Insufficient stock" | You don't have enough foreign currency | Can't complete SELL, source more stock |
| "Pending approval" | Transaction requires manager | Wait or escalate to manager |
| "Customer flagged" | Customer has compliance issue | Compliance officer must review |
| "Counter closed" | Session was closed | Manager must reopen or new session |
| "Duplicate transaction" | Same transaction submitted twice | Check recent transactions |

### 10.3 Getting Help

**Immediate Help**:
1. Ask your shift supervisor/manager
2. Check this manual
3. Contact IT support

**For System Issues**:
- Take note of error message
- Screenshot if possible
- Report to IT with details

---

## 11. FAQ - Frequently Asked Questions

### 11.0 Section Introduction

This section answers the most **common questions** new employees ask. Think of it as "what everyone wants to know but is afraid to ask." These answers come from real questions asked by new tellers like you.

**Why This Section Matters**:
- Quick answers to common problems saves you time
- Knowing answers makes you look competent
- These are things your manager wishes everyone knew
- Answers here help you avoid common mistakes
- Fast access to answers improves customer service

**How to Use This Section**:
- Read through once when you start (day 1)
- Bookmark for quick reference
- Share with colleagues who might have the same questions
- If your question isn't here, ask your manager

**The Questions in This Section**:

| # | Question | Category |
|---|----------|----------|
| 1 | What if customer wants huge amount? | Transactions |
| 2 | Not enough foreign currency to sell? | Transactions |
| 3 | Customer seems suspicious? | Compliance |
| 4 | Made a mistake on transaction? | Transactions |
| 5 | Large variance on counter? | Counter |
| 6 | Customer has no ID? | Compliance |
| 7 | System goes down? | Technical |
| 8 | Is my exchange rate correct? | Transactions |

### Common Questions and Answers

### Q: What do I do if a customer wants to exchange a huge amount?

**A**: Transactions >= RM 50,000 require manager approval. Tell the customer to wait while you process, or schedule an appointment for later.

### Q: What if I don't have enough foreign currency to sell?

**A**: You cannot complete the SELL transaction. Options:
1. Ask customer to return later
2. Transfer stock from another counter (Manager approval needed)
3. Place a special order for the currency

### Q: A customer seems suspicious. What do I do?

**A**: Do NOT proceed with the transaction. Contact your manager or compliance officer immediately. Do not alert the customer.

### Q: I made a mistake on a transaction. Can I undo it?

**A**: Completed transactions cannot be edited. Options:
1. If < 24 hours old and completed: Request a REFUND
2. If pending/on-hold: Cancel and recreate
3. Always contact your manager for guidance

### Q: My counter shows a large variance. What do I do?

**A**:
1. Double-check your physical count
2. Look for missing transactions
3. Check for transposition errors (e.g., 100 vs 001)
4. If still unexplained, report to manager immediately

### Q: Can I process a transaction without a customer ID?

**A**: NO. All customers must be identified. This is a BNM requirement. No ID = No transaction.

### Q: What happens if the system goes down?

**A**:
1. Use paper backup forms (if available)
2. Inform your manager
3. System will capture transactions when restored
4. Never process transactions "off-system"

### Q: How do I know my exchange rate is correct?

**A**: Rates are fetched from configured sources. If you suspect an incorrect rate, notify your manager who can verify and override if needed.

---

## 12. Quick Reference Cards

### 12.0 Section Introduction

This section contains **Quick Reference Cards** - one-page summaries you can print and keep at your counter. They're designed to be glanced at quickly when you need a reminder. Think of them as **cheat sheets for busy tellers**.

**Why This Section Matters**:
- Quick visual reminders when you're unsure
- Print and place at your workstation
- Perfect for new staff training
- Handy during busy periods
- Great for quick refreshers before shifts

**What Each Card Covers**:
1. Transaction Checklist - Before, during, after
2. Threshold Cheat Sheet - Amount limits at a glance
3. Variance Thresholds - Green, Yellow, Red
4. Transaction Status Meanings - What each status means
5. Role Quick Guide - What each role can do
6. Currency Codes - Common currency codes and countries

**How to Use These Cards**:
```
┌─────────────────────────────────────────────────────────────────────┐
│                    PRINT AND USE                                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   RECOMMENDED: Print these cards and keep them:                     │
│                                                                      │
│   • At your workstation (laminated if possible)                   │
│   • In your training folder                                         │
│   • Near the counter for quick reference                           │
│                                                                      │
│   FOR TRAINING:                                                      │
│   • Give new tellers their own set                                │
│   • Quiz each other using the cards                               │
│   • Use as reference during shadow shifts                          │
│                                                                      │
│   FOR BUSY PERIODS:                                                 │
│   • Quick glance before rush hours                                 │
│   • Refresh memory during breaks                                  │
│   • Use as reminder for complex situations                         │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Quick Reference 1: Transaction Checklist

```
BEFORE CREATING TRANSACTION:
□ Counter is open
□ Customer identification ready
□ Customer risk level known
□ Sufficient stock (for SELL)

DURING TRANSACTION:
□ Correct transaction type (BUY/SELL)
□ Correct currency selected
□ Correct amount entered
□ Rate verified
□ Purpose selected
□ Source of funds selected

AFTER TRANSACTION:
□ Cash exchanged correctly
□ Receipt generated
□ Receipt given to customer
□ Cash stored securely
```

### Quick Reference 2: Threshold Cheat Sheet

| Amount | CDD Level | Approval Needed | Report Required |
|--------|-----------|-----------------|-----------------|
| < RM 3,000 | Simplified | None | None |
| RM 3,000 - 9,999 | Standard | None | None |
| RM 10,000 - 49,999 | Standard | None | CTOS |
| >= RM 50,000 | Enhanced | **Manager** | CTOS + LCTR |

### Quick Reference 3: Variance Thresholds

| Variance Amount | Label | Action |
|-----------------|-------|--------|
| RM 0 - 100 | Green | Accept and continue |
| RM 101 - 500 | Yellow | Write explanation notes |
| > RM 500 | Red | Manager investigation required |

### Quick Reference 4: Transaction Status Meanings

| Status | Can Proceed? | Next Step |
|--------|--------------|----------|
| Pending | NO | Wait for approval |
| OnHold | NO | Compliance review |
| Completed | YES | Give customer money |
| Cancelled | NO | Transaction rejected |
| Reversed | NO | Refund was processed |

### Quick Reference 5: Role Quick Guide

| Action | Teller | Manager | Compliance |
|--------|--------|---------|-----------|
| Create transaction | ✓ | ✓ | ✗ |
| Approve >= RM 50k | ✗ | ✓ | ✗ |
| Open/close counter | Own only | Any | ✗ |
| View audit logs | ✗ | ✗ | ✓ |
| Create STR | ✗ | ✗ | ✓ |
| Manage users | ✗ | ✗ | ✗ (Admin only) |

### Quick Reference 6: Currency Codes

| Code | Currency | Country |
|------|----------|---------|
| USD | US Dollar | United States |
| EUR | Euro | European Union |
| GBP | British Pound | United Kingdom |
| SGD | Singapore Dollar | Singapore |
| THB | Thai Baht | Thailand |
| AUD | Australian Dollar | Australia |
| JPY | Japanese Yen | Japan |
| HKD | Hong Kong Dollar | Hong Kong |

---

## Appendix: Glossary

| Term | Definition |
|------|------------|
| **AML** | Anti-Money Laundering - laws to prevent illegal money activities |
| **BNM** | Bank Negara Malaysia - Malaysia's central bank |
| **Buy** | Transaction where we purchase foreign currency from customer |
| **CCTOS** | Central Bank's system for reporting (older term, now just CTOS) |
| **CDD** | Customer Due Diligence - verifying customer identity |
| **Counter/Till** | Physical workstation for currency exchange |
| **CTOS** | Cash Transaction Report - BNM required for >= RM 10,000 |
| **CFT** | Countering Financing of Terrorism |
| **EDD** | Enhanced Due Diligence - extra verification for high-risk |
| **KYC** | Know Your Customer - same as CDD |
| **LCTR** | Large Cash Transaction Report - BNM required for >= RM 50,000 |
| **MFA** | Multi-Factor Authentication - extra login security |
| **MSB** | Money Services Business - licensed businesses like us |
| **MYR** | Malaysian Ringgit (RM) |
| **PEP** | Politically Exposed Person - high-risk customer type |
| **Sell** | Transaction where we sell foreign currency to customer |
| **STR** | Suspicious Transaction Report - BNM filing for suspicious activity |
| **Till/Counter** | Same thing - your cash workstation |
| **Variance** | Difference between expected and actual cash |

---

## Document Version History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2026-04-04 | Initial release | CEMS-MY Team |
| 2.0 | 2026-04-06 | Major update for implementation accuracy | CEMS-MY Team |
| 2.1 | 2026-04-12 | Added handover validation rules | CEMS-MY Team |
| 3.0 | 2026-04-12 | Complete rewrite for new employees - expanded with step-by-step guides, scenarios, FAQ, quick reference cards | CEMS-MY Team |
| 3.1 | 2026-04-12 | Added detailed introductions to ALL sections with real-world context, visual diagrams, and comprehensive explanations for new employees | CEMS-MY Team |

---

**END OF USER MANUAL - NEW EMPLOYEE EDITION**

*For technical documentation, system administrators, and developers, please refer to the Developer Documentation.*