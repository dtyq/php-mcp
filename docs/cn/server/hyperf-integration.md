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

## 📝 基于注解的注册

注册 MCP 工具、提示和资源的最简单方法是使用注解。这种方法会自动从方法签名生成 schema 并处理注册。

### 可用注解

#### `#[McpTool]` - 注册工具

使用 `#[McpTool]` 注解将方法注册为 MCP 工具：

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
        description: '高级数学计算',
        group: 'math'
    )]
    public function advancedCalculate(string $formula, array $variables = []): float
    {
        // 复杂计算逻辑
        return 42.0;
    }
}
```

**注解参数：**
- `name`: 工具名称（默认为方法名）
- `description`: 工具描述
- `inputSchema`: 自定义输入 schema（为空时自动生成）
- `group`: 工具分组，用于组织
- `enabled`: 是否启用工具（默认：true）

#### `#[McpPrompt]` - 注册提示

使用 `#[McpPrompt]` 注解将方法注册为提示模板：

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
    public function greeting(string $name, string $language = 'chinese'): GetPromptResult
    {
        $greetings = [
            'english' => "Hello, {$name}! Welcome to the Streamable HTTP MCP server!",
            'spanish' => "¡Hola, {$name}! ¡Bienvenido al servidor MCP Streamable HTTP!",
            'french' => "Bonjour, {$name}! Bienvenue sur le serveur MCP Streamable HTTP!",
            'chinese' => "你好，{$name}！欢迎使用 Streamable HTTP MCP 服务器！",
        ];

        $message = new PromptMessage(
            ProtocolConstants::ROLE_USER,
            new TextContent($greetings[$language] ?? $greetings['chinese'])
        );

        return new GetPromptResult('问候提示', [$message]);
    }

    #[McpPrompt(
        name: 'code_review',
        description: '生成代码审查提示',
        group: 'development'
    )]
    public function codeReview(string $code, string $language = 'php'): GetPromptResult
    {
        $prompt = "请审查以下 {$language} 代码：\n\n```{$language}\n{$code}\n```\n\n请提供以下方面的反馈：\n- 代码质量\n- 最佳实践\n- 潜在改进";
        
        $message = new PromptMessage(
            ProtocolConstants::ROLE_USER,
            new TextContent($prompt)
        );

        return new GetPromptResult('代码审查提示', [$message]);
    }
}
```

**注解参数：**
- `name`: 提示名称（默认为方法名）
- `description`: 提示描述
- `arguments`: 自定义参数 schema（为空时自动生成）
- `group`: 提示分组，用于组织
- `enabled`: 是否启用提示（默认：true）

#### `#[McpResource]` - 注册资源

使用 `#[McpResource]` 注解将方法注册为资源提供者：

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
        description: '服务器配置数据',
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

**注解参数：**
- `name`: 资源名称（默认为方法名）
- `uri`: 资源 URI（为空时自动生成）
- `description`: 资源描述
- `mimeType`: 资源 MIME 类型
- `size`: 资源大小（字节）
- `group`: 资源分组，用于组织
- `enabled`: 是否启用资源（默认：true）
- `isTemplate`: 是否为模板资源
- `uriTemplate`: URI 模板参数

### Schema 自动生成

注解系统会自动从方法签名生成 JSON schema：

```php
#[McpTool]
public function processUser(
    string $userId,           // 必需的字符串参数
    int $age = 18,           // 可选的整数参数，有默认值
    bool $active = true,     // 可选的布尔参数，有默认值
    array $tags = []         // 可选的数组参数，默认为空数组
): array {
    // 实现代码
}
```

这会生成以下 schema：
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

**支持的类型：**
- `string` → `"type": "string"`
- `int`, `integer` → `"type": "integer"`
- `float`, `double` → `"type": "number"`
- `bool`, `boolean` → `"type": "boolean"`
- `array` → `"type": "array"`

> **注意**: 不支持复杂类型（类、接口、联合类型）。自动 schema 生成只允许基本 PHP 类型。

### 基于分组的注册

您可以使用分组来组织注解并加载特定分组：

```php
// 只注册数学相关工具
Router::post('/mcp/math', function () {
    return di(HyperfMcpServer::class)->handler('math');
});

// 注册开发工具
Router::post('/mcp/dev', function () {
    return di(HyperfMcpServer::class)->handler('development');
});

// 注册所有工具（默认分组）
Router::post('/mcp', function () {
    return di(HyperfMcpServer::class)->handler();
});
```

### 完整注解示例

这是一个使用所有三种注解类型的完整服务类：

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
    #[McpTool(description: '回显消息')]
    public function echo(string $message): array
    {
        return [
            'echo' => $message,
            'timestamp' => time(),
        ];
    }

    #[McpPrompt(description: '生成欢迎消息')]
    public function welcome(string $username): GetPromptResult
    {
        $message = new PromptMessage(
            ProtocolConstants::ROLE_USER,
            new TextContent("欢迎 {$username} 来到我们的 MCP 服务器！")
        );

        return new GetPromptResult('欢迎消息', [$message]);
    }

    #[McpResource(description: '当前服务器状态')]
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