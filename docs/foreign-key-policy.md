# Foreign-Key Cascade Policy

This document records the deliberate choice made for foreign-key
delete/update behavior across the CEMS-MY database schema.

## The decision: RESTRICT on financial-audit tables

The application stores audited financial and compliance data
(journal entries, account ledger, compliance cases, screening
results, sanctions, customer KYC records). In a regulated MSB
(Money Services Business) context under Bank Negara Malaysia
AML/CFT requirements, preserving the audit trail is more important
than orphan cleanup.

**Every foreign key that points to a financial or compliance entity
defaults to `RESTRICT ON DELETE` (MySQL default). This means:**

- Deleting a `Currency` while `exchange_rates`, `transactions`,
  `till_balances`, `currency_positions`, or `revaluation_entries`
  reference it will **fail** — the parent must be removed via a
  business-approved deprecation workflow, not a raw delete.
- Deleting a `ChartOfAccount` while `journal_lines`, `account_ledger`,
  or `budgets` reference it will **fail** — the account must be
  archived first.
- Deleting a `JournalEntry` while `journal_lines` or `account_ledger`
  reference it will **fail** — journal entries are immutable once
  posted. Reversal is done via a reversing journal entry, never
  physical deletion.
- Deleting a `Customer` while compliance cases, documents, or risk
  profiles reference them will **fail** — customer records must be
  archived per 14C.29 record-keeping requirements (minimum 5 years).
- Deleting a `User` while audit logs reference them will **fail** —
  the audit trail must remain intact with the original actor ID.

## Exceptions (`NULL ON DELETE`)

A small number of FKs intentionally use `nullOnDelete()`, reserved
for reference/ownership columns where the parent's removal should
orphan the reference without failing the delete:

- `customer_documents.verified_by` → `users` (`nullOnDelete`)
  — deleting a user should not fail if they verified a document;
  the verification history remains but loses its verifier.
- `counter_sessions.assigned_to`, `branch_closing_workflow.*`
  — workflow-ownership references, not audit-critical.
- `exchange_rates.branch_id`, `rate_history.branch_id` — branch
  references; branch closure is a business event that should not
  block rate history archival.

## Exceptions (`CASCADE ON DELETE`)

Currently **no** FK in the schema uses `cascadeOnDelete()`. In a
regulated financial context, cascade is almost always the wrong
choice because it silently destroys audit trail. If a future
requirement demands cascade (e.g., `stock_transfer_items` on
`stock_transfer` — a purely operational record with no audit
implications), the addition must be:

1. Reviewed by a compliance officer
2. Recorded in this document
3. Accompanied by an E2E test that verifies the cascade does not
   destroy any audit-required data

## Historical migrations

All historical migrations that created FKs without an explicit
`onDelete`/`cascadeOnDelete`/`nullOnDelete` clause **defaulted to
MySQL's `RESTRICT` behavior**. As of the `foreign-key-policy`
documentation milestone, each of those FKs has been annotated in
its source migration with an explicit `->restrictOnDelete()` to
make the intent visible to anyone running `migrate:fresh` on a
new environment.

## Audit trail

- 2026-08-22: Policy documented. All 45 historical migrations (create-table plus add-column)
  migrations annotated with explicit `->restrictOnDelete()`
  (no schema behavior change — MySQL already enforced RESTRICT).
  Two pre-existing migrations that already declared
  `->nullOnDelete()` (customer_documents.verified_by, and the
  EDD-template / exchange-rate branch-id fixes) are left as-is.
  Four migrations that were already annotated in a later
  `align_foreign_keys` migration are also left as-is.
