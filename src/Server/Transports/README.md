# MCP Transports

This directory contains the transport layer implementations for the PHP MCP (Model Context Protocol) server. The transport layer is responsible for handling communication between MCP clients and servers according to the MCP 2025-03-26 specification.

## 📁 Directory Structure

```
Transports/
├── Core/                          # Core transport infrastructure
│   ├── TransportInterface.php     # Base transport interface
│   ├── AbstractTransport.php      # Common transport functionality
│   ├── MessageProcessor.php       # Message processing logic
│   ├── HandlerFactory.php         # Message handler factory
│   ├── TransportMetadata.php      # Transport metadata container
│   └── Handlers/                  # Message handlers
│       ├── MessageHandlerInterface.php
│       ├── AbstractMessageHandler.php
│       ├── AbstractNotificationHandler.php
│       ├── InitializeMessageHandler.php
│       ├── InitializedNotificationMessageHandler.php
│       ├── PingMessageHandler.php
│       ├── ListToolsMessageHandler.php
│       ├── CallToolMessageHandler.php
│       ├── ListPromptsMessageHandler.php
│       ├── GetPromptMessageHandler.php
│       ├── ListResourcesMessageHandler.php
│       ├── ListResourceTemplatesMessageHandler.php
│       ├── ReadResourceMessageHandler.php
│       ├── ProgressNotificationMessageHandler.php
│       └── CancelledNotificationMessageHandler.php
└── Stdio/                         # Standard I/O transport
    ├── StdioTransport.php         # Stdio transport implementation
    └── StreamHandler.php          # Stream handling utilities
```

## 🚀 Overview

The transport layer provides a flexible and extensible architecture for MCP communication. It implements:

- **JSON-RPC 2.0** message processing
- **Request/Response** handling
- **Notification** support
- **Error handling** and validation
- **Type-safe** message routing
- **Extensible** handler system

## 🏗️ Architecture

### Core Components

#### 1. TransportInterface
The base interface that all transport implementations must follow:

```php
interface TransportInterface
{
    public function start(): void;
    public function stop(): void;
    public function isRunning(): bool;
    public function handleMessage(string $message): ?string;
    public function sendMessage(string $message): void;
}
```

#### 2. AbstractTransport
Provides common functionality for all transport implementations:

- Message validation
- Error handling
- Logging integration
- Request processing

#### 3. MessageProcessor
Handles the core message processing logic:

- JSON-RPC validation
- Message routing
- Handler execution
- Response generation

#### 4. HandlerFactory
Creates appropriate handlers for different message types:

- Method mapping
- Handler instantiation
- Type safety

### Message Handlers

The transport layer uses a handler-based architecture where each MCP method has a dedicated handler:

#### Request Handlers
- **InitializeMessageHandler**: Handle server initialization
- **PingMessageHandler**: Handle ping requests
- **ListToolsMessageHandler**: List available tools
- **CallToolMessageHandler**: Execute tool calls
- **ListPromptsMessageHandler**: List available prompts
- **GetPromptMessageHandler**: Get prompt content
- **ListResourcesMessageHandler**: List available resources
- **ListResourceTemplatesMessageHandler**: List resource templates
- **ReadResourceMessageHandler**: Read resource content

#### Notification Handlers
- **InitializedNotificationMessageHandler**: Handle initialization completion
- **ProgressNotificationMessageHandler**: Handle progress updates
- **CancelledNotificationMessageHandler**: Handle cancellation requests

## 📡 Supported Transports

### Stdio Transport

The **StdioTransport** implements communication over standard input/output streams, ideal for:

- Command-line tools
- Process spawning
- Simple integration
- Development and testing

#### Features:
- **Stream buffering** for efficient I/O
- **Message delimiting** with proper framing
- **Error recovery** and validation
- **Graceful shutdown** handling
- **Configurable timeouts**

#### Configuration:
```php
'transports' => [
    'stdio' => [
        'enabled' => true,
        'buffer_size' => 8192,
        'timeout' => 30,
        'validate_messages' => true,
    ],
]
```

