# MCP Types Directory

This directory contains the complete implementation of Model Context Protocol (MCP) 2025-03-26 specification types for PHP. All types are organized into logical subdirectories and follow the official MCP protocol requirements.

## 📁 Directory Structure

```
Types/
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

## 🔧 Core Types (`Core/`)

Foundation types and interfaces that define the basic protocol structure:

- **`BaseTypes.php`** - Base utility functions and validation methods
- **`ProtocolConstants.php`** - Protocol constants, error codes, and method names
- **`RequestInterface.php`** - Interface for all request types
- **`ResultInterface.php`** - Interface for all response result types
- **`NotificationInterface.php`** - Interface for all notification types
- **`JsonRpcRequest.php`** - JSON-RPC 2.0 request message structure
- **`JsonRpcResponse.php`** - JSON-RPC 2.0 response message structure
- **`JsonRpcError.php`** - JSON-RPC 2.0 error structure

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
- **`EmbeddedResource.php`** - Embedded resource content
- **`Annotations.php`** - Content annotations for targeting and priority

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

## 📬 Response Types (`Responses/`)

Server-to-client response messages:

- **`InitializeResult.php`** - Initialization response with server capabilities
- **`ListResourcesResult.php`** - Resource list with pagination support
- **`ReadResourceResult.php`** - Resource content (text or binary)
- **`ListToolsResult.php`** - Tool list with pagination support
- **`CallToolResult.php`** - Tool execution result with content and error status
- **`ListPromptsResult.php`** - Prompt list with pagination support

## 🔔 Notification Types (`Notifications/`)

One-way notification messages (no response expected):

### Protocol Notifications
- **`InitializedNotification.php`** - Sent after successful initialization
- **`ProgressNotification.php`** - Progress updates for long-running operations
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

### JSON-RPC 2.0 Compliance
- Full compliance with JSON-RPC 2.0 specification
- Proper request/response ID handling
- Standard error code implementation

### Pagination Support
List operations support cursor-based pagination:
- `nextCursor` for forward navigation
- Consistent pagination interface across all list results

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

## 📋 Implementation Status

✅ **Complete MCP 2025-03-26 Core Protocol Support**
- All required request/response pairs implemented
- Full notification system
- Complete resource, tool, and prompt management
- Sampling capabilities for LLM interaction
- Proper error handling and validation

## 🔗 Related Documentation

- [MCP Specification 2025-03-26](https://modelcontextprotocol.io/specification/2025-03-26/)
- [JSON-RPC 2.0 Specification](https://www.jsonrpc.org/specification)
- Project development standards and coding guidelines

---

*This implementation provides a complete, type-safe PHP implementation of the Model Context Protocol, enabling seamless integration between LLM applications and external data sources and tools.* 