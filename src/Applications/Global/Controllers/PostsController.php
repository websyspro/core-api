<?php

namespace Websyspro\Server\Applications\Global\Controllers;

use Websyspro\Server\Includes\Decorators\Server\AllowAnonymous;
use Websyspro\Server\Includes\Decorators\Server\Authenticate;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Interfaces\QueryViewModel;
use Websyspro\Server\Includes\Decorators\Server\Body;
use Websyspro\Server\Includes\Decorators\Server\Get;

#[Authenticate()]
#[Controller( "posts" )]
class PostsController
{
  public function __construct(
  ){}

  #[Get("/")]
  #[AllowAnonymous()]
  public function index(
    #[Body()] QueryViewModel $queryViewModel,
    //#[Body()] object $username,
    //#[Body("username")] string $username
  ): mixed {
    return $queryViewModel;
    // return new PostsView();
  }  
}
