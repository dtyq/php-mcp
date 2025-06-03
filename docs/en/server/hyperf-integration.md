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

Router::post('/mcp', function () {
    return di(HyperfMcpServer::class)->handler();
});
```

> **Note**: ConfigProvider is auto-loaded by Hyperf, no need to manually register it in `config/config.php`.

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
Router::post('/mcp', function () {
    return di(HyperfMcpServer::class)->handler();
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