## 🛠️ Usage Examples

### Basic Stdio Server

```php
use Dtyq\PhpMcp\Server\McpServer;
use Dtyq\PhpMcp\Shared\Kernel\Application;

// Create application
$app = new Application($container, $config);

// Create MCP server
$server = new McpServer('my-server', '1.0.0', $app);

// Register tools, prompts, resources
$server
    ->registerTool($myTool)
    ->registerPrompt($myPrompt)
    ->registerResource($myResource);

// Start stdio transport
$server->stdio();
```

### Custom Transport Implementation

```php
use Dtyq\PhpMcp\Server\Transports\Core\AbstractTransport;

class CustomTransport extends AbstractTransport
{
    public function start(): void
    {
        $this->running = true;
        // Custom transport initialization
    }

    public function stop(): void
    {
        $this->running = false;
        // Custom transport cleanup
    }

    public function sendMessage(string $message): void
    {
        // Custom message sending logic
    }
}
```

### Custom Message Handler

```php
use Dtyq\PhpMcp\Server\Transports\Core\Handlers\AbstractMessageHandler;

class CustomMethodHandler extends AbstractMessageHandler
{
    public function createRequest(array $request): RequestInterface
    {
        return CustomRequest::fromArray($request);
    }

    public function handle(RequestInterface $message, TransportMetadata $metadata): ?ResultInterface
    {
        // Custom handling logic
        return new CustomResult($data);
    }
}
```

## 🔧 Configuration

### Transport Metadata

The `TransportMetadata` class provides context to message handlers:

```php
$metadata = new TransportMetadata(
    name: 'my-server',
    version: '1.0.0',
    instructions: 'Server instructions',
    toolManager: $toolManager,
    promptManager: $promptManager,
    resourceManager: $resourceManager
);
```

### Handler Registration

Handlers are automatically registered based on MCP method names:

- `initialize` → `InitializeMessageHandler`
- `ping` → `PingMessageHandler`
- `tools/list` → `ListToolsMessageHandler`
- `tools/call` → `CallToolMessageHandler`
- `prompts/list` → `ListPromptsMessageHandler`
- `prompts/get` → `GetPromptMessageHandler`
- `resources/list` → `ListResourcesMessageHandler`
- `resources/templates/list` → `ListResourceTemplatesMessageHandler`
- `resources/read` → `ReadResourceMessageHandler`

## 🧪 Testing

The transport layer includes comprehensive testing:

```bash
# Run transport tests
composer test -- --filter=Transport

# Run specific transport tests
composer test -- tests/Unit/Server/Transports/
```

## 🔍 Debugging

Enable detailed logging for transport debugging:

```php
'logging' => [
    'level' => 'debug',
    'channels' => ['transport', 'stdio', 'handler'],
]
```

## 🚀 Performance

### Optimization Tips

1. **Buffer Sizing**: Adjust buffer sizes based on message volume
2. **Timeout Configuration**: Set appropriate timeouts for your use case
3. **Handler Caching**: Handlers are instantiated once and reused
4. **Stream Management**: Proper resource cleanup prevents memory leaks

### Monitoring

- Monitor message throughput
- Track handler execution times
- Watch for error rates
- Measure memory usage

## 🔮 Future Transports

The architecture supports additional transport implementations:

- **HTTP Transport**: REST/WebSocket support
- **TCP Transport**: Direct socket communication
- **IPC Transport**: Inter-process communication
- **Custom Protocols**: Domain-specific implementations

## 📚 References

- [MCP Specification 2025-03-26](https://spec.modelcontextprotocol.io/)
- [JSON-RPC 2.0 Specification](https://www.jsonrpc.org/specification)
- [PHP MCP Documentation](../../../docs/)

## 🤝 Contributing

When adding new transports or handlers:

1. Implement the required interfaces
2. Follow the existing patterns
3. Add comprehensive tests
4. Update documentation
5. Consider error scenarios
6. Validate against MCP specification 