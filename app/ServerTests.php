<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;

class ServerTests {

  private $user;
  private $endpoint;

  private function _check_permissions(&$request, &$response) {
    session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }

    $params = $request->getQueryParams();
    
    $this->user = logged_in_user();

    // Verify an endpoint is specified and the user has permission to access it
    if(!isset($params['endpoint']))
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    $this->endpoint = ORM::for_table('micropub_endpoints')
      ->where('user_id', $this->user->id)
      ->where('id', $params['endpoint'])
      ->find_one();

    if(!$this->endpoint)
      return $response->withHeader('Location', '/dashboard')->withStatus(302);

    return null;    
  }

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    if($check = $this->_check_permissions($request, $response))
      return $check;

    $data = ORM::for_table('tests')
      ->raw_query('SELECT tests.*, test_results.passed FROM tests
        LEFT JOIN test_results ON tests.id = test_results.test_id AND test_results.endpoint_id = :endpoint_id
        WHERE tests.group = :group ORDER BY tests.number', ['endpoint_id'=>$this->endpoint->id, 'group'=>'server'])
      ->find_many();

    $tests = [];
    foreach($data as $test) {
      $tests[$test->number] = [
        'name' => $test->name,
        'passed' => $test->passed
      ];
    }

    $response->getBody()->write(view('server-tests', [
      'title' => 'Micropub Rocks!',
      'endpoint' => $this->endpoint,
      'tests' => $tests,
    ]));
    return $response;
  }

  public function get_test(ServerRequestInterface $request, ResponseInterface $response) {
    if($check = $this->_check_permissions($request, $response))
      return $check;


  }

}
