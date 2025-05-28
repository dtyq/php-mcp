# FastMcp Server Components

A high-performance, lightweight implementation of the **Model Context Protocol (MCP)** server components, providing the three core primitives for building powerful AI integrations.

## Overview

FastMcp implements the three fundamental building blocks defined in the [MCP Specification](https://modelcontextprotocol.io/specification/2025-03-26/server/):

| Primitive | Control | Description | Example |
|-----------|---------|-------------|---------|
| **Tools** | Model-controlled | Executable functions for AI models | API requests, file operations |
| **Prompts** | User-controlled | Interactive templates and workflows | Slash commands, guided interactions |
| **Resources** | Application-controlled | Contextual data and content | File contents, database records |

## Architecture

FastMcp follows a consistent architectural pattern across all three primitives:

```
┌─────────────────┐    ┌──────────────────┐
│   Registered*   │    │    *Manager      │
│   (Individual)  │    │   (Collection)   │
├─────────────────┤    ├──────────────────┤
│ • Metadata      │    │ • Registration   │
│ • Callable      │    │ • Discovery      │
│ • Execution     │    │ • Execution      │
│ • Validation    │    │ • Management     │
└─────────────────┘    └──────────────────┘
```

Each primitive consists of:
- **Registered*** class: Handles individual instances with metadata and execution
- ***Manager** class: Manages collections and provides unified APIs

## Directory Structure

```
FastMcp/
├── Tools/
│   ├── RegisteredTool.php    # Individual tool instance
│   └── ToolManager.php       # Tool collection manager
├── Prompts/
│   ├── RegisteredPrompt.php  # Individual prompt instance
│   └── PromptManager.php     # Prompt collection manager
├── Resources/
│   ├── RegisteredResource.php # Individual resource instance
│   └── ResourceManager.php    # Resource collection manager
├── README.md                 # This file (English)
└── README_zh.md             # Chinese documentation
```

## Components

### 🛠️ Tools (`/Tools`)

**Purpose**: Model-controlled functions that AI can execute to perform actions.

**Files**:
- `RegisteredTool.php` - Individual tool registration and execution
- `ToolManager.php` - Tool collection management

**Usage Example**:
```php
use Dtyq\PhpMcp\Server\FastMcp\Tools\{ToolManager, RegisteredTool};
use Dtyq\PhpMcp\Types\Tools\Tool;

// Create a tool
$schema = [
    'type' => 'object',
    'properties' => [
        'query' => ['type' => 'string'],
    ],
    'required' => ['query']
];

$tool = new Tool('search', $schema, 'Search for information');
$registeredTool = new RegisteredTool($tool, function($args) {
    return "Search results for: " . $args['query'];
});

// Register and use
$toolManager = new ToolManager();
$toolManager->register($registeredTool);
$result = $toolManager->execute('search', ['query' => 'MCP protocol']);
```

### 📝 Prompts (`/Prompts`)

**Purpose**: User-controlled templates for guiding AI interactions.

**Files**:
- `RegisteredPrompt.php` - Individual prompt registration and execution
- `PromptManager.php` - Prompt collection management

**Usage Example**:
```php
use Dtyq\PhpMcp\Server\FastMcp\Prompts\{PromptManager, RegisteredPrompt};
use Dtyq\PhpMcp\Types\Prompts\{Prompt, PromptArgument, GetPromptResult, PromptMessage};
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;

// Create a prompt with arguments
$prompt = new Prompt('code_review', 'Generate code review template', [
    new PromptArgument('language', 'Programming language', true),
    new PromptArgument('style', 'Review style', false)
]);

$registeredPrompt = new RegisteredPrompt($prompt, function($args) {
    $language = $args['language'];
    $style = $args['style'] ?? 'comprehensive';
    
    $content = "# Code Review for {$language}\nStyle: {$style}";
    $message = new PromptMessage(
        ProtocolConstants::ROLE_USER,
        new TextContent($content)
    );
    
    return new GetPromptResult('Code review template', [$message]);
});

// Register and use
$promptManager = new PromptManager();
$promptManager->register($registeredPrompt);
$result = $promptManager->execute('code_review', ['language' => 'PHP']);
```

### 📊 Resources (`/Resources`)

**Purpose**: Application-controlled data and content for AI context.

**Files**:
- `RegisteredResource.php` - Individual resource registration and access
- `ResourceManager.php` - Resource collection management

**Usage Example**:
```php
use Dtyq\PhpMcp\Server\FastMcp\Resources\{ResourceManager, RegisteredResource};
use Dtyq\PhpMcp\Types\Resources\{Resource, TextResourceContents};

// Create a resource
$resource = new Resource(
    'file:///project/config.json',
    'Project Configuration',
    'Application configuration file',
    'application/json'
);

$registeredResource = new RegisteredResource($resource, function($uri) {
    return new TextResourceContents($uri, '{"app": "MyApp"}', 'application/json');
});

// Register and use
$resourceManager = new ResourceManager();
$resourceManager->register($registeredResource);
$content = $resourceManager->getContent('file:///project/config.json');
echo $content->getText(); // {"app": "MyApp"}
```

## Key Features

### ✅ Type Safety
- Strict PHP type declarations
- Comprehensive validation
- IDE-friendly interfaces

### ✅ Error Handling
- Dedicated exception classes for each primitive
- Detailed error messages with context
- Proper error propagation

### ✅ Performance
- Lazy loading for resources
- Efficient collection management
- Minimal overhead

### ✅ Extensibility
- Plugin-friendly architecture
- Custom validation support
- Flexible callable patterns

### ✅ MCP Compliance
- Full adherence to [MCP 2025-03-26 specification](https://modelcontextprotocol.io/specification/2025-03-26/)
- Proper control hierarchy implementation
- Standard data types and error codes

## Testing

FastMcp includes comprehensive test coverage:

```bash
# Run FastMcp-specific tests
vendor/bin/phpunit tests/Unit/Server/FastMcp/

# Results: 81 tests, 234 assertions, 100% pass rate
```

Test coverage includes:
- Individual component functionality
- Manager collection operations
- Error handling scenarios
- Complex integration patterns
- MCP compliance validation

## API Reference

### Common Manager Methods

All manager classes (`ToolManager`, `PromptManager`, `ResourceManager`) provide consistent APIs:

```php
// Registration
$manager->register($registeredInstance);

// Discovery
$manager->has($name);           // Check if exists
$manager->get($name);           // Get specific instance
$manager->getAll();             // Get all instances
$manager->getNames();           // Get all names (Tools/Prompts) or getUris() (Resources)
$manager->count();              // Get total count

// Management
$manager->remove($name);        // Remove specific instance
$manager->clear();              // Remove all instances

// Execution
$manager->execute($name, $args); // Execute with arguments
// OR for Resources:
$manager->getContent($uri);     // Get resource content
```

### Common Registered Methods

All registered classes provide access to their metadata:

```php
// Basic info
$registered->getName();         // Get name/URI
$registered->getDescription();  // Get description
$registered->hasDescription(); // Check if has description

// Type-specific methods
// Tools:
$registeredTool->getInputSchema();
$registeredTool->getAnnotations();

// Prompts:
$registeredPrompt->getArguments();
$registeredPrompt->hasArguments();
$registeredPrompt->getRequiredArguments();
$registeredPrompt->getOptionalArguments();

// Resources:
$registeredResource->getMimeType();
$registeredResource->getSize();
$registeredResource->getAnnotations();
$registeredResource->hasMimeType();
$registeredResource->hasSize();
$registeredResource->hasAnnotations();
```

## Related Documentation

- **MCP Specification**: https://modelcontextprotocol.io/specification/2025-03-26/
- **Server Overview**: https://modelcontextprotocol.io/specification/2025-03-26/server/
- **Tools Documentation**: https://modelcontextprotocol.io/specification/2025-03-26/server/tools
- **Prompts Documentation**: https://modelcontextprotocol.io/specification/2025-03-26/server/prompts  
- **Resources Documentation**: https://modelcontextprotocol.io/specification/2025-03-26/server/resources

## Contributing

When contributing to FastMcp:

1. Maintain the established architectural patterns
2. Ensure full test coverage for new features
3. Follow the existing code style and documentation standards
4. Validate MCP specification compliance

## License

Part of the php-mcp project. See the main project license for details. 