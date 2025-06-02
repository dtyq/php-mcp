# Quick Start Guide

Get started with PHP MCP in just 5 minutes! This guide will walk you through creating your first MCP server and client.

## Prerequisites

- PHP 7.4 or higher
- Composer installed
- Basic knowledge of PHP

## Installation

Install PHP MCP via Composer:

```bash
composer require dtyq/php-mcp
```

## Step 1: Create Your First Server

Create a file called `my-server.php`:

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Tools\Tool;

// Simple DI container
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];
    
    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new \Psr\Log\NullLogger();
        $this->services[\Psr\EventDispatcher\EventDispatcherInterface::class] = 
            new class implements \Psr\EventDispatcher\EventDispatcherInterface {
                public function dispatch(object $event): object { return $event; }
            };
    }
    
    public function get($id) { return $this->services[$id]; }
    public function has($id): bool { return isset($this->services[$id]); }
};

// Create a simple greeting tool
function createGreetingTool(): RegisteredTool {
    $tool = new Tool(
        'greet',
        [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'Name to greet'],
            ],
            'required' => ['name'],
        ],
        'Greet someone by name'
    );

    return new RegisteredTool($tool, function (array $args): string {
        $name = $args['name'] ?? 'World';
        return "Hello, {$name}! Welcome to PHP MCP!";
    });
}

// Create and configure server
$config = ['sdk_name' => 'my-first-server'];
$app = new Application($container, $config);
$server = new McpServer('my-first-server', '1.0.0', $app);

// Register the tool and start server
$server
    ->registerTool(createGreetingTool())
    ->stdio();
```

## Step 2: Create Your First Client

Create a file called `my-client.php`:

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Content\TextContent;

// Simple DI container
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];
    
    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new \Psr\Log\NullLogger();
        $this->services[\Psr\EventDispatcher\EventDispatcherInterface::class] = 
            new class implements \Psr\EventDispatcher\EventDispatcherInterface {
                public function dispatch(object $event): object { return $event; }
            };
    }
    
    public function get($id) { return $this->services[$id]; }
    public function has($id): bool { return isset($this->services[$id]); }
};

echo "=== My First MCP Client ===\n";

try {
    // Create client
    $app = new Application($container, ['sdk_name' => 'my-first-client']);
    $client = new McpClient('my-first-client', '1.0.0', $app);

    // Connect to our server
    echo "1. Connecting to server...\n";
    $session = $client->connect('stdio', [
        'command' => 'php',
        'args' => [__DIR__ . '/my-server.php'],
    ]);

    $session->initialize();
    echo "   ✓ Connected!\n";

    // List available tools
    echo "\n2. Available tools:\n";
    $tools = $session->listTools();
    foreach ($tools->getTools() as $tool) {
        echo "   - {$tool->getName()}: {$tool->getDescription()}\n";
    }

    // Call our greeting tool
    echo "\n3. Calling greeting tool:\n";
    $result = $session->callTool('greet', ['name' => 'PHP Developer']);
    
    foreach ($result->getContent() as $content) {
        if ($content instanceof TextContent) {
            echo "   " . $content->getText() . "\n";
        }
    }

    echo "\n✅ Success! Your first MCP interaction is complete.\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($client)) {
        $client->close();
    }
}
```

## Step 3: Run Your First MCP Application

1. **Test the server directly:**
   ```bash
   php my-server.php
   ```
   The server will start and wait for JSON-RPC input.

2. **Run the client to interact with the server:**
   ```bash
   php my-client.php
   ```

You should see output like:
```
=== My First MCP Client ===
1. Connecting to server...
   ✓ Connected!

2. Available tools:
   - greet: Greet someone by name

3. Calling greeting tool:
   Hello, PHP Developer! Welcome to PHP MCP!

✅ Success! Your first MCP interaction is complete.
```

## What's Next?

Now that you have a working MCP setup, here are some next steps:

### 1. Add More Tools

Extend your server with additional tools:

```php
// Add a calculator tool
function createCalculatorTool(): RegisteredTool {
    $tool = new Tool(
        'add',
        [
            'type' => 'object',
            'properties' => [
                'a' => ['type' => 'number'],
                'b' => ['type' => 'number'],
            ],
            'required' => ['a', 'b'],
        ],
        'Add two numbers'
    );

    return new RegisteredTool($tool, function (array $args): array {
        return [
            'result' => ($args['a'] ?? 0) + ($args['b'] ?? 0),
            'operation' => 'addition',
        ];
    });
}

// Register it in your server
$server->registerTool(createCalculatorTool());
```

### 2. Add Resources

Provide data access through resources:

```php
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

function createTimeResource(): RegisteredResource {
    $resource = new Resource(
        'time://current',
        'Current Time',
        'Get the current time',
        'text/plain'
    );

    return new RegisteredResource($resource, function (string $uri): TextResourceContents {
        return new TextResourceContents($uri, date('Y-m-d H:i:s'), 'text/plain');
    });
}

$server->registerResource(createTimeResource());
```

### 3. Try HTTP Transport

For web applications, use HTTP transport:

```php
// In your HTTP server endpoint (e.g., index.php)
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$headers = getallheaders() ?: [];
$body = file_get_contents('php://input');

$request = new GuzzleHttp\Psr7\Request($method, $path, $headers, $body);

// Process and send response
$response = $server->http($request);
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("{$name}: {$value}");
    }
}
echo $response->getBody()->getContents();
```

### 4. Error Handling

Add proper error handling:

```php
try {
    $result = $session->callTool('greet', ['name' => 'Alice']);
} catch (\Dtyq\PhpMcp\Shared\Exceptions\McpError $e) {
    echo "MCP Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (\Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
```

### 5. Production Configuration

For production use, add proper logging and configuration:

```php
// Enhanced container with file logging
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];
    
    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                file_put_contents('app.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
            }
        };
        // ... rest of container setup
    }
    
    public function get($id) { return $this->services[$id]; }
    public function has($id): bool { return isset($this->services[$id]); }
};
```

## Common Issues and Solutions

### Issue: "Command not found" error
**Solution:** Make sure PHP is in your PATH and the server file path is correct.

### Issue: "Connection timeout" error
**Solution:** Increase the timeout in your client configuration:
```php
$session = $client->connect('stdio', [
    'command' => 'php',
    'args' => ['my-server.php'],
    'timeout' => 60, // Increase timeout to 60 seconds
]);
```

### Issue: "JSON-RPC parse error"
**Solution:** Check that your server is properly formatted and not outputting extra content.

## Learn More

- **[STDIO Server Guide](./server/stdio-server.md)** - Detailed server documentation
- **[STDIO Client Guide](./client/stdio-client.md)** - Detailed client documentation
- **[HTTP Server Guide](./server/http-server.md)** - Web-based MCP servers
- **[API Reference](./api-reference.md)** - Complete API documentation
- **[Examples](./server/examples.md)** - More comprehensive examples

## Community and Support

- **GitHub Repository:** [https://github.com/dtyq/php-mcp](https://github.com/dtyq/php-mcp)
- **Issues:** [Report bugs or request features](https://github.com/dtyq/php-mcp/issues)
- **Discussions:** [Ask questions and share ideas](https://github.com/dtyq/php-mcp/discussions)

Happy coding with PHP MCP! 🚀 