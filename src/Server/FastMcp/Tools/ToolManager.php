<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Tools;

use Dtyq\PhpMcp\Shared\Exceptions\ToolError;

/**
 * Simple tool registration manager.
 *
 * Manages tool registration and retrieval.
 */
class ToolManager
{
    /** @var array<string, RegisteredTool> Registered tools */
    private array $tools = [];

    /**
     * Register a tool.
     */
    public function register(RegisteredTool $registeredTool): void
    {
        $this->tools[$registeredTool->getName()] = $registeredTool;
    }

    /**
     * Get a registered tool.
     */
    public function get(string $name): ?RegisteredTool
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Check if tool exists.
     */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Remove a tool.
     */
    public function remove(string $name): bool
    {
        if (isset($this->tools[$name])) {
            unset($this->tools[$name]);
            return true;
        }
        return false;
    }

    /**
     * Get all tool names.
     *
     * @return array<string>
     */
    public function getNames(): array
    {
        return array_keys($this->tools);
    }

    /**
     * Get all registered tools.
     *
     * @return array<RegisteredTool>
     */
    public function getAll(): array
    {
        return array_values($this->tools);
    }

    /**
     * Get tool count.
     */
    public function count(): int
    {
        return count($this->tools);
    }

    /**
     * Clear all tools.
     */
    public function clear(): void
    {
        $this->tools = [];
    }

    /**
     * Execute a tool by name.
     *
     * @param array<string, mixed> $arguments
     * @return mixed
     */
    public function execute(string $name, ?array $arguments)
    {
        $arguments = $arguments ?? [];
        $registeredTool = $this->get($name);
        if ($registeredTool === null) {
            throw ToolError::unknownTool($name);
        }

        return $registeredTool->execute($arguments);
    }
}
