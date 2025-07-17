<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Configuration;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Constants\TransportTypes;

/**
 * Configuration for HTTP transport.
 * HTTP 传输的配置。
 *
 * This class holds all HTTP-specific configuration options including
 * protocol version, authentication, event replay, timeouts, and other
 * transport-specific settings for MCP Streamable HTTP client.
 * 此类包含所有 HTTP 特定的配置选项，包括协议版本、身份验证、事件重放、超时和其他
 * MCP 流式 HTTP 客户端的传输特定设置。
 */
class HttpConfig implements TransportConfigInterface
{
    /**
     * Server base URL.
     * 服务器基础 URL。
     *
     * The base URL of the MCP server to connect to. This is required for HTTP transport.
     * Should include the protocol (http:// or https://) and can include a port number.
     * 要连接的 MCP 服务器的基础 URL。这是 HTTP 传输所必需的。
     * 应包括协议（http:// 或 https://）并可以包括端口号。
     */
    private string $baseUrl;

    /**
     * Request timeout in seconds.
     * 请求超时时间（秒）。
     *
     * Maximum time to wait for a single HTTP request to complete.
     * This applies to regular API requests, not SSE streams.
     * 单个 HTTP 请求完成的最大等待时间。这适用于常规 API 请求，而不是 SSE 流。
     */
    private float $timeout;

    /**
     * SSE stream timeout in seconds.
     * SSE 流超时时间（秒）。
     *
     * Maximum time to wait for Server-Sent Events stream to remain active.
     * Longer timeouts allow for more persistent connections.
     * 服务器发送事件流保持活动状态的最大等待时间。较长的超时允许更持久的连接。
     */
    private float $sseTimeout;

    /**
     * Maximum retry attempts.
     * 最大重试次数。
     *
     * Number of times to retry failed requests before giving up.
     * Set to 0 to disable retries.
     * 在放弃之前重试失败请求的次数。设置为 0 以禁用重试。
     */
    private int $maxRetries;

    /**
     * Initial retry delay in seconds.
     * 初始重试延迟（秒）。
     *
     * Base delay between retry attempts. The actual delay may increase
     * exponentially with each retry (exponential backoff).
     * 重试尝试之间的基本延迟。实际延迟可能随着每次重试呈指数增长（指数退避）。
     */
    private float $retryDelay;

    /**
     * SSL certificate validation.
     * SSL 证书验证。
     *
     * Whether to validate SSL certificates when connecting to HTTPS servers.
     * Set to false only for testing with self-signed certificates.
     * 连接到 HTTPS 服务器时是否验证 SSL 证书。
     * 仅在使用自签名证书进行测试时设置为 false。
     */
    private bool $validateSsl;

    /**
     * User agent string.
     * 用户代理字符串。
     *
     * The User-Agent header sent with HTTP requests to identify the client.
     * Can be customized to include application-specific information.
     * 与 HTTP 请求一起发送的 User-Agent 头部，用于标识客户端。
     * 可以自定义以包含应用程序特定信息。
     */
    private string $userAgent;

    /**
     * Custom headers.
     * 自定义头部。
     *
     * Additional HTTP headers to send with requests.
     * Useful for authentication, API keys, or other custom requirements.
     * 与请求一起发送的附加 HTTP 头部。
     * 对于身份验证、API 密钥或其他自定义要求很有用。
     *
     * @var array<string, string>
     */
    private array $headers;

