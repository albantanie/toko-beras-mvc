# Unit Test Suite - Toko Beras MVC

## Overview
Comprehensive unit test suite for the Toko Beras MVC application covering all major components including Models, Controllers, Services, and Business Logic.

## Test Structure
```
unit_test/
├── README.md
├── Models/
│   ├── BarangTest.php
│   ├── PenjualanTest.php
│   ├── DetailPenjualanTest.php
│   ├── UserTest.php
│   └── CartTest.php
├── Controllers/
│   ├── HomeControllerTest.php
│   ├── CartControllerTest.php
│   ├── PenjualanControllerTest.php
│   ├── BarangControllerTest.php
│   └── DashboardControllerTest.php
├── Services/
│   ├── ImageCompressionServiceTest.php
│   └── PaymentServiceTest.php
├── BusinessLogic/
│   ├── CartLogicTest.php
│   ├── PaymentLogicTest.php
│   ├── InventoryLogicTest.php
│   └── ReportLogicTest.php
└── Helpers/
    ├── FormatterTest.php
    ├── ValidationTest.php
    └── UtilityTest.php
```

## Running Tests

### Run All Tests
```bash
# From project root
php artisan test unit_test/

# Or run specific test file
php artisan test unit_test/Models/BarangTest.php
```

### Run Tests with Coverage
```bash
# Install Xdebug first
pecl install xdebug

# Run with coverage
php artisan test --coverage unit_test/
```

### Run Tests in Parallel
```bash
php artisan test --parallel unit_test/
```

## Test Categories

### 1. Model Tests
- Database relationships
- Model methods and scopes
- Attribute casting
- Validation rules
- Business logic methods

### 2. Controller Tests
- HTTP request handling
- Response validation
- Authentication/Authorization
- Data processing
- Error handling

### 3. Service Tests
- Business logic isolation
- External service integration
- Error scenarios
- Performance testing

### 4. Business Logic Tests
- Complex calculations
- Workflow validation
- Edge cases
- Integration scenarios

### 5. Helper Tests
- Utility functions
- Formatters
- Validators
- Common operations

## Test Data
All tests use factories and seeders to create realistic test data:
- Users with different roles (admin, kasir, owner)
- Products with various categories
- Sales transactions with different statuses
- Cart items and checkout scenarios

## Best Practices
- Each test is isolated and independent
- Tests use database transactions for cleanup
- Mock external services when needed
- Test both success and failure scenarios
- Use descriptive test names
- Follow AAA pattern (Arrange, Act, Assert)

## Coverage Goals
- Models: 100%
- Controllers: 95%+
- Services: 100%
- Business Logic: 100%
- Helpers: 100%

## Continuous Integration
These tests are designed to run in CI/CD pipelines:
- GitHub Actions
- GitLab CI
- Jenkins
- Docker containers 