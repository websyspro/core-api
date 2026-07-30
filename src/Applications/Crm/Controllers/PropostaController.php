<?php

namespace Websyspro\Server\Applications\Crm\Controllers;

use stdClass;
use Websyspro\Server\Applications\Crm\Views\PropostaView;
use Websyspro\Server\Includes\Decorators\Server\Body;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Decorators\Server\Get;


#[Controller("proposta")]
class PropostaController
{
  public function __construct(
  ){}

  #[Get("/")]
  public function index(
    #[Body()] array $body
  ): mixed {
    return $body;
    //return new PropostaView();
  }  
}
