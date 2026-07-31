<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class Forbidden extends HttpException
{
  public function __construct(
    string $message = "Forbidden"
  ) {
    parent::__construct($message, HttpStatus::Forbidden);
  }
}
