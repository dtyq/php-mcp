# HTTP Client Guide

## Overview

The HTTP client provides a web-based communication method for connecting to PHP MCP servers. It supports standard HTTP requests with JSON-RPC protocol, making it ideal for web applications, microservices, and distributed systems.

## Key Features

- **HTTP/HTTPS Support**: Standard web protocols
- **JSON-RPC over HTTP**: Standard JSON-RPC protocol
- **Production Ready**: Suitable for production environments
- **Authentication Support**: Built-in authentication mechanisms
- **Error Handling**: Comprehensive error handling and retry logic

## Quick Start

### 1. Basic HTTP Client Setup

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use GuzzleHttp\Client as HttpClient;

// Simple DI container
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];

    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                echo "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
            }
        };

        $this->services[\Psr\EventDispatcher\EventDispatcherInterface::class] = 
            new class implements \Psr\EventDispatcher\EventDispatcherInterface {
                public function dispatch(object $event): object { return $event; }
            };
    }

    public function get($id) { return $this->services[$id]; }
    public function has($id): bool { return isset($this->services[$id]); }
};

// Configuration
$config = ['sdk_name' => 'my-http-client'];

// Create application and client
$app = new Application($container, $config);
$client = new McpClient('my-http-client', '1.0.0', $app);

// ✅ Recommended: Use typed configuration with shortcut method
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;

$config = new HttpConfig('https://your-mcp-server.com/mcp');
$config->setTimeout(30);
$config->setHeaders([
    'Authorization' => 'Bearer your-api-token',
    'Content-Type' => 'application/json',
]);
$session = $client->http($config);

// Initialize session
$session->initialize();

echo "Connected to HTTP MCP server!\n";
```

### 2. Connecting to Different Servers

```php
// ✅ Recommended: Use typed configuration with shortcut method
use Dtyq\PhpMcp\Client\Configuration\HttpConfig;

// Connect to local development server
$localConfig = new HttpConfig('http://localhost:8000/mcp');
$localConfig->setTimeout(10);
$session = $client->http($localConfig);

// Connect with authentication
$authConfig = new HttpConfig('https://api.example.com/mcp');
$authConfig->setHeaders([
    'Authorization' => 'Bearer ' . $apiToken,
    'X-API-Key' => $apiKey,
]);
$authConfig->setTimeout(60);
$session = $client->http($authConfig);
```

**Legacy Method (Deprecated)**:
```php
// ⚠️ This method is deprecated, use http() shortcut instead
$session = $client->connect('http', [
    'url' => 'http://localhost:8000/mcp',
    'timeout' => 10,
]);

// Connect with custom HTTP client configuration
$httpClient = new HttpClient([
    'verify' => false, // For development only
    'proxy' => 'http://proxy.company.com:8080',
    'timeout' => 30,
]);

$session = $client->connect('http', [
    'url' => 'https://internal-server.company.com/mcp',
    'http_client' => $httpClient,
]);
```

### 3. Working with Tools

```php
// List available tools
$toolsResult = $session->listTools();
echo "Available tools:\n";
foreach ($toolsResult->getTools() as $tool) {
    echo "- {$tool->getName()}: {$tool->getDescription()}\n";
}

