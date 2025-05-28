<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Server\FastMcp\Prompts;

use Dtyq\PhpMcp\Server\FastMcp\Prompts\PromptManager;
use Dtyq\PhpMcp\Server\FastMcp\Prompts\RegisteredPrompt;
use Dtyq\PhpMcp\Shared\Exceptions\PromptError;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use Dtyq\PhpMcp\Types\Prompts\GetPromptResult;
use Dtyq\PhpMcp\Types\Prompts\Prompt;
use Dtyq\PhpMcp\Types\Prompts\PromptArgument;
use Dtyq\PhpMcp\Types\Prompts\PromptMessage;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PromptManager class.
 * @internal
 */
class PromptManagerTest extends TestCase
{
    private PromptManager $promptManager;

    private RegisteredPrompt $samplePrompt;

    protected function setUp(): void
    {
        $this->promptManager = new PromptManager();

        $arguments = [
            new PromptArgument('name', 'The name to greet', true),
            new PromptArgument('style', 'Greeting style', false),
        ];

        $callable = function (array $args): GetPromptResult {
            $name = $args['name'] ?? 'World';
            $style = $args['style'] ?? 'casual';

            $greeting = $style === 'formal'
                ? "Good day, {$name}."
                : "Hello, {$name}!";

            $message = new PromptMessage(
                ProtocolConstants::ROLE_USER,
                new TextContent($greeting)
            );

            return new GetPromptResult('Greeting generated', [$message]);
        };

        $prompt = new Prompt('greeting', 'Generate a greeting message', $arguments);
        $this->samplePrompt = new RegisteredPrompt($prompt, $callable);
    }

    public function testRegisterPrompt(): void
    {
        $this->promptManager->register($this->samplePrompt);

        $this->assertTrue($this->promptManager->has('greeting'));
        $this->assertEquals(1, $this->promptManager->count());
        $this->assertContains('greeting', $this->promptManager->getNames());
    }

    public function testGetPrompt(): void
    {
        $this->promptManager->register($this->samplePrompt);

        $retrievedPrompt = $this->promptManager->get('greeting');
        $this->assertSame($this->samplePrompt, $retrievedPrompt);
    }

    public function testGetNonexistentPrompt(): void
    {
        $retrievedPrompt = $this->promptManager->get('nonexistent');
        $this->assertNull($retrievedPrompt);
    }

    public function testHasPrompt(): void
    {
        $this->assertFalse($this->promptManager->has('greeting'));

        $this->promptManager->register($this->samplePrompt);
        $this->assertTrue($this->promptManager->has('greeting'));
    }

    public function testRemovePrompt(): void
    {
        $this->promptManager->register($this->samplePrompt);
        $this->assertTrue($this->promptManager->has('greeting'));

        $result = $this->promptManager->remove('greeting');
        $this->assertTrue($result);
        $this->assertFalse($this->promptManager->has('greeting'));

        // Try to remove again
        $result = $this->promptManager->remove('greeting');
        $this->assertFalse($result);
    }

    public function testGetNames(): void
    {
        $this->assertEquals([], $this->promptManager->getNames());

        $this->promptManager->register($this->samplePrompt);
        $this->assertEquals(['greeting'], $this->promptManager->getNames());

        // Add another prompt
        $codeReviewPrompt = new RegisteredPrompt(
            new Prompt('code_review', 'Generate code review template'),
            fn (array $args) => new GetPromptResult('Code review template', [])
        );
        $this->promptManager->register($codeReviewPrompt);

        $names = $this->promptManager->getNames();
        $this->assertCount(2, $names);
        $this->assertContains('greeting', $names);
        $this->assertContains('code_review', $names);
    }

    public function testGetAll(): void
    {
        $this->assertEquals([], $this->promptManager->getAll());

        $this->promptManager->register($this->samplePrompt);
        $prompts = $this->promptManager->getAll();

        $this->assertCount(1, $prompts);
        $this->assertSame($this->samplePrompt, $prompts[0]);
    }

    public function testCount(): void
    {
        $this->assertEquals(0, $this->promptManager->count());

        $this->promptManager->register($this->samplePrompt);
        $this->assertEquals(1, $this->promptManager->count());

        $this->promptManager->remove('greeting');
        $this->assertEquals(0, $this->promptManager->count());
    }

    public function testClear(): void
    {
        $this->promptManager->register($this->samplePrompt);
        $this->assertEquals(1, $this->promptManager->count());

        $this->promptManager->clear();
        $this->assertEquals(0, $this->promptManager->count());
        $this->assertFalse($this->promptManager->has('greeting'));
    }

    public function testExecuteSuccess(): void
    {
        $this->promptManager->register($this->samplePrompt);

        $result = $this->promptManager->execute('greeting', ['name' => 'Alice', 'style' => 'formal']);

        $this->assertInstanceOf(GetPromptResult::class, $result);
        $this->assertEquals('Greeting generated', $result->getDescription());
        $this->assertCount(1, $result->getMessages());
        $this->assertEquals('Good day, Alice.', $result->getMessages()[0]->getTextContent());
    }

