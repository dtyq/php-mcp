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

try {
    // 1. Connect to AutoNavi MCP server
    echo "1. Connecting to AutoNavi MCP server...\n";

    $session = $client->connect('http', [
        'base_url' => 'https://mcp.amap.com/sse?key=123456',
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
} catch (Exception $e) {
    echo "\n❌ Connection Test Failed:\n";
    echo '   Error: ' . $e->getMessage() . "\n\n";
}

$client->close();
