#!/bin/bash

# Test Runner with Output Saving
# Saves test results to files for later analysis

set -e

TEST_DIR="/www/wwwroot/local.host/tests/output"
mkdir -p "$TEST_DIR"

echo "Running test suite..."
echo "Output will be saved to: $TEST_DIR"

# Run all tests and save full output
php artisan test 2>&1 > "$TEST_DIR/test-output-full.txt"
TEST_EXIT_CODE=$?

# Also run specific test categories and save
php artisan test --filter=NavigationTest 2>&1 > "$TEST_DIR/navigation-test.txt" || true
php artisan test --filter=Transaction 2>&1 > "$TEST_DIR/transaction-test.txt" || true
php artisan test --filter=User 2>&1 > "$TEST_DIR/user-test.txt" || true
php artisan test --filter=Branch 2>&1 > "$TEST_DIR/branch-test.txt" || true
php artisan test --filter=Api 2>&1 > "$TEST_DIR/api-test.txt" || true
php artisan test --filter=Compliance 2>&1 > "$TEST_DIR/compliance-test.txt" || true
php artisan test --filter=Accounting 2>&1 > "$TEST_DIR/accounting-test.txt" || true

# Generate summary
echo ""
echo "Test Results Summary:"
echo "====================="

# Count passed tests
PASSED=$(grep -c "✓" "$TEST_DIR/test-output-full.txt" 2>/dev/null || echo "0")
FAILED=$(grep -c "FAIL" "$TEST_DIR/test-output-full.txt" 2>/dev/null || echo "0")

# Get final line with total tests
TOTAL_LINE=$(tail -5 "$TEST_DIR/test-output-full.txt" | grep "Tests:" || echo "Tests: unknown")

echo "$TOTAL_LINE"
echo "Passed: $PASSED"
echo "Failed: $FAILED"
echo ""
echo "Output files saved:"
ls -lh "$TEST_DIR/"

echo ""
echo "To analyze errors, run:"
echo "  grep -A5 'FAIL' tests/output/test-output-full.txt"

exit $TEST_EXIT_CODE