    /**
     * Authentication configuration.
     * 身份验证配置。
     *
     * Configuration for HTTP authentication. Set to null to disable authentication.
     * The HttpAuthenticator class processes this configuration and adds appropriate
     * HTTP headers to requests based on the authentication type.
     * HTTP 身份验证配置。设置为 null 以禁用身份验证。
     * HttpAuthenticator 类处理此配置并根据身份验证类型向请求添加适当的 HTTP 头部。
     *
     * Authentication Processing Flow:
     * 身份验证处理流程：
     * 1. HttpAuthenticator::addAuthHeaders() gets this config via getAuth()
     * 2. Based on 'type' field, calls appropriate private method
     * 3. Validates required parameters and generates HTTP headers
     * 4. Returns updated headers array for HTTP requests
     * 1. HttpAuthenticator::addAuthHeaders() 通过 getAuth() 获取此配置
     * 2. 根据 'type' 字段调用相应的私有方法
     * 3. 验证必需参数并生成 HTTP 头部
     * 4. 返回用于 HTTP 请求的更新头部数组
     *
     * Bearer Token Authentication:
     * Bearer 令牌身份验证：
     * Config: ['type' => 'bearer', 'token' => 'your-token']
     * Processing: addBearerAuth() validates token exists and is string
     * Result: Adds "Authorization: Bearer {token}" header
     * 配置：['type' => 'bearer', 'token' => 'your-token']
     * 处理：addBearerAuth() 验证令牌存在且为字符串
     * 结果：添加 "Authorization: Bearer {token}" 头部
     *
     * Basic Authentication:
     * 基本身份验证：
     * Config: ['type' => 'basic', 'username' => 'user', 'password' => 'pass']
     * Processing: addBasicAuth() validates username/password, base64 encodes
     * Result: Adds "Authorization: Basic {base64(username:password)}" header
     * 配置：['type' => 'basic', 'username' => 'user', 'password' => 'pass']
     * 处理：addBasicAuth() 验证用户名/密码，进行 base64 编码
     * 结果：添加 "Authorization: Basic {base64(username:password)}" 头部
     *
     * OAuth2 Authentication (with caching):
     * OAuth2 身份验证（带缓存）：
     * Config: ['type' => 'oauth2', 'client_id' => 'id', 'client_secret' => 'secret', 'access_token' => 'token']
     * Processing: addOAuth2Auth() checks cache first, then getOAuth2Token()
     * Result: Adds "Authorization: Bearer {oauth2-token}" header, caches token
     * 配置：['type' => 'oauth2', 'client_id' => 'id', 'client_secret' => 'secret', 'access_token' => 'token']
     * 处理：addOAuth2Auth() 首先检查缓存，然后 getOAuth2Token()
     * 结果：添加 "Authorization: Bearer {oauth2-token}" 头部，缓存令牌
     *
     * Custom Authentication (multiple headers):
     * 自定义身份验证（多个头部）：
     * Config: ['type' => 'custom', 'headers' => ['X-API-Key' => 'key', 'X-Auth' => 'value']]
     * Processing: addCustomAuth() validates headers array, iterates key-value pairs
     * Result: Adds all custom headers to request (X-API-Key, X-Auth, etc.)
     * 配置：['type' => 'custom', 'headers' => ['X-API-Key' => 'key', 'X-Auth' => 'value']]
     * 处理：addCustomAuth() 验证头部数组，迭代键值对
     * 结果：将所有自定义头部添加到请求中（X-API-Key、X-Auth 等）
     *
     * Error Handling:
     * 错误处理：
     * - Missing required fields throw TransportError
     * - Invalid parameter types throw TransportError
     * - Unsupported auth types throw TransportError
     * - OAuth2 token acquisition failures throw TransportError
     * - 缺少必需字段抛出 TransportError
     * - 无效参数类型抛出 TransportError
     * - 不支持的身份验证类型抛出 TransportError
     * - OAuth2 令牌获取失败抛出 TransportError
     *
     * @var null|array<string, mixed>
     */
    private ?array $auth;

    /**
     * Protocol version.
     * 协议版本。
     *
     * MCP protocol version to use. 'auto' will negotiate the best version.
     * Specific versions can be forced for compatibility testing.
     * 要使用的 MCP 协议版本。'auto' 将协商最佳版本。
     * 可以强制使用特定版本进行兼容性测试。
     */
    private string $protocolVersion;

