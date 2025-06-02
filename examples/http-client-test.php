<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */
use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * AutoNavi (Amap) MCP HTTP Client Test.
 *
 * This example demonstrates connecting to AutoNavi's MCP server using HTTP transport
 * with automatic backwards compatibility support for legacy HTTP+SSE transport.
 *
 * The AutoNavi server uses the legacy SSE protocol (MCP 2024-11-05) so this example
 * will automatically fall back to SSE mode when the new Streamable HTTP fails.
 */
require_once __DIR__ . '/../vendor/autoload.php';

// Set timezone
date_default_timezone_set('Asia/Shanghai');

// Simple DI container implementation
$container = new class implements ContainerInterface {
    /** @var array<string, object> */
    private array $services = [];

    public function __construct()
    {
        $this->services[LoggerInterface::class] = new class extends AbstractLogger {
            public function log($level, $message, array $context = []): void
            {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
                echo "[{$timestamp}] {$level}: [php-mcp] {$message}{$contextStr}\n";
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

// Create application and client
$app = new Application($container, ['sdk_name' => 'http-client-test']);
$client = new McpClient('http-client-test', '1.0.0', $app);

// Read key from environment variable, fallback to '123456'
$key = $_ENV['MCP_API_KEY'] ?? getenv('MCP_API_KEY') ?: '123456';

try {
    // 1. Connect to AutoNavi MCP server
    echo "1. Connecting to AutoNavi MCP server...\n";
    echo '   Using API key: ' . substr($key, 0, 6) . "...\n";

    $session = $client->connect('http', [
        'base_url' => 'https://mcp.amap.com/sse?key=' . $key,
        'timeout' => 15.0,
        'sse_timeout' => 300.0,
        'max_retries' => 2,
        'validate_ssl' => true,
        'user_agent' => 'php-mcp-client/1.0.0 (php-mcp-client)',
    ]);

    echo "   ✓ Connected successfully\n";
    echo '   Session ID: ' . $session->getSessionId() . "\n";

    echo "\n2. Initializing session...\n";
    $session->initialize();
    echo "   ✓ Session initialized\n";

    echo "\n3. Listing available tools...\n";
    $toolsResult = $session->listTools();
    $tools = $toolsResult->getTools(); // 获取工具数组
    echo '   ✓ Found ' . count($tools) . " tools:\n";

    foreach ($tools as $tool) {
        echo "     - {$tool->getName()}: {$tool->getDescription()}\n";
    }

    // If key is not the default test key, execute a tool call
    if ($key !== '123456') {
        echo "\n4. Testing tool call (non-default API key detected)...\n";

        if (count($tools) > 0) {
            // Use maps_weather tool to query Shenzhen weather
            $toolName = 'maps_weather';
            $weatherTool = null;

            // Find the maps_weather tool
            foreach ($tools as $tool) {
                if ($tool->getName() === 'maps_weather') {
                    $weatherTool = $tool;
                    break;
                }
            }

            if ($weatherTool) {
                echo "   Calling tool: {$toolName} (querying Shenzhen weather)\n";

                try {
                    // Use Shenzhen as the city parameter
                    $arguments = [
                        'city' => '深圳',
                    ];

                    $result = $session->callTool($toolName, $arguments);
                    echo "   ✓ Tool call successful\n";

                    // Display the weather data properly
                    $content = $result->getContent();
                    if (! empty($content) && is_array($content)) {
                        foreach ($content as $contentItem) {
                            if (method_exists($contentItem, 'getText')) {
                                echo '   Weather Data: ' . $contentItem->getText() . "\n";
                            } elseif (method_exists($contentItem, 'toArray')) {
                                echo '   Result: ' . json_encode($contentItem->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                            }
                        }
                    } else {
                        echo '   Result: ' . json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                    }
                } catch (Exception $toolError) {
                    echo '   ⚠ Tool call failed: ' . $toolError->getMessage() . "\n";
                    echo "   This is expected if the tool requires specific parameters or authentication\n";
                }
            } else {
                echo "   maps_weather tool not found in available tools\n";
            }
        } else {
            echo "   No tools available for testing\n";
        }
    } else {
        echo "\n4. Skipping tool call (using default test key)\n";
        echo "   Set MCP_API_KEY environment variable to test with real API key\n";
    }
} catch (Exception $e) {
    echo "\n❌ Connection Test Failed:\n";
    echo '   Error: ' . $e->getMessage() . "\n\n";
}

$client->close();
