<?php

namespace App\Routes;

use Slim\App;
use App\Controller\UserController;

class RoutesManager
{
  public static function register(App $app)
  {
    $app->get('users', [UserController::class, 'getUsers']);
    $app->get('user/{id}', [UserController::class, 'getUserById']);
    $app->post('user', [UserController::class, 'createUser']);
    $app->put('user/{id}', [UserController::class, 'updateUser']);
    $app->delete('user/{id}', [UserController::class, 'deleteUser']);
  }
}
