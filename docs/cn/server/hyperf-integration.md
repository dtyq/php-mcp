# Hyperf 框架集成指南

本指南将帮助您在 Hyperf 框架中快速集成 PHP MCP 服务器。

## 🚀 快速开始

### 1. 安装依赖

```bash
composer require dtyq/php-mcp
```

### 2. 注册路由

在路由文件中（如 `config/routes.php`）添加 MCP 路由：

```php
<?php
use Hyperf\HttpServer\Router\Router;
use Dtyq\PhpMcp\Server\Framework\Hyperf\HyperfMcpServer;

Router::post('/mcp', function () {
    return di(HyperfMcpServer::class)->handler();
});
```

> **注意**: ConfigProvider 会由 Hyperf 自动加载，无需手动注册到 `config/config.php`。

## 🔧 高级配置

### 自定义认证

如果需要自定义认证，可以实现 `AuthenticatorInterface`：

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
        
        // 实现您的认证逻辑
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
        // 您的 API 密钥验证逻辑
        return $apiKey === 'your-secret-api-key';
    }
}
```

然后在配置中绑定：

```php
// config/autoload/dependencies.php
return [
    \Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface::class => App\Auth\CustomAuthenticator::class,
];
```

### 动态传输元数据管理

您可以监听 `HttpTransportAuthenticatedEvent` 事件来动态注册工具、资源和提示：

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

        // 获取认证用户信息
        $user = $authInfo->getMetadata('user');
        $permissions = $authInfo->getMetadata('permissions', []);

        // 动态注册工具
        $this->registerDynamicTools($transportMetadata, $user, $permissions);
        
        // 动态注册资源
        $this->registerDynamicResources($transportMetadata, $user, $permissions);
        
        // 动态注册提示
        $this->registerDynamicPrompts($transportMetadata, $user, $permissions);
    }

    private function registerDynamicTools($transportMetadata, $user, array $permissions): void
    {
        $toolManager = $transportMetadata->getToolManager();
        
        // 根据用户权限注册不同的工具
        if (in_array('user_management', $permissions)) {
            $userTool = new Tool('get_user_info', [
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer'],
                ],
                'required' => ['user_id'],
            ], '获取用户信息');
            
            $toolManager->register($userTool, function(array $args) use ($user) {
                // 实现工具逻辑
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
            ], '执行管理员操作');
            
            $toolManager->register($adminTool, function(array $args) {
                // 管理员专用工具逻辑
                return ['result' => "Admin action: {$args['action']}"];
            });
        }
    }

    private function registerDynamicResources($transportMetadata, $user, array $permissions): void
    {
        $resourceManager = $transportMetadata->getResourceManager();
        
        // 根据权限注册资源
        if (in_array('read_users', $permissions)) {
            $usersResource = new Resource('users', 'application/json', '用户列表');
            $resourceManager->register($usersResource, function() use ($user) {
                // 返回用户有权限访问的用户列表
                return json_encode(['users' => ['Alice', 'Bob']]);
            });
        }

        if (in_array('read_reports', $permissions)) {
            $reportsResource = new Resource('reports', 'application/json', '报告数据');
            $resourceManager->register($reportsResource, function() {
                return json_encode(['reports' => ['report1', 'report2']]);
            });
        }
    }

    private function registerDynamicPrompts($transportMetadata, $user, array $permissions): void
    {
        $promptManager = $transportMetadata->getPromptManager();
        
        // 根据用户角色注册提示模板
        if (in_array('content_creator', $permissions)) {
            $contentPrompt = new Prompt('create_content', [
                'type' => 'object',
                'properties' => [
                    'topic' => ['type' => 'string'],
                    'style' => ['type' => 'string'],
                ],
                'required' => ['topic'],
            ], '内容创作提示模板');
            
            $promptManager->register($contentPrompt, function(array $args) {
                return [
                    'prompt' => "请为主题'{$args['topic']}'创作内容，风格：" . ($args['style'] ?? '正式'),
                ];
            });
        }
    }
}
```

> **提示**: 
> - 通过事件监听器动态注册的方式比静态注册更灵活，可以根据用户身份、权限等因素动态提供不同的工具和资源
> - 后期将会增加注解机制来简化自动注册过程
> - 工具、资源和提示都支持这种动态注册方式

### Redis 会话管理配置

默认使用 Redis 进行会话管理。您可以在 `config/autoload/redis.php` 中配置 Redis 连接：

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

