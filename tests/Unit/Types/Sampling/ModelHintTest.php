<?php

declare(strict_types=1);

namespace Dtyq\PhpMcp\Tests\Unit\Types\Sampling;

use Dtyq\PhpMcp\Types\Sampling\ModelHint;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

class ModelHintTest extends TestCase
{
    public function testConstructorWithValidName(): void
    {
        $hint = new ModelHint('claude-3-sonnet');

        $this->assertSame('claude-3-sonnet', $hint->getName());
    }

    public function testConstructorWithEmptyName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'name\' cannot be empty');

        new ModelHint('');
    }

    public function testFromArrayWithValidData(): void
    {
        $data = ['name' => 'gpt-4'];
        $hint = ModelHint::fromArray($data);

        $this->assertSame('gpt-4', $hint->getName());
    }

    public function testFromArrayWithMissingName(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Required field \'name\' is missing');

        ModelHint::fromArray([]);
    }

    public function testFromArrayWithInvalidNameType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'name\': expected string, got integer');

        ModelHint::fromArray(['name' => 123]);
    }

    public function testSetName(): void
    {
        $hint = new ModelHint('original');
        $hint->setName('updated');

        $this->assertSame('updated', $hint->getName());
    }

    public function testWithName(): void
    {
        $original = new ModelHint('original');
        $modified = $original->withName('modified');

        $this->assertNotSame($original, $modified);
        $this->assertSame('original', $original->getName());
        $this->assertSame('modified', $modified->getName());
    }

    public function testMatches(): void
    {
        $hint = new ModelHint('claude');

        $this->assertTrue($hint->matches('claude-3-sonnet'));
        $this->assertTrue($hint->matches('Claude-3-Opus'));
        $this->assertFalse($hint->matches('gpt-4'));
    }

    public function testToArray(): void
    {
        $hint = new ModelHint('test-model');
        $expected = ['name' => 'test-model'];

        $this->assertSame($expected, $hint->toArray());
    }

    public function testToJson(): void
    {
        $hint = new ModelHint('test-model');
        $json = $hint->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('test-model', $decoded['name']);
    }

    public function testToString(): void
    {
        $hint = new ModelHint('test-model');

        $this->assertSame('test-model', (string) $hint);
    }
} 