    public function testExecuteUnknownPrompt(): void
    {
        $this->expectException(PromptError::class);
        $this->expectExceptionMessage('Unknown prompt: nonexistent');

        $this->promptManager->execute('nonexistent', []);
    }

    public function testExecuteWithValidationError(): void
    {
        $this->promptManager->register($this->samplePrompt);

        $this->expectException(PromptError::class);
        $this->expectExceptionMessage('Error executing prompt greeting:');

        // Missing required parameter 'name'
        $this->promptManager->execute('greeting', ['style' => 'formal']);
    }

    public function testRegisterMultiplePrompts(): void
    {
        $codeReviewPrompt = new RegisteredPrompt(
            new Prompt('code_review', 'Generate code review template', [
                new PromptArgument('language', 'Programming language', true),
            ]),
            function (array $args): GetPromptResult {
                $language = $args['language'] ?? 'unknown';
                $content = "# Code Review Template for {$language}";

                $message = new PromptMessage(
                    ProtocolConstants::ROLE_USER,
                    new TextContent($content)
                );

                return new GetPromptResult('Code review template generated', [$message]);
            }
        );

        $this->promptManager->register($this->samplePrompt);
        $this->promptManager->register($codeReviewPrompt);

        $this->assertEquals(2, $this->promptManager->count());
        $this->assertTrue($this->promptManager->has('greeting'));
        $this->assertTrue($this->promptManager->has('code_review'));

        // Test execution of both prompts
        $greetingResult = $this->promptManager->execute('greeting', ['name' => 'Alice']);
        $this->assertEquals('Hello, Alice!', $greetingResult->getMessages()[0]->getTextContent());

        $codeResult = $this->promptManager->execute('code_review', ['language' => 'PHP']);
        $this->assertEquals('# Code Review Template for PHP', $codeResult->getMessages()[0]->getTextContent());
    }

    public function testRegisterOverwritesPrompt(): void
    {
        $this->promptManager->register($this->samplePrompt);
        $this->assertEquals(1, $this->promptManager->count());

        // Create a different prompt with the same name
        $newPrompt = new RegisteredPrompt(
            new Prompt('greeting', 'Different greeting prompt'),
            fn (array $args) => new GetPromptResult('Different greeting', [])
        );

        $this->promptManager->register($newPrompt);
        $this->assertEquals(1, $this->promptManager->count()); // Still only one prompt

        $retrievedPrompt = $this->promptManager->get('greeting');
        $this->assertSame($newPrompt, $retrievedPrompt);
        $this->assertNotSame($this->samplePrompt, $retrievedPrompt);
    }

    public function testPromptWithComplexArguments(): void
    {
        $complexPrompt = new RegisteredPrompt(
            new Prompt('complex', 'Complex prompt with multiple arguments', [
                new PromptArgument('title', 'Document title', true),
                new PromptArgument('author', 'Document author', false),
                new PromptArgument('sections', 'Number of sections', false),
                new PromptArgument('format', 'Output format', false),
            ]),
            function (array $args): GetPromptResult {
                $title = $args['title'];
                $author = $args['author'] ?? 'Anonymous';
                $sections = (int) ($args['sections'] ?? 3);
                $format = $args['format'] ?? 'markdown';

                $content = "# {$title}\n\nBy: {$author}\n\n";
                for ($i = 1; $i <= $sections; ++$i) {
                    $content .= "## Section {$i}\n\n";
                }
                $content .= "Format: {$format}";

                $message = new PromptMessage(
                    ProtocolConstants::ROLE_USER,
                    new TextContent($content)
                );

                return new GetPromptResult('Document template created', [$message]);
            }
        );

        $this->promptManager->register($complexPrompt);

        $result = $this->promptManager->execute('complex', [
            'title' => 'My Document',
            'author' => 'John Doe',
            'sections' => '2',
        ]);

        $content = $result->getMessages()[0]->getTextContent();
        $this->assertStringContainsString('# My Document', $content);
        $this->assertStringContainsString('By: John Doe', $content);
        $this->assertStringContainsString('## Section 1', $content);
        $this->assertStringContainsString('## Section 2', $content);
        $this->assertStringNotContainsString('## Section 3', $content);
        $this->assertStringContainsString('Format: markdown', $content);
    }

    public function testPromptWithNoArguments(): void
    {
        $simplePrompt = new RegisteredPrompt(
            new Prompt('simple', 'Simple prompt without arguments'),
            fn (array $args) => new GetPromptResult('Simple result', [
                new PromptMessage(
                    ProtocolConstants::ROLE_USER,
                    new TextContent('This is a simple prompt result.')
                ),
            ])
        );

        $this->promptManager->register($simplePrompt);

        $result = $this->promptManager->execute('simple', []);
        $this->assertEquals('Simple result', $result->getDescription());
        $this->assertEquals('This is a simple prompt result.', $result->getMessages()[0]->getTextContent());
    }
}
