# Hyperf Framework Integration Guide

This guide will help you quickly integrate PHP MCP Server into the Hyperf framework.

## 🚀 Quick Start

### 1. Install Dependencies

```bash
composer require dtyq/php-mcp
```

### 2. Register Route

Add MCP route in your route file (e.g., `config/routes.php`):

```php
<?php
use Hyperf\HttpServer\Router\Router;
use Dtyq\PhpMcp\Server\Framework\Hyperf\HyperfMcpServer;

Router::addRoute(['POST', 'GET', 'DELETE'], '/mcp', function () {
    return \Hyperf\Context\ApplicationContext::getContainer()->get(HyperfMcpServer::class)->handler();
});
```

> **Note**: ConfigProvider is auto-loaded by Hyperf, no need to manually register it in `config/config.php`.

## 📝 Annotation-Based Registration

The easiest way to register MCP tools, prompts, and resources is using annotations. This approach automatically generates schemas from method signatures and handles registration.

### Available Annotations

#### `#[McpTool]` - Register Tools

Use the `#[McpTool]` annotation to register methods as MCP tools:

```php
<?php
declare(strict_types=1);

namespace App\Service;

use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpTool;

class CalculatorService
{
    #[McpTool]
    public function calculate(string $operation, int $a, int $b): array
    {
        $result = match ($operation) {
            'add' => $a + $b,
            'subtract' => $a - $b,
            'multiply' => $a * $b,
            'divide' => $a / $b,
            default => null,
        };

        return [
            'operation' => $operation,
            'operands' => [$a, $b],
            'result' => $result,
        ];
    }

    #[McpTool(
        name: 'advanced_calc',
        description: 'Advanced mathematical calculations',
        group: 'math'
    )]
    public function advancedCalculate(string $formula, array $variables = []): float
    {
        // Complex calculation logic
        return 42.0;
    }
}
```

**Annotation Parameters:**
- `name`: Tool name (defaults to method name)
- `description`: Tool description
- `inputSchema`: Custom input schema (auto-generated if empty)
- `group`: Tool group for organization
- `enabled`: Whether the tool is enabled (default: true)

#### `#[McpPrompt]` - Register Prompts

Use the `#[McpPrompt]` annotation to register methods as prompt templates:

```php
<?php
declare(strict_types=1);

namespace App\Service;

use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpPrompt;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

class PromptService
{
    #[McpPrompt]
    public function greeting(string $name, string $language = 'english'): GetPromptResult
    {
        $greetings = [
            'english' => "Hello, {$name}! Welcome to the Streamable HTTP MCP server!",
            'spanish' => "¡Hola, {$name}! ¡Bienvenido al servidor MCP Streamable HTTP!",
            'french' => "Bonjour, {$name}! Bienvenue sur le serveur MCP Streamable HTTP!",
            'chinese' => "你好，{$name}！欢迎使用 Streamable HTTP MCP 服务器！",
        ];

        $message = new PromptMessage(
            ProtocolConstants::ROLE_USER,
            new TextContent($greetings[$language] ?? $greetings['english'])
        );

        return new GetPromptResult('Greeting prompt', [$message]);
    }

    #[McpPrompt(
        name: 'code_review',
        description: 'Generate code review prompts',
        group: 'development'
    )]
    public function codeReview(string $code, string $language = 'php'): GetPromptResult
    {
        $prompt = "Please review the following {$language} code:\n\n```{$language}\n{$code}\n```\n\nProvide feedback on:\n- Code quality\n- Best practices\n- Potential improvements";
        
        $message = new PromptMessage(
            ProtocolConstants::ROLE_USER,
            new TextContent($prompt)
        );

        return new GetPromptResult('Code review prompt', [$message]);
    }
}
```

**Annotation Parameters:**
- `name`: Prompt name (defaults to method name)
- `description`: Prompt description
- `arguments`: Custom arguments schema (auto-generated if empty)
- `group`: Prompt group for organization
- `enabled`: Whether the prompt is enabled (default: true)

#### `#[McpResource]` - Register Resources

Use the `#[McpResource]` annotation to register methods as resource providers:

```php
<?php
declare(strict_types=1);

namespace App\Service;

use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpResource;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

class SystemService
{
    #[McpResource]
    public function systemInfo(): TextResourceContents
    {
        $info = [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'memory_usage' => memory_get_usage(true),
            'timestamp' => date('c'),
            'pid' => getmypid(),
        ];

        return new TextResourceContents(
            'mcp://system/info',
            json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'application/json'
        );
    }

    #[McpResource(
        name: 'server_config',
        uri: 'mcp://system/config',
        description: 'Server configuration data',
        mimeType: 'application/json'
    )]
    public function serverConfig(): TextResourceContents
    {
        $config = [
            'environment' => env('APP_ENV', 'production'),
            'debug' => env('APP_DEBUG', false),
            'timezone' => date_default_timezone_get(),
        ];

        return new TextResourceContents(
            'mcp://system/config',
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'application/json'
        );
    }
}
```

**Annotation Parameters:**
- `name`: Resource name (defaults to method name)
- `uri`: Resource URI (auto-generated if empty)
- `description`: Resource description
- `mimeType`: Resource MIME type
- `size`: Resource size in bytes
- `group`: Resource group for organization
- `enabled`: Whether the resource is enabled (default: true)
- `isTemplate`: Whether the resource is a template
- `uriTemplate`: URI template parameters

### Schema Auto-Generation

The annotation system automatically generates JSON schemas from method signatures:

```php
#[McpTool]
public function processUser(
    string $userId,           // Required string parameter
    int $age = 18,           // Optional integer with default value
    bool $active = true,     // Optional boolean with default value
    array $tags = []         // Optional array with default empty array
): array {
    // Implementation
}
```

This generates the following schema:
```json
{
    "type": "object",
    "properties": {
        "userId": {
            "type": "string",
            "description": "Parameter: userId"
        },
        "age": {
            "type": "integer",
            "description": "Parameter: age",
            "default": 18
        },
        "active": {
            "type": "boolean",
            "description": "Parameter: active",
            "default": true
        },
        "tags": {
            "type": "array",
            "description": "Parameter: tags",
            "items": {"type": "string"},
            "default": []
        }
    },
    "required": ["userId"]
}
```

**Supported Types:**
- `string` → `"type": "string"`
- `int`, `integer` → `"type": "integer"`
- `float`, `double` → `"type": "number"`
- `bool`, `boolean` → `"type": "boolean"`
- `array` → `"type": "array"`

> **Note**: Complex types (classes, interfaces, union types) are not supported. Only basic PHP types are allowed for automatic schema generation.

### Group-Based Registration

You can organize your annotations using groups and load specific groups:

```php
// Register only math-related tools
Router::addRoute(['POST', 'GET', 'DELETE'], '/mcp/math', function () {
    return \Hyperf\Context\ApplicationContext::getContainer()->get(HyperfMcpServer::class)->handler('math');
});

// Register development tools
Router::addRoute(['POST', 'GET', 'DELETE'], '/mcp/dev', function () {
    return \Hyperf\Context\ApplicationContext::getContainer()->get(HyperfMcpServer::class)->handler('development');
});

// Register all tools (default group)
Router::addRoute(['POST', 'GET', 'DELETE'], '/mcp', function () {
    return \Hyperf\Context\ApplicationContext::getContainer()->get(HyperfMcpServer::class)->handler();
});
```

### Complete Annotation Example

Here's a complete service class using all three annotation types:

```php
<?php
declare(strict_types=1);

namespace App\Service;

use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpTool;
use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpPrompt;
use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpResource;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;

class McpDemoService
{
    #[McpTool(description: 'Echo back a message')]
    public function echo(string $message): array
    {
        return [
            'echo' => $message,
            'timestamp' => time(),
        ];
    }

    #[McpPrompt(description: 'Generate a welcome message')]
    public function welcome(string $username): GetPromptResult
    {
        $message = new PromptMessage(
            ProtocolConstants::ROLE_USER,
            new TextContent("Welcome {$username} to our MCP server!")
        );

        return new GetPromptResult('Welcome message', [$message]);
    }

    #[McpResource(description: 'Current server status')]
    public function status(): TextResourceContents
    {
        $status = [
            'status' => 'healthy',
            'uptime' => time() - $_SERVER['REQUEST_TIME'],
            'memory' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
        ];

        return new TextResourceContents(
            'mcp://server/status',
            json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'application/json'
        );
    }
}
```

## 🔧 Advanced Configuration

### Custom Authentication

If you need custom authentication, implement `AuthenticatorInterface`:

