<?php

// src/Middleware/ValidateRequest.php
namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class ValidateRequest implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Only validate bodies on write methods
        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $body = $request->getParsedBody();
            if (!is_array($body)) {
                $resp = new Response(400);
                $resp->getBody()->write(json_encode([
                    'error' => 'Invalid or missing JSON body',
                ]));
                return $resp->withHeader('Content-Type', 'application/json');
            }
        }

        // Delegate to next middleware / handler
        return $handler->handle($request);
    }
}
