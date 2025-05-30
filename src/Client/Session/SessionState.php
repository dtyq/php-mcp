<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Client\Session;

use Dtyq\PhpMcp\Types\Responses\InitializeResult;

/**
 * Manager for session state and server capabilities.
 *
 * This class tracks the current state of the MCP session including
 * initialization status, server capabilities, and connection state.
 */
class SessionState
{
    // Session states
    public const STATE_DISCONNECTED = 'disconnected';

    public const STATE_CONNECTING = 'connecting';

    public const STATE_CONNECTED = 'connected';

    public const STATE_INITIALIZING = 'initializing';

    public const STATE_READY = 'ready';

    public const STATE_ERROR = 'error';

    /** @var bool Whether the session has been initialized */
    private bool $initialized = false;

    /** @var null|InitializeResult Server capabilities from initialization */
    private ?InitializeResult $serverCapabilities = null;

    /** @var array<string, mixed> Server information */
    private array $serverInfo = [];

    /** @var string Current session state */
    private string $currentState = self::STATE_DISCONNECTED;

    /** @var float Timestamp when session was initialized */
    private ?float $initializedAt = null;

    /** @var array<string, mixed> Additional state data */
    private array $stateData = [];

    /**
     * Get the current session state.
     *
     * @return string The current state
     */
    public function getCurrentState(): string
    {
        return $this->currentState;
    }

    /**
     * Set the current session state.
     *
     * @param string $state The new state
     */
    public function setState(string $state): void
    {
        $this->currentState = $state;
    }

    /**
     * Check if the session is initialized.
     *
     * @return bool True if initialized
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Mark the session as initialized.
     *
     * @param InitializeResult $serverCapabilities Server capabilities from initialization
     */
    public function markAsInitialized(InitializeResult $serverCapabilities): void
    {
        $this->initialized = true;
        $this->serverCapabilities = $serverCapabilities;
        $this->initializedAt = microtime(true);
        $this->setState(self::STATE_READY);
    }

    /**
     * Reset the session state.
     */
    public function reset(): void
    {
        $this->initialized = false;
        $this->serverCapabilities = null;
        $this->serverInfo = [];
        $this->currentState = self::STATE_DISCONNECTED;
        $this->initializedAt = null;
        $this->stateData = [];
    }

    /**
     * Get server capabilities.
     *
     * @return null|InitializeResult Server capabilities or null if not initialized
     */
    public function getServerCapabilities(): ?InitializeResult
    {
        return $this->serverCapabilities;
    }

    /**
     * Check if the server supports a specific capability.
     *
     * @param string $capability The capability to check
     * @return bool True if supported
     */
    public function hasServerCapability(string $capability): bool
    {
        if ($this->serverCapabilities === null) {
            return false;
        }

        $capabilities = $this->serverCapabilities->getCapabilities();
        return $this->checkNestedCapability($capabilities, $capability);
    }

    /**
     * Get a specific server capability value.
     *
     * @param string $capability The capability path (e.g., 'tools.listChanged')
     * @param mixed $default Default value if capability not found
     * @return mixed The capability value
     */
    public function getServerCapability(string $capability, $default = null)
    {
        if ($this->serverCapabilities === null) {
            return $default;
        }

        $capabilities = $this->serverCapabilities->getCapabilities();
        return $this->getNestedCapability($capabilities, $capability, $default);
    }

    /**
     * Get server information.
     *
     * @return array<string, mixed> Server information
     */
    public function getServerInfo(): array
    {
        return $this->serverInfo;
    }

    /**
     * Set server information.
     *
     * @param array<string, mixed> $serverInfo Server information
     */
    public function setServerInfo(array $serverInfo): void
    {
        $this->serverInfo = $serverInfo;
    }

    /**
     * Get when the session was initialized.
     *
     * @return null|float Timestamp or null if not initialized
     */
    public function getInitializedAt(): ?float
    {
        return $this->initializedAt;
    }

    /**
     * Get additional state data.
     *
     * @param string $key The data key
     * @param mixed $default Default value if key not found
     * @return mixed The state data
     */
    public function getStateData(string $key, $default = null)
    {
        return $this->stateData[$key] ?? $default;
    }

    /**
     * Set additional state data.
     *
     * @param string $key The data key
     * @param mixed $value The data value
     */
    public function setStateData(string $key, $value): void
    {
        $this->stateData[$key] = $value;
    }

    /**
     * Remove state data.
     *
     * @param string $key The data key to remove
     */
    public function removeStateData(string $key): void
    {
        unset($this->stateData[$key]);
    }

    /**
     * Check if the session is in a connected state.
     *
     * @return bool True if connected
     */
    public function isConnected(): bool
    {
        return in_array($this->currentState, [
            self::STATE_CONNECTED,
            self::STATE_INITIALIZING,
            self::STATE_READY,
        ], true);
    }

    /**
     * Check if the session is ready for operations.
     *
     * @return bool True if ready
     */
    public function isReady(): bool
    {
        return $this->currentState === self::STATE_READY && $this->initialized;
    }

    /**
     * Check if the session is in an error state.
     *
     * @return bool True if in error state
     */
    public function isError(): bool
    {
        return $this->currentState === self::STATE_ERROR;
    }

    /**
     * Get a summary of the current session state.
     *
     * @return array<string, mixed> State summary
     */
    public function getSummary(): array
    {
        return [
            'state' => $this->currentState,
            'initialized' => $this->initialized,
            'initializedAt' => $this->initializedAt,
            'hasServerCapabilities' => $this->serverCapabilities !== null,
            'serverInfo' => $this->serverInfo,
            'stateDataKeys' => array_keys($this->stateData),
        ];
    }

    /**
     * Check if a nested capability exists.
     *
     * @param array<string, mixed> $capabilities Capabilities array
     * @param string $path Dot-separated capability path
     * @return bool True if capability exists
     */
    private function checkNestedCapability(array $capabilities, string $path): bool
    {
        $keys = explode('.', $path);
        $current = $capabilities;

        foreach ($keys as $key) {
            if (! is_array($current) || ! isset($current[$key])) {
                return false;
            }
            $current = $current[$key];
        }

        return true;
    }

    /**
     * Get a nested capability value.
     *
     * @param array<string, mixed> $capabilities Capabilities array
     * @param string $path Dot-separated capability path
     * @param mixed $default Default value
     * @return mixed The capability value
     */
    private function getNestedCapability(array $capabilities, string $path, $default = null)
    {
        $keys = explode('.', $path);
        $current = $capabilities;

        foreach ($keys as $key) {
            if (! is_array($current) || ! isset($current[$key])) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }
}
