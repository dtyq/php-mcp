# STDIO Server Guide

## Overview

The STDIO (Standard Input/Output) transport is one of the primary communication methods supported by PHP MCP. It allows the MCP server to communicate with clients through standard input and output streams, making it ideal for command-line tools, process spawning, and development environments.

## Key Features

- **Simple Setup**: Minimal configuration required
- **Process Communication**: Direct communication through stdin/stdout
- **Development Friendly**: Easy to debug and test
- **Cross-Platform**: Works on all platforms with PHP support

## Quick Start

### 1. Basic Server Setup

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;

// Create simple DI container
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

// Configuration
$config = [
    'sdk_name' => 'my-mcp-server',
    'transports' => [
        'stdio' => [
            'enabled' => true,
            'buffer_size' => 8192,
            'timeout' => 30,
            'validate_messages' => true,
        ],
    ],
];

// Create application and server
$app = new Application($container, $config);
$server = new McpServer('my-server', '1.0.0', $app);

// Start STDIO transport
$server->stdio();
```

### 2. Adding Tools

Tools are functions that clients can call to perform operations:

```php
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Types\Tools\Tool;

function createEchoTool(): RegisteredTool {
    $tool = new Tool(
        'echo',
        [
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string', 'description' => 'Message to echo'],
            ],
            'required' => ['message'],
        ],
        'Echo back the provided message'
    );

    return new RegisteredTool($tool, function (array $args): string {
        return 'Echo: ' . ($args['message'] ?? '');
    });
}

// Register the tool
$server->registerTool(createEchoTool());
```

### 3. Adding Resources

Resources provide access to data or content:

```php
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

function createSystemInfoResource(): RegisteredResource {
    $resource = new Resource(
        'system://info',
        'System Information',
        'Current system information',
        'application/json'
    );

    return new RegisteredResource($resource, function (string $uri): TextResourceContents {
        $info = [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'memory_usage' => memory_get_usage(true),
            'timestamp' => date('c'),
        ];

        return new TextResourceContents($uri, json_encode($info, JSON_PRETTY_PRINT), 'application/json');
    });
}

// Register the resource
$server->registerResource(createSystemInfoResource());
```

### 4. Adding Prompts

Prompts provide templated conversation starters:

```php
use Dtyq\PhpMcp\Server\FastMcp\Prompts\RegisteredPrompt;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

function createGreetingPrompt(): RegisteredPrompt {
    $prompt = new Prompt(
        'greeting',
        'Generate a personalized greeting',
        [
            new PromptArgument('name', 'Person\'s name', true),
            new PromptArgument('language', 'Language for greeting', false),
        ]
    );

    return new RegisteredPrompt($prompt, function (array $args): GetPromptResult {
        $name = $args['name'] ?? 'World';
        $language = $args['language'] ?? 'english';

        $greetings = [
            'english' => "Hello, {$name}! How are you today?",
            'spanish' => "¡Hola, {$name}! ¿Cómo estás hoy?",
            'french' => "Bonjour, {$name}! Comment allez-vous aujourd'hui?",
        ];

        $greeting = $greetings[$language] ?? $greetings['english'];
        $message = new PromptMessage(ProtocolConstants::ROLE_USER, new TextContent($greeting));

        return new GetPromptResult("Greeting for {$name}", [$message]);
    });
}

// Register the prompt
$server->registerPrompt(createGreetingPrompt());
```

## Configuration Options

### STDIO Transport Configuration

```php
$config = [
    'transports' => [
        'stdio' => [
            'enabled' => true,              // Enable STDIO transport
            'buffer_size' => 8192,          // Buffer size for reading/writing
            'timeout' => 30,                // Timeout in seconds
            'validate_messages' => true,    // Validate JSON-RPC messages
            'encoding' => 'utf-8',          // Character encoding
        ],
    ],
];
```

### Logging Configuration

```php
$config = [
    'logging' => [
        'level' => 'info',              // Log level: debug, info, warning, error
        'handlers' => [
            'file' => [
                'enabled' => true,
                'path' => '/var/log/mcp-server.log',
            ],
        ],
    ],
];
```

## Complete Example

Here's a complete STDIO server implementation:

```php
<?php
declare(strict_types=1);

