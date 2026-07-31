<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class Conflict extends HttpException
{
  public function __construct(
    string $message = "Conflict"
  ) {
    parent::__construct($message, HttpStatus::Conflict);
  }
}
