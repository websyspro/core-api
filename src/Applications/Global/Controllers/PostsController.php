<?php

namespace Websyspro\Server\Applications\Global\Controllers;

use Websyspro\Server\Applications\Global\Views\PostsView;
use Websyspro\Server\Includes\Decorators\Server\AllowAnonymous;
use Websyspro\Server\Includes\Decorators\Server\Authenticate;
use Websyspro\Server\Includes\Decorators\Server\Body;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Decorators\Server\Get;
use Websyspro\Server\Includes\Interfaces\QueryViewModel;

#[Authenticate()]
#[Controller( "posts" )]
class PostsController
{
  public function __construct(
  ){}

  #[Get("/")]
  #[AllowAnonymous()]
  public function index(
    #[Body()] QueryViewModel $queryViewModel
  ): mixed {
    return $queryViewModel;
    // return new PostsView();
  }  
}
