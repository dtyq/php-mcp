<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Server\FastMcp\Prompts;

use Closure;
use Dtyq\PhpMcp\Shared\Exceptions\PromptError;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use Exception;

/**
 * Registered prompt definition and execution class.
 *
 * Stores prompt metadata and handles prompt execution.
 */
class RegisteredPrompt
{
    /** @var Prompt Prompt metadata */
    private Prompt $prompt;

    /** @var Closure The function to execute */
    private Closure $callable;

    public function __construct(Prompt $prompt, Closure $callable)
    {
        $this->prompt = $prompt;
        $this->callable = $callable;
    }

    /**
     * Execute the prompt with given arguments.
     *
     * @param array<string, mixed> $arguments
     */
    public function execute(array $arguments): GetPromptResult
    {
        try {
            // Validate arguments
            $this->prompt->validateArguments($arguments);

            // Execute the callable
            $result = call_user_func($this->callable, $arguments);

            if (is_array($result)) {
                $result = json_encode($result, JSON_UNESCAPED_UNICODE);
            }
            if (is_string($result)) {
                $message = new PromptMessage(ProtocolConstants::ROLE_USER, new TextContent($result));
                $result = new GetPromptResult(null, [$message]);
            }

            // Ensure result is GetPromptResult
            if (! $result instanceof GetPromptResult) {
                throw PromptError::executionFailed(
                    $this->prompt->getName(),
                    new Exception('Prompt callable must return GetPromptResult instance')
                );
            }

            return $result;
        } catch (Exception $e) {
            throw PromptError::executionFailed($this->prompt->getName(), $e);
        }
    }

    /**
     * Get prompt metadata.
     */
    public function getPrompt(): Prompt
    {
        return $this->prompt;
    }

    /**
     * Get prompt name.
     */
    public function getName(): string
    {
        return $this->prompt->getName();
    }

    /**
     * Get prompt description.
     */
    public function getDescription(): ?string
    {
        return $this->prompt->getDescription();
    }

    /**
     * Get prompt arguments.
     *
     * @return array<PromptArgument>
     */
    public function getArguments(): array
    {
        return $this->prompt->getArguments();
    }

    /**
     * Check if prompt has arguments.
     */
    public function hasArguments(): bool
    {
        return $this->prompt->hasArguments();
    }

    /**
     * Get required arguments.
     *
     * @return array<PromptArgument>
     */
    public function getRequiredArguments(): array
    {
        return $this->prompt->getRequiredArguments();
    }

    /**
     * Get optional arguments.
     *
     * @return array<PromptArgument>
     */
    public function getOptionalArguments(): array
    {
        return $this->prompt->getOptionalArguments();
    }
}
