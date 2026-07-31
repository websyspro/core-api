<?php

namespace Websyspro\Server\Includes\Decorators\Server;

use Attribute;
use Websyspro\Server\Includes\Exceptions\Unauthorized;
use Websyspro\Server\Includes\Request;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authenticate
{
  public function __construct(
    Request $request
  ) {
    $authorization = $request->headers[ "authorization" ] ?? null;

    if( $authorization === null ){
      throw new Unauthorized( "Token não informado" );
    }
  }
}
