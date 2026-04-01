#!/bin/bash

# CEMS-MY Test Runner Script
# Usage: ./run-tests.sh [filter]

set -e

echo "=================================="
echo "CEMS-MY Test Suite Runner"
echo "=================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan not found. Please run from project root.${NC}"
    exit 1
fi

# Function to run tests
run_tests() {
    local filter=$1
    local exit_code=0

    echo "Running tests..."
    echo ""

    if [ -z "$filter" ]; then
        # Run all tests
        php artisan test --parallel 2>&1 || exit_code=$?
    else
        # Run filtered tests
        php artisan test --filter="$filter" 2>&1 || exit_code=$?
    fi

    return $exit_code
}

# Function to run specific test suites
run_unit_tests() {
    echo -e "${YELLOW}Running Unit Tests...${NC}"
    echo ""
    php artisan test --filter="Unit" 2>&1
}

run_feature_tests() {
    echo -e "${YELLOW}Running Feature Tests...${NC}"
    echo ""
    php artisan test --filter="Feature" 2>&1
}

run_auth_tests() {
    echo -e "${YELLOW}Running Authentication Tests...${NC}"
    echo ""
    php artisan test --filter="Authentication" 2>&1
}

run_user_tests() {
    echo -e "${YELLOW}Running User Management Tests...${NC}"
    echo ""
    php artisan test --filter="User" 2>&1
}

# Main menu
case "${1:-}" in
    "unit")
        run_unit_tests
        ;;
    "feature")
        run_feature_tests
        ;;
    "auth")
        run_auth_tests
        ;;
    "user")
        run_user_tests
        ;;
    "help"|"--help"|"-h")
        echo "CEMS-MY Test Runner"
        echo ""
        echo "Usage: ./run-tests.sh [option]"
        echo ""
        echo "Options:"
        echo "  (no args)  Run all tests"
        echo "  unit        Run unit tests only"
        echo "  feature     Run feature tests only"
        echo "  auth        Run authentication tests"
        echo "  user        Run user management tests"
        echo "  <filter>    Run tests matching filter string"
        echo "  help        Show this help message"
        echo ""
        echo "Examples:"
        echo "  ./run-tests.sh"
        echo "  ./run-tests.sh unit"
        echo "  ./run-tests.sh User"
        echo "  ./run-tests.sh test_can_login"
        ;;
    "")
        echo -e "${YELLOW}Running All Tests...${NC}"
        echo ""
        run_tests
        ;;
    *)
        echo -e "${YELLOW}Running tests matching: $1${NC}"
        echo ""
        run_tests "$1"
        ;;
esac

# Summary
echo ""
echo "=================================="
if [ $? -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
else
    echo -e "${RED}Some tests failed!${NC}"
fi
echo "=================================="