如果需要自定义会话 TTL，可以通过依赖注入配置：

```php
// config/autoload/dependencies.php
use Dtyq\PhpMcp\Server\Framework\Hyperf\RedisSessionManager;
use Dtyq\PhpMcp\Server\Transports\Http\SessionManagerInterface;

return [
    SessionManagerInterface::class => function ($container) {
        return new RedisSessionManager(
            $container,
            $container->get(\Hyperf\Redis\RedisFactory::class),
            3600 // 会话 TTL 设置为 1 小时
        );
    },
];
```

## 📝 完整示例

以下是一个完整可运行的 Hyperf MCP 服务器示例：

### 1. 项目结构

```
hyperf-mcp-demo/
├── config/
│   ├── routes.php                 # 路由配置
│   └── autoload/
│       ├── dependencies.php       # 依赖注入配置
│       └── redis.php              # Redis 配置
├── app/
│   ├── Auth/
│   │   └── ApiKeyAuthenticator.php # 自定义认证器
│   ├── Listener/
│   │   └── DynamicMcpListener.php  # 动态注册监听器
│   └── Service/
│       └── UserService.php        # 业务服务
└── composer.json
```

### 2. 路由配置 (`config/routes.php`)

```php
<?php
use Hyperf\HttpServer\Router\Router;
use Dtyq\PhpMcp\Server\Framework\Hyperf\HyperfMcpServer;

// MCP 服务端点 - 只需一行代码！
Router::post('/mcp', function () {
    return di(HyperfMcpServer::class)->handler();
});
```

### 3. 自定义认证器 (`app/Auth/ApiKeyAuthenticator.php`)

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

        // 验证 API Key
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
        // 支持多种 API Key 传递方式
        $apiKey = $this->request->header('authorization', $this->request->input('key', ''));
        if (empty($apiKey)) {
            // 也支持 X-API-Key 头
            $apiKey = $this->request->header('x-api-key', '');
        }
        
        if (empty($apiKey)) {
            return '';
        }
        
        // 处理 Bearer token 格式
        if (str_starts_with($apiKey, 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }
        
        return $apiKey;
    }
    
    private function validateApiKey(string $apiKey): ?array
    {
        // 模拟 API Key 验证逻辑
        // 实际项目中，这里应该是数据库查询或外部API调用
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

### 4. 动态注册监听器 (`app/Listener/DynamicMcpListener.php`)

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

        // 动态注册工具
        $this->registerTools($transportMetadata, $authInfo, $permissions, $userService);
        
        // 动态注册资源
        $this->registerResources($transportMetadata, $authInfo, $permissions, $userService);
        
        // 动态注册提示
        $this->registerPrompts($transportMetadata, $authInfo, $permissions);
    }

    private function registerTools($transportMetadata, $authInfo, array $permissions, UserService $userService): void
    {
        $toolManager = $transportMetadata->getToolManager();
        
        // 基础工具 - 所有用户可用
        $echoTool = new Tool('echo', [
            'type' => 'object',
            'properties' => ['message' => ['type' => 'string']],
            'required' => ['message']
        ], '回显消息');
        
        $toolManager->register($echoTool, function(array $args) {
            return ['response' => $args['message'], 'timestamp' => time()];
        });

        // 用户管理工具 - 需要权限
        if (in_array('user_management', $permissions)) {
            $userTool = new Tool('get_user', [
                'type' => 'object',
                'properties' => ['user_id' => ['type' => 'string']],
                'required' => ['user_id']
            ], '获取用户信息');
            
            $toolManager->register($userTool, function(array $args) use ($userService, $authInfo) {
                return $userService->getUserInfo($args['user_id'], $authInfo);
            });
        }

        // 管理员工具
        if (in_array('admin', $permissions)) {
            $adminTool = new Tool('admin_stats', [
                'type' => 'object',
                'properties' => [],
                'required' => []
            ], '获取系统统计信息');
            
            $toolManager->register($adminTool, function(array $args) use ($userService) {
                return $userService->getSystemStats();
            });
        }
    }

    private function registerResources($transportMetadata, $authInfo, array $permissions, UserService $userService): void
    {
        $resourceManager = $transportMetadata->getResourceManager();
        
        if (in_array('read_users', $permissions)) {
            $usersResource = new Resource('users', 'application/json', '用户列表数据');
            $resourceManager->register($usersResource, function() use ($userService, $authInfo) {
                return $userService->getUsersListJson($authInfo);
            });
        }

        if (in_array('read_reports', $permissions)) {
            $reportsResource = new Resource('reports', 'application/json', '报告数据');
            $resourceManager->register($reportsResource, function() use ($userService) {
                return $userService->getReportsJson();
            });
        }
    }

    private function registerPrompts($transportMetadata, $authInfo, array $permissions): void
    {
        $promptManager = $transportMetadata->getPromptManager();
        
        // 基础提示模板
        $helpPrompt = new Prompt('help', [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ], '帮助信息提示');
        
        $promptManager->register($helpPrompt, function(array $args) use ($authInfo) {
            $userName = $authInfo->getSubject();
            return [
                'prompt' => "您好 {$userName}，我是 MCP 助手。我可以帮助您使用以下功能：\n" .
                           "- echo: 回显消息\n" .
                           "- get_user: 获取用户信息（需要权限）\n" .
                           "- admin_stats: 系统统计（管理员专用）"
            ];
        });
    }
}
```

### 5. 业务服务 (`app/Service/UserService.php`)

```php
<?php
declare(strict_types=1);

