# FastMcp 服务器组件

**Model Context Protocol (MCP)** 服务器组件的高性能、轻量级实现，为构建强大的AI集成提供三大核心原语。

## 概述

FastMcp 实现了 [MCP 规范](https://modelcontextprotocol.io/specification/2025-03-26/server/) 定义的三个基础构建块：

| 原语 | 控制方式 | 描述 | 示例 |
|------|---------|------|------|
| **Tools** | 模型控制 | AI模型可执行的函数 | API请求、文件操作 |
| **Prompts** | 用户控制 | 交互式模板和工作流 | 斜杠命令、引导式交互 |
| **Resources** | 应用程序控制 | 上下文数据和内容 | 文件内容、数据库记录 |

## 架构设计

FastMcp 在所有三个原语中遵循一致的架构模式：

```
┌─────────────────┐    ┌──────────────────┐
│   Registered*   │    │    *Manager      │
│   (单个实例)    │    │   (集合管理)     │
├─────────────────┤    ├──────────────────┤
│ • 元数据        │    │ • 注册管理       │
│ • 可调用函数    │    │ • 发现功能       │
│ • 执行逻辑      │    │ • 执行接口       │
│ • 验证机制      │    │ • 统一管理       │
└─────────────────┘    └──────────────────┘
```

每个原语包含：
- **Registered*** 类：处理单个实例的元数据和执行
- ***Manager** 类：管理集合并提供统一的API

## 目录结构

```
FastMcp/
├── Tools/
│   ├── RegisteredTool.php    # 单个工具实例
│   └── ToolManager.php       # 工具集合管理器
├── Prompts/
│   ├── RegisteredPrompt.php  # 单个提示实例
│   └── PromptManager.php     # 提示集合管理器
├── Resources/
│   ├── RegisteredResource.php # 单个资源实例
│   └── ResourceManager.php    # 资源集合管理器
├── README.md                 # 英文文档
└── README_zh.md             # 本文件（中文文档）
```

## 组件介绍

### 🛠️ 工具 (`/Tools`)

**用途**：由模型控制的函数，AI可以执行这些函数来执行操作。

**文件**：
- `RegisteredTool.php` - 单个工具的注册和执行
- `ToolManager.php` - 工具集合管理

**使用示例**：
```php
use Dtyq\PhpMcp\Server\FastMcp\Tools\{ToolManager, RegisteredTool};
use Dtyq\PhpMcp\Types\Tools\Tool;

// 创建工具
$schema = [
    'type' => 'object',
    'properties' => [
        'query' => ['type' => 'string'],
    ],
    'required' => ['query']
];

$tool = new Tool('search', $schema, '搜索信息');
$registeredTool = new RegisteredTool($tool, function($args) {
    return "搜索结果：" . $args['query'];
});

// 注册并使用
$toolManager = new ToolManager();
$toolManager->register($registeredTool);
$result = $toolManager->execute('search', ['query' => 'MCP协议']);
```

### 📝 提示 (`/Prompts`)

**用途**：由用户控制的模板，用于引导AI交互。

**文件**：
- `RegisteredPrompt.php` - 单个提示的注册和执行
- `PromptManager.php` - 提示集合管理

**使用示例**：
```php
use Dtyq\PhpMcp\Server\FastMcp\Prompts\{PromptManager, RegisteredPrompt};
use Dtyq\PhpMcp\Types\Prompts\{Prompt, PromptArgument, GetPromptResult, PromptMessage};
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

// 创建带参数的提示
$prompt = new Prompt('code_review', '生成代码审查模板', [
    new PromptArgument('language', '编程语言', true),
    new PromptArgument('style', '审查风格', false)
]);

$registeredPrompt = new RegisteredPrompt($prompt, function($args) {
    $language = $args['language'];
    $style = $args['style'] ?? '全面';
    
    $content = "# {$language} 代码审查\n风格：{$style}";
    $message = new PromptMessage(
        ProtocolConstants::ROLE_USER,
        new TextContent($content)
    );
    
    return new GetPromptResult('代码审查模板', [$message]);
});

// 注册并使用
$promptManager = new PromptManager();
$promptManager->register($registeredPrompt);
$result = $promptManager->execute('code_review', ['language' => 'PHP']);
```

### 📊 资源 (`/Resources`)

**用途**：由应用程序控制的数据和内容，为AI提供上下文。

**文件**：
- `RegisteredResource.php` - 单个资源的注册和访问
- `ResourceManager.php` - 资源集合管理

**使用示例**：
```php
use Dtyq\PhpMcp\Server\FastMcp\Resources\{ResourceManager, RegisteredResource};
use Dtyq\PhpMcp\Types\Resources\{Resource, TextResourceContents};

// 创建资源
$resource = new Resource(
    'file:///project/config.json',
    '项目配置',
    '应用程序配置文件',
    'application/json'
);

$registeredResource = new RegisteredResource($resource, function($uri) {
    return new TextResourceContents($uri, '{"app": "MyApp"}', 'application/json');
});

// 注册并使用
$resourceManager = new ResourceManager();
$resourceManager->register($registeredResource);
$content = $resourceManager->getContent('file:///project/config.json');
echo $content->getText(); // {"app": "MyApp"}
```

## 主要特性

### ✅ 类型安全
- 严格的PHP类型声明
- 全面的验证机制
- IDE友好的接口

### ✅ 错误处理
- 为每个原语提供专用异常类
- 详细的错误消息和上下文
- 正确的错误传播

### ✅ 性能优化
- 资源懒加载
- 高效的集合管理
- 最小化开销

### ✅ 可扩展性
- 插件友好的架构
- 自定义验证支持
- 灵活的可调用模式

### ✅ MCP合规性
- 完全遵循 [MCP 2025-03-26 规范](https://modelcontextprotocol.io/specification/2025-03-26/)
- 正确的控制层次实现
- 标准数据类型和错误代码

## 测试覆盖

FastMcp 包含全面的测试覆盖：

```bash
# 运行FastMcp专用测试
vendor/bin/phpunit tests/Unit/Server/FastMcp/

# 结果：81个测试，234个断言，100%通过率
```

测试覆盖包括：
- 单个组件功能
- 管理器集合操作
- 错误处理场景
- 复杂集成模式
- MCP合规性验证

## API参考

### 管理器通用方法

所有管理器类（`ToolManager`、`PromptManager`、`ResourceManager`）都提供一致的API：

```php
// 注册
$manager->register($registeredInstance);

// 发现
$manager->has($name);           // 检查是否存在
$manager->get($name);           // 获取特定实例
$manager->getAll();             // 获取所有实例
$manager->getNames();           // 获取所有名称（Tools/Prompts）或 getUris()（Resources）
$manager->count();              // 获取总数

// 管理
$manager->remove($name);        // 移除特定实例
$manager->clear();              // 移除所有实例

// 执行
$manager->execute($name, $args); // 使用参数执行
// 或者对于资源：
$manager->getContent($uri);     // 获取资源内容
```

### 注册类通用方法

所有注册类都提供访问其元数据的方法：

```php
// 基本信息
$registered->getName();         // 获取名称/URI
$registered->getDescription();  // 获取描述
$registered->hasDescription(); // 检查是否有描述

// 类型特定方法
// 工具：
$registeredTool->getInputSchema();
$registeredTool->getAnnotations();

// 提示：
$registeredPrompt->getArguments();
$registeredPrompt->hasArguments();
$registeredPrompt->getRequiredArguments();
$registeredPrompt->getOptionalArguments();

// 资源：
$registeredResource->getMimeType();
$registeredResource->getSize();
$registeredResource->getAnnotations();
$registeredResource->hasMimeType();
$registeredResource->hasSize();
$registeredResource->hasAnnotations();
```

## 相关文档

- **MCP规范**: https://modelcontextprotocol.io/specification/2025-03-26/
- **服务器概述**: https://modelcontextprotocol.io/specification/2025-03-26/server/
- **工具文档**: https://modelcontextprotocol.io/specification/2025-03-26/server/tools
- **提示文档**: https://modelcontextprotocol.io/specification/2025-03-26/server/prompts  
- **资源文档**: https://modelcontextprotocol.io/specification/2025-03-26/server/resources

## 贡献指南

为FastMcp贡献代码时：

1. 保持既定的架构模式
2. 确保新功能的完整测试覆盖
3. 遵循现有的代码风格和文档标准
4. 验证MCP规范合规性

## 许可证

php-mcp项目的一部分。详情请查看主项目许可证。 