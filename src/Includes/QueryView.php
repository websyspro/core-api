<?php

namespace Websyspro\Server\Includes;

use Websyspro\Server\Includes\Interfaces\QueryStructure;

abstract class QueryView
{
  public readonly QueryStructure $queryStructure;

  public function __construct(
  ){}
}