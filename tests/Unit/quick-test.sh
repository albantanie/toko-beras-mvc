#!/bin/bash

# Quick Test Runner for Toko Beras MVC
# Simple script for running specific tests quickly

set -e

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 Quick Test Runner - Toko Beras MVC${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: This script must be run from the Laravel project root directory${NC}"
    exit 1
fi

if [ ! -d "unit_test" ]; then
    echo -e "${RED}❌ Error: Unit test directory not found${NC}"
    exit 1
fi

# Function to run tests
run_test() {
    local test_path="$1"
    local description="$2"
    
    echo -e "${BLUE}📋 Running: $description${NC}"
    echo -e "${YELLOW}   Path: $test_path${NC}"
    echo ""
    
    if php artisan test "$test_path"; then
        echo -e "${GREEN}✅ $description completed successfully!${NC}"
        echo ""
        return 0
    else
        echo -e "${RED}❌ $description failed!${NC}"
        echo ""
        return 1
    fi
}

# Show menu
echo "Select test category to run:"
echo "1) All Unit Tests"
echo "2) Model Tests Only"
echo "3) Controller Tests Only"
echo "4) Service Tests Only"
echo "5) Business Logic Tests Only"
echo "6) Helper Tests Only"
echo "7) Specific Test File"
echo "8) Run with Coverage"
echo "9) Exit"
echo ""

read -p "Enter your choice (1-9): " choice

case $choice in
    1)
        echo -e "${BLUE}🎯 Running All Unit Tests...${NC}"
        echo ""
        run_test "unit_test/" "All Unit Tests"
        ;;
    2)
        run_test "unit_test/Models/" "Model Tests"
        ;;
    3)
        run_test "unit_test/Controllers/" "Controller Tests"
        ;;
    4)
        run_test "unit_test/Services/" "Service Tests"
        ;;
    5)
        run_test "unit_test/BusinessLogic/" "Business Logic Tests"
        ;;
    6)
        run_test "unit_test/Helpers/" "Helper Tests"
        ;;
    7)
        echo ""
        echo "Available test files:"
        echo ""
        
        # List all test files
        find unit_test -name "*.php" -type f | while read -r file; do
            filename=$(basename "$file")
            echo "  - $filename"
        done
        
        echo ""
        read -p "Enter test filename (e.g., BarangTest.php): " testfile
        
        if [ -f "unit_test/$testfile" ]; then
            run_test "unit_test/$testfile" "Specific Test: $testfile"
        else
            echo -e "${RED}❌ Test file not found: $testfile${NC}"
            exit 1
        fi
        ;;
    8)
        echo -e "${BLUE}📊 Running Tests with Coverage...${NC}"
        echo ""
        
        # Check if Xdebug is available
        if ! php -m | grep -q xdebug; then
            echo -e "${YELLOW}⚠️  Warning: Xdebug not found. Coverage report may not work properly.${NC}"
            echo -e "${YELLOW}   Install Xdebug with: pecl install xdebug${NC}"
            echo ""
        fi
        
        run_test "unit_test/ --coverage" "All Tests with Coverage"
        ;;
    9)
        echo -e "${GREEN}👋 Goodbye!${NC}"
        exit 0
        ;;
    *)
        echo -e "${RED}❌ Invalid choice. Please select 1-9.${NC}"
        exit 1
        ;;
esac

echo -e "${GREEN}🎉 Test execution completed!${NC}" 