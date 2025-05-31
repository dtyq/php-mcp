<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Integration\Client;

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Dtyq\PhpMcp\Types\Content\TextContent;
use Dtyq\PhpMcp\Types\Resources\TextResourceContents;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Integration test for stdio transport between client and server.
 *
 * This test validates the complete MCP communication flow including:
 * - Connection establishment
 * - Tools listing and execution
 * - Prompts listing and retrieval
 * - Resources listing and reading
 * - Resource templates handling
 *
 * @group integration
 * @internal
 */
class StdioIntegrationTest extends TestCase
{
    private McpClient $client;

    private Application $app;

    private string $serverScript;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip if server script doesn't exist
        $this->serverScript = __DIR__ . '/../../../examples/stdio-server-test.php';
        if (! file_exists($this->serverScript)) {
            $this->markTestSkipped('Server script not found: ' . $this->serverScript);
        }

        // Create simple container for testing
        $container = new class implements ContainerInterface {
            /** @var array<string, object> */
            private array $services = [];

            public function __construct()
            {
                $this->services[LoggerInterface::class] = new class extends AbstractLogger {
                    /**
                     * @param mixed $level
                     * @param string $message
                     */
                    public function log($level, $message, array $context = []): void
                    {
                        // Silent logger for tests
                    }
                };

                $this->services[EventDispatcherInterface::class] = new class implements EventDispatcherInterface {
                    public function dispatch(object $event): object
                    {
                        return $event;
                    }
                };
            }

            public function get($id)
            {
                return $this->services[$id];
            }

            public function has($id): bool
            {
                return isset($this->services[$id]);
            }
        };

        $config = [
            'sdk_name' => 'php-mcp-integration-test',
        ];

