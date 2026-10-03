<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use Rocks\View\Raw;

class ClientReports {

  public function show_reports(Request $request, $args = []) {
    $response = Response::make();
    session_setup();

    $response = $response->withBody(page('reports/clients', [
      'title' => 'Client Reports - Micropub Rocks!',
    ]));
    return $response;
  }

}
