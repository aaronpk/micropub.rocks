<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;

class ServerTests {

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getQueryParams();
    
    $user = logged_in_user();

    // Verify an endpoint is specified and the user has permission to access it
    if(!isset($params['endpoint']))
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $user->id)
      ->where('endpoint_id', $params['endpoint'])
      ->find_one();

    if(!$endpoint)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);


    
    $response->getBody()->write(view('tests', [
      'title' => 'Micropub Rocks!',
    ]));
    return $response;
  }

}
