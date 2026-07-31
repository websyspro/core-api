<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class TooManyRequests extends HttpException
{
  public function __construct(
    string $message = "Too Many Requests"
  ) {
    parent::__construct($message, HttpStatus::TooManyRequests);
  }
}
