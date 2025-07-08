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

// Function to detect PHP executable path
function detectPhpPath(): string
{
    // Try to find PHP executable
    $possiblePaths = [
        'php', // System PATH
        '/usr/bin/php', // Linux standard
        '/usr/local/bin/php', // Common Linux location
        '/opt/homebrew/bin/php', // macOS Homebrew
        '/opt/homebrew/opt/php@8.3/bin/php', // macOS Homebrew PHP 8.3
        '/opt/homebrew/opt/php@8.2/bin/php', // macOS Homebrew PHP 8.2
        '/opt/homebrew/opt/php@8.1/bin/php', // macOS Homebrew PHP 8.1
        'C:\xampp\php\php.exe', // Windows XAMPP
        'C:\php\php.exe', // Windows standalone PHP
    ];

    foreach ($possiblePaths as $path) {
        if (is_executable($path)) {
            return $path;
        }
    }

    // Try using 'which' command
    $which = shell_exec('which php 2>/dev/null');
    if ($which && is_executable(trim($which))) {
        return trim($which);
    }

    // Try using 'where' command on Windows
    $where = shell_exec('where php 2>NUL');
    if ($where && is_executable(trim($where))) {
        return trim($where);
    }

    // Fallback to PHP_BINARY constant
    if (defined('PHP_BINARY') && is_executable(PHP_BINARY)) {
        return PHP_BINARY;
    }

    // Last resort
    return 'php';
}

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

                // Ensure log directory exists
                $logDir = __DIR__ . '/../.log';
                if (! is_dir($logDir)) {
                    mkdir($logDir, 0755, true);
                }

                file_put_contents($logDir . '/env-stdio-client.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
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

echo "=== PHP MCP Environment Variables Demo (Portable Version) ===\n\n";

// Detect PHP path
$phpPath = detectPhpPath();
echo "Detected PHP executable: {$phpPath}\n\n";

// Connect to server with custom environment variables
echo "1. Connecting to MCP server with custom environment variables...\n";
$session = $client->connect('stdio', [
    'command' => $phpPath,
    'args' => [__DIR__ . '/env-stdio-server.php'],
    'env' => [
        // Custom environment variables that will be passed to the server
        'DEMO_APP_NAME' => 'PHP MCP Environment Demo (Portable)',
        'DEMO_VERSION' => '1.0.0',
        'DEMO_ENVIRONMENT' => 'development',
        'DEMO_DEBUG' => 'true',
        'DEMO_API_KEY' => 'demo-key-12345',
        'DEMO_DATABASE_URL' => 'postgres://localhost:5432/demo_db',
        'DEMO_REDIS_URL' => 'redis://localhost:6379',
        'OPENAPI_MCP_HEADERS' => '{"Authorization": "Bearer demo-token", "Content-Type": "application/json"}',
        'NODE_ENV' => 'development',
        'PHP_CUSTOM_VAR' => 'This is a custom PHP variable',
        'DETECTED_PHP_PATH' => $phpPath,
    ],
    'inherit_environment' => true, // Inherit parent environment and merge with custom vars
]);

$session->initialize();
echo "   ✓ Connected and initialized with custom environment variables\n\n";

// Test environment variable passing
echo "2. Testing environment variable passing:\n";
try {
    $result = $session->callTool('get_env', ['name' => 'DETECTED_PHP_PATH']);
    $content = $result->getContent();
    if (is_array($content) && isset($content[0])) {
        $data = json_decode($content[0]->getText(), true);
        echo "   PHP Path environment variable:\n";
        echo "     - Client detected: {$phpPath}\n";
        echo '     - Server received: ' . ($data['value'] ?? 'not found') . "\n";
        echo '     - Match: ' . (($data['value'] ?? '') === $phpPath ? 'YES' : 'NO') . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to test environment variable passing: {$e->getMessage()}\n\n";
}

// List available tools
echo "3. Available Environment Tools:\n";
try {
    $tools = $session->listTools();
    foreach ($tools->getTools() as $tool) {
        echo "   - {$tool->getName()}: {$tool->getDescription()}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to list tools: {$e->getMessage()}\n\n";
}

// Test all custom environment variables
echo "4. Testing all custom environment variables:\n";
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
    echo "   ✗ Failed to get custom environment variables: {$e->getMessage()}\n\n";
}

// Test comprehensive environment info
echo "5. Getting comprehensive environment information:\n";
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
        echo '     - Total Environment Variables: ' . ($data['total_env_vars'] ?? 'unknown') . "\n";

        if (isset($data['categories'])) {
            echo "   Custom Environment Variables:\n";
            foreach ($data['categories'] as $category => $vars) {
                if ($category === 'custom' && count($vars) > 0) {
                    foreach ($vars as $name => $value) {
                        if (strpos($name, 'DEMO_') === 0) {
                            echo "     - {$name}: {$value}\n";
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

// Test setting a runtime variable
echo "6. Testing runtime variable setting:\n";
try {
    $result = $session->callTool('set_env', [
        'name' => 'DEMO_RUNTIME_VAR',
        'value' => 'Set at runtime: ' . date('Y-m-d H:i:s') . ' on ' . php_uname('n'),
    ]);
    $content = $result->getContent();
    if (is_array($content) && isset($content[0])) {
        $data = json_decode($content[0]->getText(), true);
        echo "   Runtime variable set:\n";
        echo "     - Name: DEMO_RUNTIME_VAR\n";
        echo '     - Value: ' . ($data['value'] ?? 'unknown') . "\n";
        echo '     - Success: ' . (($data['success'] ?? false) ? 'YES' : 'NO') . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to set runtime variable: {$e->getMessage()}\n\n";
}

// Test searching for variables
echo "7. Testing environment variable search:\n";
try {
    $result = $session->callTool('search_env', [
        'pattern' => '*DEMO*',
        'search_in' => 'keys',
    ]);
    $content = $result->getContent();
    if (is_array($content) && isset($content[0])) {
        $data = json_decode($content[0]->getText(), true);
        echo "   Search Results for '*DEMO*':\n";
        echo '     - Matches found: ' . ($data['matches_found'] ?? 0) . "\n";

        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $match) {
                echo "     - {$match['key']}: {$match['value']}\n";
            }
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to search environment variables: {$e->getMessage()}\n\n";
}

// Display session statistics
echo "8. Session Summary:\n";
$stats = $client->getStats();
echo "   - PHP Path: {$phpPath}\n";
echo '   - Connection attempts: ' . $stats->getConnectionAttempts() . "\n";
echo '   - Connection errors: ' . $stats->getConnectionErrors() . "\n";
echo '   - Status: ' . $stats->getStatus() . "\n";
echo '   - Session ID: ' . $session->getSessionId() . "\n";
echo "\n";

// Close client
echo "9. Closing session...\n";
$client->close();
echo "   ✓ Session closed\n\n";

echo "=== Environment Variables Demo completed successfully ===\n";
echo "\nThis portable demo automatically detected the PHP executable path\n";
echo "and successfully demonstrated environment variable passing functionality.\n";
echo "\nKey features tested:\n";
echo "1. Automatic PHP path detection\n";
echo "2. Custom environment variable passing\n";
echo "3. Environment variable retrieval and filtering\n";
echo "4. Runtime environment variable setting\n";
echo "5. Environment variable searching\n";
echo "6. Comprehensive environment information\n";
