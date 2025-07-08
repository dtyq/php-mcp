<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

// Set timezone to Shanghai
date_default_timezone_set('Asia/Shanghai');

// Simple configuration
$config = [
    'sdk_name' => 'php-mcp-env-demo-server',
    'logging' => [
        'level' => 'info',
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

// Simple DI container implementation
$container = new class implements ContainerInterface {
    private array $services = [];

    public function __construct()
    {
        $this->services[LoggerInterface::class] = new class extends AbstractLogger {
            public function log($level, $message, array $context = []): void
            {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                file_put_contents(__DIR__ . '/../.log/env-stdio-server.log', "[{$timestamp}] {$level}: {$message}{$contextStr}\n", FILE_APPEND);
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

// Create tools for environment variable operations
function createGetEnvTool(): RegisteredTool
{
    $tool = new Tool(
        'get_env',
        [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Environment variable name to get (optional, if not provided, returns all environment variables)',
                ],
                'filter' => [
                    'type' => 'string',
                    'description' => 'Filter environment variables by prefix (optional)',
                ],
            ],
            'required' => [],
        ],
        'Get environment variable(s) from the current process'
    );

    return new RegisteredTool($tool, function (array $args): array {
        $name = $args['name'] ?? null;
        $filter = $args['filter'] ?? null;

        if ($name) {
            // Get specific environment variable
            $value = getenv($name);
            return [
                'type' => 'single',
                'name' => $name,
                'value' => $value !== false ? $value : null,
                'exists' => $value !== false,
            ];
        }
        // Get all environment variables
        $env = getenv();

        // Apply filter if provided
        if ($filter) {
            $env = array_filter($env, function ($key) use ($filter) {
                return strpos($key, $filter) === 0;
            }, ARRAY_FILTER_USE_KEY);
        }

        return [
            'type' => 'multiple',
            'filter' => $filter,
            'count' => count($env),
            'variables' => $env,
        ];
    });
}

function createSetEnvTool(): RegisteredTool
{
    $tool = new Tool(
        'set_env',
        [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Environment variable name to set',
                ],
                'value' => [
                    'type' => 'string',
                    'description' => 'Value to set for the environment variable',
                ],
            ],
            'required' => ['name', 'value'],
        ],
        'Set an environment variable in the current process'
    );

    return new RegisteredTool($tool, function (array $args): array {
        $name = $args['name'];
        $value = $args['value'];

        $oldValue = getenv($name);
        $success = putenv("{$name}={$value}");

        return [
            'name' => $name,
            'value' => $value,
            'old_value' => $oldValue !== false ? $oldValue : null,
            'success' => $success,
        ];
    });
}

function createEnvInfoTool(): RegisteredTool
{
    $tool = new Tool(
        'env_info',
        [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ],
        'Get comprehensive environment information including process details'
    );

    return new RegisteredTool($tool, function (array $args): array {
        $env = getenv();

        // Group environment variables by category
        $categories = [
            'system' => [],
            'path' => [],
            'php' => [],
            'custom' => [],
        ];

        foreach ($env as $key => $value) {
            if (in_array($key, ['HOME', 'USER', 'USERNAME', 'OS', 'SHELL', 'TERM', 'PWD', 'OLDPWD'])) {
                $categories['system'][$key] = $value;
            } elseif (strpos($key, 'PATH') !== false) {
                $categories['path'][$key] = $value;
            } elseif (strpos($key, 'PHP') === 0) {
                $categories['php'][$key] = $value;
            } else {
                $categories['custom'][$key] = $value;
            }
        }

        return [
            'process_id' => getmypid(),
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'current_user' => get_current_user(),
            'working_directory' => getcwd(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => date('c'),
            'total_env_vars' => count($env),
            'categories' => $categories,
        ];
    });
}

function createEnvSearchTool(): RegisteredTool
{
    $tool = new Tool(
        'search_env',
        [
            'type' => 'object',
            'properties' => [
                'pattern' => [
                    'type' => 'string',
                    'description' => 'Search pattern (supports wildcards * and ?)',
                ],
                'search_in' => [
                    'type' => 'string',
                    'enum' => ['keys', 'values', 'both'],
                    'description' => 'Search in keys, values, or both',
                ],
            ],
            'required' => ['pattern'],
        ],
        'Search environment variables by pattern'
    );

    return new RegisteredTool($tool, function (array $args): array {
        $pattern = $args['pattern'];
        $searchIn = $args['search_in'] ?? 'both';

        $env = getenv();
        $results = [];

        // Convert wildcard pattern to regex
        $regex = str_replace(['*', '?'], ['.*', '.'], $pattern);
        $regex = "/^{$regex}$/i";

        foreach ($env as $key => $value) {
            $keyMatch = preg_match($regex, $key);
            $valueMatch = preg_match($regex, $value);

            $match = false;
            switch ($searchIn) {
                case 'keys':
                    $match = $keyMatch;
                    break;
                case 'values':
                    $match = $valueMatch;
                    break;
                case 'both':
                default:
                    $match = $keyMatch || $valueMatch;
                    break;
            }

            if ($match) {
                $results[] = [
                    'key' => $key,
                    'value' => $value,
                    'key_match' => (bool) $keyMatch,
                    'value_match' => (bool) $valueMatch,
                ];
            }
        }

        return [
            'pattern' => $pattern,
            'search_in' => $searchIn,
            'matches_found' => count($results),
            'results' => $results,
        ];
    });
}

// Create application
$app = new Application($container, $config);

// Create and configure server
$server = new McpServer('env-demo-server', '1.0.0', $app);

// Register tools using fluent interface
$server
    ->registerTool(createGetEnvTool())
    ->registerTool(createSetEnvTool())
    ->registerTool(createEnvInfoTool())
    ->registerTool(createEnvSearchTool())
    ->stdio(); // Start stdio transport
