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
 *  ──────────────────────────────────────────────────────────────────────────
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
 *  ──────────────────────────────────────────────────────────────────────────
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
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/** -------------------------------------------------------------------------
 *  1. Environment & Configuration
 *  ---------------------------------------------------------------------- */
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad(); // Loads .env → $_ENV

$config    = require __DIR__ . '/../config/app.php';   // array<string, mixed>
$diConfig  = __DIR__ . '/../config/di.php';            // PHP-DI definitions

// Initialize the logger here
$logger = new Logger('app');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));

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

// Log incoming request
$logger->info('Incoming request', [
    'method'  => $_SERVER['REQUEST_METHOD'],
    'uri'     => $_SERVER['REQUEST_URI'],
    'headers' => getallheaders()
]);

/** -------------------------------------------------------------------------
 *  4. Route Definitions
 *  ---------------------------------------------------------------------- */
$router->group('/v1', function (RouteCollectorProxy $group) use ($logger): void {
    // Example route
    $group->get('/oot', function (Request $request, Response $response) use ($logger): ResponseInterface {
        try {
            $logger->info('Handling /oot route');
            
            // Create a new Slim\Psr7\Response instead of modifying the existing one directly
            $newResponse = $response->withHeader('Content-Type', 'text/plain');
            $newResponse->getBody()->write('good'); // Writing to the response body
    
            return $newResponse;  // Return the modified response
        } catch (Exception $e) {
            $logger->error('Error handling /oot route', ['exception' => $e->getMessage()]);
            throw $e; // Re-throw exception for further handling
        }
    });
    

    // Include your other routes
    include_once __DIR__ . '/../routes/api.php';
});

/** -------------------------------------------------------------------------
 *  5. Handle & Emit
 *  ---------------------------------------------------------------------- */
$serverRequest = ServerRequestCreatorFactory::create()
    ->createServerRequestFromGlobals();

try {
    $response = $appBootstrap->handle($serverRequest);
    $appBootstrap->emit($response);
} catch (Throwable $e) {
    // Log uncaught exceptions and errors
    $logger->error('Uncaught exception', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    // Return a generic error response
    $response = new Response();
    $response->getBody()->write(json_encode(['error' => 'An unexpected error occurred.']));
    $response = $response->withHeader('Content-Type', 'application/json');
    $appBootstrap->emit($response);
}

