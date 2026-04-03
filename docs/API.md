# CEMS-MY API Documentation

**Version**: 1.0  
**Last Updated**: April 2026  
**Base URL**: `https://api.your-domain.com/api/v1`

---

## Table of Contents

1. [Authentication](#1-authentication)
2. [Rate Limiting](#2-rate-limiting)
3. [Error Handling](#3-error-handling)
4. [Transactions API](#4-transactions-api)
5. [Customers API](#5-customers-api)
6. [Currency API](#6-currency-api)
7. [Reports API](#7-reports-api)
8. [Webhooks](#8-webhooks)

---

## 1. Authentication

CEMS-MY API uses Bearer token authentication.

### Obtaining a Token

**Endpoint**: `POST /auth/login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "your-password",
  "mfa_code": "123456"  // Optional, if MFA enabled
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 28800,
    "user": {
      "id": 1,
      "username": "john.doe",
      "email": "user@example.com",
      "role": "manager"
    }
  }
}
```

### Using the Token

Include the token in the Authorization header:

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Refreshing the Token

**Endpoint**: `POST /auth/refresh`

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 28800
  }
}
```

### Logout

**Endpoint**: `POST /auth/logout`

---

## 2. Rate Limiting

- **Authenticated requests**: 60 requests per minute
- **Authentication endpoints**: 10 requests per minute

Rate limit headers are included in responses:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1649092800
```

---

## 3. Error Handling

All errors follow a consistent format:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "amount": ["The amount field is required."],
      "currency": ["The selected currency is invalid."]
    }
  }
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

### Error Codes

| Code | Description |
|------|-------------|
| `AUTHENTICATION_ERROR` | Invalid credentials |
| `AUTHORIZATION_ERROR` | Insufficient permissions |
| `VALIDATION_ERROR` | Request validation failed |
| `RESOURCE_NOT_FOUND` | Requested resource not found |
| `INSUFFICIENT_BALANCE` | Not enough currency stock |
| `RATE_LIMIT_EXCEEDED` | Rate limit reached |
| `SERVER_ERROR` | Internal server error |

---

## 4. Transactions API

### Create Transaction

Create a new foreign currency transaction.

**Endpoint**: `POST /transactions`

**Authorization**: Teller, Manager, Admin

**Request:**
```json
{
  "customer_id": 123,
  "till_id": "TILL-001",
  "type": "Buy",
  "currency_code": "USD",
  "amount_foreign": "1000.00",
  "rate": "4.7500",
  "purpose": "Travel",
  "source_of_funds": "Salary",
  "idempotency_key": "unique-key-123"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "customer_id": 123,
    "till_id": "TILL-001",
    "type": "Buy",
    "currency_code": "USD",
    "amount_foreign": "1000.00",
    "amount_local": "4750.00",
    "rate": "4.7500",
    "status": "Pending",
    "cdd_level": "Standard",
    "hold_reason": "EDD_Required: Large transaction (≥ RM 50,000)",
    "created_at": "2026-04-04T10:30:00Z",
    "requires_approval": true
  },
  "message": "Transaction created successfully. Manager approval required."
}
```

### Get Transaction

Retrieve a specific transaction.

**Endpoint**: `GET /transactions/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "customer": {
      "id": 123,
      "full_name": "John Doe",
      "id_type": "mykad",
      "nationality": "Malaysian"
    },
    "user": {
      "id": 10,
      "username": "teller01"
    },
    "till_id": "TILL-001",
    "type": "Buy",
    "currency_code": "USD",
    "amount_foreign": "1000.00",
    "amount_local": "4750.00",
    "rate": "4.7500",
    "purpose": "Travel",
    "source_of_funds": "Salary",
    "status": "Completed",
    "cdd_level": "Standard",
    "approved_by": {
      "id": 5,
      "username": "manager01"
    },
    "approved_at": "2026-04-04T10:35:00Z",
    "created_at": "2026-04-04T10:30:00Z"
  }
}
```

### List Transactions

Retrieve a paginated list of transactions.

**Endpoint**: `GET /transactions`

**Query Parameters:**
- `page` (integer, optional): Page number, default 1
- `per_page` (integer, optional): Items per page, default 20, max 100
- `status` (string, optional): Filter by status
- `currency_code` (string, optional): Filter by currency
- `type` (string, optional): Filter by type (Buy/Sell)
- `date_from` (date, optional): Start date (YYYY-MM-DD)
- `date_to` (date, optional): End date (YYYY-MM-DD)
- `customer_id` (integer, optional): Filter by customer

**Response:**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 456,
        "type": "Buy",
        "currency_code": "USD",
        "amount_foreign": "1000.00",
        "amount_local": "4750.00",
        "status": "Completed",
        "created_at": "2026-04-04T10:30:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 20,
      "total": 195,
      "total_revenue": "125000.00"
    }
  }
}
```

### Approve Transaction

Approve a pending transaction.

**Endpoint**: `POST /transactions/{id}/approve`

**Authorization**: Manager, Admin only

**Request:**
```json
{
  "notes": "Approved after verifying customer information"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "status": "Completed",
    "approved_by": {
      "id": 5,
      "username": "manager01"
    },
    "approved_at": "2026-04-04T10:35:00Z"
  },
  "message": "Transaction approved successfully"
}
```

### Reject Transaction

Reject a pending transaction.

**Endpoint**: `POST /transactions/{id}/reject`

**Authorization**: Manager, Admin only

**Request:**
```json
{
  "reason": "Customer information incomplete"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "status": "OnHold",
    "hold_reason": "Rejected: Customer information incomplete"
  },
  "message": "Transaction rejected"
}
```

### Cancel Transaction

Cancel a transaction.

**Endpoint**: `POST /transactions/{id}/cancel`

**Request:**
```json
{
  "reason": "Customer requested cancellation"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "status": "Cancelled",
    "cancelled_at": "2026-04-04T11:00:00Z",
    "cancellation_reason": "Customer requested cancellation"
  },
  "message": "Transaction cancelled successfully"
}
```

### Refund Transaction

Create a refund for a completed transaction.

**Endpoint**: `POST /transactions/{id}/refund`

**Request:**
```json
{
  "reason": "Customer returned currency"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "original_transaction_id": 456,
    "refund_transaction_id": 457,
    "amount_foreign": "1000.00",
    "amount_local": "4750.00",
    "created_at": "2026-04-04T12:00:00Z"
  },
  "message": "Refund processed successfully"
}
```

---

## 5. Customers API

### Create Customer

Register a new customer.

**Endpoint**: `POST /customers`

**Request:**
```json
{
  "full_name": "John Doe",
  "id_type": "mykad",
  "id_number": "900101-14-5678",
  "nationality": "Malaysian",
  "date_of_birth": "1990-01-01",
  "address": "123 Jalan Bukit Bintang",
  "phone": "+60123456789",
  "email": "john@example.com",
  "occupation": "Software Engineer",
  "employer": "Tech Company Sdn Bhd"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "full_name": "John Doe",
    "id_type": "mykad",
    "nationality": "Malaysian",
    "date_of_birth": "1990-01-01",
    "phone": "+60123456789",
    "email": "john@example.com",
    "risk_rating": "Low",
    "risk_score": 25,
    "created_at": "2026-04-04T10:00:00Z"
  },
  "message": "Customer created successfully"
}
```

### Get Customer

Retrieve customer details.

**Endpoint**: `GET /customers/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "full_name": "John Doe",
    "id_type": "mykad",
    "nationality": "Malaysian",
    "date_of_birth": "1990-01-01",
    "address": "123 Jalan Bukit Bintang",
    "phone": "+60123456789",
    "email": "john@example.com",
    "risk_rating": "Low",
    "risk_score": 25,
    "pep_status": false,
    "last_transaction_at": "2026-04-04T10:30:00Z",
    "transaction_count": 5,
    "total_volume": "25000.00",
    "created_at": "2026-04-04T10:00:00Z"
  }
}
```

### List Customers

Retrieve a paginated list of customers.

**Endpoint**: `GET /customers`

**Query Parameters:**
- `page` (integer, optional): Page number
- `per_page` (integer, optional): Items per page
- `search` (string, optional): Search by name/phone/ID
- `risk_rating` (string, optional): Filter by risk (Low/Medium/High)
- `date_from` (date, optional): Registration date from
- `date_to` (date, optional): Registration date to

### Update Customer

Update customer information.

**Endpoint**: `PUT /customers/{id}`

**Request:**
```json
{
  "address": "456 Jalan Ampang",
  "phone": "+60198765432"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "address": "456 Jalan Ampang",
    "phone": "+60198765432",
    "updated_at": "2026-04-04T11:00:00Z"
  },
  "message": "Customer updated successfully"
}
```

### Get Customer Transactions

Get transaction history for a customer.

**Endpoint**: `GET /customers/{id}/transactions`

**Response:**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 456,
        "type": "Buy",
        "currency_code": "USD",
        "amount_foreign": "1000.00",
        "status": "Completed",
        "created_at": "2026-04-04T10:30:00Z"
      }
    ],
    "meta": {
      "total": 5,
      "total_volume": "25000.00",
      "avg_transaction": "5000.00"
    }
  }
}
```