```php
<?php
declare(strict_types=1);

namespace App\Auth;

use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Exceptions\AuthenticationError;
use Dtyq\PhpMcp\Types\Auth\AuthInfo;
use Hyperf\HttpServer\Contract\RequestInterface;

class CustomAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        protected RequestInterface $request,
    ) {
    }

    public function authenticate(): AuthInfo
    {
        $apiKey = $this->request->header('X-API-Key');
        
        // Implement your authentication logic
        if (!$this->validateApiKey($apiKey)) {
            throw new AuthenticationError('Authentication failed');
        }
        
        return AuthInfo::create(
            subject: 'user-123',
            scopes: ['read', 'write'],
            metadata: ['api_key' => $apiKey]
        );
    }
    
    private function validateApiKey(string $apiKey): bool
    {
        // Your API key validation logic
        return $apiKey === 'your-secret-api-key';
    }
}
```

Then bind it in configuration:

```php
// config/autoload/dependencies.php
return [
    \Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface::class => App\Auth\CustomAuthenticator::class,
];
```

### Dynamic Transport Metadata Management

You can listen to `HttpTransportAuthenticatedEvent` to dynamically register tools, resources and prompts:

```php
<?php
declare(strict_types=1);

namespace App\Listener;

use App\Service\UserToolService;
use Dtyq\PhpMcp\Server\Transports\Http\Event\HttpTransportAuthenticatedEvent;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
class DynamicMcpResourcesListener implements ListenerInterface
{
    public function __construct(
        protected ContainerInterface $container,
    ) {
    }

    public function listen(): array
    {
        return [
            HttpTransportAuthenticatedEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if (!$event instanceof HttpTransportAuthenticatedEvent) {
            return;
        }

        $transportMetadata = $event->getTransportMetadata();
        $authInfo = $event->getAuthInfo();

        // Get authenticated user information
        $user = $authInfo->getMetadata('user');
        $permissions = $authInfo->getMetadata('permissions', []);

        // Dynamic tool registration
        $this->registerDynamicTools($transportMetadata, $user, $permissions);
        
        // Dynamic resource registration
        $this->registerDynamicResources($transportMetadata, $user, $permissions);
        
        // Dynamic prompt registration
        $this->registerDynamicPrompts($transportMetadata, $user, $permissions);
    }

    private function registerDynamicTools($transportMetadata, $user, array $permissions): void
    {
        $toolManager = $transportMetadata->getToolManager();
        
        // Register different tools based on user permissions
        if (in_array('user_management', $permissions)) {
            $userTool = new Tool('get_user_info', [
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer'],
                ],
                'required' => ['user_id'],
            ], 'Get user information');
            
            $toolManager->register($userTool, function(array $args) use ($user) {
                // Implement tool logic
                return $this->container->get(UserToolService::class)->getUserInfo($args['user_id'], $user);
            });
        }

        if (in_array('admin', $permissions)) {
            $adminTool = new Tool('admin_operation', [
                'type' => 'object',
                'properties' => [
                    'action' => ['type' => 'string'],
                    'target' => ['type' => 'string'],
                ],
                'required' => ['action'],
            ], 'Execute admin operations');
            
            $toolManager->register($adminTool, function(array $args) {
                // Admin-specific tool logic
                return ['result' => "Admin action: {$args['action']}"];
            });
        }
    }

    private function registerDynamicResources($transportMetadata, $user, array $permissions): void
    {
        $resourceManager = $transportMetadata->getResourceManager();
        
        // Register resources based on permissions
        if (in_array('read_users', $permissions)) {
            $usersResource = new Resource('users', 'application/json', 'Users list');
            $resourceManager->register($usersResource, function() use ($user) {
                // Return users list that user has permission to access
                return json_encode(['users' => ['Alice', 'Bob']]);
            });
        }

        if (in_array('read_reports', $permissions)) {
            $reportsResource = new Resource('reports', 'application/json', 'Reports data');
            $resourceManager->register($reportsResource, function() {
                return json_encode(['reports' => ['report1', 'report2']]);
            });
        }
    }

    private function registerDynamicPrompts($transportMetadata, $user, array $permissions): void
    {
        $promptManager = $transportMetadata->getPromptManager();
        
        // Register prompt templates based on user roles
        if (in_array('content_creator', $permissions)) {
            $contentPrompt = new Prompt('create_content', [
                'type' => 'object',
                'properties' => [
                    'topic' => ['type' => 'string'],
                    'style' => ['type' => 'string'],
                ],
                'required' => ['topic'],
            ], 'Content creation prompt template');
            
            $promptManager->register($contentPrompt, function(array $args) {
                return [
                    'prompt' => "Please create content for topic '{$args['topic']}' with style: " . ($args['style'] ?? 'formal'),
                ];
            });
        }
    }
}
```

