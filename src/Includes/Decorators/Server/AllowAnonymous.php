<?php

namespace Websyspro\Server\Includes\Decorators\Server;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class AllowAnonymous
{
  public function __construct() {}
}
