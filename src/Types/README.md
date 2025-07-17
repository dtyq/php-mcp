# MCP Types Directory

This directory contains the complete implementation of Model Context Protocol (MCP) 2025-03-26 specification types for PHP. All types are organized into logical subdirectories and follow the official MCP protocol requirements.

> **📖 Official Documentation**: This implementation follows the [MCP 2025-03-26 Specification](https://modelcontextprotocol.io/specification/2025-03-26/)

## 📁 Directory Structure

```
Types/
├── Auth/           # Authentication types and data structures
├── Core/           # Core protocol types and interfaces
├── Messages/       # Message types for communication
├── Content/        # Content types (text, image, embedded resources)
├── Requests/       # Request message types
├── Responses/      # Response message types  
├── Notifications/  # Notification message types
├── Resources/      # Resource-related types
├── Tools/          # Tool-related types
├── Prompts/        # Prompt-related types
└── Sampling/       # Sampling-related types
```

## 🔐 Authentication Types (`Auth/`)

Types for managing authentication context and user permissions in MCP operations:

- **`AuthInfo.php`** - Authentication information container with scope-based permissions

**Key Features:**
- **Scope-based permissions**: Fine-grained access control using string-based scopes
- **Wildcard support**: Universal access through `*` scope
- **Metadata storage**: Additional authentication context and user information
- **Expiration handling**: Time-based authentication validity
- **Type safety**: Comprehensive validation and type-safe operations

**Usage Example:**
```php
// Create authentication info with specific scopes
$authInfo = AuthInfo::create('user123', ['read', 'write'], [
    'role' => 'admin',
    'department' => 'engineering'
], time() + 3600);

// Check permissions
if ($authInfo->hasScope('read')) {
    // User can read
}

if ($authInfo->hasAllScopes(['read', 'write'])) {
    // User can read and write
}

// Anonymous universal access
$anonymous = AuthInfo::anonymous();
assert($anonymous->hasScope('any-scope') === true);
```

## 🔧 Core Types (`Core/`)

Foundation types and interfaces that define the basic protocol structure:

- **`BaseTypes.php`** - Base utility functions and validation methods
- **`ProtocolConstants.php`** - Protocol constants, error codes, method names, and transport types
- **`MessageValidator.php`** - Universal message validator with MCP stdio compliance
- **`RequestInterface.php`** - Interface for all request types
- **`ResultInterface.php`** - Interface for all response result types
- **`NotificationInterface.php`** - Interface for all notification types
- **`JsonRpcRequest.php`** - JSON-RPC 2.0 request message structure
- **`JsonRpcResponse.php`** - JSON-RPC 2.0 response message structure
- **`JsonRpcError.php`** - JSON-RPC 2.0 error structure

### Transport Type Constants

**New in latest version**: `ProtocolConstants` now includes standardized transport type definitions:

```php
// Transport types for consistent reference
ProtocolConstants::TRANSPORT_TYPE_STDIO     // 'stdio'
ProtocolConstants::TRANSPORT_TYPE_HTTP      // 'http'  
ProtocolConstants::TRANSPORT_TYPE_SSE       // 'sse'

// Utility methods
ProtocolConstants::getSupportedTransportTypes(): array
ProtocolConstants::isValidTransportType(string $type): bool
```

**Benefits:**
- **Eliminates magic strings** - No more hardcoded transport type strings
- **Type safety** - Reduces spelling errors and improves IDE support
- **Centralized management** - All transport types defined in one location
- **Future extensibility** - Easy to add new transport types

### Message Validation System

**Enhanced `MessageValidator`** provides comprehensive JSON-RPC and MCP compliance validation:

```php
// Core validation (throws ValidationError on failure)
MessageValidator::validateMessage(string $message, bool $strictMode = false): void

// Strict mode enables MCP stdio format validation
MessageValidator::validateMessage($message, true); // No embedded newlines allowed

// Convenience methods
MessageValidator::isValidMessage(string $message, bool $strictMode = false): bool
MessageValidator::getMessageInfo(string $message): array

// Granular validation methods
MessageValidator::validateUtf8(string $message): void
MessageValidator::validateStdioFormat(string $message): void
MessageValidator::validateStructure($decoded): void
```

**Key Features:**
- **UTF-8 encoding validation** - Ensures proper character encoding
- **MCP stdio format compliance** - Validates no embedded newlines in strict mode
- **JSON-RPC 2.0 structure validation** - Complete protocol compliance
- **Batch message support** - Handles both single and batch JSON-RPC messages
- **Detailed error reporting** - Comprehensive ValidationError exceptions
- **Transport-aware validation** - Different rules for different transport types

## 💬 Messages (`Messages/`)

High-level message types for protocol communication:

- **`MessageInterface.php`** - Base interface for all message types
- **`PromptMessage.php`** - Message structure for prompt templates
- **`SamplingMessage.php`** - Message structure for LLM sampling

## 📄 Content Types (`Content/`)

Content that can be included in messages and responses:

- **`ContentInterface.php`** - Base interface for all content types
- **`TextContent.php`** - Plain text content with optional annotations
- **`ImageContent.php`** - Base64-encoded image content
- **`AudioContent.php`** - Base64-encoded audio content (MCP 2025-03-26)
- **`EmbeddedResource.php`** - Embedded resource content
- **`Annotations.php`** - Content annotations for targeting and priority

### Audio Content Support

**New in MCP 2025-03-26**: Full audio content support with multiple format compatibility:

```php
// Create audio content from base64 data
$audioContent = new AudioContent($base64Data, 'audio/mpeg');

// Create from file with auto-detection
$audioContent = AudioContent::fromFile('/path/to/audio.mp3');

// Supported formats
- MP3 (audio/mpeg)
- WAV (audio/wav) 
- OGG (audio/ogg)
- M4A (audio/mp4)
- WebM (audio/webm)
- Custom audio/* types
```

## 📨 Request Types (`Requests/`)

Client-to-server request messages:

### Connection Management
- **`InitializeRequest.php`** - Initialize MCP connection with capabilities
- **`PingRequest.php`** - Connection health check

### Resource Operations
- **`ListResourcesRequest.php`** - List available resources with pagination
- **`ReadResourceRequest.php`** - Read specific resource content
- **`SubscribeRequest.php`** - Subscribe to resource update notifications
- **`UnsubscribeRequest.php`** - Unsubscribe from resource updates

### Tool Operations
- **`ListToolsRequest.php`** - List available tools with pagination
- **`CallToolRequest.php`** - Execute a tool with arguments

### Prompt Operations
- **`ListPromptsRequest.php`** - List available prompts with pagination
- **`GetPromptRequest.php`** - Get prompt template with arguments

### Completion Operations
- **`CompleteRequest.php`** - Get autocompletion suggestions (MCP 2025-03-26)

## 📬 Response Types (`Responses/`)

Server-to-client response messages:

- **`InitializeResult.php`** - Initialization response with server capabilities
- **`ListResourcesResult.php`** - Resource list with pagination support
- **`ReadResourceResult.php`** - Resource content (text or binary)
- **`ListToolsResult.php`** - Tool list with pagination support
- **`CallToolResult.php`** - Tool execution result with content and error status
- **`ListPromptsResult.php`** - Prompt list with pagination support
- **`CompleteResult.php`** - Autocompletion suggestions (MCP 2025-03-26)

## 🔔 Notification Types (`Notifications/`)

One-way notification messages (no response expected):

### Protocol Notifications
- **`InitializedNotification.php`** - Sent after successful initialization
- **`ProgressNotification.php`** - Progress updates with descriptive messages (enhanced in MCP 2025-03-26)
- **`CancelledNotification.php`** - Request cancellation notification

### Change Notifications
- **`ResourceListChangedNotification.php`** - Resource list has changed
- **`ResourceUpdatedNotification.php`** - Specific resource has been updated
- **`ToolListChangedNotification.php`** - Tool list has changed
- **`PromptListChangedNotification.php`** - Prompt list has changed

## 🗂️ Resource Types (`Resources/`)

Types for managing contextual data and content:

- **`Resource.php`** - Resource definition with metadata
- **`ResourceContents.php`** - Base class for resource content
- **`TextResourceContents.php`** - Text-based resource content
- **`BlobResourceContents.php`** - Binary resource content (base64 encoded)
- **`ResourceTemplate.php`** - Template for parameterized resources

## 🔧 Tool Types (`Tools/`)

Types for executable functions and capabilities:

- **`Tool.php`** - Tool definition with schema and metadata
- **`ToolResult.php`** - Tool execution result container
- **`ToolAnnotations.php`** - Tool metadata and behavioral hints

## 💭 Prompt Types (`Prompts/`)

Types for templated messages and workflows:

- **`Prompt.php`** - Prompt template definition
- **`PromptArgument.php`** - Prompt parameter definition
- **`PromptMessage.php`** - Individual message in prompt template
- **`GetPromptResult.php`** - Result of prompt template execution

## 🤖 Sampling Types (`Sampling/`)

Types for LLM interaction and message generation:

- **`CreateMessageRequest.php`** - Request for LLM message generation
- **`CreateMessageResult.php`** - LLM-generated message response
- **`SamplingMessage.php`** - Message structure for sampling
- **`ModelPreferences.php`** - LLM model preferences and hints
- **`ModelHint.php`** - Hints for model selection

## 🏗️ Architecture Principles

### Interface-Based Design
All types implement appropriate interfaces (`RequestInterface`, `ResultInterface`, `NotificationInterface`) ensuring consistent behavior and type safety.

### Validation & Error Handling
- All types use `ValidationError` for consistent error reporting
- Comprehensive input validation with descriptive error messages
- Type-safe construction and data access methods
- **Transport-layer validation** - Message validation handled at transport level for consistency
- **Strict mode support** - Enhanced validation for specific transport requirements (e.g., stdio)

### Constant-Based Configuration
- **Eliminates magic strings** - All protocol constants centrally defined in `ProtocolConstants`
- **Transport type standardization** - Consistent transport type references across codebase
- **IDE-friendly** - Better autocomplete and refactoring support
- **Type safety** - Compile-time checking of constant usage

### JSON-RPC 2.0 Compliance
- Full compliance with JSON-RPC 2.0 specification
- Proper request/response ID handling
- Standard error code implementation

> **📋 Reference**: [JSON-RPC 2.0 Messages](https://modelcontextprotocol.io/specification/2025-03-26/basic#messages) | [Error Handling](https://modelcontextprotocol.io/specification/2025-03-26/basic#responses)

### Pagination Support
List operations support cursor-based pagination:
- `nextCursor` for forward navigation
- Consistent pagination interface across all list results

> **📋 Reference**: [Resource Pagination](https://modelcontextprotocol.io/specification/2025-03-26/server/resources) | [Tool Pagination](https://modelcontextprotocol.io/specification/2025-03-26/server/tools)

### Extensibility
- Meta field support (`_meta`) for additional information
- Annotation system for content targeting and priority
- Flexible content type system

## 🔄 Protocol Flow Examples

### Basic Resource Access
```
Client -> ListResourcesRequest -> Server
Server -> ListResourcesResult -> Client
Client -> ReadResourceRequest -> Server  
Server -> ReadResourceResult -> Client
```

### Tool Execution
```
Client -> ListToolsRequest -> Server
Server -> ListToolsResult -> Client
Client -> CallToolRequest -> Server
Server -> CallToolResult -> Client
```

### Subscription Model
```
Client -> SubscribeRequest -> Server
Server -> (acknowledgment) -> Client
Server -> ResourceUpdatedNotification -> Client
```

## 🆕 MCP 2025-03-26 New Features

This implementation includes all major updates from the MCP 2025-03-26 specification:

### 1. Audio Content Support
- **`AudioContent.php`** - Full audio content type with base64 encoding
- Support for MP3, WAV, OGG, M4A, WebM formats
- File-based creation with auto-detection
- Size calculation and format validation

### 2. Enhanced Progress Notifications
- **Descriptive messages** - `ProgressNotification` now includes optional `message` field
- Better user experience with status descriptions
- Backward compatible with existing implementations

### 3. Completions Capability
- **`CompleteRequest.php`** - Request autocompletion suggestions
- **`CompleteResult.php`** - Return completion suggestions
- Support for prompt and resource template argument completion
- Enables better IDE-like experiences

### 4. Comprehensive Tool Annotations
- Enhanced tool metadata and behavioral hints
- Read-only vs destructive operation indicators
- Better tool discovery and safety

## 📋 Implementation Status

✅ **Complete MCP 2025-03-26 Core Protocol Support**
- All required request/response pairs implemented
- Full notification system with enhanced progress updates
- Complete resource, tool, and prompt management
- Audio content support for multimedia applications
- Autocompletion capabilities for better UX
- Sampling capabilities for LLM interaction
- Proper error handling and validation

## 🔗 Related Documentation

- [MCP Specification 2025-03-26](https://modelcontextprotocol.io/specification/2025-03-26/)
- [JSON-RPC 2.0 Specification](https://www.jsonrpc.org/specification)
- [MCP Basic Protocol](https://modelcontextprotocol.io/specification/2025-03-26/basic)
- [MCP Server Resources](https://modelcontextprotocol.io/specification/2025-03-26/server/resources)
- [MCP Server Tools](https://modelcontextprotocol.io/specification/2025-03-26/server/tools)
- [MCP Server Prompts](https://modelcontextprotocol.io/specification/2025-03-26/server/prompts)
- [MCP Client Sampling](https://modelcontextprotocol.io/specification/2025-03-26/client/sampling)
- [MCP Changelog](https://modelcontextprotocol.io/specification/2025-03-26/changelog)
- Project development standards and coding guidelines

---

*This implementation provides a complete, type-safe PHP implementation of the Model Context Protocol, enabling seamless integration between LLM applications and external data sources and tools.*