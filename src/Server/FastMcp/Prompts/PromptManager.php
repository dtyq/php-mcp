<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Prompts;

use Dtyq\PhpMcp\Shared\Exceptions\PromptError;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;

/**
 * Simple prompt registration manager.
 *
 * Manages prompt registration and retrieval.
 */
class PromptManager
{
    /** @var array<string, RegisteredPrompt> Registered prompts */
    private array $prompts = [];

    /**
     * Register a prompt.
     */
    public function register(RegisteredPrompt $registeredPrompt): void
    {
        $this->prompts[$registeredPrompt->getName()] = $registeredPrompt;
    }

    /**
     * Get a registered prompt.
     */
    public function get(string $name): ?RegisteredPrompt
    {
        return $this->prompts[$name] ?? null;
    }

    /**
     * Check if prompt exists.
     */
    public function has(string $name): bool
    {
        return isset($this->prompts[$name]);
    }

    /**
     * Remove a prompt.
     */
    public function remove(string $name): bool
    {
        if (isset($this->prompts[$name])) {
            unset($this->prompts[$name]);
            return true;
        }
        return false;
    }

    /**
     * Get all prompt names.
     *
     * @return array<string>
     */
    public function getNames(): array
    {
        return array_keys($this->prompts);
    }

    /**
     * Get all registered prompts.
     *
     * @return array<RegisteredPrompt>
     */
    public function getAll(): array
    {
        return array_values($this->prompts);
    }

    /**
     * Get prompt count.
     */
    public function count(): int
    {
        return count($this->prompts);
    }

    /**
     * Clear all prompts.
     */
    public function clear(): void
    {
        $this->prompts = [];
    }

    /**
     * Execute a prompt by name.
     *
     * @param array<string, mixed> $arguments
     */
    public function execute(string $name, array $arguments): GetPromptResult
    {
        $registeredPrompt = $this->get($name);
        if ($registeredPrompt === null) {
            throw PromptError::unknownPrompt($name);
        }

        return $registeredPrompt->execute($arguments);
    }
}