    /**
     * Enable event replay mechanism.
     * 启用事件重放机制。
     *
     * Whether to enable event replay for connection resumption.
     * This allows recovering from temporary disconnections.
     * 是否启用事件重放以便连接恢复。这允许从临时断开连接中恢复。
     */
    private bool $enableResumption;

    /**
     * Event store type.
     * 事件存储类型。
     *
     * Type of storage to use for event replay: 'memory', 'file', or 'redis'.
     * Memory is fastest but not persistent across restarts.
     * 用于事件重放的存储类型：'memory'、'file' 或 'redis'。
     * 内存是最快的，但在重启后不持久。
     */
    private string $eventStoreType;

    /**
     * Event store configuration.
     * 事件存储配置。
     *
     * Configuration specific to the chosen event store type.
     * For file: path, max_size. For redis: host, port, database.
     * 特定于所选事件存储类型的配置。
     * 对于文件：路径、最大大小。对于 redis：主机、端口、数据库。
     *
     * @var array<string, mixed>
     */
    private array $eventStoreConfig;

    /**
     * Use JSON response instead of SSE.
     * 使用 JSON 响应而不是 SSE。
     *
     * Whether to use JSON response mode instead of Server-Sent Events.
     * JSON mode is simpler but doesn't support real-time streaming.
     * 是否使用 JSON 响应模式而不是服务器发送事件。
     * JSON 模式更简单但不支持实时流。
     */
    private bool $jsonResponseMode;

    /**
     * Send termination request on close.
     * 关闭时发送终止请求。
     *
     * Whether to send a termination request to the server when closing the connection.
     * This allows for graceful cleanup of server resources.
     * 关闭连接时是否向服务器发送终止请求。
     * 这允许优雅地清理服务器资源。
     */
    private bool $terminateOnClose;

    /**
     * Constructor for HTTP configuration.
     * HTTP 配置构造函数。
     *
     * @param string $baseUrl Server base URL / 服务器基础 URL
     * @param float $timeout Request timeout in seconds / 请求超时时间（秒）
     * @param float $sseTimeout SSE stream timeout in seconds / SSE 流超时时间（秒）
     * @param int $maxRetries Maximum retry attempts / 最大重试次数
     * @param float $retryDelay Initial retry delay in seconds / 初始重试延迟（秒）
     * @param bool $validateSsl SSL certificate validation / SSL 证书验证
     * @param string $userAgent User agent string / 用户代理字符串
     * @param array<string, string> $headers Custom headers / 自定义头部
     * @param null|array<string, mixed> $auth Authentication configuration (see property docs for format) / 身份验证配置（格式见属性文档）
     * @param string $protocolVersion Protocol version / 协议版本
     * @param bool $enableResumption Enable event replay mechanism / 启用事件重放机制
     * @param string $eventStoreType Event store type / 事件存储类型
     * @param array<string, mixed> $eventStoreConfig Event store configuration / 事件存储配置
     * @param bool $jsonResponseMode Use JSON response instead of SSE / 使用 JSON 响应而不是 SSE
     * @param bool $terminateOnClose Send termination request on close / 关闭时发送终止请求
     */
    public function __construct(
        string $baseUrl,
        float $timeout = 30.0,
        float $sseTimeout = 300.0,
        int $maxRetries = 3,
        float $retryDelay = 1.0,
        bool $validateSsl = true,
        string $userAgent = 'php-mcp-client/1.0',
        array $headers = [],
        ?array $auth = null,
        string $protocolVersion = 'auto',
        bool $enableResumption = true,
        string $eventStoreType = 'memory',
        array $eventStoreConfig = [],
        bool $jsonResponseMode = false,
        bool $terminateOnClose = true
    ) {
        $this->setBaseUrl($baseUrl);
        $this->setTimeout($timeout);
        $this->setSseTimeout($sseTimeout);
        $this->setMaxRetries($maxRetries);
        $this->setRetryDelay($retryDelay);
        $this->setValidateSsl($validateSsl);
        $this->setUserAgent($userAgent);
        $this->setHeaders($headers);
        $this->setAuth($auth);
        $this->setProtocolVersion($protocolVersion);
        $this->setEnableResumption($enableResumption);
        $this->setEventStoreType($eventStoreType);
        $this->setEventStoreConfig($eventStoreConfig);
        $this->setJsonResponseMode($jsonResponseMode);
        $this->setTerminateOnClose($terminateOnClose);
    }

