# Checkpoint spillover: Files read (historical)
_Sibling spillover from checkpoint.md §6. Historical files from prior audit cycles (models, config, routes, jobs, providers, exceptions handler)._

## Files read this cycle (direct by main agent) — COMPLETED PRIOR CYCLE
- `app/Models/BaseModel.php` — abstract base with `protected $guarded = ['*']` (forces explicit $fillable)
- `app/Models/Bases/TransactionModel.php` — base class with BelongsToBranch/Currency/Customer/User + HasApprover/Status
- `app/Models/Bases/ComplianceModel.php` — base class with BelongsToCustomer + HasStatus
- `app/Models/AuditTrail.php` — stub: only user() relationship, no scopes
- `app/Models/CostCenter.php` — stub: only department() relationship
- `app/Models/SanctionsAnalysis.php` — stub: only customer() relationship
- `app/Models/Compliance/EddDocumentRequest.php` — incomplete, missing parent relationships
- `app/Models/Compliance/CustomerBehavioralBaseline.php` — stub: only customer() relationship
- `app/Models/Traits/HasStatus.php` — well-implemented trait with 11 methods
- `app/Models/Traits/HasNotes.php` — trivial (1 method, adds notes to fillable)
- `app/Models/Traits/BelongsToBranch.php` — well-implemented (3 methods)
- `app/Models/Customer.php` — well-implemented (22+ relationships)
- `app/Models/Transaction.php` — well-implemented (scopes + relationships)
- `app/Models/User.php` — well-implemented (MFA, role checks, relationships)
- `app/Models/BackupLog.php` — well-implemented (scopes, duration, formatted size)
- `routes/auth.php` — stub: only login/logout, no password reset
- `routes/channels.php` — broken: `App.Models.User` dot notation instead of backslash
- `config/broadcasting.php` — default `'null'` driver
- `config/notifications.php` — broadcast/sms in channels but neither works
- `config/hashing.php` — bcrypt hardcoded, no env override
- `config/cors.php` — supports_credentials: false
- `config/accounting.php` — 6 hardcoded account code fallbacks
- `config/sanctions.php` — hardcoded opensanctions.org URLs
- `config/database.php` — missing query_monitoring keys
- `config/mail.php` — placeholder 'hello@example.com'
- `config/horizon.php` — sampled (267 lines)
- `config/cems.php` — confirmed placeholder license/contacts, hardcoded position limits
- `app/Providers/NotificationServiceProvider.php` — SMS/webhook channels commented out
- `app/Providers/QueryLogServiceProvider.php` — references non-existent config keys
- `app/Exceptions/Handler.php` — empty reportable() callback
- `database/migrations/2026_09_09_000001_drop_orphan_tables.php` — drops orphan tables
- `app/Jobs/RescreenHighRiskCustomersJob.php` — hardcoded risk_score >= 70
- `app/Jobs/Accounting/ReconcileDeferredAccountingJob.php` — manual transaction management
- `app/Jobs/Compliance/SanctionsRescreeningJob.php` — hardcoded default date
- `feature_audit_report.md` — **84 findings + Executive Summary**