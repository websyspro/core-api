<?php

namespace Websyspro\Server\Includes\Interfaces;

class FieldStructure
{
  public function __construct(
    public string $name,
    public string $type
  ){}
}