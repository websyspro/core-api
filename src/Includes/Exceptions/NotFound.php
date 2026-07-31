<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class NotFound extends HttpException
{
  public function __construct(
    string $message = "Not Found"
  ) {
    parent::__construct($message, HttpStatus::NotFound);
  }
}
