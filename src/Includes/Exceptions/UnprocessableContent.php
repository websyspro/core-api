<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class UnprocessableContent extends HttpException
{
  public function __construct(
    string $message = "Unprocessable Content"
  ) {
    parent::__construct($message, HttpStatus::UnprocessableContent);
  }
}
