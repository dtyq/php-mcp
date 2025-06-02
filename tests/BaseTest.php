<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests;

use Dtyq\PhpMcp\Shared\Kernel\Application;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Copyright (c) The Magic , Distributed under the software license.
 * @internal
 */
abstract class BaseTest extends TestCase
{
    protected function createApplication(): Application
    {
        // Create simple container for testing
        $container = new class implements ContainerInterface {
            /** @var array<string, object> */
            private array $services = [];

            public function __construct()
            {
                $this->services[LoggerInterface::class] = new class extends AbstractLogger {
                    /**
                     * @param mixed $level
                     * @param string $message
                     */
                    public function log($level, $message, array $context = []): void
                    {
                        // Silent logger for tests
                    }
                };

                $this->services[EventDispatcherInterface::class] = new class implements EventDispatcherInterface {
                    public function dispatch(object $event): object
                    {
                        return $event;
                    }
                };
            }

            public function get($id)
            {
                return $this->services[$id];
            }

            public function has($id): bool
            {
                return isset($this->services[$id]);
            }
        };

        $config = [
            'sdk_name' => 'php-mcp-test',
        ];

        return new Application($container, $config);
    }
}
