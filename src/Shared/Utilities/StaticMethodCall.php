<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Utilities;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use ReflectionException;
use ReflectionMethod;

/**
 * Static method call definition for serialization-safe callable replacement.
 *
 * This class provides an alternative to closures that can be safely serialized.
 * It stores a static method reference (class name + method name) along with
 * optional bound parameters that will be merged with runtime arguments.
 *
 * Usage:
 * ```php
 * // Simple static method call
 * $call = new StaticMethodCall(MyClass::class, 'handleTool');
 *
 * // With bound parameters (will be merged with runtime arguments)
 * $call = new StaticMethodCall(MyClass::class, 'handleTool', ['config' => 'value']);
 *
 * // Execute with runtime arguments
 * $result = $call->invoke(['arg1' => 'value1']);
 * ```
 */
class StaticMethodCall
{
    /**
     * @var string Fully qualified class name
     */
    private string $className;

    /**
     * @var string Static method name
     */
    private string $methodName;

    /**
     * @var array<string, mixed> Bound parameters (scalar values only)
     */
    private array $boundParams;

    /**
     * Create a new static method call definition.
     *
     * @param string $className Fully qualified class name
     * @param string $methodName Static method name
     * @param array<string, mixed> $boundParams Optional bound parameters (scalar values only)
     * @throws ValidationError If validation fails
     */
    public function __construct(string $className, string $methodName, array $boundParams = [])
    {
        $this->validateClassName($className);
        $this->validateMethodName($className, $methodName);
        $this->validateBoundParams($boundParams);

        $this->className = $className;
        $this->methodName = $methodName;
        $this->boundParams = $boundParams;
    }

    /**
     * Invoke the static method with given arguments.
     *
     * Bound parameters are merged with runtime arguments, with runtime arguments
     * taking precedence over bound parameters.
     *
     * @param array<string, mixed> $arguments Runtime arguments
     * @return mixed The method return value
     */
    public function invoke(array $arguments = [])
    {
        // Merge bound params with runtime arguments (runtime takes precedence)
        $mergedArgs = array_merge($this->boundParams, $arguments);

        return call_user_func([$this->className, $this->methodName], $mergedArgs);
    }

    /**
     * Get the class name.
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Get the method name.
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * Get the bound parameters.
     *
     * @return array<string, mixed>
     */
    public function getBoundParams(): array
    {
        return $this->boundParams;
    }

    /**
     * Check if the method call has bound parameters.
     */
    public function hasBoundParams(): bool
    {
        return ! empty($this->boundParams);
    }

    /**
     * Create a callable array representation.
     *
     * @return array{0: string, 1: string}
     */
    public function toCallable(): array
    {
        return [$this->className, $this->methodName];
    }

    /**
     * Convert to array for serialization.
     *
     * @return array{class: string, method: string, params: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'class' => $this->className,
            'method' => $this->methodName,
            'params' => $this->boundParams,
        ];
    }

    /**
     * Create from array representation.
     *
     * @param array{class: string, method: string, params?: array<string, mixed>} $data
     * @throws ValidationError If validation fails
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['class']) || ! is_string($data['class'])) {
            throw ValidationError::requiredFieldMissing('class');
        }

        if (! isset($data['method']) || ! is_string($data['method'])) {
            throw ValidationError::requiredFieldMissing('method');
        }

        $params = [];
        if (isset($data['params']) && is_array($data['params'])) {
            $params = $data['params'];
        }

        return new self($data['class'], $data['method'], $params);
    }

    /**
     * Validate class name.
     *
     * @throws ValidationError If class does not exist
     */
    private function validateClassName(string $className): void
    {
        if (empty($className)) {
            throw ValidationError::emptyField('className');
        }

        if (! class_exists($className)) {
            throw ValidationError::invalidFieldValue('className', $className, 'Class does not exist');
        }
    }

    /**
     * Validate method name and ensure it's a static method.
     *
     * @throws ValidationError If method does not exist or is not static
     */
    private function validateMethodName(string $className, string $methodName): void
    {
        if (empty($methodName)) {
            throw ValidationError::emptyField('methodName');
        }

        if (! method_exists($className, $methodName)) {
            throw ValidationError::invalidFieldValue(
                'methodName',
                $methodName,
                sprintf('Method does not exist in class %s', $className)
            );
        }

        try {
            $reflection = new ReflectionMethod($className, $methodName);
            if (! $reflection->isStatic()) {
                throw ValidationError::invalidFieldValue(
                    'methodName',
                    $methodName,
                    'Method must be static for serialization safety'
                );
            }

            if (! $reflection->isPublic()) {
                throw ValidationError::invalidFieldValue(
                    'methodName',
                    $methodName,
                    'Method must be public'
                );
            }
        } catch (ReflectionException $e) {
            throw ValidationError::invalidFieldValue(
                'methodName',
                $methodName,
                sprintf('Failed to reflect method: %s', $e->getMessage())
            );
        }
    }

    /**
     * Validate bound parameters (must be scalar or null).
     *
     * @param array<string, mixed> $params
     * @throws ValidationError If parameters contain non-scalar values
     */
    private function validateBoundParams(array $params): void
    {
        foreach ($params as $key => $value) {
            if (! $this->isSerializablePrimitive($value)) {
                throw ValidationError::invalidFieldValue(
                    'boundParams.' . $key,
                    gettype($value),
                    'Bound parameters must be scalar values (string, int, float, bool, null) or arrays of scalar values'
                );
            }
        }
    }

    /**
     * Check if a value is a serializable primitive (scalar, null, or array of such).
     *
     * @param mixed $value
     */
    private function isSerializablePrimitive($value): bool
    {
        if (is_scalar($value) || $value === null) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->isSerializablePrimitive($item)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }
}
