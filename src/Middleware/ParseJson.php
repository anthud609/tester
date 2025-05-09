<?php
// src/Middleware/ParseJson.php
namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class ParseJson implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (stristr($contentType, 'application/json')) {
            $body = (string)$request->getBody();
            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request = $request->withParsedBody($decoded);
                } else {
                    $resp = new Response(400);
                    $resp->getBody()->write(json_encode([
                        'error' => 'Invalid JSON: ' . json_last_error_msg()
                    ]));
                    return $resp->withHeader('Content-Type', 'application/json');
                }
            }
        }

        return $handler->handle($request);
    }
}
