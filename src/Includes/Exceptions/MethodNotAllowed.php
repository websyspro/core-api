<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class MethodNotAllowed extends HttpException
{
  public function __construct(
    string $message = "Method Not Allowed"
  ) {
    parent::__construct($message, HttpStatus::MethodNotAllowed);
  }
}
