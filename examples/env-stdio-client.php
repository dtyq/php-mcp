<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

// Set timezone to Shanghai
date_default_timezone_set('Asia/Shanghai');

// Simple DI container implementation
$container = new class implements ContainerInterface {
    /** @var array<string, object> */
    private array $services = [];

    public function __construct()
    {
        $this->services[LoggerInterface::class] = new class extends AbstractLogger {
            /**
             * @param mixed $level
             * @param string $message
             */
            public function log($level, $message, array $context = []): void
            {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
                file_put_contents(__DIR__ . '/../.log/env-stdio-client.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
            }
        };

        $this->services[EventDispatcherInterface::class] = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };
    }

    public function get($id)
    {
        return $this->services[$id];
    }

    public function has($id): bool
    {
        return isset($this->services[$id]);
    }
};

// Create application
$config = [
    'sdk_name' => 'php-mcp-env-demo-client',
];
$app = new Application($container, $config);

// Create client
$client = new McpClient('env-demo-client', '1.0.0', $app);

echo "=== PHP MCP Environment Variables Demo ===\n\n";

// Connect to server with custom environment variables
echo "1. Connecting to MCP server with custom environment variables...\n";
$session = $client->connect('stdio', [
    'command' => '/opt/homebrew/opt/php@8.3/bin/php',
    'args' => [__DIR__ . '/env-stdio-server.php'],
    'env' => [
        // Custom environment variables that will be passed to the server
        'DEMO_APP_NAME' => 'PHP MCP Environment Demo',
        'DEMO_VERSION' => '1.0.0',
        'DEMO_ENVIRONMENT' => 'development',
        'DEMO_DEBUG' => 'true',
        'DEMO_API_KEY' => 'demo-key-12345',
        'DEMO_DATABASE_URL' => 'postgres://localhost:5432/demo_db',
        'DEMO_REDIS_URL' => 'redis://localhost:6379',
        'OPENAPI_MCP_HEADERS' => '{"Authorization": "Bearer demo-token", "Content-Type": "application/json"}',
        'NODE_ENV' => 'development',
        'PHP_CUSTOM_VAR' => 'This is a custom PHP variable',
    ],
    'inherit_environment' => true, // Inherit parent environment and merge with custom vars
]);

$session->initialize();
echo "   ✓ Connected and initialized with custom environment variables\n\n";

