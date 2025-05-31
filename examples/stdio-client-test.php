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

/**
 * Copyright (c) The Magic , Distributed under the software license.
 */
require_once __DIR__ . '/../vendor/autoload.php';

// Set timezone to Shanghai
date_default_timezone_set('Asia/Shanghai');

// Simple DI container implementation for PHP 7.4
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

                file_put_contents(__DIR__ . '/../.log/stdio-client-test.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
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

$config = [
    'sdk_name' => 'php-mcp-stdio-test',
];

// Create application
$app = new Application($container, $config);

// Create client with Application
$client = new McpClient('stdio-test-client', '1.0.0', $app);

echo "=== PHP MCP Client Comprehensive Demo ===\n";

// Connect to server
echo "1. Connecting to MCP server...\n";
$session = $client->connect('stdio', [
    'command' => 'php',
    'args' => [__DIR__ . '/stdio-server-test.php'],
]);
$session->initialize();
echo "   ✓ Connected and initialized\n";

// Test Tools
echo "\n2. Testing Tools:\n";
try {
    // List available tools
    $tools = $session->listTools();
    echo '   Available tools: ' . count($tools->getTools()) . "\n";
    foreach ($tools->getTools() as $tool) {
        echo '   - ' . $tool->getName() . ': ' . $tool->getDescription() . "\n";
    }

    // Test echo tool
    echo "\n   Testing 'echo' tool:\n";
    $echoResult = $session->callTool('echo', ['message' => 'Hello from PHP MCP Client!']);
    echo '   Result: ' . json_encode($echoResult->getContent()) . "\n";

    // Test calculator tool
    echo "\n   Testing 'calculate' tool:\n";
    $calcResult = $session->callTool('calculate', [
        'operation' => 'add',
        'a' => 15,
        'b' => 27,
    ]);
    echo '   Result: ' . json_encode($calcResult->getContent()) . "\n";
} catch (Exception $e) {
    echo '   ✗ Tools test failed: ' . $e->getMessage() . "\n";
}

// Test Prompts
echo "\n3. Testing Prompts:\n";
try {
    // List available prompts
    $prompts = $session->listPrompts();
    echo '   Available prompts: ' . count($prompts->getPrompts()) . "\n";
    foreach ($prompts->getPrompts() as $prompt) {
        echo '   - ' . $prompt->getName() . ': ' . $prompt->getDescription() . "\n";
    }

    // Test greeting prompt
    echo "\n   Testing 'greeting' prompt:\n";
    $greetingResult = $session->getPrompt('greeting', [
        'name' => 'Alice',
        'language' => 'spanish',
    ]);
    echo '   Description: ' . ($greetingResult->getDescription() ?? 'No description') . "\n";
    foreach ($greetingResult->getMessages() as $message) {
        $content = $message->getContent();
        if ($content instanceof TextContent) {
            echo '   Message: ' . $content->getText() . "\n";
        }
    }
} catch (Exception $e) {
    echo '   ✗ Prompts test failed: ' . $e->getMessage() . "\n";
}

// Test Resources
echo "\n4. Testing Resources:\n";
try {
    // List available resources
    $resources = $session->listResources();
    echo '   Available resources: ' . count($resources->getResources()) . "\n";
    foreach ($resources->getResources() as $resource) {
        echo '   - ' . $resource->getUri() . ': ' . $resource->getName() . "\n";
    }

    // Test system info resource
    echo "\n   Reading 'system://info' resource:\n";
    $sysInfoResult = $session->readResource('system://info');
    foreach ($sysInfoResult->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $jsonText = $content->getText();
            if ($jsonText !== null) {
                $data = json_decode($jsonText, true);
                if (is_array($data)) {
                    echo "   System Info:\n";
                    echo '     - PHP Version: ' . ($data['php_version'] ?? 'unknown') . "\n";
                    echo '     - OS: ' . ($data['os'] ?? 'unknown') . "\n";
                    echo '     - Memory Usage: ' . number_format((float) ($data['memory_usage'] ?? 0)) . " bytes\n";
                    echo '     - PID: ' . ($data['pid'] ?? 'unknown') . "\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo '   ✗ Resources test failed: ' . $e->getMessage() . "\n";
}

// Test Resource Templates
echo "\n5. Testing Resource Templates:\n";
try {
    // Test user profile template
    echo "   Reading user profile template (user://admin/profile):\n";
    $userProfileResult = $session->readResource('user://admin/profile');
    foreach ($userProfileResult->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $jsonText = $content->getText();
            if ($jsonText !== null) {
                $profile = json_decode($jsonText, true);
                if (is_array($profile)) {
                    echo '     - User ID: ' . ($profile['userId'] ?? 'unknown') . "\n";
                    echo '     - Username: ' . ($profile['username'] ?? 'unknown') . "\n";
                    echo '     - Role: ' . ($profile['role'] ?? 'unknown') . "\n";
                    $preferences = $profile['preferences'] ?? [];
                    echo '     - Theme: ' . (is_array($preferences) ? ($preferences['theme'] ?? 'unknown') : 'unknown') . "\n";
                }
            }
        }
    }

    // Test configuration template
    echo "\n   Reading configuration template (config://database/production):\n";
    $configResult = $session->readResource('config://database/production');
    foreach ($configResult->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $jsonText = $content->getText();
            if ($jsonText !== null) {
                $config = json_decode($jsonText, true);
                if (is_array($config)) {
                    echo '     - Module: ' . ($config['module'] ?? 'unknown') . "\n";
                    echo '     - Environment: ' . ($config['environment'] ?? 'unknown') . "\n";
                    $settings = $config['settings'] ?? [];
                    if (is_array($settings)) {
                        echo '     - Debug: ' . (($settings['debug'] ?? false) ? 'enabled' : 'disabled') . "\n";
                        echo '     - API Endpoint: ' . ($settings['api_endpoint'] ?? 'unknown') . "\n";
                    }
                }
            }
        }
    }

    // Test documentation template
    echo "\n   Reading documentation template (docs://api/authentication):\n";
    $docsResult = $session->readResource('docs://api/authentication');
    foreach ($docsResult->getContents() as $content) {
        if ($content instanceof TextResourceContents) {
            $text = $content->getText();
            if ($text !== null) {
                $lines = explode("\n", $text);
                echo '     - Title: ' . trim($lines[0] ?? '', '# ') . "\n";
                echo '     - Content preview: ' . substr($text, 0, 150) . "...\n";
            }
        }
    }
} catch (Exception $e) {
    echo '   ✗ Resource templates test failed: ' . $e->getMessage() . "\n";
}

// Display session statistics
echo "\n6. Session Summary:\n";
$stats = $client->getStats();
$statsArray = $stats->toArray();
echo '   Connection attempts: ' . $stats->getConnectionAttempts() . "\n";
echo '   Connection errors: ' . $stats->getConnectionErrors() . "\n";
echo '   Status: ' . $stats->getStatus() . "\n";
echo '   Session ID: ' . $session->getSessionId() . "\n";

// Close client
echo "\n7. Closing session...\n";
$client->close();
echo "   ✓ Session closed\n";

echo "\n=== Demo completed successfully ===\n";
