<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Dtyq\PhpMcp\Server\FastMcp\Prompts\RegisteredPrompt;
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResourceTemplate;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Constants\MessageConstants;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\ResourceTemplate;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use Dtyq\PhpMcp\Types\Tools\Tool;
use GuzzleHttp\Psr7\Request;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Simple session ID generator for testing.
 */
function generateSessionId(): string
{
    return 'mcp_session_' . uniqid() . '_' . bin2hex(random_bytes(8));
}

/**
 * Extract session ID from request headers.
 * @param array<string, array<string>|string> $headers
 */
function getSessionIdFromHeaders(array $headers): ?string
{
    // Check for Mcp-Session-Id header (case-insensitive)
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'mcp-session-id') {
            return is_array($value) ? $value[0] : $value;
        }
    }
    return null;
}

// Set timezone to Shanghai
date_default_timezone_set('Asia/Shanghai');

// Get request information
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$headers = getallheaders() ?: [];
$body = file_get_contents('php://input');

$request = new Request($method, $path, $headers, $body);

if ($path !== '/mcp') {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found', 'message' => 'Endpoint not found']);
    exit;
}

// Handle DELETE requests for session termination
if ($method === 'DELETE') {
    $sessionId = getSessionIdFromHeaders($headers);
    if (! $sessionId) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => [
                'code' => -32002,
                'message' => 'Missing Mcp-Session-Id header for session termination.',
            ],
        ]);
        exit;
    }

    // Log session termination
    error_log("Session terminated: {$sessionId}");

    // Return success response for session termination
    http_response_code(200);
    header('Content-Type: application/json');
    header("Mcp-Session-Id: {$sessionId}");
    echo json_encode([
        'success' => true,
        'message' => 'Session terminated successfully',
        'session_id' => $sessionId,
    ]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    header('Allow: POST, DELETE');
    echo json_encode([
        'jsonrpc' => '2.0',
        'error' => [
            'code' => -32601,
            'message' => 'Method not allowed - use POST for JSON-RPC or DELETE for session termination',
        ],
        'id' => null,
    ]);
    exit;
}

// Parse request body to determine if this is an initialize request
$requestData = json_decode($body, true);
$isInitialize = isset($requestData['method']) && $requestData['method'] === 'initialize';

// Simple session handling for testing
$sessionId = null;
if ($isInitialize) {
    // For initialize request, generate new session ID
    $sessionId = generateSessionId();
} else {
    // For other requests, just check if session header exists
    $sessionId = getSessionIdFromHeaders($headers);
    if (! $sessionId) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32002,
                'message' => 'Missing Mcp-Session-Id header. Please include session ID.',
            ],
            'id' => $requestData['id'] ?? null,
        ]);
        exit;
    }
}

// Create MCP server instance for processing
$container = createContainer();
$app = new Application($container, getConfig());
$mcpServer = new McpServer('streamable-http-test-server', '1.0.0', $app);

// Register tools, prompts, and resources
$mcpServer
    ->registerTool(createEchoTool())
    ->registerTool(createCalculatorTool())
    ->registerTool(createStreamableInfoTool($sessionId))
    ->registerPrompt(createGreetingPrompt())
    ->registerResource(createSystemInfoResource())
    ->registerTemplate(createStreamableLogTemplate());

$response = $mcpServer->http($request);
http_response_code($response->getStatusCode());

// Add Mcp-Session-Id header to response
header("Mcp-Session-Id: {$sessionId}");

// Add other headers from response
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("{$name}: {$value}");
    }
}

echo $response->getBody()->getContents();

/**
 * Create simple DI container.
 */
function createContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        /** @var array<string, mixed> */
        private array $services = [];

        public function __construct()
        {
            $this->services[LoggerInterface::class] = new class extends AbstractLogger {
                /**
                 * @param mixed $level
                 * @param mixed $message
                 * @param array<string, mixed> $context
                 */
                public function log($level, $message, array $context = []): void
                {
                    $timestamp = date('Y-m-d H:i:s');
                    $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
                    $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";

                    // Log to file
                    file_put_contents(__DIR__ . '/../.log/http-streamable-server-test.log', $logEntry, FILE_APPEND);
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
}

/**
 * Get server configuration.
 */
/**
 * @return array<string, mixed>
 */
function getConfig(): array
{
    return [
        'sdk_name' => 'php-mcp-streamable-http-test',
        'logging' => [
            'level' => 'info',
        ],
    ];
}

// Helper functions to create components (same as before)
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

function createStreamableInfoTool(string $sessionId): RegisteredTool
{
    $tool = new Tool(
        'streamable_info',
        [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ],
        'Get Streamable HTTP server information and capabilities'
    );

    return new RegisteredTool($tool, function (array $args) use ($sessionId): array {
        return [
            'transport' => 'streamable-http',
            'protocol_version' => '2025-03-26',
            'server_info' => [
                'name' => 'php-mcp-streamable-http-test',
                'version' => '1.0.0',
                'php_version' => PHP_VERSION,
            ],
            'session_info' => [
                'current_session_id' => $sessionId,
                'session_created_at' => date('c'),
                'note' => 'This is a test server with simplified session handling',
            ],
            'capabilities' => [
                'direct_request_response' => true,
                'sse_notifications' => true,
                'session_header_validation' => true,
                'simplified_testing' => true,
            ],
            'features' => [
                'no_complex_broadcasting' => true,
                'header_based_session_check' => true,
                'direct_message_routing' => true,
                'test_mode_enabled' => true,
            ],
            'runtime_info' => [
                'start_time' => date('c'),
                'process_id' => getmypid(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ],
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
            'english' => "Hello, {$name}! Welcome to the Streamable HTTP MCP server!",
            'spanish' => "¡Hola, {$name}! ¡Bienvenido al servidor MCP Streamable HTTP!",
            'french' => "Bonjour, {$name}! Bienvenue sur le serveur MCP Streamable HTTP!",
            'chinese' => "你好，{$name}！欢迎使用 Streamable HTTP MCP 服务器！",
        ];

        $greeting = $greetings[$language] ?? $greetings['english'];
        $message = new PromptMessage(MessageConstants::ROLE_USER, new TextContent($greeting));

        return new GetPromptResult("Streamable HTTP greeting for {$name}", [$message]);
    });
}

function createSystemInfoResource(): RegisteredResource
{
    $resource = new Resource(
        'system://streamable-info',
        'Streamable System Information',
        'Current system information for Streamable HTTP transport',
        'application/json'
    );

    return new RegisteredResource($resource, function (string $uri): TextResourceContents {
        $info = [
            'transport' => [
                'type' => 'streamable-http',
                'protocol_version' => '2025-03-26',
                'specification' => 'MCP 2025-03-26 Streamable HTTP',
                'features' => [
                    'direct_responses' => true,
                    'sse_streaming' => true,
                    'session_management' => 'simplified',
                    'stateless_capable' => true,
                ],
            ],
            'server' => [
                'name' => 'php-mcp-streamable-http-test',
                'version' => '1.0.0',
                'host' => '127.0.0.1',
                'port' => 8000,
                'endpoint' => '/mcp',
            ],
            'system' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'timestamp' => date('c'),
                'pid' => getmypid(),
                'uptime' => time() - ($_SERVER['REQUEST_TIME_FLOAT'] ?? time()),
            ],
            'streamable_principles' => [
                'each_request_gets_direct_response' => true,
                'no_complex_broadcasting' => true,
                'simplified_session_handling' => true,
                'optional_sse_for_notifications' => true,
            ],
        ];

        return new TextResourceContents($uri, json_encode($info, JSON_PRETTY_PRINT), 'application/json');
    });
}

function createStreamableLogTemplate(): RegisteredResourceTemplate
{
    $template = new ResourceTemplate(
        'logs://streamable/{date}',
        'Streamable HTTP Server Logs Template',
        'Access Streamable HTTP server logs for a specific date',
        'text/plain'
    );

    return new RegisteredResourceTemplate($template, function (array $parameters): TextResourceContents {
        $date = $parameters['date'] ?? date('Y-m-d');

        // Generate mock log entries demonstrating Streamable HTTP behavior
        $logEntries = [
            "[{$date} 10:30:15] INFO: Streamable HTTP server started",
            "[{$date} 10:30:16] INFO: MCP endpoint available at /mcp",
            "[{$date} 10:30:17] INFO: Direct request-response mode enabled",
            "[{$date} 10:32:45] INFO: Client request - direct response sent",
            "[{$date} 10:33:12] INFO: Tool call: echo - direct response",
            "[{$date} 10:35:23] INFO: Resource request: system://streamable-info - direct response",
            "[{$date} 10:37:44] INFO: Optional SSE stream established for notifications",
            "[{$date} 10:40:15] INFO: Health check - direct response: OK",
            "[{$date} 10:42:30] INFO: Streamable principle: no broadcasting complexity",
            "[{$date} 10:45:12] INFO: Session handling: simplified approach",
        ];

        $content = "Streamable HTTP Server Log - {$date}\n";
        $content .= str_repeat('=', 60) . "\n\n";
        $content .= "Streamable HTTP Features:\n";
        $content .= "- Direct request-response pattern\n";
        $content .= "- No complex broadcasting\n";
        $content .= "- Simplified session management\n";
        $content .= "- Optional SSE for server notifications\n\n";
        $content .= "Log Entries:\n";
        $content .= implode("\n", $logEntries) . "\n";
        $content .= "\nTotal entries: " . count($logEntries) . "\n";

        $uri = "logs://streamable/{$date}";
        return new TextResourceContents($uri, $content, 'text/plain');
    });
}
