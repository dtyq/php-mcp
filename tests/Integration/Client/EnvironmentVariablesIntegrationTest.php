<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\PhpMcp\Tests\Integration\Client;

use PHPUnit\Framework\TestCase;

/**
 * Integration test to validate environment variables functionality.
 *
 * This test ensures that environment variable passing works correctly:
 * - env-stdio-client.php with env-stdio-server.php
 * - env-stdio-client-portable.php with env-stdio-server.php
 * - Environment variable tools function properly
 *
 * @group integration
 * @group environment
 * @internal
 */
class EnvironmentVariablesIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure log directory exists
        $logDir = __DIR__ . '/../../../.log';
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Test environment variables client-server communication using example files.
     */
    public function testEnvironmentVariablesExampleIntegration(): void
    {
        $clientScript = __DIR__ . '/../../../examples/env-stdio-client.php';
        $serverScript = __DIR__ . '/../../../examples/env-stdio-server.php';

        // Verify files exist
        $this->assertFileExists($clientScript, 'env-stdio-client.php not found');
        $this->assertFileExists($serverScript, 'env-stdio-server.php not found');

        // Run client test (server is spawned internally via stdio transport)
        $output = [];
        $returnCode = 0;

        $command = 'cd ' . escapeshellarg(dirname($clientScript)) . ' && php ' . escapeshellarg(basename($clientScript)) . ' 2>&1';
        exec($command, $output, $returnCode);

        $outputText = implode("\n", $output);

        // Verify the test completed successfully
        $this->assertEquals(0, $returnCode, "Environment variables example failed with return code {$returnCode}:\n{$outputText}");
        $this->assertStringContainsString('Environment Variables Demo completed successfully', $outputText, 'Environment variables demo did not complete successfully');
        $this->assertStringContainsString('Connected and initialized', $outputText, 'Environment variables connection failed');
        $this->assertStringContainsString('Available Environment Tools:', $outputText, 'Tools listing failed');
        $this->assertStringContainsString('Session closed', $outputText, 'Session cleanup failed');

        // Verify specific environment variable functionality
        $this->assertStringContainsString('get_env', $outputText, 'get_env tool not found');
        $this->assertStringContainsString('set_env', $outputText, 'set_env tool not found');
        $this->assertStringContainsString('env_info', $outputText, 'env_info tool not found');
        $this->assertStringContainsString('search_env', $outputText, 'search_env tool not found');

        // Verify environment variable passing
        $this->assertStringContainsString('DEMO_APP_NAME', $outputText, 'Custom environment variable DEMO_APP_NAME not found');
        $this->assertStringContainsString('DEMO_VERSION', $outputText, 'Custom environment variable DEMO_VERSION not found');
        $this->assertStringContainsString('DEMO_ENVIRONMENT', $outputText, 'Custom environment variable DEMO_ENVIRONMENT not found');
        $this->assertStringContainsString('DEMO_DEBUG', $outputText, 'Custom environment variable DEMO_DEBUG not found');
        $this->assertStringContainsString('DEMO_API_KEY', $outputText, 'Custom environment variable DEMO_API_KEY not found');

        // Verify tool responses
        $this->assertStringContainsString('PHP MCP Environment Demo', $outputText, 'Environment variable value not correctly passed');
        $this->assertStringContainsString('1.0.0', $outputText, 'Version environment variable not correctly passed');
        $this->assertStringContainsString('development', $outputText, 'Environment variable not correctly passed');
        $this->assertStringContainsString('true', $outputText, 'Debug environment variable not correctly passed');
        $this->assertStringContainsString('demo-key-12345', $outputText, 'API key environment variable not correctly passed');

        // Verify environment tools work
        $this->assertStringContainsString('Testing get_env tool', $outputText, 'get_env tool test missing');
        $this->assertStringContainsString('Testing set_env tool', $outputText, 'set_env tool test missing');
        $this->assertStringContainsString('Testing env_info tool', $outputText, 'env_info tool test missing');
        $this->assertStringContainsString('Testing search_env tool', $outputText, 'search_env tool test missing');
    }

    /**
     * Test portable environment variables client-server communication.
     */
    public function testPortableEnvironmentVariablesExampleIntegration(): void
    {
        $clientScript = __DIR__ . '/../../../examples/env-stdio-client-portable.php';
        $serverScript = __DIR__ . '/../../../examples/env-stdio-server.php';

        // Verify files exist
        $this->assertFileExists($clientScript, 'env-stdio-client-portable.php not found');
        $this->assertFileExists($serverScript, 'env-stdio-server.php not found');

        // Run portable client test
        $output = [];
        $returnCode = 0;

        $command = 'cd ' . escapeshellarg(dirname($clientScript)) . ' && php ' . escapeshellarg(basename($clientScript)) . ' 2>&1';
        exec($command, $output, $returnCode);

        $outputText = implode("\n", $output);

        // Verify the test completed successfully
        $this->assertEquals(0, $returnCode, "Portable environment variables example failed with return code {$returnCode}:\n{$outputText}");
        $this->assertStringContainsString('Environment Variables Demo completed successfully', $outputText, 'Portable environment variables demo did not complete successfully');
        $this->assertStringContainsString('Connected and initialized', $outputText, 'Portable environment variables connection failed');
        $this->assertStringContainsString('Detected PHP executable:', $outputText, 'PHP path detection failed');
        $this->assertStringContainsString('Session closed', $outputText, 'Session cleanup failed');

        // Verify environment variable functionality works the same
        $this->assertStringContainsString('DEMO_APP_NAME', $outputText, 'Custom environment variable DEMO_APP_NAME not found in portable version');
        $this->assertStringContainsString('PHP MCP Environment Demo', $outputText, 'Environment variable value not correctly passed in portable version');
    }

    /**
     * Test that environment variables are properly isolated between processes.
     */
    public function testEnvironmentVariableIsolation(): void
    {
        $clientScript = __DIR__ . '/../../../examples/env-stdio-client-portable.php';
        $serverScript = __DIR__ . '/../../../examples/env-stdio-server.php';

        // Verify files exist
        $this->assertFileExists($clientScript, 'env-stdio-client-portable.php not found');
        $this->assertFileExists($serverScript, 'env-stdio-server.php not found');

        // Set some environment variables in the test process
        $originalEnv = getenv('TEST_ISOLATION_VAR');
        putenv('TEST_ISOLATION_VAR=parent_process_value');

        try {
            // Run client test which spawns server with custom env
            $output = [];
            $returnCode = 0;

            $command = 'cd ' . escapeshellarg(dirname($clientScript)) . ' && php ' . escapeshellarg(basename($clientScript)) . ' 2>&1';
            exec($command, $output, $returnCode);

            $outputText = implode("\n", $output);

            // Verify the test completed successfully
            $this->assertEquals(0, $returnCode, "Environment variable isolation test failed with return code {$returnCode}:\n{$outputText}");

            // Verify the server process received the custom environment variables
            $this->assertStringContainsString('DEMO_APP_NAME', $outputText, 'Custom environment variable not found in server process');
            $this->assertStringContainsString('PHP MCP Environment Demo', $outputText, 'Custom environment variable value not correctly passed to server process');

            // Verify the current process still has its original environment
            $this->assertEquals('parent_process_value', getenv('TEST_ISOLATION_VAR'), 'Parent process environment was corrupted');
        } finally {
            // Restore original environment
            if ($originalEnv === false) {
                putenv('TEST_ISOLATION_VAR');
            } else {
                putenv("TEST_ISOLATION_VAR={$originalEnv}");
            }
        }
    }

    /**
     * Test that environment variable tools work correctly.
     */
    public function testEnvironmentVariableTools(): void
    {
        $clientScript = __DIR__ . '/../../../examples/env-stdio-client-portable.php';
        $serverScript = __DIR__ . '/../../../examples/env-stdio-server.php';

        // Verify files exist
        $this->assertFileExists($clientScript, 'env-stdio-client-portable.php not found');
        $this->assertFileExists($serverScript, 'env-stdio-server.php not found');

        // Run client test
        $output = [];
        $returnCode = 0;

        $command = 'cd ' . escapeshellarg(dirname($clientScript)) . ' && php ' . escapeshellarg(basename($clientScript)) . ' 2>&1';
        exec($command, $output, $returnCode);

        $outputText = implode("\n", $output);

        // Verify the test completed successfully
        $this->assertEquals(0, $returnCode, "Environment variable tools test failed with return code {$returnCode}:\n{$outputText}");

        // Verify each tool was tested and worked
        $this->assertStringContainsString('Testing all custom environment variables', $outputText, 'get_env tool was not tested');
        $this->assertStringContainsString('PHP MCP Environment Demo', $outputText, 'get_env tool did not return expected result');

        $this->assertStringContainsString('Testing runtime variable setting', $outputText, 'set_env tool was not tested');
        $this->assertStringContainsString('Success: YES', $outputText, 'set_env tool did not work correctly');

        $this->assertStringContainsString('Getting comprehensive environment information', $outputText, 'env_info tool was not tested');
        $this->assertStringContainsString('Process ID:', $outputText, 'env_info tool did not return process information');
        $this->assertStringContainsString('PHP Version:', $outputText, 'env_info tool did not return PHP version');

        $this->assertStringContainsString('Testing environment variable search', $outputText, 'search_env tool was not tested');
        $this->assertStringContainsString('Matches found:', $outputText, 'search_env tool did not find variables');
    }

    /**
     * Test that environment variables are correctly passed through StdioConfig.
     */
    public function testStdioConfigEnvironmentVariables(): void
    {
        // This test uses a smaller, focused script to verify the core functionality
        $tempClientScript = sys_get_temp_dir() . '/test_stdio_config_env.php';
        $serverScript = __DIR__ . '/../../../examples/env-stdio-server.php';

        // Create a minimal test script
        $testScript = '<?php
require_once "' . __DIR__ . '/../../../vendor/autoload.php";

use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

// Simple DI container
$container = new class implements ContainerInterface {
    private array $services = [];

    public function __construct() {
        $this->services[LoggerInterface::class] = new class extends AbstractLogger {
            public function log($level, $message, array $context = []): void {
                // Silent logger for test
            }
        };
        $this->services[EventDispatcherInterface::class] = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object { return $event; }
        };
    }

    public function get($id) { return $this->services[$id]; }
    public function has($id): bool { return isset($this->services[$id]); }
};