// List available tools
echo "2. Available Environment Tools:\n";
try {
    $tools = $session->listTools();
    foreach ($tools->getTools() as $tool) {
        echo "   - {$tool->getName()}: {$tool->getDescription()}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to list tools: {$e->getMessage()}\n\n";
}

// Test 1: Get specific environment variable
echo "3. Testing get_env tool - Getting specific environment variable:\n";
try {
    $result = $session->callTool('get_env', ['name' => 'DEMO_APP_NAME']);
    $content = $result->getContent();
    if (is_array($content) && isset($content[0])) {
        $data = json_decode($content[0]->getText(), true);
        echo "   Environment variable 'DEMO_APP_NAME':\n";
        echo '     - Value: ' . ($data['value'] ?? 'not found') . "\n";
        echo '     - Exists: ' . (($data['exists'] ?? false) ? 'yes' : 'no') . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to get environment variable: {$e->getMessage()}\n\n";
}

// Test 2: Get all environment variables with filter
echo "4. Testing get_env tool - Getting all DEMO_ variables:\n";
try {
    $result = $session->callTool('get_env', ['filter' => 'DEMO_']);
    $content = $result->getContent();
    if (is_array($content) && isset($content[0])) {
        $data = json_decode($content[0]->getText(), true);
        echo "   Found {$data['count']} DEMO_ variables:\n";
        foreach ($data['variables'] as $name => $value) {
            echo "     - {$name}: {$value}\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to get filtered environment variables: {$e->getMessage()}\n\n";
}

// Test 3: Set a new environment variable
echo "5. Testing set_env tool - Setting new environment variable:\n";
try {
    $result = $session->callTool('set_env', [
        'name' => 'DEMO_RUNTIME_VAR',
        'value' => 'This was set at runtime: ' . date('Y-m-d H:i:s'),
    ]);
    $content = $result->getContent();
    if (is_array($content) && isset($content[0])) {
        $data = json_decode($content[0]->getText(), true);
        echo "   Set environment variable 'DEMO_RUNTIME_VAR':\n";
        echo '     - New value: ' . ($data['value'] ?? 'unknown') . "\n";
        echo '     - Old value: ' . ($data['old_value'] ?? 'none') . "\n";
        echo '     - Success: ' . (($data['success'] ?? false) ? 'yes' : 'no') . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to set environment variable: {$e->getMessage()}\n\n";
}

// Test 4: Get comprehensive environment info
echo "6. Testing env_info tool - Getting comprehensive environment info:\n";
try {
    $result = $session->callTool('env_info');
    $content = $result->getContent();
    if (is_array($content) && isset($content[0])) {
        $data = json_decode($content[0]->getText(), true);
        echo "   Process Information:\n";
        echo '     - Process ID: ' . ($data['process_id'] ?? 'unknown') . "\n";
        echo '     - PHP Version: ' . ($data['php_version'] ?? 'unknown') . "\n";
        echo '     - PHP SAPI: ' . ($data['php_sapi'] ?? 'unknown') . "\n";
        echo '     - Current User: ' . ($data['current_user'] ?? 'unknown') . "\n";
        echo '     - Working Directory: ' . ($data['working_directory'] ?? 'unknown') . "\n";
        echo '     - Memory Usage: ' . number_format((float) ($data['memory_usage'] ?? 0)) . " bytes\n";
        echo '     - Peak Memory: ' . number_format((float) ($data['memory_peak'] ?? 0)) . " bytes\n";
        echo '     - Total Environment Variables: ' . ($data['total_env_vars'] ?? 'unknown') . "\n";

        if (isset($data['categories'])) {
            echo "   Environment Variables by Category:\n";
            foreach ($data['categories'] as $category => $vars) {
                echo "     - {$category}: " . count($vars) . " variables\n";
                if ($category === 'custom' && count($vars) > 0) {
                    echo "       Custom variables:\n";
                    foreach ($vars as $name => $value) {
                        if (strpos($name, 'DEMO_') === 0) {
                            echo "         * {$name}: {$value}\n";
                        }
                    }
                }
            }
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to get environment info: {$e->getMessage()}\n\n";
}

// Test 5: Search environment variables
echo "7. Testing search_env tool - Searching for variables containing 'demo':\n";
try {
    $result = $session->callTool('search_env', [
        'pattern' => '*demo*',
        'search_in' => 'both',
    ]);
    $content = $result->getContent();
    if (is_array($content) && isset($content[0])) {
        $data = json_decode($content[0]->getText(), true);
        echo "   Search Results:\n";
        echo '     - Pattern: ' . ($data['pattern'] ?? 'unknown') . "\n";
        echo '     - Search in: ' . ($data['search_in'] ?? 'unknown') . "\n";
        echo '     - Matches found: ' . ($data['matches_found'] ?? 0) . "\n";

        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $match) {
                echo "     - {$match['key']}: {$match['value']}\n";
                echo '       (Key match: ' . ($match['key_match'] ? 'yes' : 'no')
                     . ', Value match: ' . ($match['value_match'] ? 'yes' : 'no') . ")\n";
            }
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to search environment variables: {$e->getMessage()}\n\n";
}

// Test 6: Verify the runtime variable was set
echo "8. Verifying runtime variable was set:\n";
try {
    $result = $session->callTool('get_env', ['name' => 'DEMO_RUNTIME_VAR']);
    $content = $result->getContent();
    if (is_array($content) && isset($content[0])) {
        $data = json_decode($content[0]->getText(), true);
        echo "   Runtime variable 'DEMO_RUNTIME_VAR':\n";
        echo '     - Value: ' . ($data['value'] ?? 'not found') . "\n";
        echo '     - Exists: ' . (($data['exists'] ?? false) ? 'yes' : 'no') . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to verify runtime variable: {$e->getMessage()}\n\n";
}

// Display session statistics
echo "9. Session Summary:\n";
$stats = $client->getStats();
echo '   - Connection attempts: ' . $stats->getConnectionAttempts() . "\n";
echo '   - Connection errors: ' . $stats->getConnectionErrors() . "\n";
echo '   - Status: ' . $stats->getStatus() . "\n";
echo '   - Session ID: ' . $session->getSessionId() . "\n";
echo "\n";

// Close client
echo "10. Closing session...\n";
$client->close();
echo "    ✓ Session closed\n\n";

echo "=== Environment Variables Demo completed successfully ===\n";
echo "\nThis demo showed how to:\n";
echo "1. Pass custom environment variables to stdio server processes\n";
echo "2. Use tools to read environment variables from the server\n";
echo "3. Set new environment variables at runtime\n";
echo "4. Search and filter environment variables\n";
echo "5. Get comprehensive environment information\n";
echo "\nThe custom environment variables (DEMO_*) were successfully passed\n";
echo "to the server and are available for the tools to access.\n";
