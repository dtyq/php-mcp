# PHP MCP Development Standards

## Exception Handling

### 1. Exception Hierarchy

All exceptions in the PHP MCP project must follow a strict hierarchy:

```
Exception (PHP built-in)
└── McpError (project base exception)
    ├── ValidationError (validation and input errors)
    ├── ProtocolError (MCP protocol violations)
    ├── TransportError (transport layer errors)
    ├── AuthenticationError (authentication failures)
    ├── AuthorizationError (authorization failures)
    └── ... (other specific error types)
```

### 2. Exception Usage Rules

#### ✅ DO: Use project-specific exceptions

```php
// Correct - use ValidationError for validation failures
if (!is_string($data['name'])) {
    throw ValidationError::invalidFieldType('name', 'string', gettype($data['name']));
}

// Correct - use ValidationError for missing required fields
if (!isset($data['required_field'])) {
    throw ValidationError::requiredFieldMissing('required_field', 'ClassName');
}
```

#### ❌ DON'T: Use PHP built-in exceptions directly

```php
// Incorrect - never use InvalidArgumentException directly
if (!is_string($data['name'])) {
    throw new InvalidArgumentException('Name must be a string');
}

// Incorrect - never use RuntimeException directly
if ($file_operation_failed) {
    throw new RuntimeException('File operation failed');
}
```

### 3. ValidationError Factory Methods

The `ValidationError` class provides convenient factory methods for common validation scenarios:

#### Field Validation
- `ValidationError::requiredFieldMissing(string $fieldName, string $context = '')`
- `ValidationError::invalidFieldType(string $fieldName, string $expectedType, string $actualType)`
- `ValidationError::invalidFieldValue(string $fieldName, string $reason)`
- `ValidationError::emptyField(string $fieldName)`

#### Content Validation
- `ValidationError::invalidContentType(string $expectedType, string $actualType)`
- `ValidationError::unsupportedContentType(string $contentType, string $context = '')`

#### Data Format Validation
- `ValidationError::invalidBase64(string $fieldName)`
- `ValidationError::invalidJsonFormat(string $reason)`

#### File Operations
- `ValidationError::fileOperationError(string $operation, string $filePath, string $reason)`

#### Argument Validation
- `ValidationError::invalidArgumentType(string $argumentName, string $expectedType, string $actualType)`
- `ValidationError::missingRequiredArgument(string $argumentName)`

### 4. Error Messages

#### Consistency
All error messages should follow consistent patterns:

```php
// Field type errors
"Invalid type for field 'fieldName': expected string, got integer"

// Missing field errors
"Required field 'fieldName' is missing for ClassName"

// Empty field errors
"Field 'fieldName' cannot be empty"

// Content type errors
"Invalid content type: expected text, got image"
```

#### Internationalization
- Error messages are currently in English only
- Use clear, descriptive language
- Avoid technical jargon when possible
- Include context information (field names, expected vs actual values)

### 5. Exception Data

Exceptions can include additional structured data:

```php
$exception = ValidationError::invalidFieldType(
    'age',
    'integer',
    'string',
    ['input_value' => 'twenty-five', 'field_path' => 'user.profile.age']
);
```

### 6. Testing Exception Handling

When writing tests for exception scenarios:

```php
public function testInvalidFieldType(): void
{
    $this->expectException(ValidationError::class);
    $this->expectExceptionMessage('Invalid type for field \'name\': expected string, got integer');
    
    SomeClass::fromArray(['name' => 123]);
}
```

### 7. Migration from Built-in Exceptions

When updating existing code:

1. **Identify** all `throw new InvalidArgumentException()` statements
2. **Replace** with appropriate `ValidationError` factory methods
3. **Update** corresponding test expectations
4. **Verify** error messages are consistent with project standards

#### Before (Incorrect):
```php
if (!is_string($title)) {
    throw new InvalidArgumentException('Title field must be a string');
}
```

#### After (Correct):
```php
if (!is_string($title)) {
    throw ValidationError::invalidFieldType('title', 'string', gettype($title));
}
```

### 8. Error Codes

All exceptions use standardized error codes from `ErrorCodes` class:

- `VALIDATION_ERROR` (-32005): Input validation failures
- `PARSE_ERROR` (-32700): JSON parsing errors
- `INVALID_PARAMS` (-32602): Invalid method parameters
- `PROTOCOL_ERROR` (-32002): MCP protocol violations

### 9. Exception Context

Always provide sufficient context in exceptions:

```php
// Good - includes context about where the error occurred
throw ValidationError::requiredFieldMissing('inputSchema', 'Tool');

// Good - includes the actual vs expected values
throw ValidationError::invalidFieldType('count', 'integer', gettype($value));

// Good - includes the operation that failed
throw ValidationError::fileOperationError('read', $filePath, 'file does not exist');
```

## Code Style

### 1. PHP Standards

- Follow PSR-12 coding standards
- Use strict typing: `declare(strict_types=1);`
- Use proper type hints for all parameters and return values

### 2. Documentation

- All public methods must have PHPDoc comments
- Include `@param` and `@return` annotations
- Document exceptions with `@throws` annotations

### 3. Naming Conventions

- Use camelCase for methods and properties
- Use PascalCase for class names
- Use UPPER_CASE for constants
- Use descriptive names that clearly indicate purpose

## Testing Standards

### 1. Test Coverage

- Aim for 100% code coverage
- Test both success and failure scenarios
- Include edge cases and boundary conditions

### 2. Test Organization

- Group related tests in the same test class
- Use descriptive test method names
- Follow the pattern: `testMethodName_WithCondition_ExpectedResult`

### 3. Assertions

- Use specific assertions (`assertSame` vs `assertEquals`)
- Test exception types and messages explicitly
- Verify all relevant object state changes

## Version Control

### 1. Commit Messages

Follow conventional commit format:
- `feat(scope): add new feature`
- `fix(scope): fix bug description`
- `docs(scope): update documentation`
- `test(scope): add or update tests`
- `refactor(scope): refactor code without changing behavior`

### 2. Pull Requests

- Include comprehensive description
- Reference related issues
- Ensure all tests pass
- Update documentation as needed 