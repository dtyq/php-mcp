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

    public function setCapability(string $name, mixed $value): void
    {
        $this->capabilities[$name] = $value;
    }

    public function getCapability(string $name): mixed
    {
        return $this->capabilities[$name] ?? null;
    }
}
