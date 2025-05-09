<?php

// src/Middleware/RateLimiter.php
namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Slim\Psr7\Response;

class RateLimiter implements MiddlewareInterface
{
    private CacheInterface $cache;
    private int $limit;
    private int $window;
// seconds

    public function __construct(CacheInterface $cache, int $limit = 100, int $window = 60)
    {
        $this->cache  = $cache;
        $this->limit  = $limit;
        $this->window = $window;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip  = $request->getServerParams()['REMOTE_ADDR'] ?? 'global';
        $key = "rate:{$ip}";
        $data = $this->cache->get($key, ['remaining' => $this->limit, 'reset' => time() + $this->window]);
// reset window if expired
        if ($data['reset'] <= time()) {
            $data = ['remaining' => $this->limit, 'reset' => time() + $this->window];
        }

        // too many requests
        if ($data['remaining'] <= 0) {
            $resp = new Response(429);
            return $resp
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string)($data['reset'] - time()));
        }

        // consume a token
        $data['remaining']--;
        $this->cache->set($key, $data, $this->window);
// proceed
        $response = $handler->handle($request);
// inject rate-limit headers
        return $response
            ->withHeader('X-RateLimit-Limit', (string)$this->limit)
            ->withHeader('X-RateLimit-Remaining', (string)$data['remaining'])
            ->withHeader('X-RateLimit-Reset', (string)$data['reset']);
    }
}
