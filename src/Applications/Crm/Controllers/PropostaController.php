<?php

namespace Websyspro\Server\Applications\Crm\Controllers;

use Websyspro\Server\Applications\Crm\Views\PropostaView;
use Websyspro\Server\Includes\Decorators\Server\AllowAnonymous;
use Websyspro\Server\Includes\Decorators\Server\Authenticate;
use Websyspro\Server\Includes\Decorators\Server\Body;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Decorators\Server\Get;
use Websyspro\Server\Includes\Decorators\Server\Query;
use Websyspro\Server\Includes\Exceptions\InternalServerError;

#[Authenticate()]
#[Controller("proposta")]
class PropostaController
{
  public function __construct(
  ){}

  #[Get("/")]
  #[AllowAnonymous()]
  public function index(
    #[Body()] object $body,
    #[Query("isError")] int $isError
  ): mixed {
    if( $isError === 1 ){
      throw new InternalServerError("Erro de teste no controller");
    }

    return new PropostaView();
  }  
}
