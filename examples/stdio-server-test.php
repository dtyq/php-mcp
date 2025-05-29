<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Dtyq\PhpMcp\Server\FastMcp\Prompts\RegisteredPrompt;
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

// Simple configuration
$config = [
    'sdk_name' => 'php-mcp-stdio-test',
    'logging' => [
        //        'level' => 'warning',
    ],
    'transports' => [
        'stdio' => [
            'enabled' => true,
            'buffer_size' => 8192,
            'timeout' => 30,
            'validate_messages' => true,
        ],
    ],
];

// Simple DI container implementation for PHP 7.4
$container = new class implements ContainerInterface {
    private array $services = [];

    public function __construct()
    {
        $this->services[LoggerInterface::class] = new class extends AbstractLogger {
            public function log($level, $message, array $context = []): void
            {
                $timestamp = date('Y-m-d H:i:s') . rand(1, 9);
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                file_put_contents(__DIR__ . '/../.log/stdio-server-test.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
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

// Helper functions to create components
function createEchoTool(): RegisteredTool
{
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

function createCalculatorTool(): RegisteredTool
{
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

        // PHP 7.4 compatible switch statement instead of match
        switch ($operation) {
            case 'add':
                $result = $a + $b;
                break;
            case 'subtract':
                $result = $a - $b;
                break;
            case 'multiply':
                $result = $a * $b;
                break;
            case 'divide':
                if ($b == 0) {
                    throw new InvalidArgumentException('Division by zero');
                }
                $result = $a / $b;
                break;
            default:
                throw new InvalidArgumentException('Unknown operation: ' . $operation);
        }

        return [
            'operation' => $operation,
            'operands' => [$a, $b],
            'result' => $result,
        ];
    });
}

function createGreetingPrompt(): RegisteredPrompt
{
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

function createSystemInfoResource(): RegisteredResource
{
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
            'pid' => getmypid(),
        ];

        return new TextResourceContents($uri, json_encode($info, JSON_PRETTY_PRINT), 'application/json');
    });
}

// Create application
$app = new Application($container, $config);

// Create MCP server - this is the ideal usage pattern!
$server = new McpServer('stdio-test-server', '1.0.0', $app);
// Register tools using fluent interface
$server
    ->registerTool(createEchoTool())
    ->registerTool(createCalculatorTool())
    ->registerPrompt(createGreetingPrompt())
    ->registerResource(createSystemInfoResource())
    ->stdio(); // Start stdio transport