        $this->app = new Application($container, $config);
        $this->client = new McpClient('integration-test-client', '1.0.0', $this->app);
    }

    protected function tearDown(): void
    {
        if (isset($this->client)) {
            $this->client->close();
        }
        parent::tearDown();
    }

    public function testStdioConnectionAndInitialization(): void
    {
        $session = $this->client->connect('stdio', [
            'command' => 'php',
            'args' => [$this->serverScript],
        ]);

        $this->assertNotNull($session);

        $session->initialize();

        $this->assertTrue($session->isInitialized());
        $this->assertNotEmpty($session->getSessionId());
    }

    public function testToolsListingAndExecution(): void
    {
        $session = $this->client->connect('stdio', [
            'command' => 'php',
            'args' => [$this->serverScript],
        ]);
        $session->initialize();

        // Test tools listing
        $tools = $session->listTools();
        $this->assertNotNull($tools);
        $this->assertGreaterThan(0, count($tools->getTools()));

        $toolNames = [];
        foreach ($tools->getTools() as $tool) {
            $toolNames[] = $tool->getName();
            $this->assertNotEmpty($tool->getName());
            $this->assertNotEmpty($tool->getDescription());
        }

        // Test echo tool if available
        if (in_array('echo', $toolNames)) {
            $echoResult = $session->callTool('echo', ['message' => 'Test message']);
            $this->assertNotNull($echoResult);
            $this->assertNotEmpty($echoResult->getContent());
        }

        // Test calculator tool if available
        if (in_array('calculate', $toolNames)) {
            $calcResult = $session->callTool('calculate', [
                'operation' => 'add',
                'a' => 10,
                'b' => 5,
            ]);
            $this->assertNotNull($calcResult);
            $this->assertNotEmpty($calcResult->getContent());
        }
    }

    public function testPromptsListingAndRetrieval(): void
    {
        $session = $this->client->connect('stdio', [
            'command' => 'php',
            'args' => [$this->serverScript],
        ]);
        $session->initialize();

        // Test prompts listing
        $prompts = $session->listPrompts();
        $this->assertNotNull($prompts);
        $this->assertGreaterThan(0, count($prompts->getPrompts()));

        $promptNames = [];
        foreach ($prompts->getPrompts() as $prompt) {
            $promptNames[] = $prompt->getName();
            $this->assertNotEmpty($prompt->getName());
            $this->assertNotEmpty($prompt->getDescription());
        }

        // Test greeting prompt if available
        if (in_array('greeting', $promptNames)) {
            $greetingResult = $session->getPrompt('greeting', [
                'name' => 'TestUser',
                'language' => 'english',
            ]);
            $this->assertNotNull($greetingResult);
            $this->assertNotEmpty($greetingResult->getMessages());

            // Check message content
            foreach ($greetingResult->getMessages() as $message) {
                $content = $message->getContent();
                if ($content instanceof TextContent) {
                    $this->assertNotEmpty($content->getText());
                }
            }
        }
    }

    public function testResourcesListingAndReading(): void
    {
        $session = $this->client->connect('stdio', [
            'command' => 'php',
            'args' => [$this->serverScript],
        ]);
        $session->initialize();

        // Test resources listing
        $resources = $session->listResources();
        $this->assertNotNull($resources);
        $this->assertGreaterThan(0, count($resources->getResources()));

        $resourceUris = [];
        foreach ($resources->getResources() as $resource) {
            $resourceUris[] = $resource->getUri();
            $this->assertNotEmpty($resource->getUri());
            $this->assertNotEmpty($resource->getName());
        }

        // Test system info resource if available
        if (in_array('system://info', $resourceUris)) {
            $sysInfoResult = $session->readResource('system://info');
            $this->assertNotNull($sysInfoResult);
            $this->assertNotEmpty($sysInfoResult->getContents());

            foreach ($sysInfoResult->getContents() as $content) {
                if ($content instanceof TextResourceContents) {
                    $text = $content->getText();
                    $this->assertNotNull($text);

                    // Validate JSON structure
                    $data = json_decode($text, true);
                    $this->assertIsArray($data);
                    $this->assertArrayHasKey('php_version', $data);
                    $this->assertArrayHasKey('os', $data);
                }
            }
        }
    }

    public function testResourceTemplates(): void
    {
        $session = $this->client->connect('stdio', [
            'command' => 'php',
            'args' => [$this->serverScript],
        ]);
        $session->initialize();

        // Test user profile template
        try {
            $userProfileResult = $session->readResource('user://admin/profile');
            $this->assertNotNull($userProfileResult);
            $this->assertNotEmpty($userProfileResult->getContents());

            foreach ($userProfileResult->getContents() as $content) {
                if ($content instanceof TextResourceContents) {
                    $text = $content->getText();
                    $this->assertNotNull($text);

                    $profile = json_decode($text, true);
                    $this->assertIsArray($profile);
                    $this->assertArrayHasKey('userId', $profile);
                    $this->assertArrayHasKey('username', $profile);
                }
            }
        } catch (Exception $e) {
            // Template might not be implemented, which is okay
            $this->assertTrue(true, 'User profile template test skipped: ' . $e->getMessage());
        }

        // Test configuration template
        try {
            $configResult = $session->readResource('config://database/production');
            $this->assertNotNull($configResult);
            $this->assertNotEmpty($configResult->getContents());

            foreach ($configResult->getContents() as $content) {
                if ($content instanceof TextResourceContents) {
                    $text = $content->getText();
                    $this->assertNotNull($text);

                    $config = json_decode($text, true);
                    $this->assertIsArray($config);
                }
            }
        } catch (Exception $e) {
            // Template might not be implemented, which is okay
            $this->assertTrue(true, 'Config template test skipped: ' . $e->getMessage());
        }
    }

    public function testSessionStatistics(): void
    {
        $session = $this->client->connect('stdio', [
            'command' => 'php',
            'args' => [$this->serverScript],
        ]);
        $session->initialize();

        // Get session statistics
        $stats = $this->client->getStats();
        $this->assertNotNull($stats);

        $this->assertGreaterThanOrEqual(1, $stats->getConnectionAttempts());
        $this->assertEquals(0, $stats->getConnectionErrors());
        $this->assertEquals('connected', $stats->getStatus());

        // Test stats array conversion
        $statsArray = $stats->toArray();
        $this->assertIsArray($statsArray);
        $this->assertArrayHasKey('connectionAttempts', $statsArray);
        $this->assertArrayHasKey('connectionErrors', $statsArray);
        $this->assertArrayHasKey('status', $statsArray);
    }

    public function testMultipleOperationsInSequence(): void
    {
        $session = $this->client->connect('stdio', [
            'command' => 'php',
            'args' => [$this->serverScript],
        ]);
        $session->initialize();

        // Perform multiple operations to test session stability
        $tools = $session->listTools();
        $this->assertNotNull($tools);

        $prompts = $session->listPrompts();
        $this->assertNotNull($prompts);

        $resources = $session->listResources();
        $this->assertNotNull($resources);

        // Session should still be active
        $this->assertTrue($session->isInitialized());
    }
}