require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

// Simple container implementation
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];

    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                file_put_contents('server.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
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
$config = [
    'sdk_name' => 'complete-stdio-server',
    'transports' => [
        'stdio' => [
            'enabled' => true,
            'buffer_size' => 8192,
            'timeout' => 30,
            'validate_messages' => true,
        ],
    ],
];

// Create tools
function createCalculatorTool(): RegisteredTool {
    $tool = new Tool(
        'calculate',
        [
            'type' => 'object',
            'properties' => [
                'operation' => ['type' => 'string', 'enum' => ['add', 'subtract', 'multiply', 'divide']],
                'a' => ['type' => 'number'],
                'b' => ['type' => 'number'],
            ],
            'required' => ['operation', 'a', 'b'],
        ],
        'Perform mathematical operations'
    );

    return new RegisteredTool($tool, function (array $args): array {
        $a = $args['a'] ?? 0;
        $b = $args['b'] ?? 0;
        $operation = $args['operation'] ?? 'add';

        switch ($operation) {
            case 'add': $result = $a + $b; break;
            case 'subtract': $result = $a - $b; break;
            case 'multiply': $result = $a * $b; break;
            case 'divide':
                if ($b == 0) throw new \Dtyq\PhpMcp\Shared\Exceptions\ValidationError('Division by zero');
                $result = $a / $b;
                break;
            default:
                throw new \Dtyq\PhpMcp\Shared\Exceptions\ValidationError('Unknown operation: ' . $operation);
        }

        return [
            'operation' => $operation,
            'operands' => [$a, $b],
            'result' => $result,
        ];
    });
}

// Create application and server
$app = new Application($container, $config);
$server = new McpServer('complete-stdio-server', '1.0.0', $app);

// Register components
$server
    ->registerTool(createCalculatorTool())
    ->stdio(); // Start STDIO transport
```

## Running the Server

### Command Line Usage

Save your server code to a file (e.g., `server.php`) and run:

```bash
php server.php
```

The server will start and listen for JSON-RPC messages on stdin/stdout.

### Testing with Client

You can test your server using the provided client example or any MCP-compatible client:

```bash
# In another terminal
php client.php
```

## Error Handling

### Common Issues

1. **Invalid JSON-RPC Format**
   ```php
   // Server automatically validates and responds with proper error codes
   ```

2. **Tool Execution Errors**
   ```php
   return new RegisteredTool($tool, function (array $args) {
       try {
           // Your tool logic here
           return $result;
       } catch (Exception $e) {
           throw new \Dtyq\PhpMcp\Shared\Exceptions\McpError(
               'Tool execution failed: ' . $e->getMessage(),
               -32000
           );
       }
   });
   ```

3. **Resource Access Errors**
   ```php
   return new RegisteredResource($resource, function (string $uri) {
       if (!$this->hasAccess($uri)) {
           throw new \Dtyq\PhpMcp\Shared\Exceptions\McpError(
               'Access denied to resource: ' . $uri,
               -32001
           );
       }
       // Return resource content
   });
   ```

## Best Practices

1. **Validate Input**: Always validate tool arguments and resource URIs
2. **Handle Errors**: Provide meaningful error messages
3. **Resource Management**: Clean up resources properly
4. **Logging**: Log important events for debugging
5. **Testing**: Test your server with different clients

## Next Steps

- [HTTP Server Guide](./http-server.md) - Learn about HTTP transport
- [Server Examples](./examples.md) - See complete implementation examples
- [Client Documentation](../client/stdio-client.md) - Learn how to create clients 