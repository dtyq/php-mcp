# Client Configuration Guide

## Overview

The PHP MCP client provides a comprehensive configuration system that supports multiple transport protocols with type-safe configuration classes. This guide covers all available configuration options and their usage patterns.

## Configuration Architecture

The configuration system is built around a hierarchical structure:

- **TransportConfigInterface**: Base interface for all transport configurations
- **ConnectionConfig**: Unified wrapper for transport-specific configurations
- **StdioConfig**: Configuration for STDIO transport
- **HttpConfig**: Configuration for HTTP transport

## Transport Configuration Interface

All transport configurations implement the `TransportConfigInterface`, which provides:

```php
interface TransportConfigInterface extends JsonSerializable
{
    public static function fromArray(array $config): self;
    public static function getDefaults(): array;
    public function getTransportType(): string;
    public function toArray(): array;
    public function validate(): void;
    public function withChanges(array $changes): self;
}
```

### Key Features

- **Type Safety**: All configurations are strongly typed
- **Validation**: Built-in validation for all configuration values
- **Immutability**: Configurations support immutable updates via `withChanges()`
- **Serialization**: Full JSON serialization support
- **Factory Methods**: Easy creation from arrays or default values

## STDIO Configuration

The `StdioConfig` class configures Standard Input/Output transport for process-based MCP servers.

### Basic Usage

```php
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;

// Simple configuration
$config = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);

// With additional options
$config = new StdioConfig('python', ['server.py'], [
    'read_timeout' => 30,
    'write_timeout' => 10,
    'buffer_size' => 8192,
]);
```

### Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `command` | string | Required | Command to execute |
| `args` | array | `[]` | Command arguments |
| `env` | array | `[]` | Environment variables |
| `cwd` | string\|null | `null` | Working directory |
| `read_timeout` | int | `30` | Read timeout in seconds |
| `write_timeout` | int | `10` | Write timeout in seconds |
| `buffer_size` | int | `8192` | Buffer size for I/O operations |
| `use_stderr` | bool | `false` | Capture stderr output |
| `validate_command` | bool | `true` | Validate command existence |

### Advanced Configuration

```php
// Create from array
$config = StdioConfig::fromArray([
    'command' => 'node',
    'args' => ['server.js'],
    'env' => [
        'NODE_ENV' => 'production',
        'DEBUG' => 'mcp:*',
    ],
    'cwd' => '/path/to/server',
    'read_timeout' => 60,
    'buffer_size' => 16384,
]);

// Environment configuration
$config->setEnv([
    'MCP_LOG_LEVEL' => 'debug',
    'DISABLE_THOUGHT_LOGGING' => 'true',
]);

// Timeout configuration
$config->setTimeout(45);
$config->setReadTimeout(60);
$config->setWriteTimeout(15);

// Immutable updates
$newConfig = $config->withChanges([
    'read_timeout' => 120,
    'buffer_size' => 32768,
]);
```

### Validation

```php
try {
    $config->validate();
    echo "Configuration is valid\n";
} catch (ValidationError $e) {
    echo "Invalid configuration: " . $e->getMessage() . "\n";
}
```

## HTTP Configuration

The `HttpConfig` class configures HTTP transport for web-based MCP servers.

### Basic Usage

```php
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;

// Simple configuration
$config = new HttpConfig('https://api.example.com/mcp');

// With authentication
$config = new HttpConfig('https://api.example.com/mcp', [
    'headers' => [
        'Authorization' => 'Bearer your-token-here',
        'X-API-Key' => 'your-api-key',
    ],
    'timeout' => 30,
]);
```

### Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `base_url` | string | Required | Base URL for HTTP requests |
| `headers` | array | `[]` | HTTP headers |
| `timeout` | int | `30` | Request timeout in seconds |
| `connect_timeout` | int | `10` | Connection timeout in seconds |
| `protocol_version` | string | `'1.1'` | HTTP protocol version |
| `verify_ssl` | bool | `true` | Verify SSL certificates |
| `ca_bundle` | string\|null | `null` | CA bundle path |
| `proxy` | string\|null | `null` | Proxy server URL |
| `auth` | array\|null | `null` | Authentication configuration |
| `cookies` | bool | `false` | Enable cookie handling |
| `max_redirects` | int | `5` | Maximum redirects to follow |
| `event_replay` | bool | `false` | Enable event replay for debugging |

### Advanced Configuration

```php
// Create from array
$config = HttpConfig::fromArray([
    'base_url' => 'https://api.example.com/mcp',
    'headers' => [
        'Authorization' => 'Bearer token',
        'Content-Type' => 'application/json',
        'User-Agent' => 'PHP-MCP-Client/1.0',
    ],
    'timeout' => 60,
    'verify_ssl' => false, // For development only
    'proxy' => 'http://proxy.example.com:8080',
]);

// Header management
$config->setHeaders([
    'Authorization' => 'Bearer new-token',
    'X-Request-ID' => uniqid(),
]);

$config->addHeader('X-Custom-Header', 'value');

// Timeout configuration
$config->setTimeout(45);
$config->setConnectTimeout(15);

// SSL configuration
$config->setVerifySSL(true);
$config->setCABundle('/path/to/ca-bundle.pem');

// Authentication
$config->setAuth([
    'username' => 'user',
    'password' => 'pass',
    'type' => 'basic',
]);

// Immutable updates
$newConfig = $config->withChanges([
    'timeout' => 120,
    'max_redirects' => 10,
]);
```

### SSL and Security

