<?php

namespace App\Controller;

use Slim\Psr7\Response;
use Slim\Psr7\Request;

use App\Database\Connection;

use PDO;
use PDOException;

class UserController
{
  public static function getUsers(Request $req, Response $res): Response
  {
    try {
      $stmt = Connection::connect()->query('SELECT * FROM users ORDER BY id DESC');
      $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $res->getBody()->write(json_encode($users));

      return $res->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {

      $error = ['error' => 'Database error', 'message' => $e->getMessage()];

      $res->getBody()->write(json_encode($error));

      return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
  }

  public static function getUserById(Request $req, Response $res, array $args): Response
  {
    try {
      $stmt = Connection::connect()->prepare('SELECT * FROM users WHERE id = ?');
      $stmt->execute([$args['id']]);

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$user) {
        $res->getBody()->write(json_encode(['error' => 'User not found']));

        return $res->withHeader('Content-Type', 'application/json')->withStatus(404);
      }

      $res->getBody()->write(json_encode($user));
      return $res->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {

      $error = ['error' => 'Database error', 'message' => $e->getMessage()];

      $res->getBody()->write(json_encode($error));

      return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
  }

  public static function createUser(Request $req, Response $res): Response
  {

    try {
      $data = json_decode($req->getBody()->getContents(), true);

      if (!isset($data['name'], $data['email'])) {
        $res->getBody()->write(json_encode(['error' => 'name and email are required']));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
      }

      $stmt = Connection::connect()->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
      $stmt->execute([$data['name'], $data['email']]);

      return $res->withStatus(201)->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {

      $error = ['error' => 'Database error', 'message' => $e->getMessage()];

      $res->getBody()->write(json_encode($error));

      return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
  }

  public static function updateUser(Request $req, Response $res, array $args): Response
  {

    try {
      $data = json_decode($req->getBody()->getContents(), true);

      if (!isset($data['name'], $data['email'])) {
        $res->getBody()->write(json_encode(['error' => 'name and email are required']));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
      }

      $stmt = Connection::connect()->prepare('UPDATE users SET name = ?,email = ? WHERE id = ?');
      $stmt->execute([$data['name'], $data['email'], $args['id']]);

      if ($stmt->rowCount() === 0) {
        $res->getBody()->write(json_encode(['error' => 'User not found']));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(404);
      }

      return $res->withStatus(201)->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {

      $error = ['error' => 'Database error', 'message' => $e->getMessage()];

      $res->getBody()->write(json_encode($error));

      return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
  }

  public static function deleteUser(Request $req, Response $res, array $args): Response
  {
    try {
      $stmt = Connection::connect()->prepare('DELETE FROM users WHERE id = ?');
      $stmt->execute([$args['id']]);

      if ($stmt->rowCount() === 0) {
        $res->getBody()->write(json_encode(['error' => 'User not found']));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(404);
      }

      return $res->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (PDOException $e) {

      $error = ['error' => 'Database error', 'message' => $e->getMessage()];

      $res->getBody()->write(json_encode($error));

      return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
  }
}
