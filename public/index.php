<?php

//composer init // todo enter
//composer install
//composer dump-autoload -o 
//composer require slim/slim:"4.*"
//composer require slim/psr7
//composer require illuminate/database
//composer require "illuminate/events"
//composer require firebase/php-jwt

use Slim\Routing\RouteCollectorProxy;
use Slim\Factory\AppFactory;
use Config\Database;
use App\Controllers\LoginController;
use App\Controllers\UsuarioController;
use App\Controllers\MesaController;
use App\Controllers\ComandaController;
use App\Controllers\PedidoController;
use App\Middlewares\JsonMiddleware;
use App\Middlewares\AuthMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$conn = new Database;

$app = AppFactory::create();

$app->setBasePath('/tp3Comanda/public');

$app->group('/login', function (RouteCollectorProxy $group) {
    $group->get('[/]', LoginController::class.":getAll")->add(new AuthMiddleware("socio"));
    $group->post('[/]', LoginController::class.":login");  
})
->add(new JsonMiddleware);

$app->group('/comanda', function (RouteCollectorProxy $group) {
    $group->get('[/]', ComandaController::class.":getAll")->add(new AuthMiddleware("socio"));
    $group->get('/{codigo_pedido}', ComandaController::class.":getOne")->add(new AuthMiddleware("socio"));
    $group->post('[/]', ComandaController::class.":addOne")->add(new AuthMiddleware("socio"));
    $group->post('/{codigo_pedido}', ComandaController::class.":updateOne")->add(new AuthMiddleware("socio"));
})
->add(new JsonMiddleware);

$app->group('/pedido', function (RouteCollectorProxy $group) {
    $group->get('[/]', PedidoController::class.":getAll")->add(new AuthMiddleware("socio"));
    $group->get('/{codigo_pedido}', PedidoController::class.":getOne")->add(new AuthMiddleware("socio"));
    $group->post('[/]', PedidoController::class.":addOne")->add(new AuthMiddleware("socio","mozo"));
    $group->post('/{codigo_pedido}', PedidoController::class.":updateOne")->add(new AuthMiddleware("bartender","cervecero", "cocinero"));
})
->add(new JsonMiddleware);

$app->group('/usuario', function (RouteCollectorProxy $group) {
    $group->get('[/]', UsuarioController::class.":getAll")->add(new AuthMiddleware("socio"));
    $group->get('/{id_usuario}', UsuarioController::class.":getOne")->add(new AuthMiddleware("socio"));
    $group->post('[/]', UsuarioController::class.":addOne")->add(new AuthMiddleware("socio"));
    $group->post('/modificar/{id}', UsuarioController::class.":updateOne")->add(new AuthMiddleware("socio"));
    $group->delete('/{id}', UsuarioController::class.":deleteOne")->add(new AuthMiddleware("socio"));
})
->add(new JsonMiddleware);

$app->group('/mesa', function (RouteCollectorProxy $group) {
    $group->get('[/]', MesaController::class.":getAll")->add(new AuthMiddleware("socio"));
    $group->get('/{codigo_mesa}', MesaController::class.":getOne")->add(new AuthMiddleware("socio"));
    $group->post('[/]', MesaController::class.":addOne")->add(new AuthMiddleware("socio"));
    $group->post('/{id}', MesaController::class.":updateOne")->add(new AuthMiddleware("socio", "mozo"));
})
->add(new JsonMiddleware);

$app->group('/cliente', function (RouteCollectorProxy $group) {
    $group->get('/{codigo_mesa}/{codigo_pedido}', ComandaController::class .":tiempoEspera");
    $group->post('/encuesta', ComandaController::class.":addEncuesta");
})->add(new JsonMiddleware);


$app->run();
