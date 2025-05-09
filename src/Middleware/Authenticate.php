<?php
namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Authenticate implements MiddlewareInterface
{
    private string $testToken = 'test-token-123';

    public function __construct()
    {
        // You could inject a real auth service here later
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            if (str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
                if ($token === $this->testToken) {
                    // Fake user payload
                    $user = [
                        'id' => 1,
                        'username' => 'testuser',
                        'roles' => ['admin'],
                    ];
                    $request = $request->withAttribute('user', $user);
                    return $handler->handle($request);
                }
            }

            // Log authentication failure
            $response = new Response(401);
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            // Log the exception
            $this->logger->error('Authentication error', ['exception' => $e->getMessage()]);
            throw $e; // Re-throw for further handling
        }
    }
}