> **Tips**: 
> - Dynamic registration via event listeners is more flexible than static registration, allowing different tools and resources based on user identity, permissions, etc.
> - Annotation mechanism will be added in the future to simplify auto-registration process
> - Tools, resources and prompts all support this dynamic registration approach

### Redis Session Management Configuration

Redis is used for session management by default. Configure Redis connection in `config/autoload/redis.php`:

```php
<?php
return [
    'default' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'auth' => env('REDIS_AUTH', null),
        'port' => (int) env('REDIS_PORT', 6379),
        'db' => (int) env('REDIS_DB', 0),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
        ],
    ],
];
```

To customize session TTL, configure via dependency injection:

```php
// config/autoload/dependencies.php
use Dtyq\PhpMcp\Server\Framework\Hyperf\RedisSessionManager;
use Dtyq\PhpMcp\Server\Transports\Http\SessionManagerInterface;

return [
    SessionManagerInterface::class => function ($container) {
        return new RedisSessionManager(
            $container,
            $container->get(\Hyperf\Redis\RedisFactory::class),
            3600 // Set session TTL to 1 hour
        );
    },
];
```

## 📝 Complete Example

Here's a complete working Hyperf MCP server example:

### 1. Project Structure

```
hyperf-mcp-demo/
├── config/
│   ├── routes.php                 # Route configuration
│   └── autoload/
│       ├── dependencies.php       # Dependency injection config
│       └── redis.php              # Redis configuration
├── app/
│   ├── Auth/
│   │   └── ApiKeyAuthenticator.php # Custom authenticator
│   ├── Listener/
│   │   └── DynamicMcpListener.php  # Dynamic registration listener
│   └── Service/
│       └── UserService.php        # Business service
└── composer.json
```

### 2. Route Configuration (`config/routes.php`)

```php
<?php
use Hyperf\HttpServer\Router\Router;
use Dtyq\PhpMcp\Server\Framework\Hyperf\HyperfMcpServer;

// MCP server endpoint - just one line of code!
Router::addRoute(['POST', 'GET', 'DELETE'], '/mcp', function () {
    return \Hyperf\Context\ApplicationContext::getContainer()->get(HyperfMcpServer::class)->handler();
});
```

### 3. Custom Authenticator (`app/Auth/ApiKeyAuthenticator.php`)

```php
<?php
declare(strict_types=1);

namespace App\Auth;

use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Exceptions\AuthenticationError;
use Dtyq\PhpMcp\Types\Auth\AuthInfo;
use Hyperf\HttpServer\Contract\RequestInterface;

class ApiKeyAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        protected RequestInterface $request,
    ) {
    }

    public function authenticate(): AuthInfo
    {
        $apiKey = $this->getRequestApiKey();
        if (empty($apiKey)) {
            throw new AuthenticationError('No API key provided');
        }

        // Validate API Key
        $userInfo = $this->validateApiKey($apiKey);
        if (!$userInfo) {
            throw new AuthenticationError('Invalid API key');
        }

        return AuthInfo::create(
            subject: $userInfo['user_id'],
            scopes: $userInfo['scopes'],
            metadata: [
                'user' => $userInfo,
                'permissions' => $userInfo['permissions'],
                'api_key' => $apiKey,
            ]
        );
    }
    
    private function getRequestApiKey(): string
    {
        // Support multiple ways to pass API Key
        $apiKey = $this->request->header('authorization', $this->request->input('key', ''));
        if (empty($apiKey)) {
            // Also support X-API-Key header
            $apiKey = $this->request->header('x-api-key', '');
        }
        
        if (empty($apiKey)) {
            return '';
        }
        
        // Handle Bearer token format
        if (str_starts_with($apiKey, 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }
        
        return $apiKey;
    }
    
    private function validateApiKey(string $apiKey): ?array
    {
        // Mock API Key validation logic
        // In real projects, this should be database queries or external API calls
        $validKeys = [
            'admin-key-123' => [
                'user_id' => 'admin',
                'scopes' => ['*'],
                'permissions' => ['admin', 'user_management', 'read_users', 'read_reports'],
            ],
            'user-key-456' => [
                'user_id' => 'user1',
                'scopes' => ['read', 'write'],
                'permissions' => ['read_users'],
            ],
        ];
        
        return $validKeys[$apiKey] ?? null;
    }
}
```

