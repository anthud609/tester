<?php
declare(strict_types=1);

/**
 * Front-controller entry point for the **Enterprise API**.
 *
 * This script **boots, routes, and responds** for every HTTP request that
 * reaches the service.  It must stay tiny, predictable, and secure.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  ✦  Primary Responsibilities
 * ──────────────────────────────────────────────────────────────────────────
 * 1. Load Composer’s autoloader and environment variables **before** doing
 *    anything else that could rely on configuration or secret values.
 * 2. Build the PSR-11 dependency-injection container from `config/di.php`.
 * 3. Instantiate the {@see \App\Application} which wires up Slim,
 *    registers global middleware, and hands back a router instance.
 * 4. Define or import all HTTP routes (REST, RPC, GraphQL, etc.).
 * 5. Convert PHP super-globals into a {@see \Psr\Http\Message\ServerRequestInterface}.
 * 6. Delegate the request to Slim and obtain a
 *    {@see \Psr\Http\Message\ResponseInterface}.
 * 7. Emit the response to the client **without** leaking internal state.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  ✦  Security / Ops Notes
 * ──────────────────────────────────────────────────────────────────────────
 * • NEVER output anything (echo/var_dump) before headers are sent; that would
 *   corrupt the response and expose stack traces in production.
 * • ALWAYS load `Dotenv` before instantiating services so secrets are present.
 * • Pin PHP and extension versions in CI to guarantee parity with prod.
 * • Keep this file under 150 LOC; all heavy lifting belongs in
 *   domain-layer classes, middleware, or service providers.
 *
 * PHP version 8.3+
 *
 * @file      public/index.php
 * @category  Enterprise-API
 * @package   App
 * @author    Your Name <you@example.com>
 * @copyright Copyright © 2025 Your Company
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/your-org/enterprise-api
 * @version   GIT: @commit@
 * @since     1.0.0
 */


require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Application;
use DI\ContainerBuilder;
use Slim\Routing\RouteCollectorProxy;
use Slim\Factory\ServerRequestCreatorFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/** -------------------------------------------------------------------------
 *  1. Environment & Configuration
 *  ---------------------------------------------------------------------- */
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();                            // Loads .env → $_ENV

$config    = require __DIR__ . '/../config/app.php';   // array<string, mixed>
$diConfig  = __DIR__ . '/../config/di.php';            // PHP-DI definitions

/** -------------------------------------------------------------------------
 *  2. Build the DI Container
 *  ---------------------------------------------------------------------- */
$container = (new ContainerBuilder())
    ->addDefinitions($diConfig)
    ->build();

/** -------------------------------------------------------------------------
 *  3. Bootstrap the Slim application
 *  ---------------------------------------------------------------------- */
$appBootstrap = new Application($config, $container);
$router       = $appBootstrap->getRouter();    // Slim\App—route registrar

/** -------------------------------------------------------------------------
 *  4. Route Definitions
 *  ---------------------------------------------------------------------- */
$router->group('/v1', function (RouteCollectorProxy $group): void {
    $group->get('/health', function (Request $request, Response $response): Response {
        $response->getBody()->write(json_encode(['status' => 'ok']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store');
    });
    // TODO: include_route_files(__DIR__ . '/../routes/api.php');
});

/** -------------------------------------------------------------------------
 *  5. Handle & Emit
 *  ---------------------------------------------------------------------- */
$serverRequest = ServerRequestCreatorFactory::create()
    ->createServerRequestFromGlobals();

$response = $appBootstrap->handle($serverRequest);
$appBootstrap->emit($response);
