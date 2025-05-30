<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Resources;

use Dtyq\PhpMcp\Shared\Exceptions\ResourceError;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\ResourceContents;
use Dtyq\PhpMcp\Types\Resources\ResourceTemplate;

/**
 * Simple resource registration manager.
 *
 * Manages resource registration and access, including resource templates.
 */
class ResourceManager
{
    /** @var array<string, RegisteredResource> Registered resources indexed by URI */
    private array $resources = [];

    /** @var array<string, RegisteredResourceTemplate> Registered resource templates indexed by URI template */
    private array $templates = [];

    /**
     * Register a resource.
     */
    public function register(RegisteredResource $registeredResource): void
    {
        $this->resources[$registeredResource->getUri()] = $registeredResource;
    }

    /**
     * Register a resource template.
     */
    public function registerTemplate(RegisteredResourceTemplate $registeredTemplate): void
    {
        $this->templates[$registeredTemplate->getUriTemplate()] = $registeredTemplate;
    }

    /**
     * Get a registered resource by URI.
     */
    public function get(string $uri): ?RegisteredResource
    {
        return $this->resources[$uri] ?? null;
    }

    /**
     * Get a registered resource template by URI template.
     */
    public function getTemplate(string $uriTemplate): ?RegisteredResourceTemplate
    {
        return $this->templates[$uriTemplate] ?? null;
    }

    /**
     * Check if resource exists.
     */
    public function has(string $uri): bool
    {
        return isset($this->resources[$uri]);
    }

    /**
     * Check if resource template exists.
     */
    public function hasTemplate(string $uriTemplate): bool
    {
        return isset($this->templates[$uriTemplate]);
    }

    /**
     * Remove a resource.
     */
    public function remove(string $uri): bool
    {
        if (isset($this->resources[$uri])) {
            unset($this->resources[$uri]);
            return true;
        }
        return false;
    }

    /**
     * Remove a resource template.
     */
    public function removeTemplate(string $uriTemplate): bool
    {
        if (isset($this->templates[$uriTemplate])) {
            unset($this->templates[$uriTemplate]);
            return true;
        }
        return false;
    }

    /**
     * Get all resource URIs.
     *
     * @return array<string>
     */
    public function getUris(): array
    {
        return array_keys($this->resources);
    }

    /**
     * Get all template URI templates.
     *
     * @return array<string>
     */
    public function getTemplateUris(): array
    {
        return array_keys($this->templates);
    }

    /**
     * Get all registered resources.
     *
     * @return array<RegisteredResource>
     */
    public function getAll(): array
    {
        return array_values($this->resources);
    }

    /**
     * Get all registered resource templates.
     *
     * @return array<RegisteredResourceTemplate>
     */
    public function getAllTemplates(): array
    {
        return array_values($this->templates);
    }

    /**
     * Get resource count.
     */
    public function count(): int
    {
        return count($this->resources);
    }

    /**
     * Get template count.
     */
    public function countTemplates(): int
    {
        return count($this->templates);
    }

    /**
     * Clear all resources.
     */
    public function clear(): void
    {
        $this->resources = [];
    }

    /**
     * Clear all resource templates.
     */
    public function clearTemplates(): void
    {
        $this->templates = [];
    }

    /**
     * Access a resource by URI.
     */
    public function getContent(string $uri): ResourceContents
    {
        // First check for exact resource match
        $registeredResource = $this->get($uri);
        if ($registeredResource !== null) {
            return $registeredResource->getContent();
        }

        // Then check templates for pattern match
        foreach ($this->templates as $template) {
            $parameters = $this->matchUriTemplate($template->getUriTemplate(), $uri);
            if ($parameters !== null) {
                return $template->generateContent($parameters);
            }
        }

        throw ResourceError::unknownResource($uri);
    }

    /**
     * Generate resource content from template.
     *
     * @param array<string, mixed> $parameters Template parameters
     */
    public function generateFromTemplate(string $uriTemplate, array $parameters = []): ResourceContents
    {
        $registeredTemplate = $this->getTemplate($uriTemplate);
        if ($registeredTemplate === null) {
            throw ResourceError::unknownResource($uriTemplate);
        }

        return $registeredTemplate->generateContent($parameters);
    }

    /**
     * Find resources by pattern matching against URI.
     *
     * @return array<RegisteredResource>
     */
    public function findByPattern(string $pattern): array
    {
        $matches = [];
        foreach ($this->resources as $uri => $resource) {
            if (fnmatch($pattern, $uri)) {
                $matches[] = $resource;
            }
        }
        return $matches;
    }

    /**
     * Find templates by pattern matching against URI template.
     *
     * @return array<RegisteredResourceTemplate>
     */
    public function findTemplatesByPattern(string $pattern): array
    {
        $matches = [];
        foreach ($this->templates as $uriTemplate => $template) {
            if (fnmatch($pattern, $uriTemplate)) {
                $matches[] = $template;
            }
        }
        return $matches;
    }

    /**
     * Find resources by MIME type.
     *
     * @return array<RegisteredResource>
     */
    public function findByMimeType(string $mimeType): array
    {
        $matches = [];
        foreach ($this->resources as $resource) {
            if ($resource->getMimeType() === $mimeType) {
                $matches[] = $resource;
            }
        }
        return $matches;
    }

    /**
     * Find templates by MIME type.
     *
     * @return array<RegisteredResourceTemplate>
     */
    public function findTemplatesByMimeType(string $mimeType): array
    {
        $matches = [];
        foreach ($this->templates as $template) {
            if ($template->getMimeType() === $mimeType) {
                $matches[] = $template;
            }
        }
        return $matches;
    }

    /**
     * Get all resource metadata without content.
     *
     * @return array<\Dtyq\PhpMcp\Types\Resources\Resource>
     */
    public function getResourceMetadata(): array
    {
        return array_map(
            fn (RegisteredResource $registered) => $registered->getResource(),
            $this->resources
        );
    }

    /**
     * Get all template metadata.
     *
     * @return array<ResourceTemplate>
     */
    public function getTemplateMetadata(): array
    {
        return array_map(
            fn (RegisteredResourceTemplate $registered) => $registered->getTemplate(),
            $this->templates
        );
    }

    /**
     * Match URI against template pattern and extract parameters.
     *
     * @return null|array<string, string>
     */
    private function matchUriTemplate(string $template, string $uri): ?array
    {
        // Convert URI template to regex pattern
        // Replace {variable} with named capture groups
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $template);
        $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Extract only named captures
            $parameters = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $parameters[$key] = $value;
                }
            }
            return $parameters;
        }

        return null;
    }
}
