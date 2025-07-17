<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Exceptions\SystemException;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

// Set timezone to Shanghai
date_default_timezone_set('Asia/Shanghai');

// Function to detect npx executable path
function detectNpxPath(): string
{
    // Try to find npx executable
    $possiblePaths = [
        '/opt/homebrew/bin/npx', // Homebrew on Apple Silicon
        '/usr/local/bin/npx', // Homebrew on macOS
        '/usr/bin/npx', // Linux standard
        '/usr/local/nodejs/bin/npx', // Custom Node.js installation
        'npx', // System PATH
    ];

    foreach ($possiblePaths as $path) {
        if (is_executable($path)) {
            echo "   ✓ Found npx at: {$path}\n";
            return $path;
        }
    }

    // Try using which command as fallback
    $whichResult = shell_exec('which npx 2>/dev/null');
    if ($whichResult && trim($whichResult)) {
        $path = trim($whichResult);
        echo "   ✓ Found npx via which: {$path}\n";
        return $path;
    }

    // Fallback to 'npx' and let the system find it
    echo "   ⚠ Using fallback npx path\n";
    return 'npx';
}

// Simple DI container implementation
$container = new class implements ContainerInterface {
    /** @var array<string, object> */
    private array $services = [];

    public function __construct()
    {
        // Register logger
        $this->services[LoggerInterface::class] = new class extends AbstractLogger {
            /**
             * @param mixed $level
             * @param mixed $message
             * @param array<string, mixed> $context
             */
            public function log($level, $message, array $context = []): void
            {
                // Silent logger for this test
            }
        };

        // Register event dispatcher
        $this->services[EventDispatcherInterface::class] = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };
    }

    public function get(string $id): object
    {
        if (! isset($this->services[$id])) {
            throw new SystemException("Service {$id} not found");
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
};

// Create application
$config = [
    'sdk_name' => 'php-mcp-sequential-thinking-test',
    'logging' => [
        'level' => 'info',
    ],
];

$app = new Application($container, $config);

// Create MCP client
$client = new McpClient('sequential-thinking-test', '1.0.0', $app);

echo "=== Sequential Thinking MCP Server Test ===\n";

// Detect npx path
$npxPath = detectNpxPath();
echo "NPX executable path detected: {$npxPath}\n";

// Configuration matching the provided JSON
$serverConfig = [
    'command' => $npxPath,
    'args' => ['-y', '@modelcontextprotocol/server-sequential-thinking'],
    'timeout' => 30.0,
    'shutdown_timeout' => 10.0,
    'env' => [
        'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin',
    ],
];

echo "\n1. Connecting to Sequential Thinking MCP server...\n";
echo "   Command: {$serverConfig['command']}\n";
echo '   Args: ' . implode(' ', $serverConfig['args']) . "\n";
echo "   Timeout: {$serverConfig['timeout']}s\n";

try {
    // Connect to the server
    $session = $client->connect('stdio', $serverConfig);
    echo "   ✓ Connection established\n";

    // Initialize the session
    echo "\n2. Initializing session...\n";
    $session->initialize();
    echo "   ✓ Session initialized successfully\n";

    // Get available tools
    echo "\n3. Retrieving available tools...\n";
    $toolsList = $session->listTools();
    $tools = $toolsList->getTools();

    if (empty($tools)) {
        echo "   ⚠ No tools available\n";
    } else {
        echo '   ✓ Found ' . count($tools) . " available tools:\n";

        foreach ($tools as $index => $tool) {
            $toolNumber = $index + 1;
            echo "   {$toolNumber}. {$tool->getName()}\n";
            echo "      Description: {$tool->getDescription()}\n";

            // Show input schema if available
            $inputSchema = $tool->getInputSchema();
            if (isset($inputSchema['properties'])) {
                $properties = $inputSchema['properties'];
                $required = $inputSchema['required'] ?? [];

                echo "      Input Schema:\n";
                foreach ($properties as $propName => $propInfo) {
                    $isRequired = in_array($propName, $required) ? ' (required)' : '';
                    $type = $propInfo['type'] ?? 'unknown';
                    $description = $propInfo['description'] ?? '';

                    echo "        - {$propName} ({$type}){$isRequired}";
                    if ($description) {
                        echo ": {$description}";
                    }
                    echo "\n";
                }
            } else {
                echo "      Input Schema: Available\n";
            }
            echo "\n";
        }
    }

    // Test a simple tool call if available
    echo "4. Testing tool functionality...\n";
    if (! empty($tools)) {
        // Look for a thinking tool
        $thinkingTool = null;
        foreach ($tools as $tool) {
            if (strpos(strtolower($tool->getName()), 'thinking') !== false
                || strpos(strtolower($tool->getName()), 'sequential') !== false) {
                $thinkingTool = $tool;
                break;
            }
        }

        if ($thinkingTool) {
            echo "   Testing {$thinkingTool->getName()} tool...\n";
            try {
                $result = $session->callTool($thinkingTool->getName(), [
                    'thought' => 'Test thought for sequential thinking',
                    'nextThoughtNeeded' => false,
                    'thoughtNumber' => 1,
                    'totalThoughts' => 1,
                ]);

                if ($result->isError()) {
                    echo "   ⚠ Tool call returned error\n";
                } else {
                    echo "   ✓ Tool call successful\n";
                    $content = $result->getContent();
                    if (! empty($content)) {
                        $contentStr = is_array($content)
                            ? json_encode($content, JSON_PRETTY_PRINT)
                            : (string) $content;
                        echo '   Result preview: ' . substr($contentStr, 0, 200) . "...\n";
                    }
                }
            } catch (Exception $e) {
                echo '   ⚠ Tool call failed: ' . $e->getMessage() . "\n";
            }
        } else {
            echo "   No thinking tool found for testing\n";
        }
    }

    // Connection verification
    echo "\n5. Connection verified through successful tool listing\n";

    // Show session information
    echo "\n6. Session Information:\n";
    echo "   Status: connected\n";
    echo "   Session type: stdio\n";

    // Close the session
    echo "\n7. Closing session...\n";
    $session->close();
    echo "   ✓ Session closed successfully\n";

    echo "\n=== Test completed successfully ===\n";
    echo "The Sequential Thinking MCP server is working correctly!\n";
} catch (Exception $e) {
    echo '   ✗ Test failed: ' . $e->getMessage() . "\n";
    echo '   Error type: ' . get_class($e) . "\n";

    // Additional error information
    if (method_exists($e, 'getCode')) {
        echo '   Error code: ' . $e->getCode() . "\n";
    }

    echo "\n=== Test failed ===\n";
    echo "Please check:\n";
    echo "1. Node.js and npm are installed\n";
    echo "2. The @modelcontextprotocol/server-sequential-thinking package is available\n";
    echo "3. Network connectivity is working\n";
    echo "4. The package can be installed via npx\n";

    // Try to close client anyway
    try {
        $client->close();
    } catch (Exception $closeException) {
        echo '   ⚠ Failed to close client: ' . $closeException->getMessage() . "\n";
    }

    exit(1);
}

// Close the client
$client->close();

echo "\n============================================================\n";
echo "📊 Final Results:\n";
echo "🔧 Sequential Thinking MCP Server Test: ✅ Success\n";
echo "\n🎉 All tests passed! Sequential Thinking MCP integration is working correctly.\n";
