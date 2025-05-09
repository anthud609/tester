<?php
// config/app.php
use Monolog\Level;

return [

    // Environment & debug flags
    'env'   => $_ENV['APP_ENV']   ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),

    // Database connection settings
    'db' => [
        'host'     => $_ENV['DB_HOST']     ?? '127.0.0.1',
        'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_DATABASE'] ?? 'bims',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ],

    // Logger settings (for your DI definitions)
    'logger' => [
        'name'  => 'app',
        'path'  => __DIR__ . '/../logs/app.log',
        'level' => Level::Info,
    ],

    // Any other services’ settings…
];
