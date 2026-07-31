<?php

namespace Websyspro\Server\Applications\Global\Controllers;

use Websyspro\Server\Applications\Global\Views\PostsView;
use Websyspro\Server\Includes\Decorators\Server\AllowAnonymous;
use Websyspro\Server\Includes\Decorators\Server\Authenticate;
use Websyspro\Server\Includes\Decorators\Server\Controller;
use Websyspro\Server\Includes\Decorators\Server\Get;

#[Authenticate()]
#[Controller( "posts" )]
class PostsController
{
  public function __construct(
  ){}

  #[Get("/")]
  public function index(
  ): mixed {
    return new PostsView();
  }  
}
