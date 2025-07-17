# STDIO Client Guide

## Overview

The STDIO (Standard Input/Output) client provides a simple way to communicate with MCP servers using standard input and output streams. This transport is perfect for command-line applications, process automation, and development testing.

In this guide, we'll use the **Sequential Thinking MCP Server** as our primary example. This server provides a structured approach to problem-solving through step-by-step reasoning, making it ideal for demonstrating MCP capabilities.

## Key Features

- **Process Spawning**: Automatically spawn and manage server processes
- **Simple Communication**: Direct stdin/stdout communication
- **Development Friendly**: Easy debugging and testing
- **Cross-Platform**: Works on all platforms with PHP support

### Sequential Thinking MCP Server Features

The Sequential Thinking server we'll use in examples provides:

- **Structured Problem-Solving**: Break down complex problems into manageable steps
- **Thought Revision**: Refine and improve previous thoughts as understanding deepens
- **Branching Logic**: Explore alternative reasoning paths
- **Dynamic Adjustment**: Modify the total number of thoughts as needed
- **Context Preservation**: Maintain context across multiple thinking steps

## Quick Start

### 1. Basic Client Setup

```php
<?php
require_once 'vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;

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
$config = ['sdk_name' => 'my-mcp-client'];

// Create application and client
$app = new Application($container, $config);
$client = new McpClient('my-client', '1.0.0', $app);

// ✅ Recommended: Use typed configuration with shortcut method
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;

// Connect to Sequential Thinking MCP Server
$config = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$session = $client->stdio($config);

// Initialize session
$session->initialize();

echo "Connected to Sequential Thinking MCP server!\n";
```

### 2. Connecting to Different Servers

```php
// ✅ Recommended: Use typed configuration with shortcut method
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;

// Connect to Sequential Thinking MCP Server (recommended for testing)
$sequentialConfig = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$session = $client->stdio($sequentialConfig);

// Connect to other MCP servers
$nodeConfig = new StdioConfig('node', ['path/to/server.js']);
$session = $client->stdio($nodeConfig);

// Connect to Python MCP server
$pythonConfig = new StdioConfig('python', ['path/to/server.py']);
$session = $client->stdio($pythonConfig);

// Connect with environment variables (disable thought logging)
$customConfig = new StdioConfig('npx', ['-y', '@modelcontextprotocol/server-sequential-thinking']);
$customConfig->setEnv([
    'DISABLE_THOUGHT_LOGGING' => 'true',
]);
$session = $client->stdio($customConfig);
```

**Legacy Method (Deprecated)**:
```php
// ⚠️ This method is deprecated, use stdio() shortcut instead
$session = $client->connect('stdio', [
    'command' => 'node',
    'args' => ['path/to/server.js'],
]);
```

### 3. Working with Sequential Thinking Tool

