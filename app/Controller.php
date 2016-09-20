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

  public function tests(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    if(!is_logged_in()) {
      return $response->withHeader('Location', '/?login_required')->withStatus(302);
    }
    
    $user = ORM::for_table('users')->where('id', $_SESSION['user_id'])->find_one();

    $response->getBody()->write(view('tests', [
      'title' => 'Micropub Rocks!',
      'email' => $user->email
    ]));
    return $response;
  }

}