### 4. Dynamic Registration Listener (`app/Listener/DynamicMcpListener.php`)

```php
<?php
declare(strict_types=1);

namespace App\Listener;

use App\Service\UserService;
use Dtyq\PhpMcp\Server\Transports\Http\Event\HttpTransportAuthenticatedEvent;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
class DynamicMcpListener implements ListenerInterface
{
    public function __construct(
        protected ContainerInterface $container,
    ) {
    }

    public function listen(): array
    {
        return [HttpTransportAuthenticatedEvent::class];
    }

    public function process(object $event): void
    {
        if (!$event instanceof HttpTransportAuthenticatedEvent) {
            return;
        }

        $transportMetadata = $event->getTransportMetadata();
        $authInfo = $event->getAuthInfo();
        
        $permissions = $authInfo->getMetadata('permissions', []);
        $userService = $this->container->get(UserService::class);

        // Dynamic tool registration
        $this->registerTools($transportMetadata, $authInfo, $permissions, $userService);
        
        // Dynamic resource registration
        $this->registerResources($transportMetadata, $authInfo, $permissions, $userService);
        
        // Dynamic prompt registration
        $this->registerPrompts($transportMetadata, $authInfo, $permissions);
    }

    private function registerTools($transportMetadata, $authInfo, array $permissions, UserService $userService): void
    {
        $toolManager = $transportMetadata->getToolManager();
        
        // Basic tool - available to all users
        $echoTool = new Tool('echo', [
            'type' => 'object',
            'properties' => ['message' => ['type' => 'string']],
            'required' => ['message']
        ], 'Echo message');
        
        $toolManager->register($echoTool, function(array $args) {
            return ['response' => $args['message'], 'timestamp' => time()];
        });

        // User management tool - requires permission
        if (in_array('user_management', $permissions)) {
            $userTool = new Tool('get_user', [
                'type' => 'object',
                'properties' => ['user_id' => ['type' => 'string']],
                'required' => ['user_id']
            ], 'Get user information');
            
            $toolManager->register($userTool, function(array $args) use ($userService, $authInfo) {
                return $userService->getUserInfo($args['user_id'], $authInfo);
            });
        }

        // Admin tool
        if (in_array('admin', $permissions)) {
            $adminTool = new Tool('admin_stats', [
                'type' => 'object',
                'properties' => [],
                'required' => []
            ], 'Get system statistics');
            
            $toolManager->register($adminTool, function(array $args) use ($userService) {
                return $userService->getSystemStats();
            });
        }
    }

    private function registerResources($transportMetadata, $authInfo, array $permissions, UserService $userService): void
    {
        $resourceManager = $transportMetadata->getResourceManager();
        
        if (in_array('read_users', $permissions)) {
            $usersResource = new Resource('users', 'application/json', 'Users list data');
            $resourceManager->register($usersResource, function() use ($userService, $authInfo) {
                return $userService->getUsersListJson($authInfo);
            });
        }

        if (in_array('read_reports', $permissions)) {
            $reportsResource = new Resource('reports', 'application/json', 'Reports data');
            $resourceManager->register($reportsResource, function() use ($userService) {
                return $userService->getReportsJson();
            });
        }
    }

    private function registerPrompts($transportMetadata, $authInfo, array $permissions): void
    {
        $promptManager = $transportMetadata->getPromptManager();
        
        // Basic prompt template
        $helpPrompt = new Prompt('help', [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ], 'Help information prompt');
        
        $promptManager->register($helpPrompt, function(array $args) use ($authInfo) {
            $userName = $authInfo->getSubject();
            return [
                'prompt' => "Hello {$userName}, I am the MCP assistant. I can help you with the following features:\n" .
                           "- echo: Echo messages\n" .
                           "- get_user: Get user information (requires permission)\n" .
                           "- admin_stats: System statistics (admin only)"
            ];
        });
    }
}
```

### 5. Business Service (`app/Service/UserService.php`)