```php
// List available tools
$toolsResult = $session->listTools();
echo "Available tools:\n";
foreach ($toolsResult->getTools() as $tool) {
    echo "- {$tool->getName()}: {$tool->getDescription()}\n";
}

// Use Sequential Thinking for problem-solving
try {
    // Start with the first thought
    $result = $session->callTool('sequential_thinking', [
        'thought' => 'Let me break down this complex problem: How to build a scalable web application?',
        'nextThoughtNeeded' => true,
        'thoughtNumber' => 1,
        'totalThoughts' => 5,
    ]);
    
    echo "First thought processed:\n";
    foreach ($result->getContent() as $content) {
        if ($content instanceof \Dtyq\PhpMcp\Types\Content\TextContent) {
            echo $content->getText() . "\n";
        }
    }
    
    // Continue with next thought
    $result = $session->callTool('sequential_thinking', [
        'thought' => 'I need to consider the architecture: microservices vs monolithic approach.',
        'nextThoughtNeeded' => true,
        'thoughtNumber' => 2,
        'totalThoughts' => 5,
    ]);
    
    // Branch into alternative thinking
    $result = $session->callTool('sequential_thinking', [
        'thought' => 'Actually, let me explore a different approach - serverless architecture.',
        'nextThoughtNeeded' => true,
        'thoughtNumber' => 3,
        'totalThoughts' => 6,
        'branchFromThought' => 2,
        'branchId' => 'serverless-branch',
        'needsMoreThoughts' => true,
    ]);
    
    echo "Sequential thinking process completed!\n";
    
} catch (Exception $e) {
    echo "Sequential thinking failed: " . $e->getMessage() . "\n";
}

// Simple tool call example
try {
    $result = $session->callTool('sequential_thinking', [
        'thought' => 'What are the key factors for choosing a database?',
        'nextThoughtNeeded' => false,
        'thoughtNumber' => 1,
        'totalThoughts' => 1,
    ]);
    
    echo "Single thought result:\n";
    foreach ($result->getContent() as $content) {
        if ($content instanceof \Dtyq\PhpMcp\Types\Content\TextContent) {
            echo $content->getText() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Tool call failed: " . $e->getMessage() . "\n";
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

// Read a resource
try {
    $resourceResult = $session->readResource('system://info');
    foreach ($resourceResult->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $text = $content->getText();
            if ($text !== null) {
                $data = json_decode($text, true);
                echo "System Info:\n";
                echo "- PHP Version: " . ($data['php_version'] ?? 'unknown') . "\n";
                echo "- OS: " . ($data['os'] ?? 'unknown') . "\n";
                echo "- Memory: " . number_format((float)($data['memory_usage'] ?? 0)) . " bytes\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Resource read failed: " . $e->getMessage() . "\n";
}

// Read resource template with parameters
try {
    $userProfile = $session->readResource('user://admin/profile');
    foreach ($userProfile->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $text = $content->getText();
            if ($text !== null) {
                $profile = json_decode($text, true);
                echo "User Profile:\n";
                echo "- User ID: " . ($profile['userId'] ?? 'unknown') . "\n";
                echo "- Role: " . ($profile['role'] ?? 'unknown') . "\n";
                echo "- Email: " . ($profile['email'] ?? 'unknown') . "\n";
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

// Get a prompt
try {
    $promptResult = $session->getPrompt('greeting', [
        'name' => 'Alice',
        'language' => 'spanish',
    ]);
    
    echo "Prompt: " . ($promptResult->getDescription() ?? 'No description') . "\n";
    echo "Messages:\n";
    
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

### STDIO Transport Configuration

```php
$session = $client->connect('stdio', [
    'command' => 'php',                    // Command to execute
    'args' => ['server.php'],              // Command arguments
    'cwd' => '/path/to/working/directory', // Working directory
    'env' => [                             // Environment variables
        'LOG_LEVEL' => 'debug',
        'CONFIG_PATH' => '/etc/config',
    ],
    'timeout' => 30,                       // Timeout in seconds
    'buffer_size' => 8192,                 // Buffer size for I/O
    'validate_messages' => true,           // Validate JSON-RPC messages
]);
```

### Client Configuration

```php
$config = [
    'sdk_name' => 'my-mcp-client',
    'timeout' => 30,                       // Default timeout
    'max_retries' => 3,                    // Maximum retry attempts
    'retry_delay' => 1000,                 // Retry delay in milliseconds
    'logging' => [
        'level' => 'info',                 // Log level
        'enabled' => true,                 // Enable logging
    ],
];
```

## Advanced Usage

### 1. Error Handling

```php
use Dtyq\PhpMcp\Shared\Exceptions\McpError;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Exceptions\ConnectionError;

