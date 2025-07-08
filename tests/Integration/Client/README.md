# Client Integration Tests

This directory contains integration tests for the PHP MCP client functionality.

## Test Categories

### Environment Variables Integration Tests

**File**: `EnvironmentVariablesIntegrationTest.php`

**Purpose**: Validates that environment variable passing works correctly in the PHP MCP implementation.

**Test Methods**:

1. **`testEnvironmentVariablesExampleIntegration()`**
   - Tests the basic environment variable demo (`env-stdio-client.php`)
   - Verifies that custom environment variables are passed to the server process
   - Validates that all environment variable tools work correctly
   - Checks that the communication between client and server is successful

2. **`testPortableEnvironmentVariablesExampleIntegration()`**
   - Tests the portable environment variable demo (`env-stdio-client-portable.php`)
   - Verifies automatic PHP executable path detection
   - Ensures environment variable functionality works across different systems

3. **`testEnvironmentVariableIsolation()`**
   - Verifies that environment variables are properly isolated between processes
   - Tests that parent process environment is not corrupted by child process operations
   - Ensures that custom environment variables are only available in the target process

4. **`testEnvironmentVariableTools()`**
   - Specifically tests all environment variable tools:
     - `get_env`: Getting environment variables
     - `set_env`: Setting environment variables  
     - `env_info`: Getting process and environment information
     - `search_env`: Searching environment variables by pattern
   - Validates tool responses and functionality

5. **`testStdioConfigEnvironmentVariables()`**
   - Tests the core `StdioConfig` functionality for environment variable passing
   - Uses a minimal test script to verify the fundamental mechanics
   - Validates that the configuration properly passes environment variables to child processes

**Test Environment Variables**:
- `DEMO_APP_NAME`: Application name
- `DEMO_VERSION`: Version number
- `DEMO_ENVIRONMENT`: Environment type (development/production)
- `DEMO_DEBUG`: Debug flag
- `DEMO_API_KEY`: API key for testing
- `DEMO_DATABASE_URL`: Database connection URL
- `DEMO_REDIS_URL`: Redis connection URL
- `OPENAPI_MCP_HEADERS`: JSON headers for OpenAPI
- `NODE_ENV`: Node environment
- `PHP_CUSTOM_VAR`: Custom PHP variable

**Test Assertions**:
- Environment variables are successfully passed to server processes
- All environment variable tools function correctly
- Process isolation is maintained
- Communication between client and server works properly
- Error handling is robust

### Examples Integration Tests

**File**: `ExamplesIntegrationTest.php`

**Purpose**: Validates that the example scripts work correctly and demonstrate the library functionality.

## Running the Tests

### Run All Integration Tests
```bash
php vendor/bin/phpunit tests/Integration/ -v
```

### Run Environment Variable Tests Only
```bash
php vendor/bin/phpunit tests/Integration/Client/EnvironmentVariablesIntegrationTest.php -v
```

### Run Example Tests Only
```bash
php vendor/bin/phpunit tests/Integration/Client/ExamplesIntegrationTest.php -v
```

## Test Requirements

- PHP 8.3+ (automatic detection across different systems)
- Composer dependencies installed
- Access to file system for temporary files
- Process execution permissions

## Test Output

The tests verify both:
1. **Functional correctness**: Environment variables are passed and tools work
2. **Integration completeness**: Full client-server communication cycle works
3. **Error handling**: Proper error messages and cleanup
4. **Cross-platform compatibility**: Works on macOS, Linux, and Windows

## Notes

- Tests create temporary files in system temp directory
- Tests automatically detect PHP executable path
- Tests include proper cleanup of resources
- Tests verify both successful cases and error conditions
- Tests ensure no side effects on parent process environment 