```php
<?php
declare(strict_types=1);

namespace App\Service;

use Dtyq\PhpMcp\Types\Auth\AuthInfo;

class UserService
{
    public function getUserInfo(string $userId, AuthInfo $authInfo): array
    {
        // Mock user data
        $users = [
            'admin' => ['id' => 'admin', 'name' => 'Administrator', 'role' => 'admin'],
            'user1' => ['id' => 'user1', 'name' => 'Alice', 'role' => 'user'],
            'user2' => ['id' => 'user2', 'name' => 'Bob', 'role' => 'user'],
        ];
        
        if (!isset($users[$userId])) {
            throw new \InvalidArgumentException("User {$userId} not found");
        }
        
        return ['user' => $users[$userId]];
    }
    
    public function getUsersListJson(AuthInfo $authInfo): string
    {
        $permissions = $authInfo->getMetadata('permissions', []);
        
        // Return different user lists based on permissions
        if (in_array('admin', $permissions)) {
            $users = [
                ['id' => 'admin', 'name' => 'Administrator', 'role' => 'admin'],
                ['id' => 'user1', 'name' => 'Alice', 'role' => 'user'],
                ['id' => 'user2', 'name' => 'Bob', 'role' => 'user'],
            ];
        } else {
            $users = [
                ['id' => 'user1', 'name' => 'Alice', 'role' => 'user'],
                ['id' => 'user2', 'name' => 'Bob', 'role' => 'user'],
            ];
        }
        
        return json_encode(['users' => $users]);
    }
    
    public function getReportsJson(): string
    {
        return json_encode([
            'reports' => [
                ['id' => 1, 'title' => 'Daily Report', 'date' => date('Y-m-d')],
                ['id' => 2, 'title' => 'Weekly Report', 'date' => date('Y-m-d', strtotime('last monday'))],
            ]
        ]);
    }
    
    public function getSystemStats(): array
    {
        return [
            'stats' => [
                'total_users' => 3,
                'active_sessions' => 1,
                'uptime' => '2 hours',
                'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            ]
        ];
    }
}
```

### 6. Dependency Injection Configuration (`config/autoload/dependencies.php`)

```php
<?php
return [
    \Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface::class => \App\Auth\ApiKeyAuthenticator::class,
];
```

### 7. Testing Examples

```bash
# 1. Initialize request (using admin API key)
curl -X POST http://localhost:9501/mcp \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin-key-123" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "initialize",
    "params": {
      "protocolVersion": "2025-03-26",
      "capabilities": {},
      "clientInfo": {"name": "test-client", "version": "1.0.0"}
    }
  }'

# 2. List tools
curl -X POST http://localhost:9501/mcp \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin-key-123" \
  -H "Mcp-Session-Id: YOUR_SESSION_ID" \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/list"
  }'

# 3. Call tool
curl -X POST http://localhost:9501/mcp \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin-key-123" \
  -H "Mcp-Session-Id: YOUR_SESSION_ID" \
  -d '{
    "jsonrpc": "2.0",
    "id": 3,
    "method": "tools/call",
    "params": {
      "name": "echo",
      "arguments": {"message": "Hello Hyperf MCP!"}
    }
  }'

# 4. Read resource
curl -X POST http://localhost:9501/mcp \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin-key-123" \
  -H "Mcp-Session-Id: YOUR_SESSION_ID" \
  -d '{
    "jsonrpc": "2.0",
    "id": 4,
    "method": "resources/read",
    "params": {"uri": "users"}
  }'
```

This complete example demonstrates:
- ✅ API Key-based authentication
- ✅ Permission-based dynamic tool registration
- ✅ Session management
- ✅ Actually runnable code
- ✅ Complete testing workflow

## 🧪 Testing Your Server

Test your MCP server using cURL:

```bash
# Test tool call
curl -X POST http://localhost:9501/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/call",
    "params": {
      "name": "echo",
      "arguments": {"message": "Hello, Hyperf MCP!"}
    }
  }'
```

## 🔍 Troubleshooting

### Common Issues

1. **Redis Connection Failed**
   - Check if Redis service is running
   - Verify Redis configuration is correct

2. **Authentication Failed**
   - Ensure custom authenticator is implemented correctly
   - Check if request headers contain required authentication information

3. **Tool Not Found**
   - Ensure tools are registered correctly
   - Check if tool names match

### Debug Mode

In development environment, you can enable detailed error logging:

```php
// config/autoload/logger.php
return [
    'default' => [
        'handler' => [
            'class' => \Monolog\Handler\StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
                'level' => \Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => [
            'class' => \Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
];
```

## 📚 More Resources

- [MCP Protocol Specification](https://modelcontextprotocol.io/)
- [Hyperf Official Documentation](https://hyperf.wiki/)
- [PHP MCP Complete Documentation](../README.md) 