---

## 6. Currency API

### Get Current Rates

Retrieve current exchange rates.

**Endpoint**: `GET /currencies/rates`

**Response:**
```json
{
  "success": true,
  "data": {
    "base": "MYR",
    "rates": {
      "USD": {
        "code": "USD",
        "name": "US Dollar",
        "rate": "4.7500",
        "buy_rate": "4.7400",
        "sell_rate": "4.7600",
        "updated_at": "2026-04-04T10:00:00Z"
      },
      "EUR": {
        "code": "EUR",
        "name": "Euro",
        "rate": "5.1200",
        "buy_rate": "5.1100",
        "sell_rate": "5.1300",
        "updated_at": "2026-04-04T10:00:00Z"
      }
    }
  }
}
```

### Get Currency Positions

Get current currency inventory positions.

**Endpoint**: `GET /currencies/positions`

**Authorization**: Manager, Admin

**Response:**
```json
{
  "success": true,
  "data": {
    "positions": [
      {
        "currency_code": "USD",
        "till_id": "TILL-001",
        "balance": "15250.00",
        "avg_cost_rate": "4.6500",
        "last_valuation_rate": "4.7500",
        "unrealized_pnl": "1525.00",
        "last_valuation_at": "2026-04-04T00:00:00Z"
      }
    ]
  }
}
```

