<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Unit\Types\Core;

use Dtyq\PhpMcp\Types\Core\ProtocolConstants;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ProtocolConstantsTest extends TestCase
{
    public function testProtocolVersionConstant(): void
    {
        $this->assertEquals('2025-03-26', ProtocolConstants::LATEST_PROTOCOL_VERSION);
    }

    public function testJsonRpcVersionConstant(): void
    {
        $this->assertEquals('2.0', ProtocolConstants::JSONRPC_VERSION);
    }

    public function testStandardJsonRpcErrorCodes(): void
    {
        $this->assertEquals(-32700, ProtocolConstants::PARSE_ERROR);
        $this->assertEquals(-32600, ProtocolConstants::INVALID_REQUEST);
        $this->assertEquals(-32601, ProtocolConstants::METHOD_NOT_FOUND);
        $this->assertEquals(-32602, ProtocolConstants::INVALID_PARAMS);
        $this->assertEquals(-32603, ProtocolConstants::INTERNAL_ERROR);
    }

    public function testMcpSpecificErrorCodes(): void
    {
        $this->assertEquals(-32000, ProtocolConstants::MCP_ERROR);
        $this->assertEquals(-32001, ProtocolConstants::AUTHENTICATION_ERROR);
        $this->assertEquals(-32002, ProtocolConstants::AUTHORIZATION_ERROR);
        $this->assertEquals(-32003, ProtocolConstants::RESOURCE_NOT_FOUND);
        $this->assertEquals(-32004, ProtocolConstants::TOOL_NOT_FOUND);
        $this->assertEquals(-32005, ProtocolConstants::PROMPT_NOT_FOUND);
        $this->assertEquals(-32006, ProtocolConstants::INVALID_URI);
        $this->assertEquals(-32007, ProtocolConstants::TRANSPORT_ERROR);
        $this->assertEquals(-32008, ProtocolConstants::PROTOCOL_ERROR);
        $this->assertEquals(-32009, ProtocolConstants::VALIDATION_ERROR);
    }

    public function testRoleConstants(): void
    {
        $this->assertEquals('user', ProtocolConstants::ROLE_USER);
        $this->assertEquals('assistant', ProtocolConstants::ROLE_ASSISTANT);
    }

    public function testContentTypeConstants(): void
    {
        $this->assertEquals('text', ProtocolConstants::CONTENT_TYPE_TEXT);
        $this->assertEquals('image', ProtocolConstants::CONTENT_TYPE_IMAGE);
        $this->assertEquals('resource', ProtocolConstants::CONTENT_TYPE_RESOURCE);
    }

    public function testStopReasonConstants(): void
    {
        $this->assertEquals('endTurn', ProtocolConstants::STOP_REASON_END_TURN);
        $this->assertEquals('maxTokens', ProtocolConstants::STOP_REASON_MAX_TOKENS);
        $this->assertEquals('stopSequence', ProtocolConstants::STOP_REASON_STOP_SEQUENCE);
        $this->assertEquals('toolUse', ProtocolConstants::STOP_REASON_TOOL_USE);
    }

    public function testReferenceTypeConstants(): void
    {
        $this->assertEquals('ref/resource', ProtocolConstants::REF_TYPE_RESOURCE);
        $this->assertEquals('ref/prompt', ProtocolConstants::REF_TYPE_PROMPT);
    }

    public function testLoggingLevelConstants(): void
    {
        $this->assertEquals('debug', ProtocolConstants::LOG_LEVEL_DEBUG);
        $this->assertEquals('info', ProtocolConstants::LOG_LEVEL_INFO);
        $this->assertEquals('notice', ProtocolConstants::LOG_LEVEL_NOTICE);
        $this->assertEquals('warning', ProtocolConstants::LOG_LEVEL_WARNING);
        $this->assertEquals('error', ProtocolConstants::LOG_LEVEL_ERROR);
        $this->assertEquals('critical', ProtocolConstants::LOG_LEVEL_CRITICAL);
        $this->assertEquals('alert', ProtocolConstants::LOG_LEVEL_ALERT);
        $this->assertEquals('emergency', ProtocolConstants::LOG_LEVEL_EMERGENCY);
    }

    public function testMethodNameConstants(): void
    {
        $this->assertEquals('initialize', ProtocolConstants::METHOD_INITIALIZE);
        $this->assertEquals('ping', ProtocolConstants::METHOD_PING);
        $this->assertEquals('resources/list', ProtocolConstants::METHOD_RESOURCES_LIST);
        $this->assertEquals('tools/list', ProtocolConstants::METHOD_TOOLS_LIST);
        $this->assertEquals('prompts/list', ProtocolConstants::METHOD_PROMPTS_LIST);
    }

    public function testNotificationMethodConstants(): void
    {
        $this->assertEquals('notifications/initialized', ProtocolConstants::NOTIFICATION_INITIALIZED);
        $this->assertEquals('notifications/progress', ProtocolConstants::NOTIFICATION_PROGRESS);
        $this->assertEquals('notifications/cancelled', ProtocolConstants::NOTIFICATION_CANCELLED);
        $this->assertEquals('notifications/message', ProtocolConstants::NOTIFICATION_MESSAGE);
    }

    public function testMimeTypeConstants(): void
    {
        $this->assertEquals('text/plain', ProtocolConstants::MIME_TYPE_TEXT_PLAIN);
        $this->assertEquals('text/html', ProtocolConstants::MIME_TYPE_TEXT_HTML);
        $this->assertEquals('application/json', ProtocolConstants::MIME_TYPE_APPLICATION_JSON);
        $this->assertEquals('image/png', ProtocolConstants::MIME_TYPE_IMAGE_PNG);
        $this->assertEquals('image/jpeg', ProtocolConstants::MIME_TYPE_IMAGE_JPEG);
    }

    public function testGetSupportedMethodsReturnsArray(): void
    {
        $methods = ProtocolConstants::getSupportedMethods();

        $this->assertIsArray($methods);
        $this->assertNotEmpty($methods);
        $this->assertContains('initialize', $methods);
        $this->assertContains('ping', $methods);
        $this->assertContains('resources/list', $methods);
        $this->assertContains('tools/list', $methods);
        $this->assertContains('prompts/list', $methods);
    }

    public function testGetSupportedNotificationsReturnsArray(): void
    {
        $notifications = ProtocolConstants::getSupportedNotifications();

        $this->assertIsArray($notifications);
        $this->assertNotEmpty($notifications);
        $this->assertContains('notifications/initialized', $notifications);
        $this->assertContains('notifications/progress', $notifications);
        $this->assertContains('notifications/cancelled', $notifications);
    }

    public function testGetValidRolesReturnsArray(): void
    {
        $roles = ProtocolConstants::getValidRoles();

        $this->assertIsArray($roles);
        $this->assertEquals(['user', 'assistant'], $roles);
    }

    public function testGetValidLogLevelsReturnsArray(): void
    {
        $levels = ProtocolConstants::getValidLogLevels();

        $this->assertIsArray($levels);
        $this->assertNotEmpty($levels);
        $this->assertContains('debug', $levels);
        $this->assertContains('info', $levels);
        $this->assertContains('error', $levels);
        $this->assertContains('emergency', $levels);
    }

    public function testIsValidMethodWithSupportedMethod(): void
    {
        $this->assertTrue(ProtocolConstants::isValidMethod('initialize'));
        $this->assertTrue(ProtocolConstants::isValidMethod('ping'));
        $this->assertTrue(ProtocolConstants::isValidMethod('resources/list'));
        $this->assertTrue(ProtocolConstants::isValidMethod('tools/call'));
    }

    public function testIsValidMethodWithSupportedNotification(): void
    {
        $this->assertTrue(ProtocolConstants::isValidMethod('notifications/initialized'));
        $this->assertTrue(ProtocolConstants::isValidMethod('notifications/progress'));
        $this->assertTrue(ProtocolConstants::isValidMethod('notifications/cancelled'));
    }

    public function testIsValidMethodWithUnsupportedMethod(): void
    {
        $this->assertFalse(ProtocolConstants::isValidMethod('unsupported/method'));
        $this->assertFalse(ProtocolConstants::isValidMethod('invalid'));
        $this->assertFalse(ProtocolConstants::isValidMethod(''));
    }

    public function testIsValidRoleWithValidRoles(): void
    {
        $this->assertTrue(ProtocolConstants::isValidRole('user'));
        $this->assertTrue(ProtocolConstants::isValidRole('assistant'));
    }

    public function testIsValidRoleWithInvalidRoles(): void
    {
        $this->assertFalse(ProtocolConstants::isValidRole('system'));
        $this->assertFalse(ProtocolConstants::isValidRole('invalid'));
        $this->assertFalse(ProtocolConstants::isValidRole(''));
    }

    public function testIsValidLogLevelWithValidLevels(): void
    {
        $this->assertTrue(ProtocolConstants::isValidLogLevel('debug'));
        $this->assertTrue(ProtocolConstants::isValidLogLevel('info'));
        $this->assertTrue(ProtocolConstants::isValidLogLevel('warning'));
        $this->assertTrue(ProtocolConstants::isValidLogLevel('error'));
        $this->assertTrue(ProtocolConstants::isValidLogLevel('emergency'));
    }

    public function testIsValidLogLevelWithInvalidLevels(): void
    {
        $this->assertFalse(ProtocolConstants::isValidLogLevel('trace'));
        $this->assertFalse(ProtocolConstants::isValidLogLevel('fatal'));
        $this->assertFalse(ProtocolConstants::isValidLogLevel('invalid'));
        $this->assertFalse(ProtocolConstants::isValidLogLevel(''));
    }

    public function testAllSupportedMethodsAreUnique(): void
    {
        $methods = ProtocolConstants::getSupportedMethods();
        $uniqueMethods = array_unique($methods);

        $this->assertEquals(count($methods), count($uniqueMethods));
    }

    public function testAllSupportedNotificationsAreUnique(): void
    {
        $notifications = ProtocolConstants::getSupportedNotifications();
        $uniqueNotifications = array_unique($notifications);

        $this->assertEquals(count($notifications), count($uniqueNotifications));
    }

    public function testMethodAndNotificationNamesDoNotOverlap(): void
    {
        $methods = ProtocolConstants::getSupportedMethods();
        $notifications = ProtocolConstants::getSupportedNotifications();

        $overlap = array_intersect($methods, $notifications);

        $this->assertEmpty($overlap);
    }
}
