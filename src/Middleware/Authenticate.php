<?php

// src/Middleware/Authenticate.php
namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class Authenticate implements MiddlewareInterface
{
    // Hard-coded test token
    private string $testToken = 'test-token-123';
    public function __construct()
    {
        // You could inject a real auth service here later
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            if ($token === $this->testToken) {
        // Fake user payload – replace with real user lookup later
                $user = [
                    'id'       => 1,
                    'username' => 'testuser',
                    'roles'    => ['admin'],
                ];
        // Attach to request for downstream handlers/controllers
                $request = $request->withAttribute('user', $user);
                return $handler->handle($request);
            }
        }

        // If no valid token, return 401
        $response = new Response(401);
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}
