# Session Checkpoint Spillover — Task Tree (Early Findings #1-#30)

## §4 Task tree (extracted)

✅ T2 Fix #1: ReportingService hardcoded license number placeholder
✅ T3 Fix #2: Remove TestQueryLogController debug endpoint
✅ T4 Fix #3: Gate TestResultsController behind environment check
✅ T5 Fix #4: Add reject method to API TransactionApprovalController
✅ T6 Fix #5: Pass workflow step to StockTransfer show view
✅ T7 Fix #6: Add role:manager middleware to API RateController routes
✅ T8 Fix #7: Add reason and branch_id to API RateController override
✅ T9 Fix #8: Replace MySQL FIELD() with portable CASE WHEN in AlertController
✅ T10 Fix #9: Remove dead SanctionController@upload endpoint
✅ T11 Fix #10: Add input validation to RevaluationController::run
✅ T12 Fix #11: AlertTriageController::dismiss should delegate to service
✅ T13 Fix #12: Add input validation to UnifiedAlertController
✅ T14 Fix #13: Add branch scope check to API TransactionController::show
✅ T15 Fix #14: Add history and status methods to web ScreeningController
✅ T16 Fix #15: Resolve duplicate PUT and PATCH routes in BudgetController
✅ T17 Fix #16: Add CddLevel::Specific case to verifyCddDocuments()
✅ T18 Fix #17-18: Add CddLevel::Specific to label() and color()
✅ T19 Fix #19: Replace LARGE_VALUE_THRESHOLD constant with ThresholdService call
✅ T20 Fix #20: TellerAllocationService null pointer in modifyAllocation
✅ T21 Fix #21: Already covered by finding #1
✅ T22 Fix #22: Add listeners for 4 events with no listeners
✅ T23 Fix #23: Add handleRiskScoreCalculated to ComplianceEventListener
✅ T24 Fix #24: Add toDatabase() to compliance/security notifications
✅ T25 Fix #25: Add missing env keys to .env.example
✅ T26 Fix #26: Replace hardcoded sanctions URLs with env()
✅ T27 Fix #27: APP_DEBUG=false in .env.example
✅ T28 Fix #28: Already resolved by finding #1
✅ T29 Fix #29: Replace hardcoded fiscal year/currency arrays with dynamic DB queries
✅ T30 Fix #30: Add SQS_PREFIX to .env.example