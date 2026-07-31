<?php

namespace Websyspro\Server\Includes\Exceptions;

use Websyspro\Server\Includes\Enums\Server\HttpStatus;

class ServiceUnavailable extends HttpException
{
  public function __construct(
    string $message = "Service Unavailable"
  ) {
    parent::__construct($message, HttpStatus::ServiceUnavailable);
  }
}
