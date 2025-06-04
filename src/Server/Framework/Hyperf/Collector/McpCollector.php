<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf\Collector;

use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpTool;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\AnnotationCollector;
use RuntimeException;

class McpCollector
{
    protected static bool $collect = false;

    /**
     * @var array<string, array<string, RegisteredTool>>
     */
    protected static array $tools = [];

    /**
     * @return array<string, RegisteredTool>
     */
    public static function getTools(string $group = ''): array
    {
        self::collect();
        return self::$tools[$group] ?? [];
    }

    public static function collect(): void
    {
        if (self::$collect) {
            return;
        }

        self::collectTools();

        self::$collect = true;
    }

    protected static function collectTools(): void
    {
        $mcpToolAnnotations = AnnotationCollector::getMethodsByAnnotation(McpTool::class);
        var_dump($mcpToolAnnotations);
        foreach ($mcpToolAnnotations as $data) {
            $class = $data['class'] ?? '';
            $method = $data['method'] ?? '';
            /** @var McpTool $mcpTool */
            $mcpTool = $data['annotation'] ?? null;
            if (empty($class) || empty($method) || empty($mcpTool)) {
                continue;
            }
            if (! $mcpTool->isEnabled()) {
                continue;
            }
            $registeredTool = new RegisteredTool(
                new Tool(
                    $mcpTool->getName(),
                    $mcpTool->getInputSchema(),
                    $mcpTool->getDescription()
                ),
                function (array $arguments) use ($class, $method) {
                    $container = ApplicationContext::getContainer();
                    if (method_exists($container, 'make')) {
                        $instance = $container->make($class);
                    }
                    if (! isset($instance) || ! method_exists($instance, $method)) {
                        throw new RuntimeException("Method {$method} does not exist in class {$class}");
                    }
                    return $instance->{$method}(...$arguments);
                }
            );
            self::$tools[$mcpTool->getGroup()][$mcpTool->getName()] = $registeredTool;
        }
    }
}
