# Checkpoint Task Tree — Early Entries (Findings #1-#73)

Spilled from checkpoint.md §4 Task tree. All entries in this file are ✅ DONE.
These are historical/completed findings no longer directly relevant to current work.

## Findings #1-#20
- ✅ T2 Fix #1: ReportingService hardcoded license number placeholder
- ✅ T3 Fix #2: Remove TestQueryLogController debug endpoint
- ✅ T4 Fix #3: Gate TestResultsController behind environment check
- ✅ T5 Fix #4: Add reject method to API TransactionApprovalController
- ✅ T6 Fix #5: Pass workflow step to StockTransfer show view
- ✅ T7 Fix #6: Add role:manager middleware to API RateController routes
- ✅ T8 Fix #7: Add reason and branch_id to API RateController override
- ✅ T9 Fix #8: Replace MySQL FIELD() with portable CASE WHEN in AlertController
- ✅ T10 Fix #9: Remove dead SanctionController@upload endpoint
- ✅ T11 Fix #10: Add input validation to RevaluationController::run
- ✅ T12 Fix #11: AlertTriageController::dismiss should delegate to service
- ✅ T13 Fix #12: Add input validation to UnifiedAlertController
- ✅ T14 Fix #13: Add branch scope check to API TransactionController::show
- ✅ T15 Fix #14: Add history and status methods to web ScreeningController
- ✅ T16 Fix #15: Resolve duplicate PUT and PATCH routes in BudgetController
- ✅ T17 Fix #16: Add CddLevel::Specific case to verifyCddDocuments()
- ✅ T18 Fix #17-18: Add CddLevel::Specific to label() and color()
- ✅ T19 Fix #19: Replace LARGE_VALUE_THRESHOLD constant with ThresholdService call
- ✅ T20 Fix #20: TellerAllocationService null pointer in modifyAllocation

## Findings #21-#30
- ✅ T21 Fix #21: Already covered by finding #1 (config/cems.php exists and fallback was changed to null)
- ✅ T22 Fix #22: Add listeners for 4 events with no listeners
- ✅ T23 Fix #23: Add handleRiskScoreCalculated to ComplianceEventListener
- ✅ T24 Fix #24: Add toDatabase() to compliance/security notifications
- ✅ T25 Fix #25: Add missing env keys to .env.example
- ✅ T26 Fix #26: Wrap sanctions URL constants in env()
- ✅ T27 Fix #27: (in early checkpoint)
- ✅ T28 Fix #28: (in early checkpoint)
- ✅ T29 Fix #29: (in early checkpoint)
- ✅ T30 Fix #30: (in early checkpoint)

## Findings #31-#50
- ✅ T31 Fix #31: Hash admin password in SetupController
- ✅ T32 Fix #32: Add authorization to all StockTransferController methods
- ✅ T33 Fix #33: Add authorization to all RiskDashboardController methods
- ✅ T34 Fix #34: Add authentication to HealthCheckController
- ✅ T35 Fix #35: Replace internal error message with generic message
- ✅ T36 Fix #36: Implement proper CashFlowService
- ✅ T37 Fix #37: Derive source slugs dynamically from config
- ✅ T38 Fix #38: Add token validation to SanctionsWebhookController health
- ✅ T39 Fix #39: Move hardcoded CSV template data to config
- ✅ T40 Fix #40: Add auth middleware to downloadTemplate route
- ✅ T41 Fix #41: Add account code validation to ReportController::ledgerAccount
- ✅ T42 Fix #42: Fix Navigation.php duplicate route references
- ✅ T43 Fix #43: Remove Tasks navigation item
- ✅ T44 Fix #44: Add admin bypass to FlaggedTransactionPolicy
- ✅ T45 Fix #45: Add branch scope check to CounterPolicy and BranchPolicy
- ✅ T46 Fix #46: Add branch scoping to ComplianceCasePolicy
- ✅ T47 Fix #47: Add Manager role to AlertPolicy
- ✅ T48 Fix #48: Add expectsJson check to CheckRole middleware
- ✅ T49 Fix #49: Add expectsJson check to IpBlocker middleware
- ✅ T50 Fix #50: Already resolved during finding #22

## Findings #51-#73
- ✅ T51 Fix #51: Gate test commands behind environment check
- ✅ T52 Fix #52: Generate random demo passwords
- ✅ T53 Fix #53: Source opening balances from config
- ✅ T54 Fix #54: Add parent::__construct() to SanctionsRescreeningMonitor
- ✅ T55 Fix #55: Add try/catch around screening call
- ✅ T56 Fix #56: Return degraded when no real current data
- ✅ T57 Fix #57: Source default baselines from ThresholdService
- ✅ T58 Fix #58: Use ThresholdService in StructuringRiskService::isStructuring()
- ✅ T59 Fix #59: Use getVelocityWindowDays() in VelocityRiskService
- ✅ T60 Fix #60: Inject ThresholdService into GeographicRiskService
- ✅ T61 Fix #61: Implement actual email sending in SendNotificationDigest
- ✅ T62 Fix #62: Derive friendly notification names from class metadata
- ✅ T63 Fix #63: Normalize role comparison in CheckRole middleware
- ✅ T64 Fix #64: Add null driver warning in broadcasting config
- ✅ T65 Fix #65: Remove sms from critical_channels (SmsChannel not implemented)
- ✅ T66 Fix #66: Create config/monitoring.php with alert_recipients
- ✅ T67 Fix #67: Add missing config keys to config/database.php
- ✅ T68 Fix #68: Implement alert logic in Exception Handler reportable()
- ✅ T69 Fix #69: Fix dot notation class reference in channels.php
- ✅ T70 Fix #70: Already fixed by finding #1 (no fallback in config/cems.php)
- ✅ T71 Fix #71: Replace placeholder email in backup.php with env()
- ✅ T72 Fix #72: Change timezone from UTC to Asia/Kuala_Lumpur
- ✅ T73 Fix #73: Change hash driver to argon2id in hashing.php