<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\Framework\Hyperf\Collector;

use Dtyq\PhpMcp\Server\FastMcp\Prompts\RegisteredPrompt;
use Dtyq\PhpMcp\Server\FastMcp\Resources\RegisteredResource;
use Dtyq\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpPrompt;
use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpResource;
use Dtyq\PhpMcp\Server\Framework\Hyperf\Collector\Annotations\McpTool;
use Dtyq\PhpMcp\Shared\Exceptions\SystemException;
use Dtyq\PhpMcp\Shared\Utilities\StaticMethodCall;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Tools\Tool;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\AnnotationCollector;
use ReflectionMethod;

class McpCollector
{
    protected static bool $collect = false;

    /**
     * @var array<string, array<string, RegisteredTool>>
     */
    protected static array $tools = [];

    /**
     * @var array<string, RegisteredTool>
     */
    protected static array $globalTools = [];

    /**
     * @var array<string, array<string, RegisteredPrompt>>
     */
    protected static array $prompts = [];

    /**
     * @var array<string, RegisteredPrompt>
     */
    protected static array $globalPrompts = [];

    /**
     * @return array<string, array<string, RegisteredResource>>
     */
    protected static array $resources = [];

    /**
     * @var array<string, RegisteredResource>
     */
    protected static array $globalResources = [];

    /**
     * @param mixed $version
     * @return array<string, RegisteredTool>
     */
    public static function getTools(string $server = '', $version = ''): array
    {
        self::collect();
        $current = self::$tools[self::createGroup($server, $version)] ?? [];
        return array_merge(self::$globalTools, $current);
    }

    /**
     * @param mixed $version
     * @return array<string, RegisteredPrompt>
     */
    public static function getPrompts(string $server = '', $version = ''): array
    {
        self::collect();
        $current = self::$prompts[self::createGroup($server, $version)] ?? [];
        return array_merge(self::$globalPrompts, $current);
    }

    /**
     * @param mixed $version
     * @return array<string, RegisteredResource>
     */
    public static function getResources(string $server = '', $version = ''): array
    {
        self::collect();
        $current = self::$resources[self::createGroup($server, $version)] ?? [];
        return array_merge(self::$globalResources, $current);
    }

    public static function collect(): void
    {
        if (self::$collect) {
            return;
        }

        self::collectTools();
        self::collectPrompts();
        self::collectResources();

        self::$collect = true;
    }