```php
// Production SSL configuration
$config = new HttpConfig('https://api.example.com/mcp');
$config->setVerifySSL(true);
$config->setCABundle('/etc/ssl/certs/ca-certificates.crt');

// Development configuration (disable SSL verification)
$config = new HttpConfig('https://localhost:8080/mcp');
$config->setVerifySSL(false); // Only for development!
```

## Connection Configuration

The `ConnectionConfig` class provides a unified interface for managing different transport configurations.

### Basic Usage

```php
use Dtyq\PhpMcp\Client\Configuration\ConnectionConfig;
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;

// Create with STDIO transport
$stdioConfig = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$connectionConfig = new ConnectionConfig('stdio', $stdioConfig);

// Create with HTTP transport
$httpConfig = new HttpConfig('https://api.example.com/mcp');
$connectionConfig = new ConnectionConfig('http', $httpConfig);
```

### Factory Methods

```php
// Create from array with automatic transport detection
$connectionConfig = ConnectionConfig::fromArray('stdio', [
    'command' => 'python',
    'args' => ['server.py'],
    'read_timeout' => 60,
]);

$connectionConfig = ConnectionConfig::fromArray('http', [
    'base_url' => 'https://api.example.com/mcp',
    'timeout' => 30,
    'headers' => ['Authorization' => 'Bearer token'],
]);
```

### Configuration Access

```php
// Get transport type
$transportType = $connectionConfig->getTransportType(); // 'stdio' or 'http'

// Get specific configuration
$stdioConfig = $connectionConfig->getStdioConfig(); // Returns StdioConfig
$httpConfig = $connectionConfig->getHttpConfig();   // Returns HttpConfig

// Serialization
$array = $connectionConfig->toArray();
$json = json_encode($connectionConfig);
```

## Configuration Examples

### Sequential Thinking Server

```php
// STDIO configuration for Sequential Thinking server
$config = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$config->setEnv(['DISABLE_THOUGHT_LOGGING' => 'true']);
$config->setReadTimeout(60);

// Use with client
$session = $client->stdio($config);
```

### Custom Python Server

```php
// STDIO configuration for custom Python server
$config = new StdioConfig('python', ['my_server.py'], [
    'env' => [
        'PYTHONPATH' => '/path/to/modules',
        'MCP_LOG_LEVEL' => 'debug',
    ],
    'cwd' => '/path/to/server',
    'read_timeout' => 45,
]);

$session = $client->stdio($config);
```

### HTTP API Server

```php
// HTTP configuration for API server
$config = new HttpConfig('https://api.example.com/mcp');
$config->setHeaders([
    'Authorization' => 'Bearer ' . $apiToken,
    'X-API-Version' => '2024-01-01',
]);
$config->setTimeout(30);
$config->setVerifySSL(true);

$session = $client->http($config);
```

## Best Practices

### 1. Use Type-Safe Configuration

```php
// ✅ Recommended: Use typed configuration classes
$config = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);

// ❌ Avoid: Raw arrays (deprecated)
$session = $client->connect('stdio', ['command' => 'npx']);
```

### 2. Validate Configuration

```php
try {
    $config->validate();
    $session = $client->stdio($config);
} catch (ValidationError $e) {
    throw new RuntimeException("Invalid configuration: " . $e->getMessage());
}
```

### 3. Use Environment Variables

```php
// Load sensitive data from environment
$config = new HttpConfig($_ENV['MCP_SERVER_URL']);
$config->setHeaders([
    'Authorization' => 'Bearer ' . $_ENV['MCP_API_TOKEN'],
]);
```

### 4. Immutable Updates

```php
// Create variations without modifying original
$baseConfig = new StdioConfig('python', ['server.py']);

$debugConfig = $baseConfig->withChanges([
    'env' => ['DEBUG' => '1'],
    'read_timeout' => 120,
]);

$prodConfig = $baseConfig->withChanges([
    'env' => ['ENVIRONMENT' => 'production'],
    'read_timeout' => 30,
]);
```

### 5. Configuration Serialization

```php
// Save configuration for later use
$configArray = $config->toArray();
file_put_contents('config.json', json_encode($configArray, JSON_PRETTY_PRINT));

// Load configuration
$configArray = json_decode(file_get_contents('config.json'), true);
$config = StdioConfig::fromArray($configArray);
```

## Error Handling

### Common Validation Errors

```php
try {
    $config = new StdioConfig('', []); // Empty command
    $config->validate();
} catch (ValidationError $e) {
    // Handle validation error
    echo "Configuration error: " . $e->getMessage();
}
```

### Timeout Configuration

```php
// Configure appropriate timeouts
$config = new StdioConfig('slow-server', ['--mode', 'complex']);
$config->setReadTimeout(300);  // 5 minutes for complex operations
$config->setWriteTimeout(60);  // 1 minute for writes
```

## Migration Guide

### From Array Configuration

```php
// Old way (deprecated)
$session = $client->connect('stdio', [
    'command' => 'python',
    'args' => ['server.py'],
    'env' => ['DEBUG' => '1'],
]);

// New way (recommended)
$config = new StdioConfig('python', ['server.py']);
$config->setEnv(['DEBUG' => '1']);
$session = $client->stdio($config);
```

### From ConnectionConfig

```php
// If you were using ConnectionConfig
$connectionConfig = ConnectionConfig::fromArray('stdio', $arrayConfig);
$session = $client->connect('stdio', $connectionConfig);

// Migrate to direct configuration
$stdioConfig = StdioConfig::fromArray($arrayConfig);
$session = $client->stdio($stdioConfig);
```

This configuration system provides a robust, type-safe way to configure MCP client connections while maintaining flexibility and ease of use. 