try {
    $session = $client->connect('stdio', [
        'command' => 'nonexistent-command',
        'args' => [],
    ]);
    $session->initialize();
} catch (ConnectionError $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    echo "Make sure the server command is correct and executable\n";
} catch (TransportError $e) {
    echo "Transport error: " . $e->getMessage() . "\n";
} catch (McpError $e) {
    echo "MCP protocol error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}

// Tool call error handling
try {
    $result = $session->callTool('nonexistent_tool', []);
} catch (McpError $e) {
    if ($e->getCode() === -32601) {
        echo "Tool not found\n";
    } else {
        echo "Tool error: " . $e->getMessage() . "\n";
    }
}
```

### 2. Session Management

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

### 3. Batch Operations

```php
// Perform multiple operations efficiently
$operations = [
    ['type' => 'tool', 'name' => 'echo', 'args' => ['message' => 'First']],
    ['type' => 'tool', 'name' => 'echo', 'args' => ['message' => 'Second']],
    ['type' => 'resource', 'uri' => 'system://info'],
];

foreach ($operations as $op) {
    try {
        if ($op['type'] === 'tool') {
            $result = $session->callTool($op['name'], $op['args']);
            echo "Tool '{$op['name']}' result: ";
            // Process result...
        } elseif ($op['type'] === 'resource') {
            $result = $session->readResource($op['uri']);
            echo "Resource '{$op['uri']}' content: ";
            // Process result...
        }
    } catch (Exception $e) {
        echo "Operation failed: " . $e->getMessage() . "\n";
        continue;
    }
}
```

### 4. Async Operations (Future Enhancement)

```php
// Note: This is a conceptual example for future async support
/*
use React\Promise\Promise;

// Async tool calls
$promises = [];
$promises[] = $session->callToolAsync('tool1', ['param' => 'value1']);
$promises[] = $session->callToolAsync('tool2', ['param' => 'value2']);

Promise::all($promises)->then(function ($results) {
    foreach ($results as $i => $result) {
        echo "Tool " . ($i + 1) . " result: " . json_encode($result) . "\n";
    }
});
*/
```

## Complete Example

Here's a complete STDIO client implementation:

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
                file_put_contents('client.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
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

echo "=== PHP MCP Client Demo ===\n";

try {
    // Create client
    $app = new Application($container, ['sdk_name' => 'demo-client']);
    $client = new McpClient('demo-client', '1.0.0', $app);

    // Connect to server
    echo "1. Connecting to server...\n";
    $session = $client->connect('stdio', [
        'command' => 'php',
        'args' => [__DIR__ . '/server.php'],
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
        
        // Call echo tool if available
        if ($firstTool->getName() === 'echo') {
            $result = $session->callTool('echo', ['message' => 'Hello from demo!']);
            $content = $result->getContent();
            if (!empty($content) && $content[0] instanceof TextContent) {
                echo "   Result: " . $content[0]->getText() . "\n";
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
                    echo "   Content preview: " . substr($text, 0, 100) . "...\n";
                }
            }
        }
    }

    // Test prompts
    echo "\n4. Testing prompts...\n";
    $prompts = $session->listPrompts();
    echo "   Available prompts: " . count($prompts->getPrompts()) . "\n";

    if (count($prompts->getPrompts()) > 0) {
        $firstPrompt = $prompts->getPrompts()[0];
        echo "   Testing prompt: " . $firstPrompt->getName() . "\n";
        
        if ($firstPrompt->getName() === 'greeting') {
            $promptResult = $session->getPrompt('greeting', [
                'name' => 'Demo User',
                'language' => 'english',
            ]);
            
            echo "   Prompt description: " . ($promptResult->getDescription() ?? 'None') . "\n";
        }
    }

    // Show statistics
    echo "\n5. Session statistics...\n";
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
        echo "\n6. Closing client...\n";
        $client->close();
        echo "   ✓ Client closed\n";
    }
}

echo "\n=== Demo completed ===\n";
```

## Testing and Debugging

### 1. Enabling Debug Logging

```php
// Enhanced logging container
$container = new class implements \Psr\Container\ContainerInterface {
    private array $services = [];

    public function __construct() {
        $this->services[\Psr\Log\LoggerInterface::class] = new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s.u');
                $contextStr = empty($context) ? '' : "\n  Context: " . json_encode($context, JSON_PRETTY_PRINT);
                
                $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
                
                // Log to file and console
                file_put_contents('debug.log', $logEntry, FILE_APPEND);
                if (in_array($level, ['error', 'warning'])) {
                    echo $logEntry;
                }
            }
        };
        // ... rest of container setup
    }
};
```

### 2. Message Inspection

```php
// Custom transport wrapper for message inspection
class DebuggingStdioTransport {
    private $originalTransport;
    
    public function __construct($originalTransport) {
        $this->originalTransport = $originalTransport;
    }
    
    public function send($message) {
        echo ">>> Sending: " . json_encode($message, JSON_PRETTY_PRINT) . "\n";
        $response = $this->originalTransport->send($message);
        echo "<<< Received: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
        return $response;
    }
}
```

### 3. Connection Testing

```php
function testConnection($command, $args = []) {
    // Test if the command is executable
    $fullCommand = $command . ' ' . implode(' ', array_map('escapeshellarg', $args));
    
    echo "Testing command: {$fullCommand}\n";
    
    $process = proc_open($fullCommand, [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],  // stderr
    ], $pipes);
    
    if (!is_resource($process)) {
        echo "❌ Failed to start process\n";
        return false;
    }
    
    // Send a simple test message
    $testMessage = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'initialize',
        'id' => 1,
    ]);
    
    fwrite($pipes[0], $testMessage . "\n");
    fclose($pipes[0]);
    
    // Read response
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    $exitCode = proc_close($process);
    
    echo "Exit code: {$exitCode}\n";
    if (!empty($output)) {
        echo "Output: {$output}\n";
    }
    if (!empty($error)) {
        echo "Error: {$error}\n";
    }
    
    return $exitCode === 0;
}

// Test server connectivity
testConnection('php', ['server.php']);
```

## Best Practices

1. **Always Close Connections**: Use try-finally blocks to ensure proper cleanup
2. **Handle Errors Gracefully**: Catch and handle specific exception types
3. **Validate Responses**: Check response types before accessing data
4. **Use Timeouts**: Set appropriate timeouts for long-running operations
5. **Log Important Events**: Enable logging for debugging and monitoring
6. **Test Server Compatibility**: Verify server commands work before using in production

## Next Steps

- [HTTP Client Guide](./http-client.md) - Learn about HTTP transport
- [Client Examples](./examples.md) - See complete implementation examples
- [Server Documentation](../server/stdio-server.md) - Learn how to create servers 