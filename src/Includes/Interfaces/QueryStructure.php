<?php

namespace Websyspro\Server\Includes\Interfaces;

use Websyspro\Server\Commons\Collection;

class QueryStructure
{
  public function __construct(
    public readonly Collection $fields,
  ){}
}