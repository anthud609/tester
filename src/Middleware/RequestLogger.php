<?php

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
        try {
            $this->logger->info('Request received', [
                'method'  => $request->getMethod(),
                'uri'     => (string) $request->getUri(),
                'headers' => $request->getHeaders(),
                'body'    => (string) $request->getBody(),
            ]);

            $response = $handler->handle($request);

            $this->logger->info('Response sent', [
                'status'  => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body'    => (string) $response->getBody(),
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('Error processing request', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw exception for further handling
        }
    }
}
