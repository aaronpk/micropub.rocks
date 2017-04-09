<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ClientReports {

  public function show_reports(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    $response->getBody()->write(view('reports/clients', [
      'title' => 'Client Reports - Micropub Rocks!',
    ]));
    return $response;
  }

}
