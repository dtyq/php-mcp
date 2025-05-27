<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp;

use Dtyq\PhpMcp\Server\Core\ServerConfig;

/**
 * Builder for ServerConfig instances.
 */
class ServerConfigBuilder
{
    private string $name;

    private string $version = '1.0.0';

    private string $description = '';

    /**
     * @var array<string, string>
     */
    private array $vendor = [];

    /**
     * @var array<string, mixed>
     */
    private array $transport = [];

    /**
     * @var array<string, mixed>
     */
    private array $logging = [];

    /**
     * @var array<string, mixed>
     */
    private array $performance = [];

    /**
     * @var array<string, mixed>
     */
    private array $security = [];

    /**
     * @var array<string, mixed>
     */
    private array $custom = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param array<string, string> $vendor
     */
    public function setVendor(array $vendor): self
    {
        $this->vendor = $vendor;
        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setTransportType(string $type, array $options = []): self
    {
        $this->transport = array_merge(['type' => $type], $options);
        return $this;
    }

    public function setLogLevel(string $level): self
    {
        $this->logging['level'] = $level;
        return $this;
    }

    /**
     * @param array<string, mixed> $performance
     */
    public function setPerformance(array $performance): self
    {
        $this->performance = array_merge($this->performance, $performance);
        return $this;
    }

    /**
     * @param array<string, mixed> $security
     */
    public function setSecurity(array $security): self
    {
        $this->security = array_merge($this->security, $security);
        return $this;
    }

    /**
     * @param array<string, mixed> $custom
     */
    public function setCustom(array $custom): self
    {
        $this->custom = array_merge($this->custom, $custom);
        return $this;
    }

    public function build(): ServerConfig
    {
        $options = [
            'vendor' => $this->vendor,
            'transport' => $this->transport,
            'logging' => $this->logging,
            'performance' => $this->performance,
            'security' => $this->security,
            'custom' => $this->custom,
        ];

        return new ServerConfig($this->name, $this->version, $this->description, $options);
    }
}
