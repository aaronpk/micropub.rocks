<?php

declare(strict_types=1);

use Rocks\Router;
use Rocks\Http\HttpException;
use Rocks\Http\Request;
use Rocks\Http\Response;

// When running under `php -S` with this file as the router script, let the
// built-in server handle real files (CSS, JS, images) itself.
if (PHP_SAPI === 'cli-server') {
  $file = __DIR__ . '/' . ltrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
  if ($file !== __DIR__ . '/' && is_file($file)) {
    return false;
  }
}

chdir('..');

if (!is_file('vendor/autoload.php')) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Dependencies are not installed. Run:\n\n    composer install\n";
  exit(1);
}

require 'vendor/autoload.php';

$router = new Router;

$router->get('/', [App\Controller::class, 'index']);

$router->post('/auth/start', [App\Auth::class, 'start']);
$router->get('/auth/code', [App\Auth::class, 'code']);
$router->post('/auth/register/challenge', [App\Auth::class, 'register_challenge']);
$router->post('/auth/register', [App\Auth::class, 'register']);
$router->post('/auth/login/challenge', [App\Auth::class, 'login_challenge']);
$router->post('/auth/login', [App\Auth::class, 'login']);
$router->get('/auth/signout', [App\Auth::class, 'signout']);

$router->get('/account', [App\Account::class, 'index']);
$router->get('/account/passkey', [App\Account::class, 'passkey_prompt']);
$router->post('/account/passkeys/challenge', [App\Account::class, 'add_challenge']);
$router->post('/account/passkeys', [App\Account::class, 'add']);
$router->post('/account/passkeys/{id}/rename', [App\Account::class, 'rename']);
$router->post('/account/passkeys/{id}/remove', [App\Account::class, 'remove']);

$router->get('/dashboard', [App\Controller::class, 'dashboard']);


//////////////////////////////////////////////////////////////////////////
// Server Management
$router->post('/endpoints/new', [App\Controller::class, 'new_endpoint']);
$router->get('/endpoints/callback', [App\Controller::class, 'endpoint_callback']);
$router->get('/endpoints/{id}', [App\Controller::class, 'edit_endpoint']);
$router->post('/endpoints/save', [App\Controller::class, 'save_endpoint']);

// Server Tests
$router->get('/server-tests', [App\ServerTests::class, 'index']);
$router->post('/server-tests/micropub', [App\ServerTests::class, 'micropub_request']);
$router->post('/server-tests/media-check', [App\ServerTests::class, 'media_check']);
$router->post('/server-tests/store-result', [App\ServerTests::class, 'store_result']);
$router->get('/server-tests/{num}', [App\ServerTests::class, 'get_test']);
//////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////////
// Client Management
$router->post('/clients/new', [App\Controller::class, 'new_client']);
$router->get('/clients/{id}', [App\Controller::class, 'edit_client']);
$router->post('/clients/save', [App\Controller::class, 'save_client']);
$router->post('/clients/{id}/new_access_token', [App\Controller::class, 'create_client_access_token']);

// Client Tests
$router->get('/client/{token}', [App\ClientTests::class, 'index']);
$router->get('/client/{token}/auth', [App\ClientTests::class, 'auth']);
$router->get('/client/{token}/micropub', [App\ClientTests::class, 'micropub_get']);
$router->get('/client/{token}/{num}', [App\ClientTests::class, 'get_test']);
$router->get('/client/{token}/{num}/{key}', [App\ClientTests::class, 'get_test']);
$router->get('/client/{token}/{num}/{key}/photo.jpg', [App\ClientTests::class, 'get_image']);
$router->get('/client/{token}/{num}/{key}/video.mp4', [App\ClientTests::class, 'get_video']);
$router->get('/client/{token}/{num}/{key}/audio.mp3', [App\ClientTests::class, 'get_audio']);
$router->get('/client/{token}/{num}/{key}/file', [App\ClientTests::class, 'get_image']);
$router->post('/client/{token}/auth', [App\ClientTests::class, 'auth_confirm']);
$router->post('/client/{token}/token', [App\ClientTests::class, 'token']);
$router->post('/client/{token}/micropub', [App\ClientTests::class, 'micropub']);
$router->post('/client/{token}/media', [App\ClientTests::class, 'media_endpoint']);
$router->options('/client/{token}/micropub', [App\ClientTests::class, 'options']);
$router->options('/client/{token}/media', [App\ClientTests::class, 'options']);
//////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////////
// Reports
// Trailing slashes are removed from request paths before matching
$router->get('/implementation-reports/servers', [App\ImplementationReport::class, 'show_reports']);
$router->get('/implementation-reports/servers/summary', [App\ImplementationReport::class, 'server_report_summary']);
$router->get('/implementation-reports/servers/{id}', [App\ImplementationReport::class, 'get_server_report']);
$router->get('/implementation-reports/servers/{id}/{token}', [App\ImplementationReport::class, 'view_server_report']);

$router->get('/implementation-reports/clients', [App\ClientReports::class, 'show_reports']);

$router->get('/implementation-reports', [App\Controller::class, 'redirect_home']);

$router->get('/implementation-report/client/{id}', [App\ImplementationReport::class, 'get_client_report']);
$router->post('/implementation-report/store-result', [App\ImplementationReport::class, 'store_result']);
$router->post('/implementation-report/save', [App\ImplementationReport::class, 'save_report']);
$router->post('/implementation-report/publish', [App\ImplementationReport::class, 'publish_report']);

// Old redirects
$router->get('/implementation-report/server/{id}', [App\ImplementationReport::class, 'redirect_server']);
$router->get('/implementation-report/server/{id}/{token}', [App\ImplementationReport::class, 'redirect_server']);
$router->get('/reports', [App\Controller::class, 'redirect_reports']);
//////////////////////////////////////////////////////////////////////////


$request = Request::fromGlobals();

try {
  $match = $router->match($request->method, $request->path);
  [$class, $method] = $match->handler;
  $response = (new $class)->$method($request, $match->params);
} catch (HttpException $e) {
  $response = Response::text($e->getMessage() . "\n", $e->status);
  foreach ($e->headers as $name => $value) {
    $response = $response->withHeader($name, $value);
  }
}

$response->send();
