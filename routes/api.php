<?php
// routes/api.php

use App\Application;
use App\Controller\UserController;

/**
 * @param Application $app
 */
return function(Application $app): void {
    $router = $app->getRouter();
    $router->get('/users', [UserController::class, 'index']);
    $router->post('/users', [UserController::class, 'store']);
    // ...etc
};
