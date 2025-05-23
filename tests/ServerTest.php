<?php

declare(strict_types=1);

namespace Dtyq\Mcp\Tests;

use Dtyq\Mcp\Server\Server;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    public function testCanRegisterFunction()
    {
        $server = new Server();
        
        $handler = function () {
            return 'test';
        };
        
        $server->registerFunction('test', $handler);
        
        $request = json_encode([
            'function' => 'test',
            'parameters' => [],
        ]);
        
        $response = $server->handleRequest($request);
        $data = json_decode($response, true);
        
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('test', $data['data']);
    }
    
    public function testHandlesInvalidFunction()
    {
        $server = new Server();
        
        $request = json_encode([
            'function' => 'invalid',
            'parameters' => [],
        ]);
        
        $response = $server->handleRequest($request);
        $data = json_decode($response, true);
        
        $this->assertEquals('error', $data['status']);
        $this->assertEquals("Function 'invalid' not found", $data['message']);
    }
} 