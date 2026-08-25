# Checkpoint Spillover — Review-Round History (extracted from §5)

## Review round 1 — session changes (#188-#232). Delivered 3 findings; ALL FIXED on approval:
1. [P1] system/alerts/index.blade.php @can on policy-less SystemAlert → replaced with `@if(auth()->user()?->isAdmin())` mirroring controller requireAdmin()
2. [P2] TransactionValidationServiceTest.php:61 stale mock constraint → `->with(CddLevel::Standard, [])`
3. [P3] confirm.blade.php duplicate "Total (MYR)" row → removed
Verified: view:cache compiles clean; test file lints.

## Review round 2 — fixes #1-#60. Delivered report:
- [P1] Api/V1/TransactionApprovalController::reject calls `$this->authorize('reject', ...)` but TransactionPolicy had no reject ability → 500 on every API reject
- [P1] VelocityRiskService checkAmountThreshold/get24hAmount/get24hCount switched to 90-day getVelocityWindowDays() while contract/docblocks/result key say 24h (`amount_24h`) → ComplianceService::checkVelocity flags routine customers every transaction
- [P1] ComplianceCasePolicy::view compared `$user->branch_id === $case->customer_id` (branch id vs customer id) and gave compliance officers nothing
- [P2] TestResultsController `protected $middleware = ['test.dashboard']` is DEAD in Laravel 12 (needs HasMiddleware)
- [P2] ScreeningController::history/status render nonexistent views compliance.screening.{history,status}
- [P2] StockTransferController show()/showStep×6 GET routes had no role checks (mutators did)
- [P2] GeographicRiskService added MYR amount tiers (50k/30k) as score points AND classification cutoffs
- [P3] `$latest->status->value ?? 'unknown'` missing nullsafe

## Fix pass for round 2 (T207) — all 7 + P3 COMPLETE:
1. TransactionPolicy: added `reject()` delegating to `approve()` (same Manager/Admin + branch rules)
2. ThresholdService: new `getVelocityAmountWindowHours()` (velocity.amount_window_hours, default 24); VelocityRiskService 3 methods use it — 24h contract restored, scoring lookback (VelocityMonitor window_days=90) untouched
3. ComplianceCasePolicy::view rewritten: Admin bypass → ComplianceOfficer full visibility → correct `Customer::where('id',$case->customer_id)->where('branch_id',$user->branch_id)->exists()`; Customer imported; FQCNs normalized via edit replace_all
4. TestResultsController implements HasMiddleware (`static middleware(): array` returning `new Middleware('test.dashboard')`) + nullsafe `$latest?->status?->value`
5. Created resources/views/compliance/screening/history.blade.php (ScreeningResult table: date/screened_name/result badge/match_score %/action_taken) and status.blade.php (sanction_hit badge, last_result, last_screened_at, last_match_score from getStatus() array)
6. StockTransferController show()+showStep(): `requireManagerOrAdmin()` added
7. GeographicRiskService switched to `getGeographicHighCountryWeight()`/`getGeographicRecentTravelWeight()`; getRiskTier cutoffs derived from the weights. All lint-clean, view:cache compiled, T207 done'd.

## Round-3 verified-GOOD sweep (fixes #61-#120) — reviewed clean, no action needed:
config env-driving (timezone Asia/Kuala_Lumpur env-wrapped, argon2id driver, cors supports_credentials=true with array_filter origins — no wildcard+credentials clash, sanctum expiration env-int), password reset routes exist in routes/auth.php, AuditTrail scopes+morphTo (auditable columns confirmed in migration), Horizon routeMailNotificationsTo(env) + viewHorizon gate → Admin role, Handler::reportable production-gated critical alerting to monitoring.alert_recipients, channels.php class escaping, database.php query-monitoring keys added, notifications critical_channels sans sms ('sms' at notifications.default_channels:110 is inert — no consumer), backup.php env wrappers, exception hierarchy (#113-#117: constructors w/ context, DuplicateTransactionException→TransactionException, 404 overrides on Account/FiscalYear/RiskProfile/PendingAllocation NotFound; DomainException default 422 keeps approval/validation at 422), FiscalYearStatus Draft/Archived/Deleted cases, AccountCode enum expanded so Support\AccountCodes loads (ReflectionClass OK), StockTransferService::reject() implemented (admin-only, status guard, DB::transaction), SendNotificationDigest real Mail::to()->send(), ReconcileDeferredAccountingJob manual begin/commit/rollback gone (delegates to TransactionAccountingService), soft-deletes migration no longer references customer_searches/audit_trail_filters, no longtext remains in migrations, FormRequests #107-#112 populated (spot-verified exists:/in: rules), zero stub test files remain (#[Test]-aware scan), rand() and expectNotToPerformAssertions eliminated, XSS test asserts onerror=/onload=, SecurityHeadersTest drives handle() not reflection, enum-test skips removed.
