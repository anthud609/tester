<?php
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
        $app->addErrorMiddleware(
            (bool) ($config['debug'] ?? false),
            true,
            true
        );

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
            $this->app->add(
                $this->app->getContainer()->get($middlewareClass)
            );
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
        header(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ), true, $response->getStatusCode());

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