# CEMS-MY Test Suite Specification

**Document Type:** Test Specification
**Version:** 1.1
**Last Updated:** April 2026
**Total Tests:** 1,061 tests, 3,153 assertions

---

## Table of Contents

1. [Overview](#1-overview)
2. [Test Organization](#2-test-organization)
3. [Feature Tests](#3-feature-tests)
4. [Unit Tests](#4-unit-tests)
5. [Test Outcomes Reference](#5-test-outcomes-reference)
6. [Running Tests](#6-running-tests)

---

## 1. Overview

### 1.1 Purpose

This document specifies the test suites for CEMS-MY (Currency Exchange Management System - Malaysia), a Laravel 10.x application for Malaysian Money Services Businesses (MSB) compliant with Bank Negara Malaysia (BNM) AML/CFT requirements.

### 1.2 Test Statistics

| Category | Count |
|----------|-------|
| Feature Tests | 24 test files |
| Unit Tests | 30 test files |
| Total Tests | 1,061 |
| Total Assertions | 3,153 |
| Duration (full suite) | ~45 seconds |

### 1.3 Test Philosophy

- **BCMath Precision**: All monetary calculations verified with exact decimal arithmetic
- **RBAC Verification**: Role-based access control tested for every endpoint
- **Audit Trail**: All critical operations verified with audit log entries
- **Workflow Integrity**: Multi-step workflows tested end-to-end
- **Database Integrity**: Transactions, positions, and balances verified after operations

---

## 2. Test Organization

```
tests/
├── Feature/                    # Integration/acceptance tests
│   ├── AuthenticationTest.php
│   ├── AuditLogTest.php
│   ├── ComplianceViewTest.php
│   ├── ComprehensiveViewsTest.php
│   ├── CounterControllerTest.php
│   ├── CustomerHistoryTest.php
│   ├── EddWorkflowTest.php
│   ├── NavigationTest.php
│   ├── RateHistoryLoggingTest.php
│   ├── RealWorldTransactionWorkflowTest.php
│   ├── ReportsViewTest.php
│   ├── TillReconciliationTest.php
│   ├── TransactionBatchUploadTest.php
│   ├── TransactionCancellationFlowTest.php
│   ├── TransactionReceiptTest.php
│   ├── TransactionTest.php
│   └── UserManagementTest.php
└── Unit/                       # Isolated component tests
    ├── AccountingServiceFixTest.php
    ├── AccountingServiceTest.php
    ├── BudgetAndReversalFixTest.php
    ├── ComplianceServiceTest.php
    ├── CounterServiceTest.php
    ├── CurrencyPositionServiceTest.php
    ├── EncryptionServiceTest.php
    ├── ExchangeRateHistoryTest.php
    ├── ExportServiceTest.php
    ├── LedgerServiceFixTest.php
    ├── LedgerServiceTest.php
    ├── MathServiceTest.php
    ├── PeriodCloseServiceTest.php
    ├── RateApiServiceTest.php
    ├── RevaluationServiceFixTest.php
    ├── RevaluationServiceTest.php
    ├── RiskRatingServiceTest.php
    └── UserModelTest.php
```

---

## 3. Feature Tests

### 3.1 AuthenticationTest

**File:** `tests/Feature/AuthenticationTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_login_page_is_accessible` | GET /login returns 200 | PASS: 200 OK |
| `test_unauthenticated_user_is_redirected_to_login` | Accessing /dashboard without auth redirects | PASS: Redirect to /login |
| `test_teller_can_login_with_valid_credentials` | Login with valid teller credentials | PASS: Authenticated, redirected to /dashboard |
| `test_login_fails_with_invalid_password` | Login with wrong password | PASS: Error message, not authenticated |
| `test_login_fails_with_nonexistent_email` | Login with unknown email | PASS: Error message, not authenticated |
| `test_inactive_user_cannot_login` | Login with inactive (status=false) user | PASS: Error message, not authenticated |
| `test_password_is_hashed_in_database` | Verify stored password is bcrypt hash | PASS: Hash differs from plain text |
| `test_authenticated_user_can_logout` | POST /logout terminates session | PASS: Session cleared, redirected to /login |
| `test_login_creates_audit_log` | Successful login creates SystemLog entry | PASS: AuditLog with action='login' created |
| `test_failed_login_creates_audit_log` | Failed login attempt creates SystemLog | PASS: AuditLog with action='login_failed' created |
| `test_failed_login_log_does_not_reveal_user_status` | Failed login message is generic | PASS: Message doesn't indicate if email exists |
| `test_dashboard_is_accessible_to_authenticated_users` | GET /dashboard with valid session | PASS: 200 OK with dashboard content |
| `test_teller_has_correct_role_permissions` | Verify Teller role methods | PASS: canCreateTransaction=true, canAccessAccounting=false |
| `test_manager_has_correct_role_permissions` | Verify Manager role methods | PASS: canCreateTransaction=true, canApproveLargeTransactions=true |
| `test_compliance_officer_has_correct_role_permissions` | Verify ComplianceOfficer role methods | PASS: canAccessCompliance=true, canAccessAccounting=false |
| `test_admin_has_correct_role_permissions` | Verify Admin role methods | PASS: canManageUsers=true, canAccessAll=true |
| `test_teller_cannot_access_accounting` | GET /accounting as teller | PASS: 403 Forbidden |
| `test_manager_cannot_access_user_management` | GET /users as manager | PASS: 403 Forbidden |
| `test_compliance_officer_cannot_access_accounting` | GET /accounting as compliance | PASS: 403 Forbidden |
| `test_admin_can_access_user_management` | GET /users as admin | PASS: 200 OK |
| `test_manager_can_access_accounting` | GET /accounting as manager | PASS: 200 OK |
| `test_compliance_officer_can_access_compliance_portal` | GET /compliance as compliance | PASS: 200 OK |
| `test_compliance_page_is_accessible_to_compliance` | Compliance portal loads for compliance role | PASS: View renders with data |
| `test_admin_can_access_compliance_portal` | GET /compliance as admin | PASS: 200 OK |
| `test_admin_can_access_stock_cash` | GET /stock-cash as admin | PASS: 200 OK |
| `test_manager_can_access_stock_cash` | GET /stock-cash as manager | PASS: 200 OK |
| `test_teller_cannot_access_stock_cash` | GET /stock-cash as teller | PASS: 403 Forbidden |
| `test_teller_cannot_access_compliance_portal` | GET /compliance as teller | PASS: 403 Forbidden |
| `test_manager_cannot_access_compliance_portal` | GET /compliance as manager | PASS: 403 Forbidden |
| `test_admin_can_login_with_valid_credentials` | Login with admin credentials | PASS: Authenticated as admin |

---

### 3.2 TransactionTest

**File:** `tests/Feature/TransactionTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_teller_can_access_transaction_create` | GET /transactions/create as teller | PASS: 200 OK |
| `test_can_view_transaction_list` | GET /transactions as teller | PASS: 200 OK with transaction table |
| `test_can_view_transaction_details` | GET /transactions/{id} | PASS: 200 OK with transaction details |
| `test_teller_can_create_buy_transaction` | POST buy transaction with valid data | PASS: Transaction created, status=Completed |
| `test_teller_can_create_sell_transaction` | POST sell transaction with valid data | PASS: Transaction created, status=Completed |
| `test_sell_updates_currency_position` | Sell USD decreases USD position | PASS: Position balance decreased |
| `test_buy_updates_currency_position` | Buy USD increases USD position | PASS: Position balance increased |
| `test_sell_fails_with_insufficient_stock` | Sell more than available | PASS: Validation error, 422 |
| `test_transaction_requires_positive_amount` | POST with amount <= 0 | PASS: Validation error |
| `test_transaction_requires_valid_currency` | POST with invalid currency code | PASS: Validation error |
| `test_transaction_requires_valid_customer` | POST with non-existent customer | PASS: Validation error |
| `test_transaction_fails_if_till_not_open` | Transaction without open till | PASS: Error, till must be open |
| `test_large_transaction_requires_approval` | Transaction >= RM 50,000 | PASS: Status = Pending |
| `test_teller_cannot_approve_transaction` | POST /transactions/{id}/approve as teller | PASS: 403 Forbidden |
| `test_manager_can_approve_transaction` | POST /transactions/{id}/approve as manager | PASS: Status changes to Completed |
| `test_approval_creates_journal_entries` | After approval, journal entries exist | PASS: 2+ JournalEntry records |
| `test_transaction_creates_audit_log` | Transaction creation logged | PASS: SystemLog with action='transaction_created' |

---

### 3.3 TransactionCancellationFlowTest

**File:** `tests/Feature/TransactionCancellationFlowTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_cancel_button_appears_for_refundable_transactions` | Completed tx within 24h shows cancel button | PASS: View contains cancel link |
| `test_cancel_button_does_not_appear_for_old_transactions` | Completed tx after 24h hides cancel | PASS: View does not contain cancel link |
| `test_only_completed_transactions_can_be_cancelled` | Attempt to cancel non-completed tx | PASS: Error message, 422 |
| `test_original_teller_can_cancel_own_transaction` | Teller cancels own recent transaction | PASS: Status = Cancelled, refund created |
| `test_transaction_can_be_cancelled_by_admin` | Admin cancels any transaction | PASS: Status = Cancelled |
| `test_transaction_can_be_cancelled_within_24_hours_by_manager` | Manager cancels within 24h window | PASS: Status = Cancelled |
| `test_transaction_cannot_be_cancelled_after_24_hours` | Manager attempts after 24h | PASS: Error, 422 |
| `test_other_teller_cannot_cancel_transaction` | Teller attempts to cancel other's tx | PASS: 403 Forbidden |
| `test_refund_transactions_cannot_be_cancelled` | Attempt to cancel a refund transaction | PASS: Error message |
| `test_already_cancelled_transactions_cannot_be_cancelled_again` | Double cancellation attempt | PASS: Error message |
| `test_cancellation_reason_is_required_and_min_length` | Cancel without reason | PASS: Validation error (min:10 chars) |
| `test_confirmation_checkbox_is_required` | Cancel without confirmation | PASS: Validation error |
| `test_guest_users_cannot_access_cancellation` | GET cancel page without auth | PASS: Redirect to login |
| `test_refund_transaction_has_reversed_type` | Cancellation creates reversed type | PASS: refund_type = 'Reversed' |
| `test_stock_position_is_reversed_after_cancellation` | Position restored after cancel | PASS: Balance restored to pre-transaction |

---

### 3.4 TransactionReceiptTest

**File:** `tests/Feature/TransactionReceiptTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_completed_transaction_can_generate_receipt` | GET receipt for completed transaction | PASS: 200 OK, PDF returned |
| `test_completed_sell_transaction_can_generate_receipt` | Receipt for sell transaction | PASS: PDF contains correct data |
| `test_pending_transaction_cannot_generate_receipt` | Receipt for pending (unapproved) tx | PASS: Error message, 403 |
| `test_onhold_transaction_cannot_generate_receipt` | Receipt for on-hold tx | PASS: Error message, 403 |
| `test_approved_transaction_can_generate_receipt` | Receipt for approved (large) tx | PASS: 200 OK, PDF |
| `test_receipt_requires_authentication` | Receipt without login | PASS: Redirect to login |
| `test_pdf_receipt_contains_transaction_details` | Receipt PDF has transaction data | PASS: PDF contains amount, currency, rate |
| `test_pdf_receipt_contains_bnm_compliance_fields` | Receipt has BNM-required fields | PASS: BNM compliance info present |

---

### 3.5 TransactionBatchUploadTest

**File:** `tests/Feature/TransactionBatchUploadTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_manager_can_access_batch_upload_form` | GET /transactions/batch-upload as manager | PASS: 200 OK |
| `test_teller_cannot_access_batch_upload` | GET batch-upload as teller | PASS: 403 Forbidden |
| `test_admin_can_access_batch_upload` | GET batch-upload as admin | PASS: 200 OK |
| `test_template_download_works` | GET /transactions/template | PASS: CSV file download |
| `test_manager_can_upload_csv` | POST valid CSV batch | PASS: Import created, transactions processed |
| `test_fails_with_closed_till` | Batch upload with closed till | PASS: Error, till not open |
| `test_fails_with_invalid_transaction_type` | CSV with invalid type | PASS: Error validation |
| `test_sell_fails_with_insufficient_stock` | Sell more than available in CSV | PASS: Row marked as error |
| `test_empty_csv_shows_error` | Upload empty file | PASS: Error message |
| `test_file_size_validation` | Upload file exceeding limit | PASS: Validation error |
| `test_manager_can_view_import_results` | GET import results page | PASS: Results displayed |
| `test_user_can_only_view_own_import_results` | View another user's import | PASS: 403 Forbidden |
| `test_csv_with_errors_shows_error_report` | CSV with partial errors | PASS: Success and error rows shown |
| `test_mixed_success_and_failure` | CSV with 3 success, 2 failed | PASS: 3 created, 2 errors |
| `test_only_managers_can_upload` | Role check on upload endpoint | PASS: Role middleware enforced |

---

### 3.6 RealWorldTransactionWorkflowTest

**File:** `tests/Feature/RealWorldTransactionWorkflowTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_complete_daily_workflow` | Full day: open till, multiple transactions, close | PASS: All steps succeed, balances correct |
| `test_daily_transaction_summary` | End-of-day summary calculation | PASS: Summary matches individual transactions |
| `test_receipt_generation_workflow` | Receipt after each transaction type | PASS: All receipts contain correct data |
| `test_transaction_search_and_filtering` | Filter by date, currency, status | PASS: Correct subset returned |
| `test_edge_cases` | Zero amount, exact limit, boundary values | PASS: Handled correctly |

---

### 3.7 UserManagementTest

**File:** `tests/Feature/UserManagementTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_non_admin_cannot_access_user_management` | GET /users as non-admin | PASS: 403 Forbidden |
| `test_admin_can_view_user_list` | GET /users as admin | PASS: 200 OK, paginated list |
| `test_user_list_paginates` | User list pagination works | PASS: 10 per page, correct links |
| `test_create_user_page_is_accessible` | GET /users/create as admin | PASS: 200 OK |
| `test_edit_user_page_is_accessible` | GET /users/{id}/edit as admin | PASS: 200 OK |
| `test_admin_can_create_new_user` | POST new user with valid data | PASS: User created, redirected |
| `test_user_creation_validates_required_fields` | POST without required fields | PASS: Validation errors |
| `test_user_creation_validates_email_format` | POST with invalid email | PASS: Validation error |
| `test_user_creation_validates_password_confirmation` | Password != confirmation | PASS: Validation error |
| `test_user_creation_validates_role_options` | POST with invalid role | PASS: Validation error |
| `test_cannot_create_user_with_duplicate_email` | POST with existing email | PASS: Validation error |
| `test_cannot_create_user_with_weak_password` | POST with weak password | PASS: Validation error |
| `test_admin_can_update_user_role` | PUT to change user role | PASS: Role updated |
| `test_admin_can_activate_user` | Toggle user to active | PASS: User status = active |
| `test_admin_can_deactivate_user` | Toggle user to inactive | PASS: User cannot login |
| `test_user_status_toggle_creates_audit_log` | Toggle user status | PASS: SystemLog entry |
| `test_admin_cannot_delete_themselves` | DELETE current user | PASS: Error message |
| `test_admin_can_delete_other_users` | DELETE another user | PASS: User deleted |
| `test_cannot_delete_last_admin` | Delete last admin user | PASS: Error, at least one admin required |
| `test_user_creation_creates_audit_log` | New user creation logged | PASS: SystemLog entry |
| `test_user_update_creates_audit_log` | User update logged | PASS: SystemLog entry |
| `test_user_deletion_creates_audit_log` | User deletion logged | PASS: SystemLog entry |
| `test_user_update_logs_role_changes` | Role change shows old/new in log | PASS: AuditLog contains role details |

---

### 3.8 ComplianceViewTest

**File:** `tests/Feature/ComplianceViewTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_compliance_officer_can_access_compliance_portal` | GET /compliance as compliance | PASS: 200 OK |
| `test_admin_can_access_compliance_portal` | GET /compliance as admin | PASS: 200 OK |
| `test_teller_cannot_access_compliance_portal` | GET /compliance as teller | PASS: 403 Forbidden |
| `test_compliance_page_displays_flagged_transactions` | Flagged transactions shown | PASS: View renders with data |
| `test_compliance_filters_work` | Filter by status, date | PASS: Correct filtered results |
| `test_assign_flag_action` | Assign flag to officer | PASS: Flag status = UnderReview |
| `test_resolve_flag_action` | Resolve a flag | PASS: Flag status = Resolved |

---

### 3.9 EddWorkflowTest

**File:** `tests/Feature/EddWorkflowTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_edd_index_accessible_by_compliance` | GET /compliance/edd as compliance | PASS: 200 OK |
| `test_edd_create_page_loads` | GET /compliance/edd/create | PASS: 200 OK with form |
| `test_can_create_edd_record` | POST EDD with all required fields | PASS: EDD created, status=Incomplete |
| `test_edd_record_requires_source_of_funds` | POST without source_of_funds | PASS: Validation error |
| `test_edd_reference_is_auto_generated` | EDD reference auto-generated | PASS: EDD-YYYYMMDD-XXXX format |
| `test_can_view_edd_record` | GET /compliance/edd/{id} | PASS: 200 OK with details |
| `test_can_update_and_complete_edd_record` | PUT to update EDD | PASS: Fields updated |
| `test_can_approve_edd_record` | POST approve action | PASS: Status = Approved |
| `test_can_reject_edd_record` | POST reject action | PASS: Status = Rejected |

---

### 3.10 ReportsViewTest

**File:** `tests/Feature/ReportsViewTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_manager_can_access_reports_dashboard` | GET /reports as manager | PASS: 200 OK |
| `test_admin_can_access_reports_dashboard` | GET /reports as admin | PASS: 200 OK |
| `test_compliance_officer_cannot_access_reports_dashboard` | GET /reports as compliance | PASS: 403 Forbidden |
| `test_teller_cannot_access_reports_dashboard` | GET /reports as teller | PASS: 403 Forbidden |
| `test_reports_page_displays_recent_reports` | Recent reports section visible | PASS: Table with recent reports |
| `test_reports_page_displays_all_report_cards` | All 8 report type cards visible | PASS: Cards for LCTR, MSB2, etc. |
| `test_lctr_report_page_loads` | GET /reports/lctr | PASS: 200 OK |
| `test_msb2_report_page_loads` | GET /reports/msb2 | PASS: 200 OK |
| `test_lctr_requires_manager_or_admin` | LCTR as teller | PASS: 403 Forbidden |
| `test_msb2_requires_manager_or_admin` | MSB2 as teller | PASS: 403 Forbidden |

---

### 3.11 ComprehensiveViewsTest

**File:** `tests/Feature/ComprehensiveViewsTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_reports_dashboard_requires_manager_or_admin` | Role check on dashboard | PASS: Middleware enforced |
| `test_reports_dashboard_shows_all_eight_report_cards` | All report cards render | PASS: 8 cards visible |
| `test_recent_reports_shows_correct_data` | Recent reports table | PASS: Data matches database |
| `test_lctr_only_includes_qualifying_completed_transactions` | LCTR >= RM 50k, completed | PASS: Correct transaction count |
| `test_lctr_masks_customer_names` | Customer name masking | PASS: Partial masking applied |
| `test_lctr_shows_warning_for_pending_qualifying_transactions` | Warning for pending | PASS: Warning displayed |
| `test_lctr_stats_calculate_correctly` | LCTR totals | PASS: Correct sum |
| `test_msb2_aggregates_currency_data_correctly` | MSB2 currency totals | PASS: Correct aggregation |
| `test_msb2_shows_warning_for_negative_net_position` | Negative position warning | PASS: Warning displayed |
| `test_msb2_stats_calculate_correctly` | MSB2 totals | PASS: Correct calculations |
| `test_compliance_portal_requires_proper_authorization` | Role check | PASS: 403 for wrong role |
| `test_compliance_filters_work_correctly` | Filter functionality | PASS: Correct filtering |
| `test_compliance_pagination_preserves_filters` | Pagination with filters | PASS: Filters maintained |
| `test_compliance_stats_calculate_correctly` | Stats display | PASS: Correct counts |
| `test_flag_assign_requires_compliance_officer` | Assign as non-compliance | PASS: 403 Forbidden |
| `test_flag_resolve_updates_correctly` | Resolve flag | PASS: Status = Resolved |
| `test_views_handle_empty_data_gracefully` | Empty database | PASS: Empty state displayed |
| `test_views_handle_invalid_parameters` | Invalid route params | PASS: 404 or validation error |

---

### 3.12 NavigationTest

**File:** `tests/Feature/NavigationTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_unauthenticated_user_redirected_to_login` | Root path redirects | PASS: Redirect to /login |
| `test_teller_sees_navigation` | Teller nav menu items | PASS: Correct subset visible |
| `test_manager_sees_navigation` | Manager nav menu items | PASS: Accounting visible |
| `test_compliance_sees_navigation` | Compliance nav menu items | PASS: Compliance visible |
| `test_admin_sees_navigation` | Admin sees all | PASS: Full menu |
| `test_navigation_items_in_correct_order` | Menu item sequence | PASS: Correct order |
| `test_navigation_has_styling` | CSS classes present | PASS: Sidebar styling applied |
| `test_navigation_consistent_across_pages` | Same nav on different pages | PASS: Consistent |
| `test_navigation_links_are_clickable` | Nav links have href | PASS: Valid URLs |
| `test_logout_link_has_csrf_form` | Logout POST form | PASS: CSRF token present |
| `test_stock_cash_menu_item_exists` | Stock/Cash in nav | PASS: Menu item present |

---

### 3.13 CounterControllerTest

**File:** `tests/Feature/CounterControllerTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_user_can_view_counters_list` | GET /counters | PASS: 200 OK |
| `test_user_can_open_counter_form` | GET /counters/{id}/open | PASS: 200 OK |
| `test_user_can_open_counter` | POST open till | PASS: TillBalance created |
| `test_user_can_view_counter_history` | GET counter history | PASS: History displayed |
| `test_counter_api_returns_status` | GET /counters/{id}/status | PASS: JSON with status |
| `test_teller_cannot_initiate_handover` | GET handover as teller | PASS: 403 Forbidden |
| `test_manager_can_close_counter` | POST close as manager | PASS: Till closed |
| `test_teller_cannot_close_counter` | POST close as teller | PASS: 403 Forbidden |

---

### 3.14 TillReconciliationTest

**File:** `tests/Feature/TillReconciliationTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_reconciliation_shows_transaction_details` | Transaction list in report | PASS: Correct transactions |
| `test_reconciliation_calculates_expected_closing_correctly` | Expected = Opening + Buys - Sells | PASS: Correct calculation |
| `test_reconciliation_calculates_variance_correctly` | Variance = Actual - Expected | PASS: Correct variance |
| `test_reconciliation_calculates_buy_and_sell_counts` | Count of buy/sell transactions | PASS: Correct counts |
| `test_reconciliation_shows_null_variance_for_open_till` | Open till has no variance | PASS: null variance |
| `test_reconciliation_returns_error_for_nonexistent_till` | Invalid till ID | PASS: 404 error |
| `test_non_manager_cannot_access_reconciliation` | Reconciliation as teller | PASS: 403 Forbidden |

---

### 3.15 AuditLogTest

**File:** `tests/Feature/AuditLogTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_audit_log_index_is_accessible_by_manager` | GET /audit as manager | PASS: 200 OK |
| `test_audit_log_index_is_not_accessible_by_regular_user` | GET /audit as teller | PASS: 403 Forbidden |
| `test_export_audit_log_returns_csv_data` | POST /audit/export | PASS: CSV file download |
| `test_get_audit_trail_returns_filtered_results_by_user` | Filter by user_id | PASS: Correct subset |
| `test_get_audit_trail_returns_filtered_results_by_date` | Filter by date range | PASS: Correct subset |
| `test_get_audit_trail_returns_filtered_results_by_action` | Filter by action | PASS: Correct subset |
| `test_get_audit_trail_returns_filtered_results_by_severity` | Filter by severity | PASS: Correct subset |
| `test_audit_log_severity_scope_works_correctly` | Severity filter | PASS: Correct filtering |
| `test_audit_log_filters_can_be_combined` | Multiple filters | PASS: Combined filtering works |
| `test_system_log_scope_action_works` | Action scope | PASS: Correct scope |
| `test_system_log_scope_between_dates_works` | Date range scope | PASS: Correct scope |
| `test_log_with_severity_creates_entry_with_correct_severity` | Severity field populated | PASS: Correct severity |
| `test_log_with_severity_includes_session_id` | Session logged | PASS: session_id present |
| `test_system_log_get_severity_color_returns_correct_color` | Color helper | PASS: Correct color mapping |

---

### 3.16 CustomerHistoryTest

**File:** `tests/Feature/CustomerHistoryTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_customer_history_shows_paginated_transactions` | Transaction pagination | PASS: Correct pagination |
| `test_customer_history_displays_correct_statistics` | Stats (total, avg, count) | PASS: Correct calculations |
| `test_customer_history_calculates_first_and_last_transaction_dates` | Date range | PASS: Correct dates |
| `test_customer_history_returns_monthly_chart_data` | Chart.js data format | PASS: Labels and data arrays |
| `test_customer_history_export_returns_csv` | Export to CSV | PASS: CSV download |
| `test_customer_history_with_no_transactions_shows_zero_stats` | New customer | PASS: Zeros displayed |

---

### 3.17 RateHistoryLoggingTest

**File:** `tests/Feature/RateHistoryLoggingTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_rate_fetch_logs_to_history_table` | Rate fetch creates log | PASS: ExchangeRateHistory created |
| `test_rate_fetch_only_logs_once_per_day` | Duplicate prevention | PASS: Same day = same record |
| `test_get_rate_trend_returns_correct_data` | Rate trend data | PASS: Correct historical data |
| `test_get_rate_trend_returns_empty_data_for_no_history` | No history case | PASS: Empty arrays |

---

## 4. Unit Tests

### 4.1 MathServiceTest

**File:** `tests/Unit/MathServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_basic_arithmetic_operations` | add, subtract, multiply, divide | PASS: Exact decimal results |
| `test_calculate_transaction_amount` | MYR = foreign × rate | PASS: Correct with BCMath |
| `test_calculate_average_cost` | Average cost calculation | PASS: Correct weighted average |
| `test_calculate_revaluation_pnl` | Unrealized P&L | PASS: (current_rate - avg_cost) × qty |
| `test_compare_values` | compare() returns -1, 0, 1 | PASS: Correct comparison |
| `test_division_by_zero_throws_exception` | divide(1, 0) | PASS: Exception thrown |

---

### 4.2 AccountingServiceTest

**File:** `tests/Unit/AccountingServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_can_create_journal_entry` | Create balanced entry | PASS: JournalEntry created |
| `test_journal_entry_must_be_balanced` | Unbalanced entry rejected | PASS: ValidationException |
| `test_validate_balanced_returns_true_for_balanced_entry` | Balanced check | PASS: true |
| `test_validate_balanced_returns_false_for_unbalanced_entry` | Unbalanced check | PASS: false |
| `test_can_reverse_journal_entry` | Reverse creates linked entry | PASS: linked_to_id set |
| `test_get_account_balance_returns_correct_balance` | Balance calculation | PASS: Debits - Credits |
| `test_get_account_balance_returns_zero_for_no_entries` | Empty account | PASS: 0.00 |

---

### 4.3 AccountingServiceFixTest

**File:** `tests/Unit/AccountingServiceFixTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_debit_account_balance_increases_with_debit_and_decreases_with_credit` | Asset account rules | PASS: Correct balance changes |
| `test_credit_account_balance_decreases_with_debit_and_increases_with_credit` | Liability account rules | PASS: Correct balance changes |
| `test_revenue_account_balance_increases_with_credit` | Revenue normal balance | PASS: Credits increase |
| `test_expense_account_balance_increases_with_debit` | Expense normal balance | PASS: Debits increase |
| `test_ledger_entries_ordered_by_date_then_created_at` | Ordering guarantee | PASS: Correct sort order |
| `test_get_account_balance_uses_created_at_for_same_date_entries` | Same-date tiebreaker | PASS: created_at used |
| `test_comprehensive_balance_calculation` | Mixed entries | PASS: Correct final balance |
| `test_balance_calculation_with_zero_amounts` | Zero entries | PASS: No effect on balance |
| `test_credit_normal_accounts_with_positive_balance_go_to_credit_column` | Balance sheet classification | PASS: Credit column |
| `test_debit_normal_accounts_with_positive_balance_go_to_debit_column` | Balance sheet classification | PASS: Debit column |
| `test_credit_normal_accounts_with_negative_balance_go_to_debit_column` | Negative credit normal | PASS: Debit column |
| `test_trial_balance_equity_accounts_handled_as_credit_normal` | Equity classification | PASS: Credit normal |

---

### 4.4 LedgerServiceTest

**File:** `tests/Unit/LedgerServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_get_trial_balance_returns_all_accounts` | Account list | PASS: All accounts returned |
| `test_get_trial_balance_is_balanced_after_entries` | Debits = Credits | PASS: Sum equality |
| `test_get_account_ledger_returns_entries` | Account history | PASS: Journal lines for account |
| `test_get_profit_and_loss_returns_revenue_and_expenses` | P&L accounts | PASS: Revenue and expense data |
| `test_get_balance_sheet_returns_assets_liabilities_equity` | Balance sheet sections | PASS: Three sections |

---

### 4.5 LedgerServiceFixTest

**File:** `tests/Unit/LedgerServiceFixTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_profit_and_loss_revenue_activity_calculated_as_credits_minus_debits` | Revenue formula | PASS: Credits - Debits |
| `test_profit_and_loss_expense_activity_calculated_as_debits_minus_credits` | Expense formula | PASS: Debits - Credits |
| `test_profit_and_loss_calculates_net_profit_correctly` | Net = Revenue - Expenses | PASS: Correct net |

---

### 4.6 RevaluationServiceTest

**File:** `tests/Unit/RevaluationServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_run_revaluation_processes_all_positions` | All currencies | PASS: Each currency revalued |
| `test_run_revaluation_returns_correct_structure` | Return format | PASS: Array with gains/losses |
| `test_run_revaluation_with_specific_till` | Till-specific | PASS: Only that till's positions |

---

### 4.7 RevaluationServiceFixTest

**File:** `tests/Unit/RevaluationServiceFixTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_revaluation_with_journal_validates_period_is_open` | Closed period blocked | PASS: Exception for closed |
| `test_revaluation_with_journal_validates_date_falls_in_period` | Date in period | PASS: Validation |
| `test_revaluation_with_journal_throws_exception_for_closed_period` | Closed period | PASS: Exception |
| `test_revaluation_processes_each_currency_independently` | Isolated processing | PASS: Each currency separate |
| `test_currency_failure_does_not_affect_other_currencies` | Error isolation | PASS: One failure doesn't cascade |
| `test_journal_entries_have_period_id_assigned` | Period association | PASS: period_id set |

---

### 4.8 ComplianceServiceTest

**File:** `tests/Unit/ComplianceServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_check_sanction_match_finds_match` | Sanctions list match | PASS: Match found |
| `test_check_sanction_match_no_match` | No sanctions match | PASS: No match |
| `test_simplified_cdd_for_small_amounts` | < RM 1,000 | PASS: CDD level = Simplified |
| `test_standard_cdd_for_medium_amounts` | RM 1,000 - 49,999 | PASS: CDD level = Standard |
| `test_enhanced_cdd_for_large_amounts` | >= RM 50,000 | PASS: CDD level = Enhanced |
| `test_enhanced_cdd_for_pep` | PEP customer | PASS: CDD level = Enhanced |
| `test_enhanced_cdd_for_high_risk_customer` | High risk rating | PASS: CDD level = Enhanced |
| `test_requires_hold_for_large_amounts` | >= RM 50,000 | PASS: Status = OnHold |
| `test_requires_hold_for_pep_status` | PEP customer | PASS: Status = OnHold |
| `test_requires_hold_for_high_risk_customer` | High risk | PASS: Status = OnHold |
| `test_requires_hold_for_sanction_match` | Sanctions match | PASS: Status = OnHold |

---

### 4.9 CurrencyPositionServiceTest

**File:** `tests/Unit/CurrencyPositionServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_creates_position_on_first_buy` | New currency buy | PASS: Position created |
| `test_updates_position_on_additional_buy` | Existing currency buy | PASS: Quantity added |
| `test_decreases_position_on_sell` | Sell decreases | PASS: Quantity reduced |
| `test_gets_position_by_currency` | Get specific currency | PASS: Correct position |
| `test_multiple_sells_cannot_exceed_total_balance` | Over-sell prevention | PASS: Exception |
| `test_position_balance_never_negative` | Balance floor at 0 | PASS: Never negative |
| `test_throws_exception_when_selling_more_than_balance` | Exact limit check | PASS: Exception |
| `test_throws_exception_when_selling_exact_balance` | Edge case | PASS: Allowed (exact balance) |
| `test_throws_exception_when_selling_with_zero_balance` | Zero balance | PASS: Exception |
| `test_allows_partial_sell_within_balance` | Partial sell | PASS: Success |
| `test_balance_prevention_error_message_includes_available_amount` | Error message detail | PASS: Available amount shown |

---

### 4.10 CounterServiceTest

**File:** `tests/Unit/CounterServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_can_open_counter_session` | Open till | PASS: TillBalance created |
| `test_cannot_open_if_already_open` | Double open | PASS: Exception |
| `test_cannot_open_if_user_at_another_counter` | User conflict | PASS: Exception |
| `test_can_close_counter_session` | Close till | PASS: Balance updated |
| `test_get_available_counters` | Available counters | PASS: List of available |
| `test_close_session_query_only_returns_expected_currencies` | Currency filter | PASS: Correct currencies |
| `test_calculates_variance_correctly` | Variance = actual - expected | PASS: Correct |
| `test_requires_supervisor_for_large_variance` | > RM 10 variance | PASS: Supervisor required |

---

### 4.11 BudgetAndReversalFixTest

**File:** `tests/Unit/BudgetAndReversalFixTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_cannot_reverse_non_posted_entry` | Unposted reversal | PASS: Exception |
| `test_cannot_reverse_already_reversed_entry` | Double reversal | PASS: Exception |
| `test_reversal_creates_explicit_link_to_original` | linked_to_id | PASS: Reference set |
| `test_reversed_entry_status_is_updated` | Status change | PASS: Status = Reversed |
| `test_budget_service_uses_period_date_range` | Budget period filter | PASS: Correct date range |
| `test_budget_actuals_respect_period_date_range` | Actual within period | PASS: Correct filtering |
| `test_period_close_service_validates_account_codes_exist` | Account validation | PASS: Validates existence |
| `test_revaluation_service_validates_account_codes_exist` | Account validation | PASS: Validates existence |

---

### 4.12 PeriodCloseServiceTest

**File:** `tests/Unit/PeriodCloseServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_can_close_open_period` | Close period | PASS: Status = Closed |
| `test_cannot_close_already_closed_period` | Double close | PASS: Exception |

---

### 4.13 RiskRatingServiceTest

**File:** `tests/Unit/RiskRatingServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_calculate_score_for_low_risk_customer` | All low-risk factors | PASS: Score < 30 |
| `test_calculate_score_for_pep_customer` | PEP risk factor | PASS: Score significantly higher |
| `test_calculate_score_with_high_risk_country` | Country risk | PASS: Score increase |
| `test_calculate_score_with_cash_intensive_pattern` | Cash pattern | PASS: Score increase |
| `test_calculate_score_combined_risk_factors` | Multiple factors | PASS: Combined score |
| `test_score_capped_at_100` | Maximum score | PASS: Never exceeds 100 |
| `test_get_low_rating_for_low_score` | Score < 30 | PASS: Low rating |
| `test_get_low_rating_for_boundary_score` | Score = 29 | PASS: Low rating |
| `test_get_low_rating_for_minimum_score` | Score = 0 | PASS: Low rating |
| `test_get_medium_rating_for_medium_score` | Score 30-59 | PASS: Medium rating |
| `test_get_medium_rating_for_lower_boundary` | Score = 30 | PASS: Medium rating |
| `test_get_medium_rating_for_upper_boundary` | Score = 59 | PASS: Medium rating |
| `test_get_high_rating_for_high_score` | Score 60-89 | PASS: High rating |
| `test_get_high_rating_for_minimum_high_score` | Score = 60 | PASS: High rating |
| `test_get_high_rating_for_maximum_score` | Score = 89 | PASS: High rating |
| `test_get_refresh_frequency_by_rating` | Rescreening frequency | PASS: Correct days |

---

### 4.14 UserModelTest

**File:** `tests/Unit/UserModelTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_user_can_be_created` | User instantiation | PASS: User created |
| `test_user_has_correct_fillable_attributes` | Mass assignment | PASS: Correct fillable |
| `test_user_has_correct_hidden_attributes` | Hidden fields | PASS: password hidden |
| `test_user_has_correct_casts` | Type casting | PASS: date_casts correct |
| `test_email_must_be_unique` | Unique email | PASS: Validation rule |
| `test_username_must_be_unique` | Unique username | PASS: Validation rule |
| `test_valid_roles` | Valid role values | PASS: All four roles |
| `test_is_admin_method` | Admin check | PASS: Correct for admin |
| `test_is_manager_method` | Manager check | PASS: Correct for manager |
| `test_is_compliance_officer_method` | Compliance check | PASS: Correct for compliance |
| `test_inactive_user_status` | Active flag | PASS: Boolean cast |
| `test_mfa_enabled_status` | MFA flag | PASS: Boolean cast |
| `test_get_auth_password_method` | Auth password | PASS: Returns hashed password |
| `test_transactions_relationship_exists` | Has many transactions | PASS: Relationship defined |
| `test_user_can_update_last_login` | Last login tracking | PASS: Timestamp updated |

---

### 4.15 EncryptionServiceTest

**File:** `tests/Unit/EncryptionServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_can_encrypt_and_decrypt_data` | Round-trip | PASS: Decrypted equals original |
| `test_encrypts_to_different_values` | Encryption randomness | PASS: Different ciphertexts |
| `test_hashing_is_deterministic` | Same input = same hash | PASS: Consistent hash |

---

### 4.16 ExportServiceTest

**File:** `tests/Unit/ExportServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_can_export_to_csv` | CSV export | PASS: Valid CSV file |
| `test_can_export_to_excel` | Excel export | PASS: Valid Excel file |
| `test_csv_export_handles_empty_data` | Empty dataset | PASS: Empty CSV created |
| `test_cleanup_old_reports_deletes_files_older_than_days` | Cleanup | PASS: Old files deleted |
| `test_cleanup_old_reports_keeps_recent_files` | Recent kept | PASS: Recent files kept |

---

### 4.17 ExchangeRateHistoryTest

**File:** `tests/Unit/ExchangeRateHistoryTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_can_create_exchange_rate_history` | Creation | PASS: Record created |
| `test_belongs_to_currency_relationship` | Currency relation | PASS: Relationship defined |
| `test_belongs_to_creator_relationship` | User relation | PASS: Relationship defined |
| `test_scope_for_currency_filters_by_currency` | Currency filter | PASS: Correct filtering |
| `test_scope_for_date_range_filters_by_date` | Date filter | PASS: Correct filtering |
| `test_get_latest_rate_returns_most_recent_rate` | Latest rate | PASS: Most recent record |
| `test_get_latest_rate_returns_null_when_no_history` | No data | PASS: Null returned |

---

### 4.18 RateApiServiceTest

**File:** `tests/Unit/RateApiServiceTest.php`

| Test | Description | Expected Outcome |
|------|-------------|------------------|
| `test_fetches_and_caches_rates` | API fetch + cache | PASS: Rates fetched, cached |
| `test_gets_rate_for_specific_currency` | Single currency | PASS: Correct rate |
| `test_returns_null_for_unknown_currency` | Invalid currency | PASS: Null returned |
| `test_throws_exception_on_api_failure` | API error | PASS: Exception thrown |

---

## 5. Test Outcomes Reference

### 5.1 Standard Outcomes

| Outcome | Meaning |
|---------|---------|
| PASS | Test executed successfully, assertion(s) true |
| FAIL | Test executed, but assertion(s) failed |
| ERROR | Test threw an unhandled exception |
| SKIPPED | Test not run (conditional logic) |

### 5.2 HTTP Response Codes in Tests

| Code | Meaning | Common Use |
|------|---------|------------|
| 200 | OK | Successful GET/POST |
| 302 | Redirect | Auth redirects |
| 403 | Forbidden | RBAC violation |
| 404 | Not Found | Invalid resource ID |
| 422 | Unprocessable Entity | Validation failed |

### 5.3 Assertion Types

| Type | Purpose |
|------|---------|
| `assertTrue($value)` | Boolean true |
| `assertFalse($value)` | Boolean false |
| `assertEquals($expected, $actual)` | Equality |
| `assertNotEquals($expected, $actual)` | Inequality |
| `assertNull($value)` | Null value |
| `assertNotNull($value)` | Non-null value |
| `assertCount($expected, $collection)` | Collection size |
| `assertInstanceOf($class, $object)` | Object type |
| `assertArrayHasKey($key, $array)` | Array key exists |
| `assertRedirect($uri)` | Redirect destination |
| `assertStatus($code)` | HTTP status code |
| `assertJson($array)` | JSON response structure |

---

## 6. Running Tests

### 6.1 Run All Tests

```bash
php artisan test
```

### 6.2 Run Specific Test File

```bash
php artisan test --filter=TransactionTest
```

### 6.3 Run Specific Test Method

```bash
php artisan test --filter=test_teller_can_create_buy_transaction
```

### 6.4 Run with Coverage

```bash
php artisan test --coverage
```

### 6.5 Run via Test Runner Script

```bash
php test-runner.php
```

### 6.6 Expected Output

```
PASS  Tests\Feature\AuthenticationTest
   ✓ login page is accessible
   ✓ unauthenticated user is redirected to login
   ...

PASS  Tests\Feature\TransactionTest
   ✓ teller can access transaction create
   ...

...

Tests:    364 passed (1063 assertions)
Duration: 30.40s
```

---

## Document Version History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2026-04-05 | Initial specification | CEMS-MY Team |
| 1.1 | 2026-04-09 | Updated test counts after MySQL migration fixes (1,061 tests) | CEMS-MY Team |

---

**END OF TEST SPECIFICATION**
