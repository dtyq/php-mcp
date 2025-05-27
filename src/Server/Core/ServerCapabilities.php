<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Core;

/**
 * Server capabilities class.
 */
class ServerCapabilities
{
    /**
     * @var array<string, mixed>
     */
    private array $capabilities = [];

    /**
     * @param array<string, mixed> $capabilities
     */
    public function __construct(array $capabilities = [])
    {
        $this->capabilities = $capabilities;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->capabilities;
    }

    /**
     * Set a capability value.
     *
     * @param string $name Capability name
     * @param mixed $value Capability value
     */
    public function setCapability(string $name, $value): void
    {
        $this->capabilities[$name] = $value;
    }

    /**
     * Get a capability value.
     *
     * @param string $name Capability name
     * @return mixed Capability value or null if not set
     */
    public function getCapability(string $name)
    {
        return $this->capabilities[$name] ?? null;
    }
}