    protected static function collectTools(): void
    {
        $mcpToolAnnotations = AnnotationCollector::getMethodsByAnnotation(McpTool::class);
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

            $tool = new Tool(
                $mcpTool->getName(),
                $mcpTool->getInputSchema(),
                $mcpTool->getDescription()
            );

            // Check if method is static - use StaticMethodCall for better serialization
            if (self::isStaticMethod($class, $method)) {
                $registeredTool = RegisteredTool::withStaticMethod(
                    $tool,
                    new StaticMethodCall($class, $method)
                );
            } else {
                // Use closure for instance methods (Hyperf DI container support)
                $registeredTool = RegisteredTool::withCallable(
                    $tool,
                    function (array $arguments) use ($class, $method) {
                        $container = ApplicationContext::getContainer();
                        if (method_exists($container, 'make')) {
                            $instance = $container->make($class);
                        }
                        if (! isset($instance) || ! method_exists($instance, $method)) {
                            throw new SystemException("Method {$method} does not exist in class {$class}");
                        }
                        return $instance->{$method}(...$arguments);
                    }
                );
            }

            if ($mcpTool->getServer()) {
                self::$tools[self::createGroup($mcpTool->getServer(), $mcpTool->getVersion())][$mcpTool->getName()] = $registeredTool;
            } else {
                self::$globalTools[$mcpTool->getName()] = $registeredTool;
            }
        }
    }

    protected static function collectPrompts(): void
    {
        $mcpPromptAnnotations = AnnotationCollector::getMethodsByAnnotation(McpPrompt::class);
        foreach ($mcpPromptAnnotations as $data) {
            $class = $data['class'] ?? '';
            $method = $data['method'] ?? '';
            /** @var McpPrompt $mcpPrompt */
            $mcpPrompt = $data['annotation'] ?? null;
            if (empty($class) || empty($method) || empty($mcpPrompt)) {
                continue;
            }
            if (! $mcpPrompt->isEnabled()) {
                continue;
            }

            $prompt = new Prompt(
                $mcpPrompt->getName(),
                $mcpPrompt->getDescription(),
                $mcpPrompt->getArguments(),
            );

            // Check if method is static - use StaticMethodCall for better serialization
            if (self::isStaticMethod($class, $method)) {
                $registeredPrompt = RegisteredPrompt::withStaticMethod(
                    $prompt,
                    new StaticMethodCall($class, $method)
                );
            } else {
                // Use closure for instance methods (Hyperf DI container support)
                $registeredPrompt = RegisteredPrompt::withCallable(
                    $prompt,
                    function (array $arguments) use ($class, $method) {
                        $container = ApplicationContext::getContainer();
                        if (method_exists($container, 'make')) {
                            $instance = $container->make($class);
                        }
                        if (! isset($instance) || ! method_exists($instance, $method)) {
                            throw new SystemException("Method {$method} does not exist in class {$class}");
                        }
                        return $instance->{$method}(...$arguments);
                    }
                );
            }

            if ($mcpPrompt->getServer()) {
                self::$prompts[self::createGroup($mcpPrompt->getServer(), $mcpPrompt->getVersion())][$mcpPrompt->getName()] = $registeredPrompt;
            } else {
                self::$globalPrompts[$mcpPrompt->getName()] = $registeredPrompt;
            }
        }
    }

    protected static function collectResources(): void
    {
        $mcpResourceAnnotations = AnnotationCollector::getMethodsByAnnotation(McpResource::class);
        foreach ($mcpResourceAnnotations as $data) {
            $class = $data['class'] ?? '';
            $method = $data['method'] ?? '';
            /** @var McpResource $mcpResource */
            $mcpResource = $data['annotation'] ?? null;
            if (empty($class) || empty($method) || empty($mcpResource)) {
                continue;
            }
            if (! $mcpResource->isEnabled()) {
                continue;
            }

            $resourceMeta = new Resource(
                $mcpResource->getUri(),
                $mcpResource->getName(),
                $mcpResource->getDescription()
            );

            // Check if method is static - use StaticMethodCall for better serialization
            if (self::isStaticMethod($class, $method)) {
                $resource = RegisteredResource::withStaticMethod(
                    $resourceMeta,
                    new StaticMethodCall($class, $method)
                );
            } else {
                // Use closure for instance methods (Hyperf DI container support)
                $resource = RegisteredResource::withCallable(
                    $resourceMeta,
                    function () use ($class, $method) {
                        $container = ApplicationContext::getContainer();
                        if (method_exists($container, 'make')) {
                            $instance = $container->make($class);
                        }
                        if (! isset($instance) || ! method_exists($instance, $method)) {
                            throw new SystemException("Method {$method} does not exist in class {$class}");
                        }
                        return $instance->{$method}();
                    }
                );
            }

            if ($mcpResource->getServer()) {
                self::$resources[self::createGroup($mcpResource->getServer(), $mcpResource->getVersion())][$mcpResource->getName()] = $resource;
            } else {
                self::$globalResources[$mcpResource->getUri()] = $resource;
            }
        }
    }

    /**
     * Check if a method is static.
     */
    protected static function isStaticMethod(string $class, string $method): bool
    {
        try {
            $reflection = new ReflectionMethod($class, $method);
            return $reflection->isStatic() && $reflection->isPublic();
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    private static function createGroup(string $server, string $version): string
    {
        return md5($server . $version);
    }
}