    /**
     * Create HTTP configuration instance with named parameters.
     * 使用命名参数创建 HTTP 配置实例。
     *
     * This static factory method provides an alternative way to create configuration
     * instances with the same parameters as the constructor. Useful for fluent APIs
     * or when you prefer static factory methods over constructors.
     * 此静态工厂方法提供了使用与构造函数相同参数创建配置实例的替代方法。
     * 适用于流畅的 API 或当您更喜欢静态工厂方法而不是构造函数时。
     *
     * @param string $baseUrl Server base URL / 服务器基础 URL
     * @param float $timeout Request timeout in seconds / 请求超时时间（秒）
     * @param float $sseTimeout SSE stream timeout in seconds / SSE 流超时时间（秒）
     * @param int $maxRetries Maximum retry attempts / 最大重试次数
     * @param float $retryDelay Initial retry delay in seconds / 初始重试延迟（秒）
     * @param bool $validateSsl SSL certificate validation / SSL 证书验证
     * @param string $userAgent User agent string / 用户代理字符串
     * @param array<string, string> $headers Custom headers / 自定义头部
     * @param null|array<string, mixed> $auth Authentication configuration (see property docs for format) / 身份验证配置（格式见属性文档）
     * @param string $protocolVersion Protocol version / 协议版本
     * @param bool $enableResumption Enable event replay mechanism / 启用事件重放机制
     * @param string $eventStoreType Event store type / 事件存储类型
     * @param array<string, mixed> $eventStoreConfig Event store configuration / 事件存储配置
     * @param bool $jsonResponseMode Use JSON response instead of SSE / 使用 JSON 响应而不是 SSE
     * @param bool $terminateOnClose Send termination request on close / 关闭时发送终止请求
     * @return self New configuration instance / 新的配置实例
     */
    public static function create(
        string $baseUrl,
        float $timeout = 30.0,
        float $sseTimeout = 300.0,
        int $maxRetries = 3,
        float $retryDelay = 1.0,
        bool $validateSsl = true,
        string $userAgent = 'php-mcp-client/1.0',
        array $headers = [],
        ?array $auth = null,
        string $protocolVersion = 'auto',
        bool $enableResumption = true,
        string $eventStoreType = 'memory',
        array $eventStoreConfig = [],
        bool $jsonResponseMode = false,
        bool $terminateOnClose = true
    ): self {
        return new self(
            $baseUrl,
            $timeout,
            $sseTimeout,
            $maxRetries,
            $retryDelay,
            $validateSsl,
            $userAgent,
            $headers,
            $auth,
            $protocolVersion,
            $enableResumption,
            $eventStoreType,
            $eventStoreConfig,
            $jsonResponseMode,
            $terminateOnClose
        );
    }