### Calculate Transaction

Calculate transaction amounts without creating.

**Endpoint**: `POST /currencies/calculate`

**Request:**
```json
{
  "type": "Buy",
  "currency_code": "USD",
  "amount_foreign": "1000.00"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "type": "Buy",
    "currency_code": "USD",
    "amount_foreign": "1000.00",
    "amount_local": "4740.00",
    "rate": "4.7400",
    "commission": "0.00",
    "total": "4740.00"
  }
}
```

---

## 7. Reports API

### Generate Transaction Report

**Endpoint**: `POST /reports/transactions`

**Authorization**: Manager, Admin, Compliance Officer

**Request:**
```json
{
  "date_from": "2026-04-01",
  "date_to": "2026-04-04",
  "currency_code": "USD",
  "format": "pdf"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "report_id": "REP-20260404-001",
    "download_url": "https://api.your-domain.com/reports/download/REP-20260404-001",
    "expires_at": "2026-04-04T12:00:00Z"
  }
}
```

### Get Compliance Summary

**Endpoint**: `GET /reports/compliance-summary`

**Authorization**: Compliance Officer, Admin

**Query Parameters:**
- `date_from` (date, required)
- `date_to` (date, required)

**Response:**
```json
{
  "success": true,
  "data": {
    "period": {
      "from": "2026-04-01",
      "to": "2026-04-04"
    },
    "summary": {
      "total_transactions": 195,
      "total_amount": "2500000.00",
      "flagged_transactions": 3,
      "large_transactions": 12,
      "suspicious_activities": 1,
      "sanctions_hits": 0
    },
    "by_currency": {
      "USD": { "count": 120, "amount": "1500000.00" },
      "EUR": { "count": 45, "amount": "600000.00" },
      "GBP": { "count": 30, "amount": "400000.00" }
    }
  }
}
```

### Export Data

**Endpoint**: `POST /reports/export`

**Request:**
```json
{
  "type": "transactions",
  "date_from": "2026-04-01",
  "date_to": "2026-04-04",
  "format": "csv"
}
```

**Supported Formats:**
- `csv` - CSV file
- `xlsx` - Excel file
- `pdf` - PDF report
- `json` - JSON data

---

## 8. Webhooks

### Event Types

| Event | Description |
|-------|-------------|
| `transaction.created` | New transaction created |
| `transaction.approved` | Transaction approved |
| `transaction.rejected` | Transaction rejected |
| `transaction.cancelled` | Transaction cancelled |
| `transaction.flagged` | Transaction flagged for review |
| `customer.created` | New customer registered |
| `customer.risk_updated` | Customer risk rating changed |
| `rate.updated` | Exchange rate updated |

### Webhook Payload

```json
{
  "event": "transaction.created",
  "timestamp": "2026-04-04T10:30:00Z",
  "data": {
    "id": 456,
    "type": "Buy",
    "currency_code": "USD",
    "amount_foreign": "1000.00",
    "amount_local": "4750.00",
    "status": "Pending"
  }
}
```

### Webhook Security

Webhooks are signed with HMAC-SHA256:

```http
X-Webhook-Signature: sha256=abc123...
```

Verify signature:

```php
$signature = hash_hmac('sha256', $payload, $webhook_secret);
```

### Retries

- Failed webhooks are retried up to 3 times
- Exponential backoff: 1s, 5s, 25s
- After 3 failures, webhook is disabled

---

## Appendix

### Testing

**API Testing with cURL:**

```bash
# Login
curl -X POST https://api.your-domain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"your-password"}'

# Create transaction
curl -X POST https://api.your-domain.com/api/v1/transactions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 123,
    "till_id": "TILL-001",
    "type": "Buy",
    "currency_code": "USD",
    "amount_foreign": "1000.00"
  }'
```

### SDKs and Libraries

- **PHP**: `composer require cems-my/sdk`
- **JavaScript**: `npm install cems-my-sdk`
- **Python**: `pip install cems-my`

---

**END OF API DOCUMENTATION**
