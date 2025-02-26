<?php

use Slim\Psr7\Factory\ServerRequestFactory;
use App\Controller\UserController;
use Slim\Psr7\Response;
use Slim\Psr7\Factory\StreamFactory;
use \App\Database\Connection;

beforeEach(function () {
    test()->pdo = Connection::connect(test: true);

    test()->pdo->exec("DELETE FROM users");
    test()->pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'users'");

    test()->response = new Response();
    test()->requestFactory = new ServerRequestFactory();
});

describe("getUsers", function () {

    test('should returns empty array and status 200', function () {
        $request = test()->requestFactory->createServerRequest('GET', '/users');


        $response = UserController::getUsers($request, test()->response);

        expect($response->getStatusCode())->toBe(200)
            ->and(json_decode((string) $response->getBody(), true))->toBe([]);
    });

    test('should return users and status 200', function () {

        test()->pdo->exec("INSERT INTO users (name, email) VALUES ('Alice', 'alice@email.com')");
        test()->pdo->exec("INSERT INTO users (name, email) VALUES ('João', 'joao@email.com')");

        $request = test()->requestFactory->createServerRequest('GET', '/users');

        $expectedUsers = [
            ['id' => 2, 'name' => 'João', 'email' => 'joao@email.com'],
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@email.com'],
        ];


        $response = UserController::getUsers($request, test()->response);
        $actualUsers = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(200)
            ->and($actualUsers)->toBe($expectedUsers);
    });
});

describe("getUserById", function () {

    test('should returns error message and status 404', function () {
        $id = 'test';

        $request = test()->requestFactory->createServerRequest('GET', "/user/$id");


        $args = ['id' => $id];

        $response = UserController::getUserById($request, test()->response, $args);

        expect($response->getStatusCode())->toBe(expected: 404)
            ->and(json_decode((string) $response->getBody(), true))->toBe([
                "error" => "User not found"
            ]);
    });

    test("should return user and status 200", function () {

        test()->pdo->exec("INSERT INTO users (name, email) VALUES ('Teste', 'teste@email.com')");

        $id = '1';

        $request = test()->requestFactory->createServerRequest('GET', "/user/$id");

        $args = ['id' => $id];

        $response = UserController::getUserById($request, test()->response, $args);

        $expectedUser = ['id' => 1, 'name' => 'Teste', 'email' => 'teste@email.com'];

        $recivedUser = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(expected: 200)
            ->and($recivedUser)->toBe($expectedUser);
    });
});

describe("createUser", function () {
    test('should create user and return status 201', function () {
        $streamFactory = new StreamFactory();
        $data = ['name' => 'Teste', 'email' => 'teste@email.com'];

        $body = $streamFactory->createStream(json_encode($data));

        $request = test()->requestFactory->createServerRequest('POST', '/user')->withBody($body);

        $response = UserController::createUser($request, test()->response);

        $stmt = test()->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        expect($response->getStatusCode())->toBe(201)
            ->and($user['email'])->tobe($data['email']);
    });

    test('should return a error message when the email field is not be provided and status 400', function () {
        $streamFactory = new StreamFactory();
        $data = ['name' => 'Teste'];

        $body = $streamFactory->createStream(json_encode($data));

        $request = test()->requestFactory->createServerRequest('POST', '/user')->withBody($body);

        $response = UserController::createUser($request, test()->response);

        expect($response->getStatusCode())->toBe(400)
            ->and(json_decode((string) $response->getBody(), true))->toBe(['error' => 'name and email are required']);
    });

    test('should return a error message when the name field is not be provided and status 400', function () {
        $streamFactory = new StreamFactory();
        $data = ['email' => 'teste@email.com'];

        $body = $streamFactory->createStream(json_encode($data));

        $request = test()->requestFactory->createServerRequest('POST', '/user')->withBody($body);

        $response = UserController::createUser($request, test()->response);

        expect($response->getStatusCode())->toBe(400)
            ->and(json_decode((string) $response->getBody(), true))->toBe(['error' => 'name and email are required']);
    });

    test('should return a error message when the name and email field are not be provided and status 400', function () {
        $streamFactory = new StreamFactory();
        $data = [];

        $body = $streamFactory->createStream(json_encode($data));

        $request = test()->requestFactory->createServerRequest('POST', '/user')->withBody($body);

        $response = UserController::createUser($request, test()->response);

        expect($response->getStatusCode())->toBe(400)
            ->and(json_decode((string) $response->getBody(), true))->toBe(['error' => 'name and email are required']);
    });
});

describe("updateUser", function () {
    test('should update user and return status 201', function () {

        test()->pdo->exec("INSERT INTO users (name, email) VALUES ('test', 'test@email.com')");

        $id = '1';
        $args = ['id' => $id];
        $data = ['name' => 'test2', 'email' => 'test@email.com'];

        $streamFactory = new StreamFactory();

        $body = $streamFactory->createStream(json_encode($data));

        $request = test()->requestFactory->createServerRequest('PUT', "/user/$id")->withBody($body);

        $response = UserController::updateUser($request, test()->response, $args);

        $stmt = test()->pdo->prepare("SELECT * FROM users WHERE name = ?");
        $stmt->execute([$data['name']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        expect($response->getStatusCode())->tobe(201)
            ->and($user['name'])->tobe($data['name']);
    });

    test('should return error message when name field not be provided and status 400', function () {

        test()->pdo->exec("INSERT INTO users (name, email) VALUES ('test', 'test@email.com')");

        $id = '1';
        $args = ['id' => $id];
        $data = ['email' => 'test@email.com'];

        $streamFactory = new StreamFactory();

        $body = $streamFactory->createStream(json_encode($data));

        $request = test()->requestFactory->createServerRequest('PUT', "/user/$id")->withBody($body);

        $response = UserController::updateUser($request, test()->response, $args);

        expect($response->getStatusCode())->tobe(expected: 400)
            ->and(json_decode((string) $response->getBody(), true))->tobe(['error' => 'name and email are required']);
    });

    test('should return error message when email field not be provided and status 400', function () {

        test()->pdo->exec("INSERT INTO users (name, email) VALUES ('test', 'test@email.com')");

        $id = '1';
        $args = ['id' => $id];
        $data = ['name' => 'test2'];

        $streamFactory = new StreamFactory();

        $body = $streamFactory->createStream(json_encode($data));

        $request = test()->requestFactory->createServerRequest('PUT', "/user/$id")->withBody($body);

        $response = UserController::updateUser($request, test()->response, $args);

        expect($response->getStatusCode())->tobe(expected: 400)
            ->and(json_decode((string) $response->getBody(), true))->tobe(['error' => 'name and email are required']);
    });
});

describe("deleteUser", function () {
    test('should delete user and return status 201', function () {

        test()->pdo->exec("INSERT INTO users (name, email) VALUES ('test', 'test@email.com')");

        $id = '1';
        $args = ['id' => $id];

        $request = test()->requestFactory->createServerRequest('DELETE', "/user/$id");

        $response = UserController::deleteUser($request, test()->response, $args);

        $stmt = test()->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        expect($response->getStatusCode())->tobe(expected: 201)
            ->and($user)->tobe(false);
    });

    test('should return error message and status 404', function () {

        $id = '1';
        $args = ['id' => $id];

        $request = test()->requestFactory->createServerRequest('DELETE', "/user/$id");

        $response = UserController::deleteUser($request, test()->response, $args);

        expect($response->getStatusCode())->tobe(expected: 404)
            ->and(json_decode((string) $response->getBody(), true))->tobe(['error' => 'User not found']);
    });
});
