<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dtyq\Mcp\Client\Client;
use Dtyq\Mcp\Server\Server;

// Create a server
$server = new Server([
    'host' => 'localhost',
    'port' => 8080,
]);

// Register a function
$server->registerFunction('add', function (array $params) {
    if (!isset($params['a']) || !isset($params['b'])) {
        throw new InvalidArgumentException('Missing parameters');
    }
    
    return $params['a'] + $params['b'];
});

// Create a client
$client = new Client([
    'host' => 'localhost',
    'port' => 8080,
]);

// In a real scenario, you would start the server in a separate process
// $server->start();

// For demonstration purposes, we'll directly call handleRequest
$request = json_encode([
    'function' => 'add',
    'parameters' => [
        'a' => 5,
        'b' => 3,
    ],
]);

$response = $server->handleRequest($request);
echo "Server response: " . $response . PHP_EOL;

// In a real implementation, the client would handle the network communication
// $result = $client->call('add', ['a' => 5, 'b' => 3]);
// echo "Result: " . $result . PHP_EOL; 