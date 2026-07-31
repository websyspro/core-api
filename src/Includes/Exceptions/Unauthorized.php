<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class Unauthorized extends HttpException
{
  public function __construct(
    string $message = "Unauthorized"
  ) {
    parent::__construct($message, HttpStatus::Unauthorized);
  }
}
