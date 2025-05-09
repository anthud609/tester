<?php
 declare(strict_types=1);

/**
 * Application bootstrap for the **Enterprise API**.
 *
 * This file defines the {@see \App\Application} class, the single source of truth
 * for creating and configuring the Slim framework instance used throughout the
 * service.  It is responsible for:
 *
 * 1. Wiring an implementation of {@see \Psr\Container\ContainerInterface}.
 * 2. Creating the {@see \Slim\App} via {@see \Slim\Factory\AppFactory}.
 * 3. Registering global middleware (body parsing, routing, error handling, CORS, etc.).
 * 4. Exposing the fully configured application to `public/index.php` so that it can
 *    dispatch the incoming {@see \Psr\Http\Message\ServerRequestInterface}.
 *
 * Keeping this logic isolated guarantees that:
 * - The HTTP front controller stays minimal.
 * - The application can be spun up in CLI tools, functional tests, or workers
 *   without boot-strapping the entire web stack.
 *
 * ### 🔒  Security Notice
 * Ensure this class is loaded **before** any user-supplied input is handled so
 * that mandatory middleware (rate-limiters, authentication, and CSRF guards)
 * is always active.
 *
 * PHP version 8.3+
 *
 * @file      src/Application.php
 * @category  Enterprise-API
 * @package   App
 * @author    Your Name <you@example.com>
 * @copyright Copyright © 2025 Your Company
 * @license   MIT License <https://opensource.org/licenses/MIT>
 * @link      https://github.com/your-org/enterprise-api
 * @version   GIT: @commit@
 * @since     1.0.0
 */


 namespace App;
 
 use Psr\Container\ContainerInterface;
 use Psr\Http\Message\ServerRequestInterface;
 use Psr\Http\Message\ResponseInterface;
 use Slim\Factory\AppFactory;
 use Slim\App as SlimApp;
 
 class Application
 {
     private SlimApp $app;
     public function __construct(array $config, ContainerInterface $container)
     {
         // Set up Slim with your DI container
         AppFactory::setContainer($container);
         $app = AppFactory::create();
     // Slim built-in middleware
         $app->addBodyParsingMiddleware();
         $app->addRoutingMiddleware();
         $app->addErrorMiddleware((bool) ($config['debug'] ?? false), true, true);
     // Register core services from config
         $this->configureServices($config);
         $this->app = $app;
     }
 
     /**
      * Add PSR-15 middleware (class names) to the Slim app
      *
      * @param string[] $list
      */
     public function middleware(array $list): void
     {
         foreach ($list as $middlewareClass) {
             $this->app->add($this->app->getContainer()->get($middlewareClass));
         }
     }
 
     /**
      * Handle the incoming request via Slim
      */
     public function handle(ServerRequestInterface $request): ResponseInterface
     {
         return $this->app->handle($request);
     }
 
     /**
      * Emit the PSR-7 response to the client without external packages
      */
     public function emit(ResponseInterface $response): void
     {
         // Status line
         header(sprintf('HTTP/%s %d %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()), true, $response->getStatusCode());
 // Headers
         foreach ($response->getHeaders() as $name => $values) {
             foreach ($values as $value) {
                 header("{$name}: {$value}", false);
             }
         }
 
         // Body
         echo $response->getBody();
     }
 
     /**
      * Expose the Slim App for defining routes
      */
     public function getRouter(): SlimApp
     {
         return $this->app;
     }
 
     /**
      * Fetch any service from the container
      */
     public function get(string $id): mixed
     {
         return $this->app->getContainer()->get($id);
     }
 
     /**
      * Register core services into the DI container
      */
     private function configureServices(array $config): void
     {
         // e.g. register DB, logger, cache, etc.
     }
 }
 