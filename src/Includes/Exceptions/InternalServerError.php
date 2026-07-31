<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class InternalServerError extends HttpException
{
  public function __construct(
    string $message = "Internal Server Error"
  ) {
    parent::__construct($message, HttpStatus::InternalServerError);
  }
}
