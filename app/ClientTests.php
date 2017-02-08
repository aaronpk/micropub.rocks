<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use Config;

class ClientTests {

  private $user;
  private $client;

  private function _check_permissions(&$request, &$response, $token) {
    session_setup();

    $this->client = ORM::for_table('micropub_clients')
      ->where('token', $token)
      ->find_one();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $this->user = logged_in_user();

    if(!$this->client || $this->client->user_id != $this->user->id)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    return null;    
  }

  public function index(ServerRequestInterface $request, ResponseInterface $response, $args) {
    if($check = $this->_check_permissions($request, $response, $args['token'])) {
      if(!$this->client) 
        return $response->withStatus(404);

      $response = $response->withHeader('Link', '<'.Config::$base.'client/'.$this->client->token.'/auth; rel="authorization_endpoint">');

      $response = $response->withHeader('X-Foo', 'bar');

      // Don't actually redirect here, instead return a public page about the client
      $response->getBody()->write(view('client-info', [
        'title' => $this->client->name,
        'client' => $this->client,
      ]));
      return $response;
    }



    $response->getBody()->write(view('client-tests', [
      'title' => 'Micropub Rocks!',
      'client' => $this->client,
    ]));
    return $response;
  }

  public function auth(ServerRequestInterface $request, ResponseInterface $response, $args) {


  }



}
