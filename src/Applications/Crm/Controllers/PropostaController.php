<?php

namespace Websyspro\Server\Applications\Crm\Controllers;

use Websyspro\Server\Applications\Crm\Views\CargosView;
use Websyspro\Server\Applications\Crm\Views\PropostaView;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Decorators\Server\Get;


#[Controller("proposta")]
class PropostaController
{
  public function __construct(
  ){}

  #[Get("/")]
  public function index(
  ): mixed {
    return new PropostaView();
  }  
}
