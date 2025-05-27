<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Core;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Types\Core\BaseTypes;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BaseTypesTest extends TestCase
{
    public function testValidateProgressTokenWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateProgressToken(null);
        BaseTypes::validateProgressToken('string-token');
        BaseTypes::validateProgressToken(12345);
    }

    public function testValidateProgressTokenWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Argument \'progressToken\' must be a string, integer, or null, double given');

        BaseTypes::validateProgressToken(3.14);
    }

    public function testValidateCursorWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateCursor(null);
        BaseTypes::validateCursor('cursor-string');
        BaseTypes::validateCursor('');
    }

    public function testValidateCursorWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Argument \'cursor\' must be a string or null, integer given');

        BaseTypes::validateCursor(123);
    }

    public function testValidateRoleWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateRole('user');
        BaseTypes::validateRole('assistant');
    }

    public function testValidateRoleWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'role\': must be one of: user, assistant');

        BaseTypes::validateRole('system');
    }

    public function testValidateRequestIdWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateRequestId('string-id');
        BaseTypes::validateRequestId(12345);
    }

    public function testValidateRequestIdWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Argument \'id\' must be a string or integer, double given');

        BaseTypes::validateRequestId(3.14);
    }

    public function testValidateUriWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateUri('https://example.com');
        BaseTypes::validateUri('http://localhost:8080');
        BaseTypes::validateUri('file:///path/to/file');
        BaseTypes::validateUri('relative/path');
    }

    public function testValidateUriWithEmptyString(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Field \'uri\' cannot be empty');

        BaseTypes::validateUri('');
    }

    public function testValidateMimeTypeWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateMimeType(null);
        BaseTypes::validateMimeType('text/plain');
        BaseTypes::validateMimeType('application/json');
        BaseTypes::validateMimeType('image/png');
        BaseTypes::validateMimeType('video/mp4');
    }

    public function testValidateMimeTypeWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'mimeType\': invalid MIME type format');

        BaseTypes::validateMimeType('invalid-mime-type');
    }

    public function testValidateLogLevelWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateLogLevel('debug');
        BaseTypes::validateLogLevel('info');
        BaseTypes::validateLogLevel('warning');
        BaseTypes::validateLogLevel('error');
        BaseTypes::validateLogLevel('emergency');
    }

    public function testValidateLogLevelWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'logLevel\': must be one of:');

        BaseTypes::validateLogLevel('invalid');
    }

    public function testValidateContentTypeWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateContentType('text');
        BaseTypes::validateContentType('image');
        BaseTypes::validateContentType('resource');
    }

    public function testValidateContentTypeWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'contentType\': must be one of:');

        BaseTypes::validateContentType('invalid');
    }

    public function testValidateReferenceTypeWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateReferenceType('ref/resource');
        BaseTypes::validateReferenceType('ref/prompt');
    }

    public function testValidateReferenceTypeWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'referenceType\': must be one of:');

        BaseTypes::validateReferenceType('ref/invalid');
    }

    public function testValidateStopReasonWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validateStopReason(null);
        BaseTypes::validateStopReason('endTurn');
        BaseTypes::validateStopReason('maxTokens');
        BaseTypes::validateStopReason('stopSequence');
        BaseTypes::validateStopReason('toolUse');
    }

    public function testValidateStopReasonWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'stopReason\': must be one of:');

        BaseTypes::validateStopReason('invalid');
    }

    public function testValidatePriorityWithValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        BaseTypes::validatePriority(null);
        BaseTypes::validatePriority(0.0);
        BaseTypes::validatePriority(0.5);
        BaseTypes::validatePriority(1.0);
    }

    public function testValidatePriorityWithInvalidValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'priority\': must be between 0.0 and 1.0');

        BaseTypes::validatePriority(1.5);
    }

    public function testValidatePriorityWithNegativeValue(): void
    {
        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Invalid value for field \'priority\': must be between 0.0 and 1.0');

        BaseTypes::validatePriority(-0.1);
    }

    public function testSanitizeText(): void
    {
        $input = "Hello\x00World\x1FTest\nNewline\tTab";
        $expected = "HelloWorldTest\nNewline\tTab";

        $result = BaseTypes::sanitizeText($input);

        $this->assertEquals($expected, $result);
    }

    public function testSanitizeTextWithCleanInput(): void
    {
        $input = "Clean text with newlines\nand tabs\t.";

        $result = BaseTypes::sanitizeText($input);

        $this->assertEquals($input, $result);
    }

    public function testGenerateIdReturnsString(): void
    {
        $id = BaseTypes::generateId();

        $this->assertIsString($id);
        $this->assertStringStartsWith('mcp_', $id);
    }

    public function testGenerateIdIsUnique(): void
    {
        $id1 = BaseTypes::generateId();
        $id2 = BaseTypes::generateId();

        $this->assertNotEquals($id1, $id2);
    }

    public function testGenerateProgressTokenReturnsString(): void
    {
        $token = BaseTypes::generateProgressToken();

        $this->assertIsString($token);
        $this->assertStringStartsWith('progress_', $token);
    }

    public function testGenerateProgressTokenIsUnique(): void
    {
        $token1 = BaseTypes::generateProgressToken();
        $token2 = BaseTypes::generateProgressToken();

        $this->assertNotEquals($token1, $token2);
    }

    public function testGenerateCursorReturnsString(): void
    {
        $cursor = BaseTypes::generateCursor();

        $this->assertIsString($cursor);
        $this->assertNotEmpty($cursor);
    }

    public function testGenerateCursorIsUnique(): void
    {
        $cursor1 = BaseTypes::generateCursor();
        $cursor2 = BaseTypes::generateCursor();

        $this->assertNotEquals($cursor1, $cursor2);
    }

    public function testGenerateCursorIsBase64Encoded(): void
    {
        $cursor = BaseTypes::generateCursor();

        // Base64 decode should not return false for valid base64
        $decoded = base64_decode($cursor, true);
        $this->assertNotFalse($decoded);

        // Re-encoding should give the same result
        $reencoded = base64_encode($decoded);
        $this->assertEquals($cursor, $reencoded);
    }
}
