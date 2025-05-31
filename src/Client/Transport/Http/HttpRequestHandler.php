<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Transport\Http;

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Exceptions\TransportError;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Dtyq\PhpMcp\Shared\Utilities\HttpUtils;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Exception;

/**
 * Handles HTTP requests and responses for MCP transport.
 *
 * This class manages the HTTP communication layer, including request building,
 * authentication, response parsing, and retry logic.
 */
class HttpRequestHandler
{
    /** @var HttpConfig Transport configuration */
    private HttpConfig $config;

    /** @var LoggerProxy Logger instance */
    private LoggerProxy $logger;

    /** @var AuthenticatorInterface Authenticator instance */
    private AuthenticatorInterface $authenticator;

    /** @var array<string, mixed> Request statistics */
    private array $stats = [];

    /**
     * @param HttpConfig $config Transport configuration
     * @param LoggerProxy $logger Logger instance
     * @param AuthenticatorInterface $authenticator Authenticator instance
     */
    public function __construct(
        HttpConfig $config,
        LoggerProxy $logger,
        AuthenticatorInterface $authenticator
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->authenticator = $authenticator;

        $this->initializeStats();
    }

    /**
     * Send an HTTP request with JSON-RPC message.
     *
     * @param string $message JSON-RPC message to send
     * @param null|string $sessionId Optional session ID for the request
     * @param string $endpoint Optional endpoint path (default: empty)
     * @return array<string, mixed> Response data with headers and body
     * @throws TransportError If request fails
     */
    public function sendRequest(
        string $message,
        ?string $sessionId = null,
        string $endpoint = ''
    ): array {
        $url = $this->config->getEndpointUrl($endpoint);
        $attempt = 0;
        $maxRetries = $this->config->getMaxRetries();

        while ($attempt <= $maxRetries) {
            try {
                $this->logger->debug('Sending HTTP request', [
                    'url' => $url,
                    'attempt' => $attempt + 1,
                    'max_retries' => $maxRetries + 1,
                    'session_id' => $sessionId,
                    'message_length' => strlen($message),
                ]);

                $response = $this->executeRequest($url, $message, $sessionId);

                $this->updateStats('requests_successful');
                return $response;
            } catch (Exception $e) {
                ++$attempt;
                $this->updateStats('requests_failed');

                if ($attempt > $maxRetries) {
                    $this->logger->error('HTTP request failed after all retries', [
                        'url' => $url,
                        'attempts' => $attempt,
                        'error' => $e->getMessage(),
                        'session_id' => $sessionId,
                    ]);
                    throw new TransportError('HTTP request failed: ' . $e->getMessage());
                }

                $delay = $this->config->getRetryDelay() * $attempt; // Linear backoff
                $this->logger->warning('HTTP request failed, retrying', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'retry_delay' => $delay,
                    'session_id' => $sessionId,
                ]);

                usleep((int) ($delay * 1000000)); // Convert to microseconds
            }
        }

