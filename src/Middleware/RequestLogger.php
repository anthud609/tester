<?php

// src/Middleware/RequestLogger.php
namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class RequestLogger implements MiddlewareInterface
{
    private LoggerInterface $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);
// Log incoming request
        $this->logger->info('Incoming request', [
            'method'  => $request->getMethod(),
            'uri'     => (string)$request->getUri(),
            'headers' => $request->getHeaders(),
            'body'    => $request->getParsedBody(),
        ]);
// Handle the request
        $response = $handler->handle($request);
// Log response status & timing
        $duration = microtime(true) - $start;
        $this->logger->info('Outgoing response', [
            'status'   => $response->getStatusCode(),
            'duration' => $duration,
        ]);
        return $response;
    }
}
