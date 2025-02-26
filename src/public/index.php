<?php

use Slim\Factory\AppFactory;
use App\Routes\RoutesManager;

require __DIR__ .  '/../../vendor/autoload.php';
require __DIR__ . '/../Database/Connection.php';

$app = AppFactory::create();

RoutesManager::register($app);

$app->setBasePath('/');

$app->addErrorMiddleware(true, true, true);

$app->run();
