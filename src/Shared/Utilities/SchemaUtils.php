<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Shared\Utilities;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class SchemaUtils
{
    /**
     * Generate input schema from method reflection.
     *
     * @param string $class The class name
     * @param string $method The method name
     * @return array<string, mixed> The generated JSON Schema
     * @throws ValidationError When class or method doesn't exist, or contains unsupported types
     */
    public static function generateInputSchemaByClassMethod(string $class, string $method): array
    {
        // Validate inputs
        if (empty(trim($class))) {
            throw ValidationError::emptyField('class');
        }

        if (empty(trim($method))) {
            throw ValidationError::emptyField('method');
        }

        // Check if class exists
        if (! class_exists($class)) {
            throw ValidationError::invalidFieldValue('class', "Class '{$class}' does not exist");
        }

        try {
            $reflectionClass = new ReflectionClass($class);

            // Check if method exists
            if (! $reflectionClass->hasMethod($method)) {
                throw ValidationError::invalidFieldValue('method', "Method '{$method}' does not exist in class '{$class}'");
            }

            $reflectionMethod = $reflectionClass->getMethod($method);
            $parameters = $reflectionMethod->getParameters();

            $properties = [];
            $required = [];

            foreach ($parameters as $parameter) {
                $paramName = $parameter->getName();
                $paramSchema = self::generateParameterSchema($parameter, $class, $method);

                if ($paramSchema) {
                    $properties[$paramName] = $paramSchema;

                    // If parameter has no default value and is not nullable, it's required
                    if (! $parameter->isOptional() && ! $parameter->allowsNull()) {
                        $required[] = $paramName;
                    }
                }
            }

            $schema = [
                'type' => 'object',
                'properties' => $properties,
            ];

            if (! empty($required)) {
                $schema['required'] = $required;
            }

            return $schema;
        } catch (ReflectionException $e) {
            throw ValidationError::invalidFieldValue('class', "Reflection error: {$e->getMessage()}");
        }
    }

    /**
     * Generate schema for a single parameter.
     *
     * @param ReflectionParameter $parameter The parameter to analyze
     * @param string $class The class name (for error context)
     * @param string $method The method name (for error context)
     * @return array<string, mixed> The parameter schema
     * @throws ValidationError When parameter type is not supported
     */
    private static function generateParameterSchema(ReflectionParameter $parameter, string $class, string $method): array
    {
        $type = $parameter->getType();
        $paramName = $parameter->getName();

        if (! $type) {
            // No type hint, default to string
            $schema = [
                'type' => 'string',
                'description' => "Parameter: {$paramName} (no type hint)",
            ];

            // Add default value if available
            if ($parameter->isDefaultValueAvailable()) {
                try {
                    $defaultValue = $parameter->getDefaultValue();
                    if ($defaultValue !== null) {
                        $schema['default'] = $defaultValue;
                    }
                } catch (Exception $e) {
                    // Ignore if default value cannot be retrieved
                }
            }

            return $schema;
        }

        if ($type instanceof ReflectionNamedType) {
            return self::generateSchemaFromNamedType($type, $parameter, $class, $method);
        }

        if ($type instanceof ReflectionUnionType) {
            throw ValidationError::invalidFieldValue(
                'parameter_type',
                "Parameter '{$paramName}' in {$class}::{$method}() has union type which is not allowed. Only basic types (string, int, float, bool, array) are supported"
            );
        }

        throw ValidationError::invalidFieldValue(
            'parameter_type',
            "Unsupported parameter type for '{$paramName}' in {$class}::{$method}()"
        );
    }

    /**
     * Generate schema from named type.
     *
     * @param ReflectionNamedType $type The reflection type
     * @param ReflectionParameter $parameter The parameter
     * @param string $class The class name (for error context)
     * @param string $method The method name (for error context)
     * @return array<string, mixed> The parameter schema
     * @throws ValidationError When type is not a basic type
     */
    private static function generateSchemaFromNamedType(
        ReflectionNamedType $type,
        ReflectionParameter $parameter,
        string $class,
        string $method
    ): array {
        $typeName = $type->getName();
        $paramName = $parameter->getName();
        $schema = [];

        switch ($typeName) {
            case 'string':
                $schema['type'] = 'string';
                break;
            case 'int':
            case 'integer':
                $schema['type'] = 'integer';
                break;
            case 'float':
            case 'double':
                $schema['type'] = 'number';
                break;
            case 'bool':
            case 'boolean':
                $schema['type'] = 'boolean';
                break;
            case 'array':
                $schema['type'] = 'array';
                $schema['items'] = ['type' => 'string']; // Default to string items
                break;
            default:
                // For any other type (classes, interfaces, etc.), throw an error
                throw ValidationError::invalidFieldValue(
                    'parameter_type',
                    "Parameter '{$paramName}' in {$class}::{$method}() has unsupported type '{$typeName}'. Only basic types (string, int, float, bool, array) are allowed"
                );
        }

        // Add parameter description
        $schema['description'] = "Parameter: {$paramName}";

        // Add default value if available
        if ($parameter->isDefaultValueAvailable()) {
            try {
                $defaultValue = $parameter->getDefaultValue();
                if ($defaultValue !== null) {
                    $schema['default'] = $defaultValue;
                }
            } catch (Exception $e) {
                // Ignore if default value cannot be retrieved
            }
        }

        return $schema;
    }
}
