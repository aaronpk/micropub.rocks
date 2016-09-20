<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;

class Controller {

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();
    
    $response->getBody()->write(view('index', [
      'title' => 'Micropub Rocks!',
    ]));
    return $response;
  }

  public function dashboard(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }
    
    $user = logged_in_user();

    $endpoints = ORM::for_table('micropub_endpoints')->where('user_id', $user->id)->find_many();

    $response->getBody()->write(view('dashboard', [
      'title' => 'Micropub Rocks!',
      'endpoints' => $endpoints,
    ]));
    return $response;
  }

  public function new_endpoint(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getParsedBody();

    $user = logged_in_user();

    // If they entered an IndieAuth URL, start logging them in
    // TODO
    if(isset($params['me'])) {

    } else {
      // Check if the endpoint already exists and update if so
      $endpoint = ORM::for_table('micropub_endpoints')
        ->where('user_id', $user->id)
        ->where('micropub_endpoint', $params['micropub_endpoint'])
        ->find_one();
      if(!$endpoint) {
        $endpoint = ORM::for_table('micropub_endpoints')->create();
        $endpoint->user_id = $user->id;
        $endpoint->micropub_endpoint = $params['micropub_endpoint'];
      }

      $endpoint->access_token = $params['access_token'];
      $endpoint->save();

      return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
  }

}
