<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Validator;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});
/*
$app->get('/users', function ($request, $response) {
    return $response->write('GET /users');
});

$app->post('/users', function ($request, $response) {
    return $response->withStatus(302);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});
*/
$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $fileUsers = json_decode(file_get_contents('data.json'), true);
    if (!empty($fileUsers)) {
        foreach ($fileUsers as ['name' => $name]) {
            $users[] = $name;
        }
    }
    if ($term) {
        $users = array_filter($users, fn($user) => strpos($user, $term) !== false);
    }
    $params = ['users' => $users, 'fileUsers' => $fileUsers, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});
/*
$app->get('/users/{nickname}/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['nickname']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});
*/
$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        $fileUser = [];
        if (file_exists('data.json') && filesize('data.json')) {
            $fileUser = json_decode(file_get_contents('data.json'), true);
        }
        $fileUser[] = $user;
        $jsonData = json_encode($fileUser);
        file_put_contents('data.json', $jsonData);
        return $response->withRedirect('/users', 302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});
$app->run();
