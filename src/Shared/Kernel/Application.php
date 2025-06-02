<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Kernel;

use Dtyq\PhpMcp\Shared\Auth\AuthenticatorInterface;
use Dtyq\PhpMcp\Shared\Auth\NullAuthenticator;
use Dtyq\PhpMcp\Shared\Exceptions\SystemException;
use Dtyq\PhpMcp\Shared\Kernel\Config\Config;
use Dtyq\PhpMcp\Shared\Kernel\Logger\LoggerProxy;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class Application
{
    protected Config $config;

    protected LoggerProxy $logger;

    protected CacheInterface $cache;

    protected EventDispatcherInterface $eventDispatcher;

    protected ContainerInterface $container;

    protected AuthenticatorInterface $authenticator;

    /**
     * @param array<string, mixed> $configs
     */
    public function __construct(ContainerInterface $container, array $configs)
    {
        $this->container = $container;
        $this->config = new Config($configs);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Get the container instance.
     *
     * @return mixed
     */
    public function get(string $id)
    {
        if (! $this->container->has($id)) {
            throw new SystemException(sprintf('Service "%s" not found in container.', $id));
        }
        return $this->container->get($id);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getLogger(): LoggerProxy
    {
        if (! empty($this->logger)) {
            return $this->logger;
        }
        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);
        if (! $logger instanceof LoggerInterface) {
            throw new SystemException('Logger Must Be An Instance Of Psr\Log\LoggerInterface');
        }
        $this->logger = new LoggerProxy($this->getConfig()->getSdkName(), $logger);
        return $this->logger;
    }

    public function getCache(): CacheInterface
    {
        if (! empty($this->cache)) {
            return $this->cache;
        }
        $cache = $this->container->get(CacheInterface::class);
        if (! $cache instanceof CacheInterface) {
            throw new SystemException('Cache Must Be An Instance Of Psr\SimpleCache\CacheInterface');
        }
        $this->cache = $cache;
        return $this->cache;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        if (! empty($this->eventDispatcher)) {
            return $this->eventDispatcher;
        }
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        if (! $dispatcher instanceof EventDispatcherInterface) {
            throw new SystemException('Event Dispatcher Must Be An Instance Of Psr\EventDispatcher\EventDispatcherInterface');
        }
        $this->eventDispatcher = $dispatcher;
        return $this->eventDispatcher;
    }

    /**
     * Set authenticator instance.
     *
     * @return $this For method chaining
     */
    public function withAuthenticator(AuthenticatorInterface $authenticator): self
    {
        $this->authenticator = $authenticator;
        return $this;
    }

    /**
     * Get Authenticator instance.
     *
     * Returns configured authenticator or NullAuthenticator as default
     */
    public function getAuthenticator(): AuthenticatorInterface
    {
        if (isset($this->authenticator)) {
            return $this->authenticator;
        }
        if ($this->container->has(AuthenticatorInterface::class)) {
            $authenticator = $this->container->get(AuthenticatorInterface::class);
        } else {
            $authenticator = new NullAuthenticator();
        }
        $this->authenticator = $authenticator;
        return $this->authenticator;
    }
}
