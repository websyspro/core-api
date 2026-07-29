<?php

namespace Websyspro\Server\Includes\Decorators\Database;

use Attribute;
use Websyspro\Server\Includes\Enums\Schema;

#[Attribute(Attribute::TARGET_CLASS)]
class OriginSchema
{
  public function __construct(
    public readonly Schema $schema
  ){}
}