$app = new Application($container, []);
$client = new McpClient("test-client", "1.0.0", $app);

// Test environment variable passing
$session = $client->connect("stdio", [
    "command" => "' . PHP_BINARY . '",
    "args" => ["' . $serverScript . '"],
    "env" => [
        "TEST_SPECIFIC_VAR" => "specific_test_value",
        "TEST_NUMERIC_VAR" => "12345"
    ]
]);

try {
    $session->initialize();
    
    // Test the get_env tool
    $result = $session->callTool("get_env", ["name" => "TEST_SPECIFIC_VAR"]);
    $content = $result->getContent();
    
    if (is_array($content) && isset($content[0])) {
        $textContent = $content[0];
        if (method_exists($textContent, "getText")) {
            $text = $textContent->getText();
            if (strpos($text, "specific_test_value") !== false) {
                echo "SUCCESS: Environment variable correctly passed\n";
            } else {
                echo "FAILED: Environment variable not found in response: " . $text . "\n";
            }
        }
    }
    
    $client->close();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
';

        file_put_contents($tempClientScript, $testScript);

        try {
            // Run the test script
            $output = [];
            $returnCode = 0;

            $command = 'php ' . escapeshellarg($tempClientScript) . ' 2>&1';
            exec($command, $output, $returnCode);

            $outputText = implode("\n", $output);

            // Verify the test completed successfully
            $this->assertEquals(0, $returnCode, "StdioConfig environment variables test failed with return code {$returnCode}:\n{$outputText}");
            $this->assertStringContainsString('SUCCESS: Environment variable correctly passed', $outputText, 'Environment variable was not correctly passed through StdioConfig');
        } finally {
            // Clean up temporary file
            if (file_exists($tempClientScript)) {
                unlink($tempClientScript);
            }
        }
    }
}
