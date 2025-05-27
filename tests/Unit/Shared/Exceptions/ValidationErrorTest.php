<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Tests\Unit\Shared\Exceptions;

use Dtyq\PhpMcp\Shared\Exceptions\ErrorCodes;
use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * Test ValidationError exception class.
 * @internal
 */
class ValidationErrorTest extends TestCase
{
    public function testRequiredFieldMissing(): void
    {
        $error = ValidationError::requiredFieldMissing('name', 'user profile');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Required field \'name\' is missing', $error->getMessage());
        $this->assertStringContainsString('user profile', $error->getMessage());
    }

    public function testRequiredFieldMissingWithoutContext(): void
    {
        $error = ValidationError::requiredFieldMissing('name');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Required field \'name\' is missing', $error->getMessage());
        $this->assertStringNotContainsString('for', $error->getMessage());
    }

    public function testInvalidFieldType(): void
    {
        $error = ValidationError::invalidFieldType('age', 'integer', 'string');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Invalid type for field \'age\'', $error->getMessage());
        $this->assertStringContainsString('expected integer', $error->getMessage());
        $this->assertStringContainsString('got string', $error->getMessage());
    }

    public function testInvalidFieldValue(): void
    {
        $error = ValidationError::invalidFieldValue('status', 'must be active or inactive');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Invalid value for field \'status\'', $error->getMessage());
        $this->assertStringContainsString('must be active or inactive', $error->getMessage());
    }

    public function testEmptyField(): void
    {
        $error = ValidationError::emptyField('title');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Field \'title\' cannot be empty', $error->getMessage());
    }

    public function testInvalidContentType(): void
    {
        $error = ValidationError::invalidContentType('text/plain', 'application/json');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Invalid content type', $error->getMessage());
        $this->assertStringContainsString('expected text/plain', $error->getMessage());
        $this->assertStringContainsString('got application/json', $error->getMessage());
    }

    public function testUnsupportedContentType(): void
    {
        $error = ValidationError::unsupportedContentType('video/mp4', 'image processing');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Unsupported content type \'video/mp4\'', $error->getMessage());
        $this->assertStringContainsString('image processing', $error->getMessage());
    }

    public function testUnsupportedContentTypeWithoutContext(): void
    {
        $error = ValidationError::unsupportedContentType('video/mp4');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Unsupported content type \'video/mp4\'', $error->getMessage());
        $this->assertStringNotContainsString('for', $error->getMessage());
    }

    public function testInvalidJsonFormat(): void
    {
        $error = ValidationError::invalidJsonFormat('malformed JSON structure');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertStringContainsString('Invalid JSON format', $error->getMessage());
        $this->assertStringContainsString('malformed JSON structure', $error->getMessage());
    }

    public function testInvalidBase64(): void
    {
        $error = ValidationError::invalidBase64('imageData');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Field \'imageData\' must be valid base64 encoded', $error->getMessage());
    }

    public function testFileOperationError(): void
    {
        $error = ValidationError::fileOperationError('read', '/path/to/file.txt', 'file not found');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Failed to read file \'/path/to/file.txt\'', $error->getMessage());
        $this->assertStringContainsString('file not found', $error->getMessage());
    }

    public function testInvalidArgumentType(): void
    {
        $error = ValidationError::invalidArgumentType('count', 'integer', 'string');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Argument \'count\' must be a integer', $error->getMessage());
        $this->assertStringContainsString('string given', $error->getMessage());
    }

    public function testMissingRequiredArgument(): void
    {
        $error = ValidationError::missingRequiredArgument('uri');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertStringContainsString('Required argument \'uri\' is missing', $error->getMessage());
    }

    public function testConstructorWithData(): void
    {
        $data = ['field' => 'test', 'value' => 123];
        $error = new ValidationError('Test validation error', $data);

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertSame('Test validation error', $error->getMessage());

        $errorData = $error->getErrorData();
        $this->assertSame($data, $errorData);
    }

    public function testConstructorWithoutData(): void
    {
        $error = new ValidationError('Test validation error');

        $this->assertInstanceOf(ValidationError::class, $error);
        $this->assertSame(ErrorCodes::VALIDATION_ERROR, $error->getErrorCode());
        $this->assertSame('Test validation error', $error->getMessage());

        $errorData = $error->getErrorData();
        $this->assertNull($errorData);
    }

    public function testFactoryMethodsWithData(): void
    {
        $data = ['custom' => 'data'];

        $error1 = ValidationError::requiredFieldMissing('name', 'context', $data);
        $this->assertSame($data, $error1->getErrorData());

        $error2 = ValidationError::invalidFieldType('field', 'string', 'int', $data);
        $this->assertSame($data, $error2->getErrorData());

        $error3 = ValidationError::invalidFieldValue('field', 'reason', $data);
        $this->assertSame($data, $error3->getErrorData());
    }
}
