#!/bin/bash

# Unit Test Runner Script for Toko Beras MVC
# This script provides various options to run unit tests

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -h, --help              Show this help message"
    echo "  -a, --all               Run all unit tests"
    echo "  -m, --models            Run only model tests"
    echo "  -c, --controllers       Run only controller tests"
    echo "  -s, --services          Run only service tests"
    echo "  -b, --business          Run only business logic tests"
    echo "  -h, --helpers           Run only helper tests"
    echo "  --coverage              Run tests with coverage report"
    echo "  --parallel              Run tests in parallel"
    echo "  --filter=PATTERN        Run tests matching pattern"
    echo "  --stop-on-failure       Stop on first failure"
    echo "  --verbose               Verbose output"
    echo "  --debug                 Debug mode"
    echo ""
    echo "Examples:"
    echo "  $0 -a                    # Run all tests"
    echo "  $0 -m                    # Run model tests only"
    echo "  $0 --coverage            # Run with coverage"
    echo "  $0 --filter=Cart         # Run tests with 'Cart' in name"
    echo "  $0 --parallel --verbose  # Run in parallel with verbose output"
}

# Function to check if we're in the right directory
check_environment() {
    if [ ! -f "artisan" ]; then
        print_error "This script must be run from the Laravel project root directory"
        exit 1
    fi

    if [ ! -d "unit_test" ]; then
        print_error "Unit test directory not found. Please ensure unit_test/ directory exists"
        exit 1
    fi

    print_success "Environment check passed"
}

# Function to run tests
run_tests() {
    local test_path="$1"
    local options="$2"
    
    print_status "Running tests for: $test_path"
    
    if [ -n "$options" ]; then
        print_status "With options: $options"
    fi
    
    # Build the command
    local cmd="php artisan test $test_path"
    
    if [ -n "$options" ]; then
        cmd="$cmd $options"
    fi
    
    print_status "Executing: $cmd"
    
    # Run the command
    if eval "$cmd"; then
        print_success "Tests completed successfully for $test_path"
        return 0
    else
        print_error "Tests failed for $test_path"
        return 1
    fi
}

# Function to run all tests
run_all_tests() {
    print_status "Running all unit tests..."
    
    local failed=0
    
    # Run each category
    for category in "Models" "Controllers" "Services" "BusinessLogic" "Helpers"; do
        if [ -d "unit_test/$category" ]; then
            if ! run_tests "unit_test/$category/"; then
                failed=1
            fi
        else
            print_warning "Directory unit_test/$category/ not found, skipping..."
        fi
    done
    
    if [ $failed -eq 0 ]; then
        print_success "All tests completed successfully!"
    else
        print_error "Some tests failed!"
        exit 1
    fi
}

# Function to run specific category
run_category() {
    local category="$1"
    local options="$2"
    
    if [ ! -d "unit_test/$category" ]; then
        print_error "Category '$category' not found in unit_test/"
        exit 1
    fi
    
    run_tests "unit_test/$category/" "$options"
}

# Function to run with coverage
run_with_coverage() {
    print_status "Running tests with coverage report..."
    
    # Check if Xdebug is available
    if ! php -m | grep -q xdebug; then
        print_warning "Xdebug not found. Coverage report may not work properly."
        print_warning "Install Xdebug with: pecl install xdebug"
    fi
    
    run_all_tests "--coverage"
}

# Function to run in parallel
run_parallel() {
    print_status "Running tests in parallel..."
    run_all_tests "--parallel"
}

# Function to run with filter
run_with_filter() {
    local pattern="$1"
    local options="$2"
    
    print_status "Running tests matching pattern: $pattern"
    run_tests "unit_test/" "--filter=$pattern $options"
}

# Main execution
main() {
    # Parse command line arguments
    local run_all=false
    local category=""
    local coverage=false
    local parallel=false
    local filter=""
    local stop_on_failure=false
    local verbose=false
    local debug=false
    local additional_options=""
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_usage
                exit 0
                ;;
            -a|--all)
                run_all=true
                shift
                ;;
            -m|--models)
                category="Models"
                shift
                ;;
            -c|--controllers)
                category="Controllers"
                shift
                ;;
            -s|--services)
                category="Services"
                shift
                ;;
            -b|--business)
                category="BusinessLogic"
                shift
                ;;
            --helpers)
                category="Helpers"
                shift
                ;;
            --coverage)
                coverage=true
                shift
                ;;
            --parallel)
                parallel=true
                shift
                ;;
            --filter=*)
                filter="${1#*=}"
                shift
                ;;
            --stop-on-failure)
                stop_on_failure=true
                shift
                ;;
            --verbose)
                verbose=true
                shift
                ;;
            --debug)
                debug=true
                shift
                ;;
            *)
                print_error "Unknown option: $1"
                show_usage
                exit 1
                ;;
        esac
    done
    
    # Check environment
    check_environment
    
    # Build additional options
    if [ "$stop_on_failure" = true ]; then
        additional_options="$additional_options --stop-on-failure"
    fi
    
    if [ "$verbose" = true ]; then
        additional_options="$additional_options --verbose"
    fi
    
    if [ "$debug" = true ]; then
        additional_options="$additional_options --debug"
    fi
    
    # Execute based on options
    if [ "$coverage" = true ]; then
        run_with_coverage
    elif [ "$parallel" = true ]; then
        run_parallel
    elif [ -n "$filter" ]; then
        run_with_filter "$filter" "$additional_options"
    elif [ "$run_all" = true ]; then
        run_all_tests
    elif [ -n "$category" ]; then
        run_category "$category" "$additional_options"
    else
        # Default: run all tests
        print_status "No specific option provided, running all tests..."
        run_all_tests
    fi
}

# Run main function with all arguments
main "$@" 