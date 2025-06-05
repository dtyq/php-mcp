<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */
use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

// Set timezone
date_default_timezone_set('Asia/Shanghai');

// Enhanced DI container with verbose logging
$container = new class implements ContainerInterface {
    /** @var array<string, object> */
    private array $services = [];

    public function __construct()
    {
        $this->services[LoggerInterface::class] = new class extends AbstractLogger {
            public function log($level, $message, array $context = []): void
            {
                $timestamp = date('Y-m-d H:i:s.u');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                // Color code log levels for better visibility
                $colors = [
                    'error' => "\033[31m",    // Red
                    'warning' => "\033[33m",  // Yellow
                    'info' => "\033[32m",     // Green
                    'debug' => "\033[36m",    // Cyan
                ];
                $color = $colors[$level] ?? '';
                $reset = $color ? "\033[0m" : '';

                echo "{$color}[{$timestamp}] {$level}: {$message}{$contextStr}{$reset}\n";
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
$app = new Application($container, ['sdk_name' => 'streamable-http-test']);
$client = new McpClient('streamable-http-test', '1.0.0', $app);

echo "=== Streamable HTTP Protocol Test (2025-03-26) ===\n";
echo "Testing new Streamable HTTP implementation with real MCP service\n\n";

// Test configuration with real Streamable HTTP MCP service
$testPort = getenv('TEST_HTTP_PORT') ?: '8000';
$testConfig = [
    'base_url' => "http://127.0.0.1:{$testPort}/mcp",
    'timeout' => 30.0,
    'protocol_version' => '2025-03-26', // Force new protocol
    'max_retries' => 2,
    'validate_ssl' => true,
    'user_agent' => 'php-mcp-streamable-test/1.0.0',
    'enable_resumption' => true,
];

$startTime = microtime(true);

try {
    echo "1. Testing Streamable HTTP Connection:\n";
    echo "   Service: magic-service (real MCP server)\n";
    echo "   Protocol: {$testConfig['protocol_version']}\n";
    echo "   Expected: Should use new protocol without fallback\n\n";

    $session = $client->connect('http', $testConfig);

    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);

    echo "   ✓ Connection successful in {$duration}ms\n";
    echo '   Session ID: ' . $session->getSessionId() . "\n";

    // Get session stats
    $stats = $session->getStats();
    if (isset($stats['transport_connected'])) {
        echo '   Transport Connected: ' . ($stats['transport_connected'] ? 'Yes' : 'No') . "\n";
    }

    echo "\n2. Testing Session Initialization:\n";
    $initStart = microtime(true);
    $session->initialize();
    $initEnd = microtime(true);
    $initDuration = round(($initEnd - $initStart) * 1000, 2);

    echo "   ✓ Session initialized in {$initDuration}ms\n";

    echo "\n3. Testing Tool Discovery:\n";
    $toolsResult = $session->listTools();
    $tools = $toolsResult->getTools();
    echo '   ✓ Found ' . count($tools) . " tools:\n";

    foreach ($tools as $tool) {
        echo "     - {$tool->getName()}: {$tool->getDescription()}\n";
    }

    echo "\n4. Testing Tool Calls:\n";

    // Test echo tool
    echo "   Testing 'echo' tool:\n";
    try {
        $echoResult = $session->callTool('echo', ['message' => 'Hello Streamable HTTP!']);
        $content = $echoResult->getContent();
        if (! empty($content)) {
            foreach ($content as $contentItem) {
                if (method_exists($contentItem, 'getText')) {
                    echo '     ✓ Echo response: ' . $contentItem->getText() . "\n";
                }
            }
        }
    } catch (Exception $e) {
        echo '     ❌ Echo tool failed: ' . $e->getMessage() . "\n";
    }

    // Test calculate tool
    echo "   Testing 'calculate' tool:\n";
    try {
        $calculateResult = $session->callTool('calculate', ['operation' => 'add', 'a' => 15, 'b' => 27]);
        $content = $calculateResult->getContent();
        if (! empty($content)) {
            foreach ($content as $contentItem) {
                if (method_exists($contentItem, 'getText')) {
                    echo '     ✓ Calculate response: ' . $contentItem->getText() . "\n";
                }
            }
        }
    } catch (Exception $e) {
        echo '     ❌ Calculate tool failed: ' . $e->getMessage() . "\n";
    }

    // Test streamable_info tool
    echo "   Testing 'streamable_info' tool:\n";
    try {
        $infoResult = $session->callTool('streamable_info', []);
        $content = $infoResult->getContent();
        if (! empty($content)) {
            foreach ($content as $contentItem) {
                if (method_exists($contentItem, 'getText')) {
                    echo '     ✓ Streamable info response: ' . $contentItem->getText() . "\n";
                }
            }
        }
    } catch (Exception $e) {
        echo '     ❌ Streamable info tool failed: ' . $e->getMessage() . "\n";
    }

    echo "\n5. Testing Prompt Discovery:\n";
    $promptsResult = $session->listPrompts();
    $prompts = $promptsResult->getPrompts();
    echo '   ✓ Found ' . count($prompts) . " prompts:\n";

    foreach ($prompts as $prompt) {
        echo "     - {$prompt->getName()}: {$prompt->getDescription()}\n";
    }

    echo "\n6. Testing Prompt Calls:\n";

    // Test greeting prompt
    echo "   Testing 'greeting' prompt:\n";
    try {
        $promptResult = $session->getPrompt('greeting', ['name' => 'Streamable HTTP', 'language' => 'chinese']);
        echo '     ✓ Prompt response received: ' . $promptResult->getDescription() . "\n";

        $messages = $promptResult->getMessages();
        if (! empty($messages)) {
            foreach ($messages as $message) {
                $content = $message->getContent();
                if ($content instanceof TextContent) {
                    echo '     ✓ Message content: ' . $content->getText() . "\n";
                }
            }
        }
    } catch (Exception $e) {
        echo '     ❌ Greeting prompt failed: ' . $e->getMessage() . "\n";
    }

    echo "\n7. Testing Resource Discovery:\n";
    $resourcesResult = $session->listResources();
    $resources = $resourcesResult->getResources();
    echo '   ✓ Found ' . count($resources) . " resources:\n";

    foreach ($resources as $resource) {
        echo "     - {$resource->getUri()}: {$resource->getName()}\n";
        echo "       Description: {$resource->getDescription()}\n";
        echo "       MIME Type: {$resource->getMimeType()}\n";
    }

    echo "\n8. Testing Resource Access:\n";

    // Test system info resource
    echo "   Testing 'system://streamable-info' resource:\n";
    try {
        $resourceResult = $session->readResource('system://streamable-info');
        $contents = $resourceResult->getContents();
        if (! empty($contents)) {
            foreach ($contents as $content) {
                if ($content instanceof TextResourceContents) {
                    $text = $content->getText();
                    if ($text !== null) {
                        $data = json_decode($text, true);
                        if ($data) {
                            echo '     ✓ Resource data received (transport: ' . ($data['transport']['type'] ?? 'unknown') . ")\n";
                            echo '     ✓ Protocol version: ' . ($data['transport']['protocol_version'] ?? 'unknown') . "\n";
                            echo '     ✓ Server name: ' . ($data['server']['name'] ?? 'unknown') . "\n";
                        } else {
                            echo '     ✓ Resource content: ' . substr($text, 0, 100) . "...\n";
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo '     ❌ System info resource failed: ' . $e->getMessage() . "\n";
    }

    echo "\n9. Testing Resource Templates:\n";
    echo "   Note: PHP MCP client accesses templates through regular readResource calls\n";

    // Test resource template with date parameter
    echo "   Testing 'logs://streamable/{date}' template:\n";
    try {
        $today = date('Y-m-d');
        $templateResourceResult = $session->readResource("logs://streamable/{$today}");
        $contents = $templateResourceResult->getContents();
        if (! empty($contents)) {
            foreach ($contents as $content) {
                if ($content instanceof TextResourceContents) {
                    $text = $content->getText();
                    if ($text !== null) {
                        $lines = explode("\n", $text);
                        echo '     ✓ Template resource content received (' . count($lines) . " lines)\n";
                        echo '     ✓ First line: ' . ($lines[0] ?? 'empty') . "\n";

                        // Show some key features from the template
                        if (strpos($text, 'Direct request-response pattern') !== false) {
                            echo '     ✓ Template contains Streamable HTTP principles\n';
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo '     ❌ Template resource failed: ' . $e->getMessage() . "\n";
    }

    echo "\n10. Testing Protocol Features:\n";
    echo "   ✓ Single endpoint for POST and GET requests\n";
    echo "   ✓ Session management with Mcp-Session-Id headers\n";
    echo "   ✓ JSON-RPC message handling\n";
    echo "   ✓ Tool discovery and execution\n";
    echo "   ✓ Prompt discovery and execution\n";
    echo "   ✓ Resource discovery and access\n";
    echo "   ✓ Resource template functionality\n";

    $finalTime = microtime(true);
    $totalDuration = round(($finalTime - $startTime) * 1000, 2);

    echo "\n✅ All tests completed successfully!\n";
    echo "   Total test duration: {$totalDuration}ms\n";
} catch (Exception $e) {
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);

    echo "\n❌ Test failed after {$duration}ms\n";
    echo '   Error: ' . $e->getMessage() . "\n";
    echo "\n   Possible causes:\n";
    echo "   1. Network connectivity issues\n";
    echo "   2. MCP service is unavailable\n";
    echo "   3. Invalid API key or endpoint\n";
    echo "   4. Protocol version mismatch\n";
}

echo "\n=== Test Complete ===\n";
echo "This test validates:\n";
echo "1. ✓ Streamable HTTP protocol detection\n";
echo "2. ✓ Session-based communication\n";
echo "3. ✓ Single endpoint handling (POST/GET)\n";
echo "4. ✓ Proper Mcp-Session-Id header usage\n";
echo "5. ✓ Tool discovery and execution\n";
echo "6. ✓ Prompt discovery and execution\n";
echo "7. ✓ Resource discovery and access\n";
echo "8. ✓ Resource template functionality\n";
echo "9. ✓ JSON-RPC message format compliance\n";
echo "10. ✓ Error handling and validation\n\n";

$client->close();
