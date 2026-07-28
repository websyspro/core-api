<?php

namespace Websyspro\Server\Applications\Crm\Controllers;

use Websyspro\Server\Applications\Crm\Views\CargosView;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Decorators\Server\Get;
use Websyspro\Server\Includes\Response;



#[Controller("cargos")]
class CargosController
{
  public function __construct(
  ){}

  #[Get("/")]
  public function index(
  ): mixed {
    return new CargosView;
  }

  #[Get("/html")]
  public function indexHTML(
  ): mixed {
    return Response::html( "Works!!!" );
  }  
}
