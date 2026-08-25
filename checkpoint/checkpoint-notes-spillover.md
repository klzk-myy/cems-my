# checkpoint-notes-spillover.md — Findings 22-33 pre-formatted content

Extracted from §11 of checkpoint.md to bring it under budget. This is pre-formatted content for appending to `feature_audit_report.md`, already summarized in §5 Current Work.

---

## [turn 14 · 2026-08-07T09:35:00Z]

IMPORTANT: Checkpoint-writer mode restricts writes to memory tree only. Cannot write to `/www/wwwroot/local.host/feature_audit_report.md`. The report file currently has 21 findings + placeholder Executive Summary. Main agent needs to: (1) add findings 22-33, (2) write Executive Summary, (3) finalize the report.

### Findings 22-33 for report (pre-formatted for append)

**F22**: config/cems.php BNM reporting contacts have empty defaults (lines 126-130) — `contact_name`, `contact_email`, `contact_phone` all default to `''`. Status: Missing Configuration.

**F23**: NotificationServiceProvider SMS/Webhook channels commented out (lines 32-41). Status: Broken Integration. Notifications config declares sms in critical_channels but the channel is not registered.

**F24**: broadcasting.php default is 'null' (line 18) but notifications.php default_channels includes 'broadcast' (line 23). Status: Broken Integration. Broadcast notifications silently dropped.

**F25**: HorizonServiceProvider all 3 notification routing methods commented out (lines 18-20). Status: Stubbed. Queue worker failures go silently unreported.

**F26**: MfaService.buildOtpauthUrl() falls back to 'user@example.com' (line 63). Status: Hardcoded. Placeholder appears in user's authenticator app.

**F27**: LoginController login() catch block (lines 51-53) has no `return` statement. Status: Missing Error Handling. Falls through to failed-login logic, recording successful login as failure.

**F28**: TestRunnerService.buildCommand() uses `--filter={suite}` for non-'full' suites (lines 84-88). Status: Stubbed/Incomplete Logic. Suite names are directory names, not PHPUnit method names — filter will match nothing.

**F29**: SanctionsDownloadService.download() accepts `$url` without validation (lines 44-47). Status: Missing Validation. SSRF risk — no scheme check, no host allowlist.

**F30**: Sanctions webhook token not in .env.example. Status: Missing Configuration. Webhook endpoint non-functional until manually configured; no setup guidance exists.

**F31**: Exception Handler returns `$e->getMessage()` for DomainException (lines 46-55). Status: Information Disclosure. Contradicts MEMORY.md Typed Exception Message Policy.

**F32**: TestRunnerService.getLatestBySuite() hardcodes suite names (line 263). Status: Hardcoded. Will drift from actual test structure over time.

**F33**: Notification digest/cleanup cron likely not registered in Kernel.php. Status: Missing Integration. Config defines schedule but no artisan schedule command wires it.

### Executive Summary draft for report

"This audit examined the CEMS-MY Laravel codebase across 60 controllers, 92+ services, 72 models, 64 enums, 46 domain exceptions, 6 route files, 36 config files, and 100+ blade templates. The codebase is structurally sound with no TODO/FIXME annotations, no debug statements (dd/dump), and no obvious security vulnerabilities (SQL injection, XSS, path traversal, hardcoded secrets). However, 33 issues were identified across six categories: (1) Broken integrations where config references features that don't exist (broadcast/SMS/webhook channels), (2) Hardcoded values that bypass configuration (license number, position limits, large value threshold), (3) Missing input validation on sensitive financial operations (revaluation, unified alerts), (4) Error handling gaps that leak internal details or corrupt audit trails, (5) Dead code (410 endpoints, debug controllers), and (6) Database portability issues. The most critical findings involve runtime crashes from incomplete enum match expressions (CddLevel::Specific) and broken notification delivery channels."