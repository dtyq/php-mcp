# Shared Directory

The `Shared` directory contains common utilities, message handling, exception management, and core kernel components that are used throughout the PHP MCP implementation. This directory provides the foundational infrastructure for the Model Context Protocol implementation.

> **📖 Official Documentation**: This implementation follows the [MCP 2025-03-26 Specification](https://modelcontextprotocol.io/specification/2025-03-26/)

## Directory Structure

```
Shared/
├── Auth/               # Authentication framework and interfaces
├── Exceptions/          # Exception handling and error management
├── Kernel/             # Core application framework
├── Message/            # JSON-RPC message handling utilities
└── Utilities/          # Common utility classes
```

## Subdirectories Overview

### 1. Auth/

Simple and flexible authentication framework for MCP operations, providing interface-based authentication with minimal dependencies.

**Files:**
- `AuthenticatorInterface.php` - Authentication contract for custom implementations
- `NullAuthenticator.php` - Default authenticator providing universal access

**Design Principles:**
- **Interface-driven**: Support multiple authentication methods through simple contract
- **Zero dependencies**: No specific OAuth2 library requirements
- **Progressive adoption**: From no-auth to full enterprise authentication
- **Application integration**: Easy integration with existing authentication systems

### 2. Exceptions/

Contains comprehensive exception handling classes for the MCP protocol, including JSON-RPC errors, MCP-specific errors, OAuth errors, and transport errors.

**Files:**
- `ErrorCodes.php` - Centralized error code constants for JSON-RPC 2.0 and MCP protocol
- `McpError.php` - Base exception class for all MCP-related errors
- `ValidationError.php` - Exception for input validation and data format errors
- `AuthenticationError.php` - Exception for authentication and OAuth-related errors
- `TransportError.php` - Exception for transport layer errors (HTTP, SSE, etc.)
- `ProtocolError.php` - Exception for MCP protocol violations
- `SystemException.php` - Exception for system-level errors
- `ErrorData.php` - Data structure for error information

### 3. Kernel/

Core application framework providing dependency injection, configuration management, authentication, and logging infrastructure.

**Files:**
- `Application.php` - Main application container and service locator with authentication support
- `Config/Config.php` - Configuration management using dot notation
- `Logger/LoggerProxy.php` - PSR-3 logger proxy with SDK name prefixing

### 4. Message/

JSON-RPC 2.0 message handling utilities for creating, parsing, and validating MCP protocol messages.

**Files:**
- `JsonRpcMessage.php` - Core JSON-RPC 2.0 message implementation
- `MessageUtils.php` - Utility methods for creating common MCP messages
- `SessionMessage.php` - Session-aware message wrapper with metadata

### 5. Utilities/

Common utility classes for JSON processing, HTTP operations, and other shared functionality.

**Files:**
- `JsonUtils.php` - JSON encoding/decoding with MCP-specific defaults
- `HttpUtils.php` - HTTP utilities for various transport methods

## Detailed File Descriptions

### Auth/AuthenticatorInterface.php

Core authentication contract for implementing custom authentication strategies in MCP applications.

**Interface Methods:**
- `authenticate(): AuthInfo` - Performs authentication and returns authentication information

**Design Philosophy:**
- **Simple contract**: Single method interface for authentication
- **Exception-based**: Returns `AuthInfo` on success, throws `AuthenticationError` on failure
- **No dependencies**: Implementations control credential extraction and validation
- **Flexible**: Supports JWT, database, API, or any custom authentication method

**Usage:**
```php
// Custom JWT authenticator
class JwtAuthenticator implements AuthenticatorInterface
{
    public function authenticate(): AuthInfo
    {
        $token = $this->extractTokenFromRequest();
        $payload = $this->validateJwtToken($token);
        
        return AuthInfo::create(
            $payload['sub'],
            $payload['scopes'] ?? [],
            ['token_type' => 'jwt', 'iat' => $payload['iat']]
        );
    }
}

// Custom database authenticator  
class DatabaseAuthenticator implements AuthenticatorInterface
{
    public function authenticate(): AuthInfo
    {
        $apiKey = $this->extractApiKeyFromRequest();
        $user = $this->findUserByApiKey($apiKey);
        
        if (!$user) {
            throw new AuthenticationError('Invalid API key');
        }
        
        return AuthInfo::create(
            $user->id,
            $user->scopes,
            ['user_type' => $user->type, 'api_key' => $apiKey]
        );
    }
}
```

### Auth/NullAuthenticator.php

Default authenticator implementation providing universal access for development and testing scenarios.

**Features:**
- **Universal access**: Grants all scopes (`*`) to anonymous user
- **Zero configuration**: Works out-of-the-box without setup
- **Development friendly**: Perfect for testing and development environments
- **Never expires**: Authentication never expires

**Usage:**
```php
$authenticator = new NullAuthenticator();
$authInfo = $authenticator->authenticate();

// Always returns anonymous user with universal access
assert($authInfo->getSubject() === 'anonymous');
assert($authInfo->hasScope('any-scope') === true);
assert($authInfo->hasAllScopes(['read', 'write', 'admin']) === true);
```

### Kernel/Application.php

Enhanced application container with authentication support through fluent interface.

**Authentication Methods:**
- `withAuthenticator(AuthenticatorInterface $authenticator): self` - Set custom authenticator
- `getAuthenticator(): AuthenticatorInterface` - Get current authenticator (defaults to NullAuthenticator)

**Usage Examples:**
```php
// No authentication (default)
$app = new Application($container, $config);
$authInfo = $app->getAuthenticator()->authenticate(); // Returns anonymous with universal access

// JWT authentication
$jwtAuth = new JwtAuthenticator($secretKey);
$app = $app->withAuthenticator($jwtAuth);

// Database authentication
$dbAuth = new DatabaseAuthenticator($connection);
$app = $app->withAuthenticator($dbAuth);

// Custom authentication
$customAuth = new class implements AuthenticatorInterface {
    public function authenticate(): AuthInfo {
        // Custom logic here
        return AuthInfo::create('custom-user', ['read', 'write']);
    }
};
$app = $app->withAuthenticator($customAuth);
```

### Exceptions/ErrorCodes.php

Defines all error codes used in the MCP implementation:

- **JSON-RPC 2.0 Standard Errors** (-32700 to -32603)
- **MCP Protocol Errors** (-32000 to -32015)
- **OAuth 2.1 Errors** (-32020 to -32030)
- **HTTP Transport Errors** (-32040 to -32049)
- **Streamable HTTP Errors** (-32050 to -32053)
- **Connection Errors** (-32060 to -32064)

**Key Features:**
- Human-readable error messages
- Error code validation methods
- Categorization helpers

**Important Note:** There are two error code definitions in the codebase:
1. `Shared/Exceptions/ErrorCodes.php` - Complete implementation with all transport-specific codes
2. `Types/Core/ProtocolConstants.php` - Core MCP protocol codes only

The Shared version provides the comprehensive error handling system, while the Types version focuses on core protocol errors. Both follow the MCP 2025-03-26 specification but serve different purposes in the architecture.

**Error Code Alignment:** The error codes have been updated to strictly follow the MCP 2025-03-26 specification:
- `-32002` is used for "Resource not found" as specified in the [official documentation](https://modelcontextprotocol.io/specification/2025-03-26/server/resources#error-handling)
- All core protocol errors (-32000 to -32009) are consistently defined across both files
- Transport-specific errors (OAuth, HTTP, Streamable HTTP, Connection) are only in the Shared version

> **📋 Reference**: [MCP Error Handling](https://modelcontextprotocol.io/specification/2025-03-26/server/resources#error-handling) | [JSON-RPC 2.0 Errors](https://modelcontextprotocol.io/specification/2025-03-26/basic#responses)

### Exceptions/ValidationError.php

Provides factory methods for common validation scenarios:

```php
ValidationError::requiredFieldMissing('name', 'user profile');
ValidationError::invalidFieldType('age', 'integer', 'string');
ValidationError::invalidJsonFormat('malformed JSON structure');
```

### Exceptions/AuthenticationError.php

Comprehensive OAuth 2.1 and authentication error handling:

```php
AuthenticationError::invalidScope('read:admin', ['read:user', 'write:user']);
AuthenticationError::expiredCredentials('access token');
AuthenticationError::insufficientPermissions('delete_resource');
```

### Exceptions/TransportError.php

Transport layer error handling for various protocols:

```php
TransportError::connectionTimeout('HTTP', 30);
TransportError::httpError(404, 'Not Found');
TransportError::streamableHttpError('session_expired', 'Session has expired');
```

### Message/JsonRpcMessage.php

Core JSON-RPC 2.0 message implementation supporting:

- **Requests** with method, params, and ID
- **Responses** with result or error
- **Notifications** without ID
- **Batch operations** (array of messages)

**Usage:**
```php
// Create a request
$request = JsonRpcMessage::createRequest('tools/list', ['cursor' => 'abc'], 1);

// Create a response
$response = JsonRpcMessage::createResponse(1, ['tools' => []]);

// Create a notification
$notification = JsonRpcMessage::createNotification('notifications/progress', [
    'progressToken' => 'token123',
    'progress' => 0.5
]);
```

### Message/MessageUtils.php

High-level utilities for creating common MCP messages:

**Protocol Information:**
- MCP Protocol Version: `2025-03-26`
- JSON-RPC Version: `2.0`

**Supported Methods:**
- `initialize` / `notifications/initialized`
- `ping`
- `tools/list` / `tools/call`
- `resources/list` / `resources/read` / `resources/subscribe` / `resources/unsubscribe`
- `prompts/list` / `prompts/get`
- `sampling/createMessage`
- `roots/list`

**Notification Types:**
- `notifications/progress`
- `notifications/message`
- `notifications/cancelled`
- `notifications/resources/updated`
- `notifications/resources/list_changed`
- `notifications/tools/list_changed`
- `notifications/prompts/list_changed`

**Usage Examples:**
```php
// Initialize connection
$init = MessageUtils::createInitializeRequest(1, [
    'name' => 'MyClient',
    'version' => '1.0.0'
], ['tools' => true]);

// List tools with pagination
$listTools = MessageUtils::createListToolsRequest(2, 'cursor123');

// Subscribe to resource updates
$subscribe = MessageUtils::createSubscribeRequest(3, 'file:///path/to/file');

// Send progress notification
$progress = MessageUtils::createProgressNotification('token123', 0.75, 100);
```

### Utilities/JsonUtils.php

JSON processing utilities with MCP-specific defaults:

**Features:**
- Safe encoding/decoding with proper error handling
- Pretty printing for debugging
- JSON validation without decoding
- Object merging and field extraction
- Size checking and normalization

**Usage:**
```php
// Encode with MCP defaults
$json = JsonUtils::encode($data);

// Safe decoding with error handling
$result = JsonUtils::safeDecode($jsonString);
if ($result['success']) {
    $data = $result['data'];
} else {
    $error = $result['error'];
}

// Validate JSON structure
if (JsonUtils::isValid($jsonString)) {
    // Process valid JSON
}
```

### Utilities/HttpUtils.php

HTTP utilities for various transport methods:

**Supported Transports:**
- Standard HTTP/HTTPS
- Server-Sent Events (SSE)
- Streamable HTTP (MCP 2025-03-26)
- Form data and JSON requests

**Features:**
- Context creation for different HTTP methods
- Authentication header helpers
- URL manipulation utilities
- Status code validation

**Usage:**
```php
// Create JSON request context
$context = HttpUtils::createJsonContext('POST', $requestData);

// Create SSE context for streaming
$sseContext = HttpUtils::createSseContext(['Authorization' => 'Bearer token']);

// Create Streamable HTTP context
$streamContext = HttpUtils::createStreamableHttpContext('POST', $data);
```

## Architecture Principles

### 1. Interface-Based Design
All components implement appropriate PSR interfaces where applicable (PSR-3 for logging, PSR-11 for containers).

### 2. Error Handling Strategy
- Comprehensive error codes following JSON-RPC 2.0 and MCP specifications
- Factory methods for common error scenarios
- Structured error data with additional context

### 3. JSON-RPC 2.0 Compliance
- Strict adherence to JSON-RPC 2.0 specification
- Support for requests, responses, notifications, and batching
- Proper ID handling and error responses

> **📋 Reference**: [JSON-RPC 2.0 Messages](https://modelcontextprotocol.io/specification/2025-03-26/basic#messages) | [Batching Support](https://modelcontextprotocol.io/specification/2025-03-26/basic#batching)

### 4. MCP 2025-03-26 Support
- Latest protocol version support
- OAuth 2.1 authentication framework
- Streamable HTTP transport
- Tool annotations and completion capabilities

> **📋 Reference**: [MCP Changelog](https://modelcontextprotocol.io/specification/2025-03-26/changelog) | [Authentication Framework](https://modelcontextprotocol.io/specification/2025-03-26/basic#auth)

### 5. Extensibility
- Modular design allowing easy extension
- Factory patterns for object creation
- Configuration-driven behavior

## Dependencies

- **PSR-3**: Logger interface
- **PSR-11**: Container interface
- **PSR-14**: Event dispatcher interface
- **PSR-16**: Simple cache interface
- **adbar/dot**: Configuration management

## Usage in MCP Implementation

The Shared directory provides the foundation for:

1. **Client Implementation**: Message creation, error handling, transport utilities
2. **Server Implementation**: Request processing, response generation, notification sending
3. **Transport Layers**: HTTP, SSE, STDIO transport implementations
4. **Protocol Compliance**: JSON-RPC 2.0 and MCP 2025-03-26 specification adherence

## Error Handling Flow

```
User Input → Validation → Business Logic → Transport → Response
     ↓           ↓            ↓           ↓         ↓
ValidationError → McpError → TransportError → JsonRpcMessage
```

## Message Flow Example

```php
// 1. Create request
$request = MessageUtils::createListToolsRequest(1);

// 2. Send via transport (HTTP, SSE, etc.)
$response = $transport->send($request);

// 3. Handle response or error
if ($response->isError()) {
    $error = $response->getError();
    throw new McpError(new ErrorData($error['code'], $error['message']));
}

$result = $response->getResult();
```

This shared infrastructure ensures consistent behavior across all MCP components while providing flexibility for different use cases and transport methods.

## 🔗 Related Documentation

- [MCP Specification 2025-03-26](https://modelcontextprotocol.io/specification/2025-03-26/)
- [JSON-RPC 2.0 Specification](https://www.jsonrpc.org/specification)
- [MCP Basic Protocol](https://modelcontextprotocol.io/specification/2025-03-26/basic)
- [MCP Server Resources](https://modelcontextprotocol.io/specification/2025-03-26/server/resources)
- [MCP Authentication](https://modelcontextprotocol.io/specification/2025-03-26/basic#auth)
- Project development standards and coding guidelines 