namespace App\Service;

use Dtyq\PhpMcp\Types\Auth\AuthInfo;

class UserService
{
    public function getUserInfo(string $userId, AuthInfo $authInfo): array
    {
        // 模拟用户数据
        $users = [
            'admin' => ['id' => 'admin', 'name' => '管理员', 'role' => 'admin'],
            'user1' => ['id' => 'user1', 'name' => '张三', 'role' => 'user'],
            'user2' => ['id' => 'user2', 'name' => '李四', 'role' => 'user'],
        ];
        
        if (!isset($users[$userId])) {
            throw new \InvalidArgumentException("用户 {$userId} 不存在");
        }
        
        return ['user' => $users[$userId]];
    }
    
    public function getUsersListJson(AuthInfo $authInfo): string
    {
        $permissions = $authInfo->getMetadata('permissions', []);
        
        // 根据权限返回不同的用户列表
        if (in_array('admin', $permissions)) {
            $users = [
                ['id' => 'admin', 'name' => '管理员', 'role' => 'admin'],
                ['id' => 'user1', 'name' => '张三', 'role' => 'user'],
                ['id' => 'user2', 'name' => '李四', 'role' => 'user'],
            ];
        } else {
            $users = [
                ['id' => 'user1', 'name' => '张三', 'role' => 'user'],
                ['id' => 'user2', 'name' => '李四', 'role' => 'user'],
            ];
        }
        
        return json_encode(['users' => $users]);
    }
    
    public function getReportsJson(): string
    {
        return json_encode([
            'reports' => [
                ['id' => 1, 'title' => '日报告', 'date' => date('Y-m-d')],
                ['id' => 2, 'title' => '周报告', 'date' => date('Y-m-d', strtotime('last monday'))],
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

### 6. 依赖注入配置 (`config/autoload/dependencies.php`)

```php
<?php
return [
    \Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface::class => \App\Auth\ApiKeyAuthenticator::class,
];
```

### 7. 测试示例

```bash
# 1. 初始化请求（使用管理员 API Key）
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

# 2. 列出工具
curl -X POST http://localhost:9501/mcp \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin-key-123" \
  -H "Mcp-Session-Id: YOUR_SESSION_ID" \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/list"
  }'

# 3. 调用工具
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

# 4. 获取资源
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

这个完整示例展示了：
- ✅ 基于 API Key 的认证
- ✅ 基于权限的动态工具注册
- ✅ 会话管理
- ✅ 实际可运行的代码
- ✅ 完整的测试流程

## 🧪 测试您的服务器

使用 cURL 测试您的 MCP 服务器：

```bash
# 测试工具调用
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

## 🔍 故障排除

### 常见问题

1. **Redis 连接失败**
   - 检查 Redis 服务是否运行
   - 验证 Redis 配置是否正确

2. **认证失败**
   - 确保自定义认证器正确实现
   - 检查请求头是否包含所需的认证信息

3. **工具未找到**
   - 确保工具已正确注册
   - 检查工具名称是否匹配

### 调试模式

在开发环境中，您可以启用详细的错误日志：

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

## 📚 更多资源

- [MCP 协议规范](https://modelcontextprotocol.io/)
- [Hyperf 官方文档](https://hyperf.wiki/)
- [PHP MCP 完整文档](../README.md) 