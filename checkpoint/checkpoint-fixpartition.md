# Checkpoint Spillover — Fix-All Batch Specs (extracted from §2)

Full per-finding file:line fix specifications for the six parallel implementation agents. Finding numbers refer to the 57-item review report delivered this session. Each agent: minimal diffs following codebase patterns, `php -l` every edited file, no full test-suite runs, per-finding status report.

## Batch A — money-pipeline
- app/Services/Branch/TillBalanceManager.php (P0#1/#2 remove duplicated apply/reverse adjustment blocks outside DB::transaction, single locked txn; P2#24 throw on missing till instead of swallow-and-return-success; P2#20 openBalance lockForUpdate probe before firstOrCreate)
- app/Models/TellerAllocation.php (P1#10 guarded conditional decrement WHERE current_balance >= amount)
- app/Services/Transaction/StockTransferService.php:190-194 ONLY (P2#17 MathService::abs((string)$variance))
- app/Services/EodReconciliationService.php (P2#16 SQL SUM / MathService::add totals)

## Batch B — auth-core
- app/Http/Controllers/Auth/PasswordResetController.php (P1#3 pass plain 'password' to mutator — bcrypt(bcrypt) bricks accounts)
- setup_completed persisted flag: EnsureSetupAccessible + SetupController + SetupService (P1#4; use app()->isProduction() — EnsureSetupAccessibleTest FORBIDS literal app()->environment('production'))
- EnsureMfaVerified order inversion + grace_days enforcement (P1#5)
- routes/api_v1.php wizard step3 +mfa.verified (P1#6)
- MfaService per-user failed-attempt counters (P1#7)
- routes/auth.php neutral reset response + throttle POST /reset-password (P2#31)
- RouteServiceProvider login limiter keyed ip+username (P3#45)
- trusted-device random-secret cookie (P3#44); config/cems.php if needed

## Batch C — concurrency-accounting
- TransactionConfirmationService (P1#11 lock confirmation row inside txn; reject via state machine, never cancel Completed)
- TransactionAccountingService:34-71 + ReconcileDeferredAccountingJob (P1#12 lockForUpdate re-check journal_entry_id)
- PeriodCloseService:64-82 + FiscalYearService:87-94 (P1#13 row locks + re-validate INSIDE txn)
- RevaluationService:53-124 (P2#23 position locks + rate re-check)
- app/Jobs/ProcessTransactionRetry.php (P2#32 implement ShouldBeUnique + re-check Failed before DLQ)
- TransactionRecoveryService:156-178 (P2#33 don't dispatch job that no-ops after PendingApproval transition)
- app/Models/StockTransfer.php:180-218 (P2#22 transfer-number gen race)

## Batch D — data-layer
- app/Models/Compliance/ComplianceCase.php OWNED ENTIRELY BY D (P1#8 canBeResolved FlagStatus::Dismissed → [Resolved->value, Rejected->value]; P3#40 fillable trim; P2#21 generateCaseNumber commit-before-insert race + withTrashed probes)
- CustomerRepository:42-79 OR grouping (P1#9)
- NEW migration unique id_number_hash/phone_hash + CustomerService createCustomer pre-check (P2#26)
- soft-delete traps (P2#27 Counter/Branch tombstone suffix on delete; Transaction idempotency_key null on soft delete)
- Transaction.php:67-85 fillable trim (P2#28 remove rate_override_approved_by/idempotency_key/original_transaction_id)
- ComplianceReportingService:112 'Case_Created' mismatch (P2#29)
- HasStatus trait normalize both sides (P3#41)
- VelocityRiskService:39-42 (P2#14); BankReconciliationService:169-316 (P2#15); CashFlowService:131-220 (P3-listed CashFlow floats); CustomerBehavioralBaseline:64-65 casts MoneyCast/decimal:2 (P2#19)

## Batch E — ops-config-tests
- config/backup.php:98-106 databases SINGLE assoc form + \Throwable catches in BackupService.php:85 & backup RunCommand:82 (P2#34 — TypeError escapes catch(\Exception); BackupLog stuck STATUS_RUNNING)
- .env.example explicit true flags MFA_ENABLED/SECURITY_IP_BLOCKING_ENABLED/SECURITY_CSP_ENABLED/STR_AUTO_GENERATE + document BACKUP_ARCHIVE_PASSWORD + ALLOW_DERIVED_ENCRYPTION_SALT=false (P2#35, P3#51 + phpunit.xml) — E OWNS .env.example
- HorizonServiceProvider:18 mail guard (P2#36); config/monitoring.php:14 array_filter recipients
- bootstrap/app.php +withoutOverlapping on recurring commands monitor:check/backups/reports/revaluation/month-end (P2#37 — E OWNS bootstrap/app.php)
- MonthEndCloseCommand:43 system_user_id instead of User::first() (P2#38)
- ExpireStockReservations chunkById + expires_at query + per-item try/catch (P2#39)
- MathService calculateRevaluationPnl honor $precision (P3#48)
- config/database.php duplicate logging/slow_query_threshold_ms keys; NotificationServiceProvider register-or-delete; DELETE dead app/Console/Kernel.php; notification:send-digest wire-or-remove (investigate producers first)
- ADD tests: TransactionStateMachine transitions-matrix, MfaService recovery-code reuse assertion, feature test post-reset-login-succeeds

## Batch F — http-authz (OWNS routes/web.php EXCLUSIVELY, incl. adding throttle:5,1 to mfa.verify.store / mfa.recovery.verify / mfa.disable POSTs per shared spec)
- StockTransferController branch scoping CONTROLLER-LEVEL ONLY (P2#25 — do NOT edit StockTransferService; agent A owns its variance lines)
- TransactionWizardStep2Request KYC file rules required|file|mimes:pdf,jpg,jpeg,png|max:10240 (P2#30)
- routes/api_v1.php:329-333 rates/validate role gate (P3#46)
- routes/web.php:417 alert acknowledge POST-only + landing view (P3#43)
- CaseManagementService:464-471 upload generated filename uuid+extension, original name DB-only, server-derived mime (P3#42)
- CustomerIndexAction:44-49 + Api/V1/CustomerController:32-40 where(fn($q)=>...orWhere) wraps (P3#47)

## Central verification (after all agents return)
1. Full-tree lint: find app -name "*.php" -print0 | xargs -0 php -l (RE-RUN after any failure — xargs aborts on first bad batch)
2. migrate:fresh against throwaway SQLite /tmp/review_migrate.sqlite
3. php artisan route:list + schedule:list boot checks
4. Targeted tests with BACKUP_NOTIFY_EMAIL=test@example.com inline env (suite otherwise blocked by known spatie InvalidConfig)
