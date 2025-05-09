<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class Cors implements MiddlewareInterface
{
    private string $origin;
    private string $methods;
    private string $headers;
    private string $credentials;
    public function __construct(string $origin = '*', string $methods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS', string $headers = 'Content-Type, Authorization', bool $credentials = true)
    {
        $this->origin      = $origin;
        $this->methods     = $methods;
        $this->headers     = $headers;
        $this->credentials = $credentials ? 'true' : 'false';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Handle preflight (OPTIONS) requests
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = new Response(204);
            return $this->addCorsHeaders($response);
        }

        // Dispatch normal request
        $response = $handler->handle($request);
        return $this->addCorsHeaders($response);
    }

    private function addCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->origin)
            ->withHeader('Access-Control-Allow-Methods', $this->methods)
            ->withHeader('Access-Control-Allow-Headers', $this->headers)
            ->withHeader('Access-Control-Allow-Credentials', $this->credentials);
    }
}