        throw new TransportError('HTTP request failed after all retries');
    }

    /**
     * Get request statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    public function getAuthenticator(): AuthenticatorInterface
    {
        return $this->authenticator;
    }

    /**
     * Execute a single HTTP request.
     *
     * @param string $url Target URL
     * @param string $message JSON-RPC message
     * @param null|string $sessionId Session ID
     * @return array<string, mixed> Response data
     * @throws TransportError If request fails
     */
    private function executeRequest(string $url, string $message, ?string $sessionId): array
    {
        // Build headers
        $headers = $this->buildHeaders($sessionId);

        // Create HTTP context
        $context = HttpUtils::createStreamableHttpContext(
            HttpUtils::METHOD_POST,
            json_decode($message, true),
            $headers,
            $this->config->getTimeout()
        );

        // Add SSL context if needed
        if ($this->config->shouldValidateSsl()) {
            $context['ssl'] = [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ];
        } else {
            $context['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ];
        }

        $streamContext = stream_context_create($context);

        // Execute request
        $startTime = microtime(true);
        $http_response_header = []; // Initialize before use
        $responseBody = @file_get_contents($url, false, $streamContext);
        $duration = microtime(true) - $startTime;

        if ($responseBody === false) {
            $error = error_get_last();
            throw new TransportError('HTTP request failed: ' . ($error['message'] ?? 'Unknown error'));
        }

        // Parse response headers
        $responseHeaders = $this->parseResponseHeaders($http_response_header);

        $this->logger->debug('HTTP request completed', [
            'url' => $url,
            'duration' => $duration,
            'response_length' => strlen($responseBody),
            'status_code' => $responseHeaders['status_code'] ?? 'unknown',
            'session_id' => $sessionId,
        ]);

        // Validate response
        $this->validateResponse($responseHeaders, $responseBody);

        return [
            'headers' => $responseHeaders,
            'body' => $responseBody,
            'duration' => $duration,
        ];
    }

    /**
     * Build HTTP headers for the request.
     *
     * @param null|string $sessionId Session ID
     * @return array<string, string> HTTP headers
     */
    private function buildHeaders(?string $sessionId): array
    {
        $headers = [
            ProtocolConstants::HTTP_HEADER_CONTENT_TYPE => ProtocolConstants::HTTP_CONTENT_TYPE_JSON,
            ProtocolConstants::HTTP_HEADER_ACCEPT => ProtocolConstants::HTTP_ACCEPT_SSE_JSON,
            ProtocolConstants::HTTP_HEADER_USER_AGENT => $this->config->getUserAgent(),
        ];

        // Add session ID if provided
        if ($sessionId !== null) {
            $headers[ProtocolConstants::HTTP_HEADER_SESSION_ID] = $sessionId;
        }

        // Add custom headers from config
        foreach ($this->config->getHeaders() as $name => $value) {
            $headers[$name] = $value;
        }

        // Add authentication headers
        $authHeaders = $this->buildAuthHeaders();
        return array_merge($headers, $authHeaders);
    }

    /**
     * Build authentication headers.
     *
     * @return array<string, string> Authentication headers
     */
    private function buildAuthHeaders(): array
    {
        if (! $this->config->hasAuth()) {
            return [];
        }

        $auth = $this->config->getAuth();
        $authType = $this->config->getAuthType();

        switch ($authType) {
            case 'bearer':
                if (isset($auth['token'])) {
                    return HttpUtils::getBearerAuthHeader($auth['token']);
                }
                break;
            case 'basic':
                if (isset($auth['username'], $auth['password'])) {
                    return HttpUtils::getBasicAuthHeader($auth['username'], $auth['password']);
                }
                break;
            case 'custom':
                if (isset($auth['headers']) && is_array($auth['headers'])) {
                    return $auth['headers'];
                }
                break;
        }

        $this->logger->warning('Authentication configured but could not build auth headers', [
            'auth_type' => $authType,
            'auth_keys' => array_keys($auth ?? []),
        ]);

        return [];
    }

    /**
     * Parse HTTP response headers.
     *
     * @param array<string> $headerLines Raw header lines
     * @return array<string, mixed> Parsed headers
     */
    private function parseResponseHeaders(array $headerLines): array
    {
        $headers = [];
        $statusCode = null;

        foreach ($headerLines as $headerLine) {
            if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $headerLine, $matches)) {
                $statusCode = (int) $matches[1];
            } elseif (strpos($headerLine, ':') !== false) {
                [$name, $value] = explode(':', $headerLine, 2);
                $headers[trim($name)] = trim($value);
            }
        }

        if ($statusCode !== null) {
            $headers['status_code'] = $statusCode;
        }

        return $headers;
    }

    /**
     * Validate HTTP response.
     *
     * @param array<string, mixed> $headers Response headers
     * @param string $body Response body
     * @throws TransportError If response is invalid
     */
    private function validateResponse(array $headers, string $body): void
    {
        $statusCode = $headers['status_code'] ?? 0;

        // Check status code
        if (! HttpUtils::isSuccessStatusCode($statusCode)) {
            $this->logger->error('HTTP request returned error status', [
                'status_code' => $statusCode,
                'headers' => $headers,
                'body_preview' => substr($body, 0, 200),
            ]);

            throw new TransportError("HTTP request failed with status {$statusCode}");
        }

        // Validate content type for JSON responses
        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';
        if (! empty($body) && strpos($contentType, 'application/json') === false) {
            $this->logger->warning('Unexpected content type in response', [
                'content_type' => $contentType,
                'expected' => 'application/json',
            ]);
        }

        // Validate JSON format if body is not empty
        if (! empty($body)) {
            $decoded = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new TransportError('Invalid JSON in response: ' . json_last_error_msg());
            }
        }
    }

    /**
     * Initialize statistics tracking.
     */
    private function initializeStats(): void
    {
        $this->stats = [
            'requests_sent' => 0,
            'requests_successful' => 0,
            'requests_failed' => 0,
            'total_retry_attempts' => 0,
            'average_response_time' => 0.0,
            'last_request_time' => null,
        ];
    }

    /**
     * Update statistics.
     *
     * @param string $key Statistics key
     * @param mixed $value Optional value to set
     */
    private function updateStats(string $key, $value = null): void
    {
        if ($value !== null) {
            $this->stats[$key] = $value;
        } else {
            // Increment counter
            if (isset($this->stats[$key])) {
                ++$this->stats[$key];
            }
        }

        $this->stats['last_request_time'] = microtime(true);
    }
}
