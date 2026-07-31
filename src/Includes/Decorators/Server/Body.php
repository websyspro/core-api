<?php

namespace Websyspro\Server\Includes\Decorators\Server;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Body
{
  public function __construct(
    public readonly string $key = ""
  ){}
}
