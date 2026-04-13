#!/bin/bash

# CEMS-MY Test Suite Runner
# Runs all tests with proper database setup and cleanup
# Skipped tests are integration tests requiring full system setup

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

echo "========================================"
echo "CEMS-MY Test Suite Runner"
echo "========================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Store original files for revert
print_status "$BLUE" ">>> Backing up modified files..."

BACKUP_FILES=(
    "app/Models/User.php"
    "app/Models/CurrencyPosition.php"
    "app/Enums/UserRole.php"
    "database/factories/CurrencyPositionFactory.php"
)

for file in "${BACKUP_FILES[@]}"; do
    if [ -f "$PROJECT_DIR/$file" ]; then
        cp "$PROJECT_DIR/$file" "$PROJECT_DIR/$file.testbackup"
    fi
done

print_status "$YELLOW" ">>> Running database migrations..."
php artisan migrate:fresh --force 2>/dev/null || php artisan migrate:fresh --force

echo ""
print_status "$CYAN" "========================================"
print_status "$CYAN" "UNIT TESTS"
print_status "$CYAN" "========================================"

echo ""
print_status "$GREEN" "--- MathServiceTest ---"
php artisan test --filter="MathServiceTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- AccountingServiceTest ---"
php artisan test --filter="AccountingServiceTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- ComplianceServiceTest ---"
php artisan test --filter="ComplianceServiceTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- CounterServiceTest ---"
php artisan test --filter="CounterServiceTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- CurrencyPositionServiceTest ---"
php artisan test --filter="CurrencyPositionServiceTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- BoundaryValueTest ---"
php artisan test --filter="BoundaryValueTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- AuditServiceTest ---"
php artisan test --filter="AuditServiceTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- EncryptionServiceTest ---"
php artisan test --filter="EncryptionServiceTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- ExchangeRateHistoryTest ---"
php artisan test --filter="ExchangeRateHistoryTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- LedgerServiceTest (skipped - requires DI) ---"
php artisan test --filter="LedgerServiceTest" 2>&1 | tail -3

echo ""
print_status "$GREEN" "--- RevaluationServiceTest (skipped - requires DI) ---"
php artisan test --filter="RevaluationServiceTest" 2>&1 | tail -3

echo ""
print_status "$CYAN" "========================================"
print_status "$CYAN" "FEATURE TESTS"
print_status "$CYAN" "========================================"

echo ""
print_status "$GREEN" "--- AuthenticationTest ---"
php artisan test --filter="AuthenticationTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- SecurityTest ---"
php artisan test --filter="SecurityTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- TransactionWorkflowTest ---"
php artisan test --filter="TransactionWorkflowTest" 2>&1 | tail -5

echo ""
print_status "$GREEN" "--- AccountingWorkflowTest ---"
php artisan test --filter="AccountingWorkflowTest" 2>&1 | tail -5

echo ""
print_status "$YELLOW" "--- TransactionTest (some skipped - require TillBalance setup) ---"
php artisan test --filter="TransactionTest" 2>&1 | tail -5

echo ""
print_status "$YELLOW" "--- TransactionCancellationFlowTest (skipped - require TillBalance setup) ---"
php artisan test --filter="TransactionCancellationFlowTest" 2>&1 | tail -3

echo ""
print_status "$CYAN" "========================================"
print_status "$CYAN" "REVERTING FILES"
print_status "$CYAN" "========================================"

# Revert modified files
for file in "${BACKUP_FILES[@]}"; do
    if [ -f "$PROJECT_DIR/$file.testbackup" ]; then
        mv "$PROJECT_DIR/$file.testbackup" "$PROJECT_DIR/$file"
        echo "Reverted: $file"
    fi
done

# Cleanup any remaining backup files
rm -f "$PROJECT_DIR"/*.testbackup 2>/dev/null || true
rm -f "$PROJECT_DIR"/tests/*.testbackup 2>/dev/null || true

echo ""
print_status "$BLUE" "========================================"
print_status "$BLUE" "TEST RUN COMPLETE"
print_status "$BLUE" "========================================"
print_status "$YELLOW" ""
print_status "$YELLOW" "NOTE: Integration tests are skipped. These require:"
print_status "$YELLOW" "  - TillBalance to be open"
print_status "$YELLOW" "  - Full service dependency injection setup"
print_status "$YELLOW" "  - Complete Laravel application context"
print_status "$YELLOW" ""
print_status "$YELLOW" "To run all tests including integration tests,"
print_status "$YELLOW" "use: php artisan test"
print_status "$NC" ""
