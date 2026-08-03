<?php

namespace Websyspro\Server\Includes\Interfaces;

use Websyspro\Server\Includes\Enums\Query\EqualType;

class WhereField
{
  public function __construct(
    public readonly string $table,
    public readonly string $field,
    public readonly EqualType $equalType,
    public readonly array $callbacks,
    public readonly array $values
  ){}
}