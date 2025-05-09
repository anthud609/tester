<?php

// config/di.php

use function DI\factory;
use function DI\autowire;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

return [

    // Expose all your .env-backed settings under 'settings'
    'settings' => require __DIR__ . '/app.php',

    // Monolog Logger
    LoggerInterface::class => factory(function (ContainerInterface $c) {

        $cfg = $c->get('settings')['logger'] ?? [];
        $logger = new Logger($cfg['name'] ?? 'app');
        $logger->pushHandler(new StreamHandler($cfg['path']  ?? __DIR__ . '/../logs/app.log', $cfg['level'] ?? Logger::INFO));
        return $logger;
    }),

    // PDO connection
    PDO::class => factory(function (ContainerInterface $c) {

        $db  = $c->get('settings')['db'] ?? [];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host']     ?? '127.0.0.1', $db['port']     ?? 3306, $db['database'] ?? '');
        return new PDO($dsn, $db['username'] ?? null, $db['password'] ?? null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }),

    // PSR-16 cache for rate limiting
    CacheInterface::class => factory(function () {

        // stores files in project_root/cache/
        $adapter = new FilesystemAdapter(namespace: '', defaultLifetime: 0, directory: __DIR__ . '/../cache');
        return new Psr16Cache($adapter);
    }),

    // PSR-15 middleware — PHP-DI will autowire their constructors
    App\Middleware\Cors::class        => autowire(),
    App\Middleware\RateLimiter::class => autowire(),
    App\Middleware\ParseJson::class   => autowire(),
    App\Middleware\ValidateRequest::class => autowire(),
    App\Middleware\RequestLogger::class  => autowire(),
    App\Middleware\Authenticate::class => autowire(),

    // ... add other services here ...
];
