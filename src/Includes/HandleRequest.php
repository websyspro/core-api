<?php

namespace Websyspro\Server\Includes;

use Closure;
use Websyspro\Server\Includes\Request;
use function call_user_func;

class HandleRequest
{
  public function __construct(
    public readonly Closure|null $closure = null,
    public readonly array|null $params = null
  ){}

  public function execute(
    Request $request
  ): Response {
    if( $this->closure instanceof Closure ){
      return call_user_func( 
        $this->closure, $request->setParams( $this->params )
      );
    }

    return Response::text( "Test" );
  }
}