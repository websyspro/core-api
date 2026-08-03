<?php

namespace Websyspro\Server\Applications\Crm\Controllers;

use Websyspro\Server\Applications\Crm\Views\CargosView;
use Websyspro\Server\Includes\Decorators\Server\Body;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Decorators\Server\Get;
use Websyspro\Server\Includes\Interfaces\QueryProps;
use Websyspro\Server\Includes\Response;



#[Controller("cargos")]
class CargosController
{
  public function __construct(
  ){}

  #[Get("/")]
  public function index(
    #[Body()] QueryProps $queryProps
  ): mixed {
    return new CargosView($queryProps);
  }

  #[Get("/html")]
  public function indexHTML(
  ): mixed {
    $date = date("h:i:s");
    return Response::html( 
      "<h1>Work!!! {$date}</h1>"
    );
  }  
}
