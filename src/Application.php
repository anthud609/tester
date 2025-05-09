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
        // 1. Set the DI container for Slim
        AppFactory::setContainer($container);

        // 2. Create the Slim App instance
        $app = AppFactory::create();

        // 3. Register Slim’s built-in middleware
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(
            (bool) ($config['debug'] ?? false),
            true,
            true
        );

        // 4. Register your application services
        $this->configureServices($config);

        $this->app = $app;
    }

    /**
     * Add PSR-15 middleware (by class name) to the Slim app.
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
     * Dispatch the incoming request through Slim.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->app->handle($request);
    }

    /**
     * Emit the PSR-7 response to the client without external packages.
     */
    public function emit(ResponseInterface $response): void
    {
        // 1) Status line
        header(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ), true, $response->getStatusCode());

        // 2) Headers
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // 3) Body
        echo $response->getBody();
    }

    /**
     * Expose Slim App instance for defining routes.
     */
    public function getRouter(): SlimApp
    {
        return $this->app;
    }

    /**
     * Fetch a service from the DI container.
     */
    public function get(string $id): mixed
    {
        return $this->app->getContainer()->get($id);
    }

    /**
     * Register core services into the DI container.
     *
     * @param array $config
     */
    private function configureServices(array $config): void
    {
        // e.g. register DB connection, logger, cache, etc.
    }
}
