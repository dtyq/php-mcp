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

    /** @var array<string, array<string, string>> Cached headers by session ID */
    private array $headerCache = [];

    /** @var array<string, mixed> Performance metrics */
    private array $performanceMetrics = [];

    /** @var float Last request time for rate limiting */
    private float $lastRequestTime = 0.0;

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
        $startTime = microtime(true);
        $url = $this->config->getEndpointUrl($endpoint);
        $attempt = 0;
        $maxRetries = $this->config->getMaxRetries();

        // Rate limiting check
        $this->enforceRateLimit();

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

                // Track performance metrics
                $totalDuration = microtime(true) - $startTime;
                $this->trackPerformanceMetrics($totalDuration, $response['duration'], $attempt);

                $this->updateStats('requests_successful');
                $this->lastRequestTime = microtime(true);

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
                        'total_duration' => microtime(true) - $startTime,
                    ]);
                    throw new TransportError('HTTP request failed: ' . $e->getMessage());
                }

                // Exponential backoff with jitter
                $baseDelay = $this->config->getRetryDelay();
                $exponentialDelay = $baseDelay * pow(2, $attempt - 1);
                $jitter = mt_rand(0, (int) ($exponentialDelay * 0.1 * 1000)) / 1000; // 10% jitter
                $delay = $exponentialDelay + $jitter;

                $this->logger->warning('HTTP request failed, retrying with exponential backoff', [
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
     * Get performance metrics.
     *
     * @return array<string, mixed>
     */
    public function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }

    /**
     * Clear header cache (useful when configuration changes).
     */
    public function clearHeaderCache(): void
    {
        $this->headerCache = [];
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
        // Security: Validate URL scheme
        $this->validateUrlSecurity($url);

        // Security: Validate message size
        $this->validateMessageSize($message);

        // Build headers
        $headers = $this->buildHeaders($sessionId);

        // Create HTTP context
        $context = HttpUtils::createStreamableHttpContext(
            HttpUtils::METHOD_POST,
            json_decode($message, true),
            $headers,
            $this->config->getTimeout()
        );

        // Enhanced SSL context with security settings
        $this->configureSecureSSLContext($context);

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

        // Enhanced response validation with security checks
        $this->validateSecureResponse($responseHeaders, $responseBody);

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
        $cacheKey = $sessionId ?? 'default';

        // Use cached headers if available and session ID hasn't changed
        if (isset($this->headerCache[$cacheKey])) {
            return $this->headerCache[$cacheKey];
        }

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
        $finalHeaders = array_merge($headers, $authHeaders);

        // Cache the headers for future use
        $this->headerCache[$cacheKey] = $finalHeaders;

        return $finalHeaders;
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

    /**
     * Enforce rate limiting to prevent overwhelming the server.
     */
    private function enforceRateLimit(): void
    {
        $minInterval = 0.01; // Minimum 10ms between requests
        $timeSinceLastRequest = microtime(true) - $this->lastRequestTime;

        if ($timeSinceLastRequest < $minInterval) {
            $sleepTime = $minInterval - $timeSinceLastRequest;
            usleep((int) ($sleepTime * 1000000));
        }
    }

    /**
     * Track performance metrics for monitoring and optimization.
     *
     * @param float $totalDuration Total request duration including retries
     * @param float $requestDuration Single request duration
     * @param int $attempt Number of attempts made
     */
    private function trackPerformanceMetrics(float $totalDuration, float $requestDuration, int $attempt): void
    {
        // Update running averages
        if (! isset($this->performanceMetrics['avg_total_duration'])) {
            $this->performanceMetrics['avg_total_duration'] = 0.0;
            $this->performanceMetrics['avg_request_duration'] = 0.0;
            $this->performanceMetrics['request_count'] = 0;
        }

        $count = $this->performanceMetrics['request_count'];
        $this->performanceMetrics['avg_total_duration']
            = ($this->performanceMetrics['avg_total_duration'] * $count + $totalDuration) / ($count + 1);
        $this->performanceMetrics['avg_request_duration']
            = ($this->performanceMetrics['avg_request_duration'] * $count + $requestDuration) / ($count + 1);

        $this->performanceMetrics['request_count'] = $count + 1;
        $this->performanceMetrics['last_total_duration'] = $totalDuration;
        $this->performanceMetrics['last_request_duration'] = $requestDuration;
        $this->performanceMetrics['last_attempt_count'] = $attempt;

        // Track min/max durations
        if (! isset($this->performanceMetrics['min_duration']) || $totalDuration < $this->performanceMetrics['min_duration']) {
            $this->performanceMetrics['min_duration'] = $totalDuration;
        }
        if (! isset($this->performanceMetrics['max_duration']) || $totalDuration > $this->performanceMetrics['max_duration']) {
            $this->performanceMetrics['max_duration'] = $totalDuration;
        }
    }

    /**
     * Validate URL security.
     *
     * @param string $url URL to validate
     * @throws TransportError If URL is not secure
     */
    private function validateUrlSecurity(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        // Check for HTTPS enforcement
        if ($this->config->isHttpsForced() && $scheme !== 'https') {
            throw new TransportError('HTTPS is required but URL uses: ' . $scheme);
        }

        // Validate against known insecure schemes
        $insecureSchemes = ['ftp', 'file', 'data'];
        if (in_array($scheme, $insecureSchemes, true)) {
            throw new TransportError('Insecure URL scheme not allowed: ' . $scheme);
        }

        // Validate hostname
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            throw new TransportError('Invalid URL: missing hostname');
        }

        // Check for localhost/private IPs in production
        if ($this->isPrivateOrLocalhost($host)) {
            $this->logger->warning('Request to private/localhost address', [
                'host' => $host,
                'url' => $url,
            ]);
        }
    }

    /**
     * Validate message size for security.
     *
     * @param string $message Message to validate
     * @throws TransportError If message is too large
     */
    private function validateMessageSize(string $message): void
    {
        $maxSize = 10 * 1024 * 1024; // 10MB limit
        $messageSize = strlen($message);

        if ($messageSize > $maxSize) {
            throw new TransportError(sprintf(
                'Message size (%d bytes) exceeds maximum allowed (%d bytes)',
                $messageSize,
                $maxSize
            ));
        }
    }

    /**
     * Configure secure SSL context.
     *
     * @param array<string, mixed> $context HTTP context array
     */
    private function configureSecureSSLContext(array &$context): void
    {
        if ($this->config->shouldValidateSsl()) {
            $context['ssl'] = [
                'verify_peer' => true,
                'verify_peer_name' => $this->config->shouldVerifyHostname(),
                'allow_self_signed' => false,
                'disable_compression' => true, // Prevent CRIME attacks
                'SNI_enabled' => true,
                'ciphers' => $this->getSecureCipherSuites(),
            ];

            // Set minimum TLS version
            $minTlsVersion = $this->config->getMinTlsVersion();
            if ($minTlsVersion === '1.3') {
                $context['ssl']['crypto_method'] = STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            } elseif ($minTlsVersion === '1.2') {
                $context['ssl']['crypto_method'] = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
        } else {
            $context['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ];
        }
    }

    /**
     * Enhanced response validation with security checks.
     *
     * @param array<string, mixed> $headers Response headers
     * @param string $body Response body
     * @throws TransportError If response is invalid or insecure
     */
    private function validateSecureResponse(array $headers, string $body): void
    {
        // Call original validation first
        $this->validateResponse($headers, $body);

        // Additional security validations
        $statusCode = $headers['status_code'] ?? 0;

        // Check for suspicious redirects
        if ($statusCode >= 300 && $statusCode < 400) {
            $location = $headers['Location'] ?? $headers['location'] ?? '';
            if (! empty($location)) {
                $this->validateUrlSecurity($location);
            }
        }

        // Validate response size
        $maxResponseSize = 50 * 1024 * 1024; // 50MB limit
        if (strlen($body) > $maxResponseSize) {
            throw new TransportError('Response size exceeds security limit');
        }

        // Check for security headers (log warnings if missing)
        $this->checkSecurityHeaders($headers);
    }

    /**
     * Get secure cipher suites.
     *
     * @return string Cipher suite string
     */
    private function getSecureCipherSuites(): string
    {
        $configuredSuites = $this->config->getAllowedCipherSuites();

        if (! empty($configuredSuites)) {
            return implode(':', $configuredSuites);
        }

        // Default secure cipher suites (TLS 1.2+)
        return 'ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256:'
               . 'ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA256:'
               . 'AES256-GCM-SHA384:AES128-GCM-SHA256';
    }

    /**
     * Check if host is private or localhost.
     *
     * @param string $host Hostname to check
     * @return bool True if private/localhost
     */
    private function isPrivateOrLocalhost(string $host): bool
    {
        // Check for localhost
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        // Check for private IP ranges
        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip !== false) {
            return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return false;
    }

    /**
     * Check for important security headers.
     *
     * @param array<string, mixed> $headers Response headers
     */
    private function checkSecurityHeaders(array $headers): void
    {
        $securityHeaders = [
            'Strict-Transport-Security',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection',
            'Content-Security-Policy',
        ];

        $missingHeaders = [];
        foreach ($securityHeaders as $header) {
            if (! isset($headers[$header]) && ! isset($headers[strtolower($header)])) {
                $missingHeaders[] = $header;
            }
        }

        if (! empty($missingHeaders)) {
            $this->logger->info('Response missing security headers', [
                'missing_headers' => $missingHeaders,
            ]);
        }
    }
}