    /**
     * Create configuration from array data.
     * 从数组数据创建配置。
     *
     * This method allows creating configuration instances from associative arrays,
     * commonly used for loading configuration from files, environment variables,
     * or other external sources. Missing keys will use default values.
     * 此方法允许从关联数组创建配置实例，通常用于从文件、环境变量或其他外部源加载配置。
     * 缺少的键将使用默认值。
     *
     * @param array<string, mixed> $config Configuration array / 配置数组
     * @return self New configuration instance / 新的配置实例
     * @throws ValidationError If configuration is invalid / 如果配置无效
     */
    public static function fromArray(array $config): self
    {
        // Base URL is required and must be provided
        if (! isset($config['base_url'])) {
            throw ValidationError::requiredFieldMissing('base_url');
        }

        // Use default values directly from constructor
        return new self(
            $config['base_url'],
            $config['timeout'] ?? 30.0,
            $config['sse_timeout'] ?? 300.0,
            $config['max_retries'] ?? 3,
            $config['retry_delay'] ?? 1.0,
            $config['validate_ssl'] ?? true,
            $config['user_agent'] ?? 'php-mcp-client/1.0',
            $config['headers'] ?? [],
            $config['auth'] ?? null,
            $config['protocol_version'] ?? 'auto',
            $config['enable_resumption'] ?? true,
            $config['event_store_type'] ?? 'memory',
            $config['event_store_config'] ?? [],
            $config['json_response_mode'] ?? false,
            $config['terminate_on_close'] ?? true
        );
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'sse_timeout' => $this->sseTimeout,
            'max_retries' => $this->maxRetries,
            'retry_delay' => $this->retryDelay,
            'validate_ssl' => $this->validateSsl,
            'user_agent' => $this->userAgent,
            'headers' => $this->headers,
            'auth' => $this->auth,
            'protocol_version' => $this->protocolVersion,
            'enable_resumption' => $this->enableResumption,
            'event_store_type' => $this->eventStoreType,
            'event_store_config' => $this->eventStoreConfig,
            'json_response_mode' => $this->jsonResponseMode,
            'terminate_on_close' => $this->terminateOnClose,
        ];
    }

    // Getters
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function getSseTimeout(): float
    {
        return $this->sseTimeout;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getRetryDelay(): float
    {
        return $this->retryDelay;
    }

    public function getValidateSsl(): bool
    {
        return $this->validateSsl;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return null|array<string, mixed>
     */
    public function getAuth(): ?array
    {
        return $this->auth;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function isResumptionEnabled(): bool
    {
        return $this->enableResumption;
    }

    public function getEventStoreType(): string
    {
        return $this->eventStoreType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventStoreConfig(): array
    {
        return $this->eventStoreConfig;
    }

    public function isJsonResponseMode(): bool
    {
        return $this->jsonResponseMode;
    }

    public function shouldTerminateOnClose(): bool
    {
        return $this->terminateOnClose;
    }

    /**
     * Get the transport type identifier.
     * 获取传输类型标识符。
     *
     * @return string Transport type identifier / 传输类型标识符
     */
    public function getTransportType(): string
    {
        return TransportTypes::TRANSPORT_TYPE_HTTP;
    }

    // Setters
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function setTimeout(float $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function setSseTimeout(float $sseTimeout): void
    {
        $this->sseTimeout = $sseTimeout;
    }

    public function setMaxRetries(int $maxRetries): void
    {
        $this->maxRetries = $maxRetries;
    }

    public function setRetryDelay(float $retryDelay): void
    {
        $this->retryDelay = $retryDelay;
    }

    public function setValidateSsl(bool $validateSsl): void
    {
        $this->validateSsl = $validateSsl;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @param null|array<string, mixed> $auth
     */
    public function setAuth(?array $auth): void
    {
        $this->auth = $auth;
    }

    public function setProtocolVersion(string $protocolVersion): void
    {
        $this->protocolVersion = $protocolVersion;
    }

    public function setEnableResumption(bool $enableResumption): void
    {
        $this->enableResumption = $enableResumption;
    }

    public function setEventStoreType(string $eventStoreType): void
    {
        $this->eventStoreType = $eventStoreType;
    }

    /**
     * @param array<string, mixed> $eventStoreConfig
     */
    public function setEventStoreConfig(array $eventStoreConfig): void
    {
        $this->eventStoreConfig = $eventStoreConfig;
    }

    public function setJsonResponseMode(bool $jsonResponseMode): void
    {
        $this->jsonResponseMode = $jsonResponseMode;
    }

    public function setTerminateOnClose(bool $terminateOnClose): void
    {
        $this->terminateOnClose = $terminateOnClose;
    }

    /**
     * Validate the complete configuration.
     *
     * @throws ValidationError If configuration is invalid
     */
    public function validate(): void
    {
        $this->validateBaseUrl($this->baseUrl);
        $this->validateTimeout($this->timeout);
        $this->validateSseTimeout($this->sseTimeout);
        $this->validateMaxRetries($this->maxRetries);
        $this->validateRetryDelay($this->retryDelay);
        $this->validateUserAgent($this->userAgent);
        $this->validateHeaders($this->headers);
        $this->validateAuth($this->auth);
        $this->validateProtocolVersion($this->protocolVersion);
        $this->validateEventStoreType($this->eventStoreType);
    }

    /**
     * Create a new configuration with the specified changes.
     *
     * @param array<string, mixed> $changes Changes to apply
     * @return self New configuration instance
     */
    public function withChanges(array $changes): self
    {
        $currentConfig = $this->toArray();
        $newConfig = array_merge($currentConfig, $changes);
        return self::fromArray($newConfig);
    }

    /**
     * Serialize for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Validate authentication configuration.
     *
     * @param array<string, mixed> $auth Authentication configuration
     * @throws ValidationError If authentication configuration is invalid
     */
    private function validateAuthConfig(array $auth): void
    {
        if (! isset($auth['type'])) {
            throw ValidationError::emptyField('auth.type');
        }

        $validTypes = ['bearer', 'basic', 'oauth2', 'custom'];
        if (! in_array($auth['type'], $validTypes, true)) {
            throw ValidationError::invalidFieldValue(
                'auth.type',
                'must be one of: ' . implode(', ', $validTypes),
                ['value' => $auth['type'], 'valid' => $validTypes]
            );
        }

        // Type-specific validation
        switch ($auth['type']) {
            case 'bearer':
                if (! isset($auth['token']) || ! is_string($auth['token']) || empty($auth['token'])) {
                    throw ValidationError::invalidFieldValue(
                        'auth.token',
                        'is required for bearer authentication',
                        ['auth' => $auth]
                    );
                }
                break;
            case 'basic':
                if (! isset($auth['username']) || ! is_string($auth['username']) || empty($auth['username'])) {
                    throw ValidationError::invalidFieldValue(
                        'auth.username',
                        'is required for basic authentication',
                        ['auth' => $auth]
                    );
                }
                if (! isset($auth['password']) || ! is_string($auth['password'])) {
                    throw ValidationError::invalidFieldValue(
                        'auth.password',
                        'is required for basic authentication',
                        ['auth' => $auth]
                    );
                }
                break;
            case 'oauth2':
                // OAuth2 validation will be implemented when OAuth2 support is added
                // For now, just ensure basic required fields exist
                $requiredFields = ['client_id', 'client_secret'];
                foreach ($requiredFields as $field) {
                    if (! isset($auth[$field]) || ! is_string($auth[$field]) || empty($auth[$field])) {
                        throw ValidationError::invalidFieldValue(
                            "auth.{$field}",
                            'is required for OAuth2 authentication',
                            ['auth' => $auth]
                        );
                    }
                }
                break;
            case 'custom':
                if (! isset($auth['headers']) || ! is_array($auth['headers'])) {
                    throw ValidationError::invalidFieldValue(
                        'auth.headers',
                        'is required for custom authentication',
                        ['auth' => $auth]
                    );
                }
                break;
        }
    }

    /**
     * Validate base URL without modifying state.
     *
     * @param string $baseUrl Base URL to validate
     * @throws ValidationError If base URL is invalid
     */
    private function validateBaseUrl(string $baseUrl): void
    {
        // Base URL is required for HTTP transport
        if (empty(trim($baseUrl))) {
            throw ValidationError::invalidFieldValue(
                'base_url',
                'cannot be empty when provided',
                ['value' => $baseUrl]
            );
        }

        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw ValidationError::invalidFieldValue(
                'base_url',
                'must be a valid URL',
                ['value' => $baseUrl]
            );
        }
    }

    /**
     * Validate timeout without modifying state.
     *
     * @param float $timeout Timeout to validate
     * @throws ValidationError If timeout is invalid
     */
    private function validateTimeout(float $timeout): void
    {
        if ($timeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'timeout',
                'must be greater than 0',
                ['value' => $timeout]
            );
        }
    }

    /**
     * Validate SSE timeout without modifying state.
     *
     * @param float $sseTimeout SSE timeout to validate
     * @throws ValidationError If SSE timeout is invalid
     */
    private function validateSseTimeout(float $sseTimeout): void
    {
        if ($sseTimeout <= 0) {
            throw ValidationError::invalidFieldValue(
                'sse_timeout',
                'must be greater than 0',
                ['value' => $sseTimeout]
            );
        }
    }

    /**
     * Validate max retries without modifying state.
     *
     * @param int $maxRetries Max retries to validate
     * @throws ValidationError If max retries is invalid
     */
    private function validateMaxRetries(int $maxRetries): void
    {
        if ($maxRetries < 0) {
            throw ValidationError::invalidFieldValue(
                'max_retries',
                'cannot be negative',
                ['value' => $maxRetries]
            );
        }
    }

    /**
     * Validate retry delay without modifying state.
     *
     * @param float $retryDelay Retry delay to validate
     * @throws ValidationError If retry delay is invalid
     */
    private function validateRetryDelay(float $retryDelay): void
    {
        if ($retryDelay < 0) {
            throw ValidationError::invalidFieldValue(
                'retry_delay',
                'cannot be negative',
                ['value' => $retryDelay]
            );
        }
    }

    /**
     * Validate user agent without modifying state.
     *
     * @param string $userAgent User agent to validate
     * @throws ValidationError If user agent is invalid
     */
    private function validateUserAgent(string $userAgent): void
    {
        if (empty(trim($userAgent))) {
            throw ValidationError::invalidFieldValue(
                'user_agent',
                'cannot be empty',
                ['value' => $userAgent]
            );
        }
    }

    /**
     * Validate headers without modifying state.
     *
     * @param array<string, string> $headers Headers to validate
     * @throws ValidationError If headers are invalid
     */
    private function validateHeaders(array $headers): void
    {
        foreach ($headers as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                throw ValidationError::invalidFieldValue(
                    'headers',
                    'must be an array of string key-value pairs',
                    ['headers' => $headers]
                );
            }
        }
    }

    /**
     * Validate auth configuration without modifying state.
     *
     * @param null|array<string, mixed> $auth Auth configuration to validate
     * @throws ValidationError If auth configuration is invalid
     */
    private function validateAuth(?array $auth): void
    {
        if ($auth !== null) {
            $this->validateAuthConfig($auth);
        }
    }

    /**
     * Validate protocol version without modifying state.
     *
     * @param string $protocolVersion Protocol version to validate
     * @throws ValidationError If protocol version is invalid
     */
    private function validateProtocolVersion(string $protocolVersion): void
    {
        $validVersions = ['auto', '2025-06-18', '2025-03-26', '2024-11-05'];
        if (! in_array($protocolVersion, $validVersions, true)) {
            throw ValidationError::invalidFieldValue(
                'protocol_version',
                'must be one of: ' . implode(', ', $validVersions),
                ['value' => $protocolVersion, 'valid' => $validVersions]
            );
        }
    }

    /**
     * Validate event store type without modifying state.
     *
     * @param string $eventStoreType Event store type to validate
     * @throws ValidationError If event store type is invalid
     */
    private function validateEventStoreType(string $eventStoreType): void
    {
        $validTypes = ['memory', 'file', 'redis'];
        if (! in_array($eventStoreType, $validTypes, true)) {
            throw ValidationError::invalidFieldValue(
                'event_store_type',
                'must be one of: ' . implode(', ', $validTypes),
                ['value' => $eventStoreType, 'valid' => $validTypes]
            );
        }
    }
}
