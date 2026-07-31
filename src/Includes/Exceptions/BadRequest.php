<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class BadRequest extends HttpException
{
  public function __construct(
    string $message = "Bad Request"
  ) {
    parent::__construct($message, HttpStatus::BadRequest);
  }
}
