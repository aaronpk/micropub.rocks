<?php
chdir('..');
include('vendor/autoload.php');

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$container = new League\Container\Container;
$container->share('response', Zend\Diactoros\Response::class);
$container->share('request', function () {
  return Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
  );
});
$container->share('emitter', Zend\Diactoros\Response\SapiEmitter::class);

$route = new League\Route\RouteCollection($container);

$route->map('GET', '/', 'App\\Controller::index');

$route->map('POST', '/auth/start', 'App\\Auth::start');
$route->map('GET', '/auth/code', 'App\\Auth::code');
$route->map('GET', '/auth/signout', 'App\\Auth::signout');

$route->map('GET', '/dashboard', 'App\\Controller::dashboard');
$route->map('POST', '/endpoints/new', 'App\\Controller::new_endpoint');
$route->map('GET', '/endpoints/callback', 'App\\Controller::endpoint_callback');
$route->map('GET', '/endpoints/{id}', 'App\\Controller::edit_endpoint');
$route->map('POST', '/endpoints/save', 'App\\Controller::save_endpoint');
$route->map('GET', '/server-tests', 'App\\ServerTests::index');
$route->map('POST', '/server-tests/micropub', 'App\\ServerTests::micropub_request');
$route->map('POST', '/server-tests/media-check', 'App\\ServerTests::media_check');
$route->map('POST', '/server-tests/store-result', 'App\\ServerTests::store_result');
$route->map('GET', '/server-tests/{num}', 'App\\ServerTests::get_test');



$route->map('GET', '/image', 'ImageProxy::image');

$templates = new League\Plates\Engine(dirname(__FILE__).'/../views');

try {
  $response = $route->dispatch($container->get('request'), $container->get('response'));
  $container->get('emitter')->emit($response);
} catch(League\Route\Http\Exception\NotFoundException $e) {
  $response = $container->get('response');
  $response->getBody()->write("Not Found\n");
  $container->get('emitter')->emit($response->withStatus(404));
} catch(League\Route\Http\Exception\MethodNotAllowedException $e) {
  $response = $container->get('response');
  $response->getBody()->write("Method not allowed\n");
  $container->get('emitter')->emit($response->withStatus(405));
}
