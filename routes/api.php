<?php
/**
 * Route definitions for the Enterprise API.
 *
 * Declares all HTTP endpoints that will be mounted by the front controller.
 *
 * @param \App\Application $app Pre-bootstrapped Slim wrapper.
 */

declare(strict_types=1);

use App\Application;
use App\Controller\UserController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Register API endpoints.
 *
 * @param Application $app
 *
 * @return void
 */
return function (Application $app): void {
    $router = $app->getRouter();

    // ─────────────────────────────────────────────────────
    //  Simple ping route: GET /oot  →  "good"
    // ─────────────────────────────────────────────────────
    $router->get('/oot', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $response->getBody()->write('good');
        return $response->withHeader('Content-Type', 'text/plain');
    });
    
    // …add more routes here
};
