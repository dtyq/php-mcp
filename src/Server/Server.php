<?php

declare(strict_types=1);

namespace Dtyq\Mcp\Server;

class Server implements ServerInterface
{
    /**
     * @var array
     */
    private array $functions = [];
    
    /**
     * @var array
     */
    private array $options = [];
    
    /**
     * @var bool
     */
    private bool $running = false;
    
    /**
     * Server constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'host' => 'localhost',
            'port' => 8080,
        ], $options);
    }
    
    /**
     * {@inheritdoc}
     */
    public function registerFunction(string $functionName, callable $handler): ServerInterface
    {
        $this->functions[$functionName] = $handler;
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function handleRequest(string $request): string
    {
        $data = json_decode($request, true);
        
        if (!isset($data['function']) || !is_string($data['function'])) {
            return $this->createErrorResponse('Invalid function name');
        }
        
        $functionName = $data['function'];
        
        if (!isset($this->functions[$functionName])) {
            return $this->createErrorResponse("Function '{$functionName}' not found");
        }
        
        $parameters = $data['parameters'] ?? [];
        
        try {
            $result = call_user_func($this->functions[$functionName], $parameters);
            
            return $this->createSuccessResponse($result);
        } catch (\Throwable $e) {
            return $this->createErrorResponse($e->getMessage());
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        if ($this->running) {
            return;
        }
        
        $this->running = true;
        
        // TODO: Implement actual server logic
    }
    
    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }
        
        $this->running = false;
        
        // TODO: Implement actual server shutdown logic
    }
    
    /**
     * Create a success response
     *
     * @param mixed $data
     * @return string
     */
    private function createSuccessResponse($data): string
    {
        return json_encode([
            'status' => 'success',
            'data' => $data,
        ]);
    }
    
    /**
     * Create an error response
     *
     * @param string $message
     * @return string
     */
    private function createErrorResponse(string $message): string
    {
        return json_encode([
            'status' => 'error',
            'message' => $message,
        ]);
    }
} 