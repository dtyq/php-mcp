<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Dtyq\PhpMcp\Client\Configuration\HttpConfig;
use Dtyq\PhpMcp\Client\Configuration\StdioConfig;
use Dtyq\PhpMcp\Client\McpClient;
use Dtyq\PhpMcp\Shared\Kernel\Application;
use Psr\Container\ContainerInterface;

// Create a simple container for the application
$container = new class implements ContainerInterface {
    public function get(string $id)
    {
        throw new Exception("Service not found: {$id}");
    }

    public function has(string $id): bool
    {
        return false;
    }
};

// Create application instance
$application = new Application($container);

// Create MCP client
$client = new McpClient('demo-client', '1.0.0', $application);

echo "=== MCP Client Quick Methods Demo ===\n\n";

// Demo 1: Stdio quick method
echo "1. Using stdio() quick method:\n";
try {
    $stdioConfig = new StdioConfig(['php', '-v']);
    echo '   - Created StdioConfig with command: ' . implode(' ', $stdioConfig->getCommand()) . "\n";
    echo "   - Timeout settings: read={$stdioConfig->getReadTimeout()}s, write={$stdioConfig->getWriteTimeout()}s\n";

    // Note: This would actually connect to a server, so we just show the config
    // $session = $client->stdio($stdioConfig);
    echo "   - Ready to connect with: \$client->stdio(\$stdioConfig)\n";
} catch (Exception $e) {
    echo '   - Error: ' . $e->getMessage() . "\n";
}

echo "\n";

// Demo 2: HTTP quick method
echo "2. Using http() quick method:\n";
try {
    $httpConfig = new HttpConfig('https://example.com/mcp');
    echo '   - Created HttpConfig with base URL: ' . $httpConfig->getBaseUrl() . "\n";
    echo "   - Timeout settings: request={$httpConfig->getTimeout()}s, sse={$httpConfig->getSseTimeout()}s\n";
    echo "   - Max retries: {$httpConfig->getMaxRetries()}\n";

    // Note: This would actually connect to a server, so we just show the config
    // $session = $client->http($httpConfig);
    echo "   - Ready to connect with: \$client->http(\$httpConfig)\n";
} catch (Exception $e) {
    echo '   - Error: ' . $e->getMessage() . "\n";
}

echo "\n";

// Demo 3: Comparison with traditional connect() method
echo "3. Comparison with traditional connect() method:\n";
echo "   Traditional way:\n";
echo "   \$session = \$client->connect('stdio', ['command' => ['php', '-v']]);\n";
echo "   \$session = \$client->connect('http', ['base_url' => 'https://example.com/mcp']);\n";
echo "\n";
echo "   Quick method way:\n";
echo "   \$session = \$client->stdio(new StdioConfig(['php', '-v']));\n";
echo "   \$session = \$client->http(new HttpConfig('https://example.com/mcp'));\n";

echo "\n";

// Demo 4: Configuration validation
echo "4. Configuration validation:\n";
try {
    new StdioConfig([]);
    echo "   - StdioConfig with empty command: Should not reach here\n";
} catch (Exception $e) {
    echo '   - StdioConfig with empty command: ' . $e->getMessage() . "\n";
}

try {
    new HttpConfig('');
    echo "   - HttpConfig with empty URL: Should not reach here\n";
} catch (Exception $e) {
    echo '   - HttpConfig with empty URL: ' . $e->getMessage() . "\n";
}

echo "\n=== Demo completed ===\n";
