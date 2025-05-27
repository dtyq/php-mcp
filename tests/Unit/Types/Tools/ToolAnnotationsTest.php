<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Tools;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Tools\ToolAnnotations;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ToolAnnotationsTest extends TestCase
{
    public function testConstructorWithAllData(): void
    {
        $title = 'Test Tool';
        $readOnlyHint = true;
        $destructiveHint = false;
        $idempotentHint = true;
        $openWorldHint = false;

        $annotations = new ToolAnnotations(
            $title,
            $readOnlyHint,
            $destructiveHint,
            $idempotentHint,
            $openWorldHint
        );

        $this->assertSame($title, $annotations->getTitle());
        $this->assertSame($readOnlyHint, $annotations->getReadOnlyHint());
        $this->assertSame($destructiveHint, $annotations->getDestructiveHint());
        $this->assertSame($idempotentHint, $annotations->getIdempotentHint());
        $this->assertSame($openWorldHint, $annotations->getOpenWorldHint());
    }

    public function testConstructorWithDefaults(): void
    {
        $annotations = new ToolAnnotations();

        $this->assertNull($annotations->getTitle());
        $this->assertNull($annotations->getReadOnlyHint());
        $this->assertNull($annotations->getDestructiveHint());
        $this->assertNull($annotations->getIdempotentHint());
        $this->assertNull($annotations->getOpenWorldHint());
        $this->assertTrue($annotations->isEmpty());
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'title' => 'Test Tool',
            'readOnlyHint' => true,
            'destructiveHint' => false,
            'idempotentHint' => true,
            'openWorldHint' => false,
        ];

        $annotations = ToolAnnotations::fromArray($data);

        $this->assertSame($data['title'], $annotations->getTitle());
        $this->assertSame($data['readOnlyHint'], $annotations->getReadOnlyHint());
        $this->assertSame($data['destructiveHint'], $annotations->getDestructiveHint());
        $this->assertSame($data['idempotentHint'], $annotations->getIdempotentHint());
        $this->assertSame($data['openWorldHint'], $annotations->getOpenWorldHint());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $annotations = ToolAnnotations::fromArray([]);

        $this->assertNull($annotations->getTitle());
        $this->assertNull($annotations->getReadOnlyHint());
        $this->assertNull($annotations->getDestructiveHint());
        $this->assertNull($annotations->getIdempotentHint());
        $this->assertNull($annotations->getOpenWorldHint());
    }

    public function testFromArrayWithInvalidTitleType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'title\': expected string, got integer');

        ToolAnnotations::fromArray(['title' => 123]);
    }

    public function testFromArrayWithInvalidReadOnlyHintType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'readOnlyHint\': expected boolean, got string');

        ToolAnnotations::fromArray(['readOnlyHint' => 'true']);
    }

    public function testFromArrayWithInvalidDestructiveHintType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'destructiveHint\': expected boolean, got integer');

        ToolAnnotations::fromArray(['destructiveHint' => 1]);
    }

    public function testFromArrayWithInvalidIdempotentHintType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'idempotentHint\': expected boolean, got integer');

        ToolAnnotations::fromArray(['idempotentHint' => 0]);
    }

    public function testFromArrayWithInvalidOpenWorldHintType(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid type for field \'openWorldHint\': expected boolean, got string');

        ToolAnnotations::fromArray(['openWorldHint' => 'false']);
    }

    public function testSetTitleWithEmptyString(): void
    {
        $annotations = new ToolAnnotations();
        $annotations->setTitle('   ');

        $this->assertNull($annotations->getTitle());
        $this->assertFalse($annotations->hasTitle());
    }

    public function testHasMethods(): void
    {
        $annotations = new ToolAnnotations(
            'Title',
            true,
            false,
            true,
            false
        );

        $this->assertTrue($annotations->hasTitle());
        $this->assertTrue($annotations->hasReadOnlyHint());
        $this->assertTrue($annotations->hasDestructiveHint());
        $this->assertTrue($annotations->hasIdempotentHint());
        $this->assertTrue($annotations->hasOpenWorldHint());
        $this->assertFalse($annotations->isEmpty());
    }

    public function testHasMethodsWithoutData(): void
    {
        $annotations = new ToolAnnotations();

        $this->assertFalse($annotations->hasTitle());
        $this->assertFalse($annotations->hasReadOnlyHint());
        $this->assertFalse($annotations->hasDestructiveHint());
        $this->assertFalse($annotations->hasIdempotentHint());
        $this->assertFalse($annotations->hasOpenWorldHint());
        $this->assertTrue($annotations->isEmpty());
    }

    public function testIsReadOnlyDefaults(): void
    {
        $annotations = new ToolAnnotations();
        $this->assertFalse($annotations->isReadOnly());

        $annotations->setReadOnlyHint(true);
        $this->assertTrue($annotations->isReadOnly());

        $annotations->setReadOnlyHint(false);
        $this->assertFalse($annotations->isReadOnly());
    }

    public function testIsDestructiveDefaults(): void
    {
        $annotations = new ToolAnnotations();
        $this->assertTrue($annotations->isDestructive()); // Default when not read-only

        $annotations->setReadOnlyHint(true);
        $this->assertFalse($annotations->isDestructive()); // Read-only tools are not destructive

        $annotations->setReadOnlyHint(false);
        $annotations->setDestructiveHint(false);
        $this->assertFalse($annotations->isDestructive());

        $annotations->setDestructiveHint(true);
        $this->assertTrue($annotations->isDestructive());
    }

    public function testIsIdempotentDefaults(): void
    {
        $annotations = new ToolAnnotations();
        $this->assertFalse($annotations->isIdempotent()); // Default when not read-only

        $annotations->setReadOnlyHint(true);
        $this->assertTrue($annotations->isIdempotent()); // Read-only tools are idempotent

        $annotations->setReadOnlyHint(false);
        $annotations->setIdempotentHint(true);
        $this->assertTrue($annotations->isIdempotent());

        $annotations->setIdempotentHint(false);
        $this->assertFalse($annotations->isIdempotent());
    }

    public function testIsOpenWorldDefaults(): void
    {
        $annotations = new ToolAnnotations();
        $this->assertTrue($annotations->isOpenWorld()); // Default

        $annotations->setOpenWorldHint(false);
        $this->assertFalse($annotations->isOpenWorld());

        $annotations->setOpenWorldHint(true);
        $this->assertTrue($annotations->isOpenWorld());
    }

    public function testToArray(): void
    {
        $annotations = new ToolAnnotations(
            'Test Tool',
            true,
            false,
            true,
            false
        );

        $expected = [
            'title' => 'Test Tool',
            'readOnlyHint' => true,
            'destructiveHint' => false,
            'idempotentHint' => true,
            'openWorldHint' => false,
        ];

        $this->assertSame($expected, $annotations->toArray());
    }

    public function testToArrayWithEmptyAnnotations(): void
    {
        $annotations = new ToolAnnotations();
        $this->assertSame([], $annotations->toArray());
    }

    public function testToJson(): void
    {
        $annotations = new ToolAnnotations('Test Tool', true);
        $json = $annotations->toJson();
        $decoded = json_decode($json, true);

        $this->assertSame('Test Tool', $decoded['title']);
        $this->assertTrue($decoded['readOnlyHint']);
    }

    public function testWithMethods(): void
    {
        $original = new ToolAnnotations('Original');

        $withTitle = $original->withTitle('New Title');
        $this->assertNotSame($original, $withTitle);
        $this->assertSame('Original', $original->getTitle());
        $this->assertSame('New Title', $withTitle->getTitle());

        $withReadOnly = $original->withReadOnlyHint(true);
        $this->assertNotSame($original, $withReadOnly);
        $this->assertNull($original->getReadOnlyHint());
        $this->assertTrue($withReadOnly->getReadOnlyHint());

        $withDestructive = $original->withDestructiveHint(false);
        $this->assertNotSame($original, $withDestructive);
        $this->assertNull($original->getDestructiveHint());
        $this->assertFalse($withDestructive->getDestructiveHint());

        $withIdempotent = $original->withIdempotentHint(true);
        $this->assertNotSame($original, $withIdempotent);
        $this->assertNull($original->getIdempotentHint());
        $this->assertTrue($withIdempotent->getIdempotentHint());

        $withOpenWorld = $original->withOpenWorldHint(false);
        $this->assertNotSame($original, $withOpenWorld);
        $this->assertNull($original->getOpenWorldHint());
        $this->assertFalse($withOpenWorld->getOpenWorldHint());
    }
}
