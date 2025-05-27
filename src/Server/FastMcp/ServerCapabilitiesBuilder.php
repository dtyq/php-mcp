<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp;

use Dtyq\PhpMcp\Server\Core\ServerCapabilities;

/**
 * Builder for ServerCapabilities instances.
 */
class ServerCapabilitiesBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $capabilities = [];

    /**
     * @param string[] $toolNames
     */
    public function enableTools(array $toolNames = []): self
    {
        $this->capabilities['tools'] = [
            'listChanged' => true,
        ];

        if (! empty($toolNames)) {
            $this->capabilities['tools']['available'] = $toolNames;
        }

        return $this;
    }

    /**
     * @param string[] $resourceTypes
     */
    public function enableResources(array $resourceTypes = []): self
    {
        $this->capabilities['resources'] = [
            'subscribe' => true,
            'listChanged' => true,
        ];

        if (! empty($resourceTypes)) {
            $this->capabilities['resources']['types'] = $resourceTypes;
        }

        return $this;
    }

    /**
     * @param string[] $promptNames
     */
    public function enablePrompts(array $promptNames = []): self
    {
        $this->capabilities['prompts'] = [
            'listChanged' => true,
        ];

        if (! empty($promptNames)) {
            $this->capabilities['prompts']['available'] = $promptNames;
        }

        return $this;
    }

    public function enableSampling(): self
    {
        $this->capabilities['sampling'] = [];
        return $this;
    }

    /**
     * @param string[] $rootPaths
     */
    public function enableRoots(array $rootPaths = []): self
    {
        $this->capabilities['roots'] = [
            'listChanged' => true,
        ];

        if (! empty($rootPaths)) {
            $this->capabilities['roots']['paths'] = $rootPaths;
        }

        return $this;
    }

    public function enableLogging(): self
    {
        $this->capabilities['logging'] = [];
        return $this;
    }

    public function setCapability(string $name, mixed $value): self
    {
        $this->capabilities[$name] = $value;
        return $this;
    }

    public function build(): ServerCapabilities
    {
        return new ServerCapabilities($this->capabilities);
    }
}