// Call a web-specific tool
try {
    $result = $session->callTool('web_scrape', [
        'url' => 'https://example.com',
        'selector' => 'h1',
    ]);
    
    echo "Web scraping result:\n";
    foreach ($result->getContent() as $content) {
        if ($content instanceof \Dtyq\PhpMcp\Types\Content\TextContent) {
            echo $content->getText() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Tool call failed: " . $e->getMessage() . "\n";
}

// Call API integration tool
$apiResult = $session->callTool('api_request', [
    'method' => 'GET',
    'endpoint' => '/users/123',
    'headers' => ['Accept' => 'application/json'],
]);

$content = $apiResult->getContent();
if (!empty($content)) {
    $firstContent = $content[0];
    if ($firstContent instanceof \Dtyq\PhpMcp\Types\Content\TextContent) {
        $data = json_decode($firstContent->getText(), true);
        echo "API Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
}
```

### 4. Working with Resources

```php
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

// List available resources
$resourcesResult = $session->listResources();
echo "Available resources:\n";
foreach ($resourcesResult->getResources() as $resource) {
    echo "- {$resource->getUri()}: {$resource->getName()}\n";
}

// Read a web resource
try {
    $resourceResult = $session->readResource('web://config/database');
    foreach ($resourceResult->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $text = $content->getText();
            if ($text !== null) {
                $config = json_decode($text, true);
                echo "Database Config:\n";
                echo "- Host: " . ($config['host'] ?? 'unknown') . "\n";
                echo "- Database: " . ($config['database'] ?? 'unknown') . "\n";
                echo "- Port: " . ($config['port'] ?? 'unknown') . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Resource read failed: " . $e->getMessage() . "\n";
}

// Read dynamic resource with parameters
try {
    $userResource = $session->readResource('api://users/456/profile');
    foreach ($userResource->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $text = $content->getText();
            if ($text !== null) {
                $profile = json_decode($text, true);
                echo "User Profile:\n";
                echo "- Name: " . ($profile['name'] ?? 'unknown') . "\n";
                echo "- Email: " . ($profile['email'] ?? 'unknown') . "\n";
                echo "- Role: " . ($profile['role'] ?? 'unknown') . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "User profile read failed: " . $e->getMessage() . "\n";
}
```

### 5. Working with Prompts

```php
use Dtyq\PhpMcp\Types\Content\TextContent;

// List available prompts
$promptsResult = $session->listPrompts();
echo "Available prompts:\n";
foreach ($promptsResult->getPrompts() as $prompt) {
    echo "- {$prompt->getName()}: {$prompt->getDescription()}\n";
    
    // Show prompt arguments
    foreach ($prompt->getArguments() as $arg) {
        $required = $arg->isRequired() ? ' (required)' : ' (optional)';
        echo "  * {$arg->getName()}: {$arg->getDescription()}{$required}\n";
    }
}

// Get a dynamic prompt
try {
    $promptResult = $session->getPrompt('email_template', [
        'type' => 'welcome',
        'user_name' => 'John Doe',
        'company' => 'Acme Corp',
    ]);
    
    echo "Email Template: " . ($promptResult->getDescription() ?? 'No description') . "\n";
    echo "Generated Content:\n";
    
    foreach ($promptResult->getMessages() as $message) {
        $content = $message->getContent();
        if ($content instanceof TextContent) {
            echo "- Role: {$message->getRole()}\n";
            echo "  Content: {$content->getText()}\n";
        }
    }
} catch (Exception $e) {
    echo "Prompt failed: " . $e->getMessage() . "\n";
}
```

## Configuration Options

### HTTP Transport Configuration

```php
$session = $client->connect('http', [
    'url' => 'https://api.example.com/mcp',  // Server endpoint
    'timeout' => 30,                         // Request timeout in seconds
    'retry_attempts' => 3,                   // Number of retry attempts
    'retry_delay' => 1000,                   // Delay between retries (ms)
    'headers' => [                           // Custom headers
        'Authorization' => 'Bearer token',
        'X-API-Key' => 'api-key',
        'User-Agent' => 'MyApp/1.0',
    ],
    'verify_ssl' => true,                    // SSL verification
    'proxy' => 'http://proxy.example.com',  // HTTP proxy
]);
```

### Client Configuration

```php
$config = [
    'sdk_name' => 'my-http-client',
    'timeout' => 30,                         // Default timeout
    'max_retries' => 3,                      // Maximum retry attempts
    'retry_delay' => 1000,                   // Retry delay in milliseconds
    'logging' => [
        'level' => 'info',                   // Log level
        'enabled' => true,                   // Enable logging
    ],
    'http' => [
        'user_agent' => 'PHP-MCP-Client/1.0', // Default User-Agent
        'verify_ssl' => true,                // SSL verification
        'follow_redirects' => true,          // Follow HTTP redirects
    ],
];
```

## Advanced Usage

### 1. Authentication

```php
// Bearer Token Authentication
$session = $client->connect('http', [
    'url' => 'https://api.example.com/mcp',
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
    ],
]);

// API Key Authentication
$session = $client->connect('http', [
    'url' => 'https://api.example.com/mcp',
    'headers' => [
        'X-API-Key' => $apiKey,
        'X-Client-ID' => $clientId,
    ],
]);

// Custom Authentication
$session = $client->connect('http', [
    'url' => 'https://api.example.com/mcp',
    'headers' => [
        'Authorization' => 'Custom ' . base64_encode($credentials),
    ],
]);
```

### 2. Error Handling

```php
use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Exceptions\ConnectionError;

try {
    $session = $client->connect('http', [
        'url' => 'https://invalid-server.example.com/mcp',
    ]);
    $session->initialize();
} catch (ConnectionError $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    echo "Check server URL and network connectivity\n";
} catch (TransportError $e) {
    echo "Transport error: " . $e->getMessage() . "\n";
    echo "HTTP status: " . $e->getCode() . "\n";
} catch (McpError $e) {
    echo "MCP protocol error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}

// HTTP-specific error handling
try {
    $result = $session->callTool('api_call', ['endpoint' => '/users']);
} catch (TransportError $e) {
    $statusCode = $e->getCode();
    switch ($statusCode) {
        case 401:
            echo "Authentication failed - check your credentials\n";
            break;
        case 403:
            echo "Access forbidden - insufficient permissions\n";
            break;
        case 404:
            echo "Endpoint not found\n";
            break;
        case 429:
            echo "Rate limit exceeded - please wait\n";
            break;
        case 500:
            echo "Server error - please try again later\n";
            break;
        default:
            echo "HTTP error {$statusCode}: " . $e->getMessage() . "\n";
    }
}
```

### 3. Session Management

```php
// Check session status
if ($session->isConnected()) {
    echo "Session is active\n";
} else {
    echo "Session is disconnected\n";
}

// Get session information
echo "Session ID: " . $session->getSessionId() . "\n";

// Reconnect if needed
if (!$session->isConnected()) {
    try {
        $session->reconnect();
        echo "Reconnected successfully\n";
    } catch (Exception $e) {
        echo "Reconnection failed: " . $e->getMessage() . "\n";
    }
}

// Close session properly
$client->close();
```

### 4. Batch Operations

```php
// Perform multiple operations efficiently
$operations = [
    ['type' => 'tool', 'name' => 'web_info', 'args' => ['url' => 'https://example.com']],
    ['type' => 'tool', 'name' => 'api_call', 'args' => ['endpoint' => '/status']],
    ['type' => 'resource', 'uri' => 'config://app/settings'],
];

$results = [];
foreach ($operations as $op) {
    try {
        if ($op['type'] === 'tool') {
            $result = $session->callTool($op['name'], $op['args']);
            $results[] = ['type' => 'tool', 'name' => $op['name'], 'result' => $result];
        } elseif ($op['type'] === 'resource') {
            $result = $session->readResource($op['uri']);
            $results[] = ['type' => 'resource', 'uri' => $op['uri'], 'result' => $result];
        }
    } catch (Exception $e) {
        $results[] = ['type' => 'error', 'operation' => $op, 'error' => $e->getMessage()];
    }
}

// Process results
foreach ($results as $result) {
    if ($result['type'] === 'error') {
        echo "Error in operation: " . $result['error'] . "\n";
    } else {
        echo "Success: " . $result['type'] . "\n";
    }
}
```

## Complete Example

Here's a complete HTTP client implementation:

```php
<?php
declare(strict_types=1);

require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

// Container setup
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];

    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
                file_put_contents('http-client.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
            }
        };

        $this->services[\Psr\EventDispatcher\EventDispatcherInterface::class] = 
            new class implements \Psr\EventDispatcher\EventDispatcherInterface {
                public function dispatch(object $event): object { return $event; }
            };
    }

    public function get($id) { return $this->services[$id]; }
    public function has($id): bool { return isset($this->services[$id]); }
};

echo "=== PHP MCP HTTP Client Demo ===\n";

try {
    // Create client
    $app = new Application($container, ['sdk_name' => 'demo-http-client']);
    $client = new McpClient('demo-http-client', '1.0.0', $app);

    // Connect to HTTP server
    echo "1. Connecting to HTTP server...\n";
    $session = $client->connect('http', [
        'url' => 'https://your-mcp-server.com/mcp',
        'headers' => [
            'Authorization' => 'Bearer your-token-here',
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
    ]);

    $session->initialize();
    echo "   ✓ Connected and initialized\n";

    // Test tools
    echo "\n2. Testing tools...\n";
    $tools = $session->listTools();
    echo "   Available tools: " . count($tools->getTools()) . "\n";

    if (count($tools->getTools()) > 0) {
        $firstTool = $tools->getTools()[0];
        echo "   Testing tool: " . $firstTool->getName() . "\n";
        
        // Call a web-specific tool
        if ($firstTool->getName() === 'web_info') {
            $result = $session->callTool('web_info', ['include_headers' => true]);
            $content = $result->getContent();
            if (!empty($content) && $content[0] instanceof TextContent) {
                $data = json_decode($content[0]->getText(), true);
                echo "   Server info: " . ($data['server_info']['software'] ?? 'unknown') . "\n";
            }
        }
    }

    // Test resources
    echo "\n3. Testing resources...\n";
    $resources = $session->listResources();
    echo "   Available resources: " . count($resources->getResources()) . "\n";

    if (count($resources->getResources()) > 0) {
        $firstResource = $resources->getResources()[0];
        echo "   Testing resource: " . $firstResource->getUri() . "\n";
        
        $resourceResult = $session->readResource($firstResource->getUri());
        foreach ($resourceResult->getContents() as $content) {
            if ($content instanceof TextResourceContents) {
                $text = $content->getText();
                if ($text !== null) {
                    echo "   Resource type: " . $content->getMimeType() . "\n";
                    echo "   Content preview: " . substr($text, 0, 100) . "...\n";
                }
            }
        }
    }

    // Show statistics
    echo "\n4. Session statistics...\n";
    $stats = $client->getStats();
    echo "   Connection attempts: " . $stats->getConnectionAttempts() . "\n";
    echo "   Connection errors: " . $stats->getConnectionErrors() . "\n";
    echo "   Status: " . $stats->getStatus() . "\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} finally {
    // Always close the client
    if (isset($client)) {
        echo "\n5. Closing client...\n";
        $client->close();
        echo "   ✓ Client closed\n";
    }
}

echo "\n=== Demo completed ===\n";
```

## Testing HTTP Servers

### 1. cURL Testing

```bash
# Test server availability
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-token" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/list",
    "id": 1
  }'

# Test tool call
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-token" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/call",
    "params": {
      "name": "web_info",
      "arguments": {"include_headers": true}
    },
    "id": 2
  }'
```

### 2. Postman Collection

Create a Postman collection for testing your MCP server:

```json
{
  "info": {
    "name": "MCP Server Tests",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "List Tools",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          },
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"jsonrpc\": \"2.0\",\n  \"method\": \"tools/list\",\n  \"id\": 1\n}"
        },
        "url": {
          "raw": "{{base_url}}/mcp",
          "host": ["{{base_url}}"],
          "path": ["mcp"]
        }
      }
    }
  ]
}
```

## Best Practices

1. **Authentication**: Always use secure authentication methods in production
2. **Error Handling**: Implement comprehensive error handling for network issues
3. **Timeouts**: Set appropriate timeouts for different types of operations
4. **Logging**: Log all HTTP requests and responses for debugging
5. **Rate Limiting**: Respect server rate limits and implement client-side throttling
6. **SSL Verification**: Always verify SSL certificates in production

## Next Steps

- [STDIO Client Guide](./stdio-client.md) - Learn about STDIO transport
- [Client Examples](./examples.md) - See complete implementation examples
- [Server Documentation](../server/http-server.md) - Learn how to create HTTP servers 