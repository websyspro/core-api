<?php

namespace Websyspro\Server\Applications\Global\Controllers;

use Websyspro\Server\Applications\Crm\Views\PropostaView;
use Websyspro\Server\Includes\Decorators\Server\AllowAnonymous;
use Websyspro\Server\Includes\Decorators\Server\Authenticate;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Decorators\Server\Body;
use Websyspro\Server\Includes\Decorators\Server\Get;
use Websyspro\Server\Includes\Interfaces\QueryProps;

#[Authenticate()]
#[Controller( "posts" )]
class PostsController
{
  public function __construct(
  ){}

  #[Get("/")]
  #[AllowAnonymous()]
  public function index(
    #[Body()] QueryProps $queryProps
  ): mixed {
    return new PropostaView(
      $queryProps
    );
  }  
}
