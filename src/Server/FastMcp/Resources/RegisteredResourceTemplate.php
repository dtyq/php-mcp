<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Resources;

use Closure;
use Dtyq\PhpMcp\Shared\Exceptions\ResourceError;
use Dtyq\PhpMcp\Types\Resources\Resource;
use Dtyq\PhpMcp\Types\Resources\ResourceContents;
use Dtyq\PhpMcp\Types\Resources\ResourceTemplate;
use Exception;
use Opis\Closure\SerializableClosure;

/**
 * Registered resource template definition and access class.
 *
 * Stores resource template metadata and handles dynamic resource generation.
 */
class RegisteredResourceTemplate
{
    /** @var ResourceTemplate Template metadata */
    private ResourceTemplate $template;

    /** @var Closure|SerializableClosure The function to execute for resource generation */
    private $callable;

    /**
     * @param Closure|SerializableClosure $callable
     */
    public function __construct(ResourceTemplate $template, $callable)
    {
        $this->template = $template;
        $this->callable = $callable;
    }

    /**
     * Generate resource content using template parameters.
     *
     * @param array<string, mixed> $parameters Template parameters
     */
    public function generateContent(array $parameters = []): ResourceContents
    {
        try {
            // Execute the callable with parameters
            $result = call_user_func($this->callable, $parameters, $this->template);

            // Ensure result is ResourceContents
            if (! $result instanceof ResourceContents) {
                throw ResourceError::accessFailed(
                    $this->template->getUriTemplate(),
                    new Exception('Resource template callable must return ResourceContents instance')
                );
            }

            return $result;
        } catch (Exception $e) {
            throw ResourceError::accessFailed($this->template->getUriTemplate(), $e);
        }
    }

    /**
     * Get the resource template metadata.
     */
    public function getTemplate(): ResourceTemplate
    {
        return $this->template;
    }

    /**
     * Get template URI template.
     */
    public function getUriTemplate(): string
    {
        return $this->template->getUriTemplate();
    }

    /**
     * Get template name.
     */
    public function getName(): string
    {
        return $this->template->getName();
    }

    /**
     * Get template description.
     */
    public function getDescription(): ?string
    {
        return $this->template->getDescription();
    }

    /**
     * Get template MIME type.
     */
    public function getMimeType(): ?string
    {
        return $this->template->getMimeType();
    }

    /**
     * Get the callable function.
     *
     * @return Closure|SerializableClosure
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * Create a resource template from a callable function.
     */
    public static function fromCallable(
        string $uriTemplate,
        string $name,
        Closure $callable,
        ?string $description = null,
        ?string $mimeType = null
    ): self {
        $template = new ResourceTemplate($uriTemplate, $name, $description, $mimeType);
        return new self($template, $callable);
    }
}
